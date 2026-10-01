<?php

namespace ShipReady\Checks\Security;

use PhpParser\Node;
use PhpParser\NodeFinder;
use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC009',
    title: 'Possible SQL injection via raw query with variable interpolation',
    category: 'security',
    severity: 'critical'
)]
final class SqlInjectionCheck extends AbstractCheck
{
    private const RAW_METHODS = ['whereRaw', 'selectRaw', 'havingRaw', 'orderByRaw', 'groupByRaw'];

    public function run(Context $context): iterable
    {
        $files = $context->code()->phpFiles();

        foreach ($files as $file) {
            $ast = $context->code()->parse($file);

            if ($ast === null) {
                continue;
            }

            $finder      = new NodeFinder();
            $methodCalls = $finder->findInstanceOf($ast, Node\Expr\MethodCall::class);

            foreach ($methodCalls as $call) {
                if (!($call->name instanceof Node\Identifier)) {
                    continue;
                }

                if (!in_array($call->name->name, self::RAW_METHODS, true)) {
                    continue;
                }

                if (empty($call->args)) {
                    continue;
                }

                $firstArg = $call->args[0];

                if (!($firstArg instanceof Node\Arg)) {
                    continue;
                }

                // Check if first argument has variable interpolation or concatenation
                if ($this->hasVariableInput($firstArg->value)) {
                    yield $this->finding(
                        message: "Potential SQL injection: {$call->name->name}() called with a dynamic/interpolated SQL string.",
                        file:    $file,
                        line:    $call->getLine(),
                        fix:     'Use parameterized bindings: ' . $call->name->name . '("column = ?", [$value]) instead of string interpolation.'
                    );
                }
            }

            // Also check DB::statement, DB::select, etc.
            $staticCalls = $finder->findInstanceOf($ast, Node\Expr\StaticCall::class);

            foreach ($staticCalls as $call) {
                if (!($call->name instanceof Node\Identifier)) {
                    continue;
                }

                $dbRawMethods = ['statement', 'select', 'insert', 'update', 'delete', 'unprepared'];

                if (!in_array($call->name->name, $dbRawMethods, true)) {
                    continue;
                }

                if (!($call->class instanceof Node\Name)) {
                    continue;
                }

                $className = (string)$call->class;

                if (!in_array($className, ['DB', 'Database', '\Illuminate\Support\Facades\DB'], true)) {
                    continue;
                }

                if (empty($call->args)) {
                    continue;
                }

                $firstArg = $call->args[0];

                if (!($firstArg instanceof Node\Arg)) {
                    continue;
                }

                if ($this->hasVariableInput($firstArg->value)) {
                    yield $this->finding(
                        message: "Potential SQL injection: DB::{$call->name->name}() called with dynamic SQL string.",
                        file:    $file,
                        line:    $call->getLine(),
                        fix:     'Use parameterized bindings as the second argument: DB::' . $call->name->name . '($sql, [$var]).'
                    );
                }
            }
        }
    }

    private function hasVariableInput(Node $node): bool
    {
        if ($node instanceof Node\Expr\Variable) {
            return true;
        }

        if ($node instanceof Node\Expr\BinaryOp\Concat) {
            return $this->hasVariableInput($node->left) || $this->hasVariableInput($node->right);
        }

        if ($node instanceof Node\Scalar\InterpolatedString) {
            foreach ($node->parts as $part) {
                if ($part instanceof Node\Expr\Variable) {
                    return true;
                }
            }
        }

        if ($node instanceof Node\Expr\FuncCall || $node instanceof Node\Expr\MethodCall) {
            return true;
        }

        return false;
    }
}
