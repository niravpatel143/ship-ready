<?php

namespace ShipReady\Checks\Reliability;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'REL002',
    title: 'Task scheduler may not be configured',
    category: 'reliability',
    severity: 'medium'
)]
final class SchedulerNotRunningCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        // Check if there are scheduled tasks defined
        $kernelFile = app_path('Console/Kernel.php');

        // Laravel 11+ does not use Kernel.php
        if (!file_exists($kernelFile)) {
            // Check routes/console.php for scheduled tasks
            $consolePath = base_path('routes/console.php');

            if (file_exists($consolePath)) {
                $contents = file_get_contents($consolePath);

                if ($contents !== false && str_contains($contents, 'Schedule') || str_contains($contents ?? '', 'schedule')) {
                    $this->warnIfNoCron();
                }
            }

            return;
        }

        $contents = file_get_contents($kernelFile);

        if ($contents === false) {
            return;
        }

        // Check if schedule() method has any task definitions
        if (!str_contains($contents, '$schedule->')) {
            return;
        }

        yield from $this->warnIfNoCron();
    }

    private function warnIfNoCron(): iterable
    {
        // Check if a "schedule:run" cron marker exists
        $cronMarker = storage_path('framework/.scheduler_running');

        if (!file_exists($cronMarker)) {
            yield $this->finding(
                message: 'Scheduled tasks appear to be defined but there is no evidence the cron is configured.',
                fix:     'Add this to crontab: * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1'
            );
        }
    }
}
