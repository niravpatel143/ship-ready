<?php

namespace ShipReady\Checks\Security;

use PhpParser\Node;
use PhpParser\NodeFinder;
use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC032',
    title: 'Potential timing attack via direct string comparison',
    category: 'security',
    severity: 'high'
)]
final class TimingAttackCheck extends AbstractCheck
{
    private const SENSITIVE_PATTERNS = [
        'token', 'hash', 'hmac', 'signature', 'sig', 'secret', 'digest', 'mac',
    ];

    public function run(Context $context): iterable
    {
        $files = $context->code()->phpFiles();

        foreach ($files as $file) {
            $ast = $context->code()->parse($file);

            if ($ast === null) {
                continue;
            }

            $finder = new NodeFinder();
            $nodes  = array_merge(
                $finder->findInstanceOf($ast, Node\Expr\BinaryOp\Identical::class),
                $finder->findInstanceOf($ast, Node\Expr\BinaryOp\Equal::class)
            );

            foreach ($nodes as $cmp) {
                /** @var Node\Expr\BinaryOp $cmp */
                if ($this->isLiteral($cmp->left) || $this->isLiteral($cmp->right)) {
                    continue;
                }

                if ($this->isSensitiveExpr($cmp->left) || $this->isSensitiveExpr($cmp->right)) {
                    yield $this->finding(
                        message: 'Direct string comparison of a security-sensitive variable is vulnerable to timing attacks.',
                        file:    $file,
                        line:    $cmp->getLine(),
                        fix:     'Use hash_equals($expected, $actual) for constant-time comparison of tokens, hashes, and signatures.'
                    );
                }
            }
        }
    }

    private function isSensitiveExpr(Node $node): bool
    {
        $name = null;

        if ($node instanceof Node\Expr\Variable && is_string($node->name)) {
            $name = strtolower($node->name);
        } elseif ($node instanceof Node\Expr\PropertyFetch && $node->name instanceof Node\Identifier) {
            $name = strtolower($node->name->name);
        } elseif ($node instanceof Node\Expr\ArrayDimFetch && $node->dim instanceof Node\Scalar\String_) {
            $name = strtolower($node->dim->value);
        }

        if ($name === null) {
            return false;
        }

        foreach (self::SENSITIVE_PATTERNS as $pattern) {
            if (str_contains($name, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function isLiteral(Node $node): bool
    {
        return $node instanceof Node\Expr\ConstFetch
            || $node instanceof Node\Scalar\LNumber
            || $node instanceof Node\Scalar\DNumber;
    }
}
