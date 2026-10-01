<?php

namespace ShipReady\Checks\Reliability;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'REL003',
    title: 'Queued jobs used but queue driver is sync',
    category: 'reliability',
    severity: 'medium'
)]
final class QueuedJobsMissingConfigCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        // Find job classes in app/Jobs
        $jobsPath = app_path('Jobs');

        if (!is_dir($jobsPath)) {
            return;
        }

        $jobFiles = glob($jobsPath . '/*.php');

        if (empty($jobFiles)) {
            return;
        }

        $queueDriver = $context->config()->get('queue.default');

        if ($queueDriver === 'sync') {
            yield $this->finding(
                message: "Job classes are defined but the queue driver is 'sync'. Jobs run synchronously and block the request.",
                fix:     'Set QUEUE_CONNECTION=redis (or database/sqs) and run a queue worker: php artisan queue:work.'
            );
        }
    }
}
