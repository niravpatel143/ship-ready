<?php

namespace ShipReady\Checks\Performance;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'PERF012',
    title: 'Log level set to debug in production',
    category: 'performance',
    severity: 'medium',
    productionOnly: true
)]
final class DebugLoggingCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $logLevel = $context->config()->get('logging.channels.' .
            $context->config()->get('logging.default', 'stack') . '.level');

        if ($logLevel === null) {
            // Check the stack channels
            $stack    = $context->config()->get('logging.channels.stack', []);
            $channels = $stack['channels'] ?? [];

            foreach ($channels as $channel) {
                $level = $context->config()->get("logging.channels.{$channel}.level");

                if ($level === 'debug') {
                    yield $this->finding(
                        message: "Log channel '{$channel}' is set to 'debug' level in production. Debug logging is verbose and can slow down the application.",
                        fix:     "Set LOG_LEVEL=warning or LOG_LEVEL=error in your production .env file."
                    );

                    return;
                }
            }

            return;
        }

        if ($logLevel === 'debug') {
            yield $this->finding(
                message: "Default log channel is set to 'debug' level. Debug logging generates excessive I/O and can expose sensitive data.",
                fix:     'Set LOG_LEVEL=warning in your production .env.'
            );
        }
    }
}
