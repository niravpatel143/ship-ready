<?php

namespace ShipReady\Checks\Security;

use PhpParser\Node;
use PhpParser\NodeFinder;
use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC028',
    title: 'Sensitive data passed to logger',
    category: 'security',
    severity: 'high'
)]
final class SensitiveDataLoggedCheck extends AbstractCheck
{
    private const SENSITIVE_KEYS = [
        'password', 'passwd', 'secret', 'api_key', 'apikey', 'api_secret',
        'token', 'access_token', 'refresh_token', 'private_key', 'credit_card',
        'card_number', 'cvv', 'ssn', 'social_security',
    ];

    private const LOG_METHODS = [
        'info', 'debug', 'warning', 'error', 'critical', 'notice', 'alert', 'emergency',
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
            $calls  = $finder->findInstanceOf($ast, Node\Expr\StaticCall::class);

            foreach ($calls as $call) {
                if (!($call->class instanceof Node\Name)) {
                    continue;
                }

                $className = $call->class->toString();

                if (!in_array($className, ['Log', 'Illuminate\Support\Facades\Log'], true)) {
                    continue;
                }

                if (!($call->name instanceof Node\Identifier)) {
                    continue;
                }

                if (!in_array($call->name->name, self::LOG_METHODS, true)) {
                    continue;
                }

                foreach ($call->args as $arg) {
                    if (!($arg->value instanceof Node\Expr\Array_)) {
                        continue;
                    }

                    foreach ($arg->value->items as $item) {
                        if ($item === null || !($item->key instanceof Node\Scalar\String_)) {
                            continue;
                        }

                        $key = strtolower($item->key->value);

                        foreach (self::SENSITIVE_KEYS as $sensitive) {
                            if (str_contains($key, $sensitive)) {
                                yield $this->finding(
                                    message: "Log::{$call->name->name}() may be logging sensitive data in key '{$item->key->value}'.",
                                    file:    $file,
                                    line:    $call->getLine(),
                                    fix:     "Remove sensitive fields from log context or mask them: str_repeat('*', strlen(\$value))."
                                );
                                break 3;
                            }
                        }
                    }
                }
            }
        }
    }
}
