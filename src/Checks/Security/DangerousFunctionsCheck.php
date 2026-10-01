<?php

namespace ShipReady\Checks\Security;

use PhpParser\Node;
use PhpParser\NodeFinder;
use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC020',
    title: 'Dangerous PHP functions used with potentially user-controlled input',
    category: 'security',
    severity: 'critical'
)]
final class DangerousFunctionsCheck extends AbstractCheck
{
    private const DANGEROUS_FUNCTIONS = [
        'eval'           => 'eval() executes arbitrary PHP code.',
        'unserialize'    => 'unserialize() on user input can lead to object injection attacks.',
        'shell_exec'     => 'shell_exec() executes OS commands.',
        'exec'           => 'exec() executes OS commands.',
        'passthru'       => 'passthru() executes OS commands.',
        'system'         => 'system() executes OS commands.',
        'popen'          => 'popen() opens a pipe to/from an OS command.',
        'proc_open'      => 'proc_open() opens a process.',
    ];

    public function run(Context $context): iterable
    {
        $files = $context->code()->phpFiles();

        foreach ($files as $file) {
            $ast = $context->code()->parse($file);

            if ($ast === null) {
                continue;
            }

            $finder    = new NodeFinder();
            $funcCalls = $finder->findInstanceOf($ast, Node\Expr\FuncCall::class);

            foreach ($funcCalls as $call) {
                if (!($call->name instanceof Node\Name)) {
                    continue;
                }

                $funcName = strtolower($call->name->getLast());

                if (!isset(self::DANGEROUS_FUNCTIONS[$funcName])) {
                    continue;
                }

                // Check if any argument might be user input
                $hasUserInput = false;

                foreach ($call->args as $arg) {
                    if (!($arg instanceof Node\Arg)) {
                        continue;
                    }

                    if ($this->mightBeUserInput($arg->value)) {
                        $hasUserInput = true;
                        break;
                    }
                }

                if ($hasUserInput) {
                    yield $this->finding(
                        message: self::DANGEROUS_FUNCTIONS[$funcName] . " Called with what appears to be dynamic input in {$file}.",
                        file:    $file,
                        line:    $call->getLine(),
                        fix:     "Avoid {$funcName}() with user-controlled values. Validate and sanitize all inputs thoroughly."
                    );
                }
            }
        }
    }

    private function mightBeUserInput(Node $node): bool
    {
        // Variable directly
        if ($node instanceof Node\Expr\Variable) {
            return true;
        }

        // Method call result (could be $request->input())
        if ($node instanceof Node\Expr\MethodCall) {
            return true;
        }

        // Concatenation with variable
        if ($node instanceof Node\Expr\BinaryOp\Concat) {
            return $this->mightBeUserInput($node->left) || $this->mightBeUserInput($node->right);
        }

        // Interpolated string with variable
        if ($node instanceof Node\Scalar\InterpolatedString) {
            foreach ($node->parts as $part) {
                if (!($part instanceof Node\Scalar\EncapsedStringPart) && $this->mightBeUserInput($part)) {
                    return true;
                }
            }
        }

        return false;
    }
}
