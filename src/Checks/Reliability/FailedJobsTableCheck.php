<?php

namespace ShipReady\Checks\Reliability;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'REL004',
    title: 'Failed jobs table not configured',
    category: 'reliability',
    severity: 'medium'
)]
final class FailedJobsTableCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $queueDriver = $context->config()->get('queue.default');

        if ($queueDriver === 'sync' || $queueDriver === 'null') {
            return;
        }

        $failedDriver = $context->config()->get('queue.failed.driver', 'database-uuids');

        if (in_array($failedDriver, ['null', null], true)) {
            yield $this->finding(
                message: 'Failed job storage is disabled. Failed jobs will be lost silently.',
                fix:     "Run `php artisan queue:failed-table && php artisan migrate` to create the failed jobs table."
            );

            return;
        }

        // Check if the migration exists
        $migrationsPath = database_path('migrations');

        if (!is_dir($migrationsPath)) {
            return;
        }

        $files    = glob($migrationsPath . '/*failed_jobs*');
        $files2   = glob($migrationsPath . '/*job_batches*');

        if (empty($files)) {
            yield $this->finding(
                message: 'No failed_jobs migration found. Failed queue jobs have nowhere to be recorded.',
                fix:     'Run `php artisan queue:failed-table` to generate the migration, then `php artisan migrate`.'
            );
        }
    }
}
