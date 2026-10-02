<?php

namespace ShipReady\Checks\Queue;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'QUE001',
    title: 'Queue retry_after is less than or equal to job timeout — jobs will be retried while still running',
    category: 'queue',
    severity: 'high'
)]
final class RetryAfterTimeoutMismatchCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $queueConfig = config_path('queue.php');

        if (!file_exists($queueConfig)) {
            return;
        }

        $connections = config('queue.connections', []);

        foreach ($connections as $name => $connection) {
            $driver      = $connection['driver'] ?? '';
            $retryAfter  = (int)($connection['retry_after'] ?? 90);

            if (!in_array($driver, ['redis', 'database', 'beanstalkd', 'sqs'], true)) {
                continue;
            }

            $analyzer = $context->code();

            if ($analyzer === null) {
                continue;
            }

            // Find jobs with a $timeout property
            foreach ($analyzer->phpFiles() as $file) {
                $content = file_get_contents($file) ?: '';

                if (!preg_match('/implements\s+ShouldQueue/', $content)) {
                    continue;
                }

                if (!preg_match('/public\s+(?:int\s+)?\$timeout\s*=\s*(\d+)/', $content, $m)) {
                    continue;
                }

                $timeout = (int)$m[1];

                if ($retryAfter <= $timeout) {
                    yield $this->finding(
                        message: "Job has \$timeout={$timeout}s but queue connection '{$name}' has retry_after={$retryAfter}s. The job will be re-dispatched before it finishes, creating duplicate runs.",
                        file:    $file,
                        fix:     "Set retry_after to at least \$timeout + 30 in config/queue.php for connection '{$name}', or reduce the job's \$timeout."
                    );
                }
            }
        }
    }
}
