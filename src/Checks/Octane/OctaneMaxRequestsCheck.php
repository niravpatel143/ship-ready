<?php

namespace ShipReady\Checks\Octane;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'OCT003',
    title: 'Octane max_requests not configured — memory grows unbounded',
    category: 'octane',
    severity: 'medium'
)]
final class OctaneMaxRequestsCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $octaneConfig = config_path('octane.php');

        if (!file_exists($octaneConfig)) {
            return; // Octane not installed
        }

        $config = @include $octaneConfig;

        if (!is_array($config)) {
            return;
        }

        $maxRequests = $config['max_requests'] ?? null;

        if ($maxRequests === null || (int)$maxRequests === 0) {
            yield $this->finding(
                message: 'octane.max_requests is not set. Workers will run indefinitely, causing gradual memory growth and potential memory exhaustion.',
                file:    $octaneConfig,
                fix:     'Set max_requests to a reasonable value (e.g. 500) in config/octane.php to recycle workers and prevent memory leaks.'
            );
        }
    }
}
