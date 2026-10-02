<?php

namespace ShipReady\Checks\Octane;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'OCT006',
    title: 'No Octane RequestReceived listener to reset service state',
    category: 'octane',
    severity: 'low'
)]
final class OctaneWorkerResetCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $octaneConfig = config_path('octane.php');

        if (!file_exists($octaneConfig)) {
            return;
        }

        // Check if any listener handles RequestReceived
        $eventServiceProvider = app_path('Providers/EventServiceProvider.php');
        $appServiceProvider   = app_path('Providers/AppServiceProvider.php');
        $bootstrapApp         = base_path('bootstrap/app.php');

        $hasResetListener = false;

        foreach ([$eventServiceProvider, $appServiceProvider, $bootstrapApp] as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $content = file_get_contents($file) ?: '';

            if (str_contains($content, 'RequestReceived') || str_contains($content, 'OctaneConcurrentlyResolved')) {
                $hasResetListener = true;
                break;
            }
        }

        // Also check octane config listeners
        $octaneContent = file_get_contents($octaneConfig) ?: '';
        if (str_contains($octaneContent, 'RequestReceived') || str_contains($octaneContent, 'listeners')) {
            $hasResetListener = true;
        }

        if (!$hasResetListener) {
            yield $this->finding(
                message: 'No Octane RequestReceived listener is registered. Without it, any service holding request-scoped state will not be reset between requests.',
                fix:     'Register a listener in AppServiceProvider: Event::listen(RequestReceived::class, fn() => app(MyService::class)->reset()); or use octane.listeners in config/octane.php.'
            );
        }
    }
}
