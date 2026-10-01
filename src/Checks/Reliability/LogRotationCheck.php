<?php

namespace ShipReady\Checks\Reliability;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'REL009',
    title: 'Log channel not configured for rotation',
    category: 'reliability',
    severity: 'low',
    productionOnly: true
)]
final class LogRotationCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $config  = $context->config();
        $default = $config->get('logging.default', 'stack');
        $channel = $config->get("logging.channels.{$default}", []);

        // Single log channel without rotation will fill disk
        if (isset($channel['driver']) && $channel['driver'] === 'single') {
            yield $this->finding(
                message: "Logging is configured with the 'single' driver. Log files will grow indefinitely without rotation.",
                fix:     "Change LOG_CHANNEL=daily in production for automatic 14-day rotation, or configure external log rotation (logrotate)."
            );

            return;
        }

        // If using 'stack', check if all sub-channels are safe
        if (isset($channel['driver']) && $channel['driver'] === 'stack') {
            $channels = $channel['channels'] ?? [];

            foreach ($channels as $subChannel) {
                $sub = $config->get("logging.channels.{$subChannel}", []);

                if (isset($sub['driver']) && $sub['driver'] === 'single') {
                    yield $this->finding(
                        message: "Stack log channel includes '{$subChannel}' using the 'single' driver. Log rotation is not configured.",
                        fix:     "Switch to 'daily' driver or configure external log rotation."
                    );
                }
            }
        }
    }
}
