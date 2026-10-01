<?php

namespace ShipReady\Analyzers;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;

final class CodeAnalyzer
{
    private array $paths;
    private array $exclude;

    /** @var array<string, Node[]|null> */
    private array $astCache = [];

    private ?\PhpParser\Parser $parser = null;
    private ?NodeFinder $nodeFinder    = null;

    public function __construct(array $paths, array $exclude = [])
    {
        $this->paths   = $paths;
        $this->exclude = $exclude;
    }

    /**
     * Return ClassInfo objects for classes that extend the given parent.
     *
     * @return ClassInfo[]
     */
    public function classesExtending(string $parentClass): array
    {
        $results    = [];
        $shortName  = class_exists($parentClass)
            ? (new \ReflectionClass($parentClass))->getShortName()
            : basename(str_replace('\\', '/', $parentClass));

        foreach ($this->phpFiles() as $file) {
            $ast = $this->parse($file);

            if ($ast === null) {
                continue;
            }

            $finder  = $this->getNodeFinder();
            $classes = $finder->findInstanceOf($ast, Node\Stmt\Class_::class);

            foreach ($classes as $class) {
                if ($class->extends === null) {
                    continue;
                }

                $extendName = $class->extends->getLast();

                if ($extendName !== $shortName && (string)$class->extends !== ltrim($parentClass, '\\')) {
                    continue;
                }

                $classInfo = $this->buildClassInfo($class, $file);
                $results[] = $classInfo;
            }
        }

        return $results;
    }

    /**
     * Return CallInfo objects for calls to the given function names.
     *
     * @param  string[] $names
     * @return CallInfo[]
     */
    public function functionCalls(array $names): array
    {
        $results = [];

        foreach ($this->phpFiles() as $file) {
            $ast = $this->parse($file);

            if ($ast === null) {
                continue;
            }

            $finder = $this->getNodeFinder();

            // Static method calls: Model::create(...)
            $staticCalls = $finder->findInstanceOf($ast, Node\Expr\StaticCall::class);

            foreach ($staticCalls as $call) {
                if (!($call->name instanceof Node\Identifier)) {
                    continue;
                }

                if (in_array($call->name->name, $names, true)) {
                    $results[] = new CallInfo(
                        name: (string)$call->class . '::' . $call->name->name,
                        file: $file,
                        line: $call->getLine(),
                        args: $this->extractArgTypes($call->args)
                    );
                }
            }

            // Method calls: $model->create(...)
            $methodCalls = $finder->findInstanceOf($ast, Node\Expr\MethodCall::class);

            foreach ($methodCalls as $call) {
                if (!($call->name instanceof Node\Identifier)) {
                    continue;
                }

                if (in_array($call->name->name, $names, true)) {
                    $results[] = new CallInfo(
                        name: $call->name->name,
                        file: $file,
                        line: $call->getLine(),
                        args: $this->extractArgTypes($call->args)
                    );
                }
            }

            // Function calls: create(...)
            $funcCalls = $finder->findInstanceOf($ast, Node\Expr\FuncCall::class);

            foreach ($funcCalls as $call) {
                if (!($call->name instanceof Node\Name)) {
                    continue;
                }

                $funcName = $call->name->getLast();

                if (in_array($funcName, $names, true)) {
                    $results[] = new CallInfo(
                        name: $funcName,
                        file: $file,
                        line: $call->getLine(),
                        args: $this->extractArgTypes($call->args)
                    );
                }
            }
        }

        return $results;
    }

    /**
     * Return CallInfo for calls to specific method names (any receiver).
     *
     * @return CallInfo[]
     */
    public function methodCalls(string $method): array
    {
        return $this->functionCalls([$method]);
    }

    /**
     * Search for regex patterns in PHP files, returning CallInfo-like results.
     *
     * @return CallInfo[]
     */
    public function grep(string $pattern): array
    {
        $results = [];

        foreach ($this->phpFiles() as $file) {
            $contents = file_get_contents($file);

            if ($contents === false) {
                continue;
            }

            $lines = explode("\n", $contents);

            foreach ($lines as $lineNo => $line) {
                if (preg_match($pattern, $line, $matches)) {
                    $results[] = new CallInfo(
                        name:    $matches[0] ?? '',
                        file:    $file,
                        line:    $lineNo + 1,
                        args:    ['match' => $matches[0] ?? '']
                    );
                }
            }
        }

        return $results;
    }

    /**
     * Get the raw AST for a file.
     */
    public function parse(string $file): ?array
    {
        if (array_key_exists($file, $this->astCache)) {
            return $this->astCache[$file];
        }

        $contents = @file_get_contents($file);

        if ($contents === false) {
            $this->astCache[$file] = null;

            return null;
        }

        try {
            $ast = $this->getParser()->parse($contents);
            $this->astCache[$file] = $ast;

            return $ast;
        } catch (\Throwable $e) {
            $this->astCache[$file] = null;

            return null;
        }
    }

    /**
     * Get all PHP files from the configured paths.
     *
     * @return string[]
     */
    public function phpFiles(): array
    {
        $files = [];

        foreach ($this->paths as $path) {
            if (!is_dir($path) && !is_file($path)) {
                continue;
            }

            if (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $files[] = $path;
                continue;
            }

            try {
                $finder = Finder::create()
                    ->files()
                    ->name('*.php')
                    ->in($path);

                foreach ($this->exclude as $excludePattern) {
                    $finder->notPath($excludePattern);
                }

                foreach ($finder as $file) {
                    $files[] = $file->getRealPath();
                }
            } catch (\Throwable $e) {
                // Skip inaccessible directories
            }
        }

        return array_unique($files);
    }

    private function getParser(): \PhpParser\Parser
    {
        if ($this->parser === null) {
            $factory      = new ParserFactory();
            $this->parser = $factory->createForNewestSupportedVersion();
        }

        return $this->parser;
    }

    private function getNodeFinder(): NodeFinder
    {
        if ($this->nodeFinder === null) {
            $this->nodeFinder = new NodeFinder();
        }

        return $this->nodeFinder;
    }

    private function buildClassInfo(Node\Stmt\Class_ $class, string $file): ClassInfo
    {
        $properties = [];

        foreach ($class->stmts as $stmt) {
            if (!($stmt instanceof Node\Stmt\Property)) {
                continue;
            }

            foreach ($stmt->props as $prop) {
                $defaultValue = null;
                $hasDefault   = false;

                if ($prop->default !== null) {
                    $hasDefault = true;

                    if ($prop->default instanceof Node\Expr\Array_) {
                        $defaultValue = [];

                        foreach ($prop->default->items as $item) {
                            if ($item instanceof Node\Expr\ArrayItem) {
                                $defaultValue[] = $item;
                            }
                        }
                    } elseif ($prop->default instanceof Node\Scalar\String_) {
                        $defaultValue = $prop->default->value;
                    } elseif ($prop->default instanceof Node\Expr\ConstFetch) {
                        $name = (string) $prop->default->name;

                        if ($name === 'true') {
                            $defaultValue = true;
                        } elseif ($name === 'false') {
                            $defaultValue = false;
                        } elseif ($name === 'null') {
                            $defaultValue = null;
                        }
                    }
                }

                $properties[] = new PropertyInfo(
                    name:         (string)$prop->name,
                    file:         $file,
                    line:         $prop->getLine(),
                    defaultValue: $defaultValue,
                    hasDefault:   $hasDefault
                );
            }
        }

        $attributes = [];

        foreach ($class->attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $attributes[(string)$attr->name] = true;
            }
        }

        return new ClassInfo(
            name:       $class->name ? (string)$class->name : 'Anonymous',
            file:       $file,
            line:       $class->getLine(),
            properties: $properties,
            attributes: $attributes
        );
    }

    private function extractArgTypes(array $args): array
    {
        $result = [];

        foreach ($args as $arg) {
            if (!($arg instanceof Node\Arg)) {
                continue;
            }

            $result[] = $this->describeNode($arg->value);
        }

        return $result;
    }

    private function describeNode(Node $node): array
    {
        if ($node instanceof Node\Expr\Variable) {
            return ['type' => 'variable', 'name' => is_string($node->name) ? $node->name : '?'];
        }

        if ($node instanceof Node\Scalar\String_) {
            return ['type' => 'string', 'value' => $node->value];
        }

        if ($node instanceof Node\Expr\BinaryOp\Concat) {
            return ['type' => 'concat'];
        }

        if ($node instanceof Node\Expr\FuncCall) {
            return ['type' => 'call', 'name' => ($node->name instanceof Node\Name)
                ? (string)$node->name : '?'];
        }

        if ($node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\StaticCall) {
            return ['type' => 'call'];
        }

        if ($node instanceof Node\Expr\Array_) {
            return ['type' => 'array'];
        }

        return ['type' => 'unknown', 'class' => get_class($node)];
    }
}
