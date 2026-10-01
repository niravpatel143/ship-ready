<?php

namespace ShipReady\Checks\VersionSpecific;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'VER012',
    title: 'routes/channels.php exists but broadcasting not enabled (Laravel 11+)',
    category: 'reliability',
    severity: 'medium',
    minLaravel: '11.0'
)]
final class BroadcastRoutesNotLoadedCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $channelsFile = base_path('routes/channels.php');

        if (!file_exists($channelsFile)) {
            return;
        }

        $content = file_get_contents($channelsFile) ?: '';

        // Skip if the file only has comments or is effectively empty
        $stripped = preg_replace('/\/\/.*$/m', '', $content);
        $stripped = trim($stripped ?? '');

        if (strlen($stripped) < 50) {
            return;
        }

        $bootstrapApp = base_path('bootstrap/app.php');

        if (!file_exists($bootstrapApp)) {
            return;
        }

        $bootstrap = file_get_contents($bootstrapApp) ?: '';

        // In L11+ broadcasting must be explicitly enabled
        if (!str_contains($bootstrap, 'channels:') && !str_contains($bootstrap, 'withBroadcasting')
            && !str_contains($bootstrap, 'BroadcastServiceProvider')) {
            yield $this->finding(
                message: 'routes/channels.php has channel definitions but broadcasting is not registered in bootstrap/app.php. Broadcast authentication routes will return 404.',
                file:    $bootstrapApp,
                fix:     "Enable broadcasting in bootstrap/app.php: ->withRouting(channels: __DIR__.'/../routes/channels.php')"
            );
        }
    }
}
