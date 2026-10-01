<?php

namespace ShipReady\Checks\VersionSpecific;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'VER002',
    title: 'Deprecated app/Http/Kernel.php used (Laravel 11+)',
    category: 'reliability',
    severity: 'low',
    minLaravel: '11.0'
)]
final class DeprecatedMiddlewareLocationCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $kernelFile = app_path('Http/Kernel.php');

        if (file_exists($kernelFile)) {
            yield $this->finding(
                message: 'app/Http/Kernel.php exists but Laravel 11+ uses bootstrap/app.php for middleware registration.',
                file:    $kernelFile,
                fix:     'Migrate global middleware to bootstrap/app.php using withMiddleware(). The Kernel.php approach is deprecated in Laravel 11.'
            );
        }

        $consoleKernel = app_path('Console/Kernel.php');

        if (file_exists($consoleKernel)) {
            yield $this->finding(
                message: 'app/Console/Kernel.php exists. Laravel 11+ registers scheduled tasks in routes/console.php.',
                file:    $consoleKernel,
                fix:     'Move scheduled task definitions to routes/console.php and remove Console/Kernel.php.'
            );
        }
    }
}
