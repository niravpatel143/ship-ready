<?php

namespace ShipReady\Checks\VersionSpecific;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'VER001',
    title: 'Old-style VerifyCsrfToken middleware class used (Laravel 11+)',
    category: 'security',
    severity: 'medium',
    minLaravel: '11.0'
)]
final class OldCsrfMiddlewareCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $middlewareFile = app_path('Http/Middleware/VerifyCsrfToken.php');

        if (file_exists($middlewareFile)) {
            yield $this->finding(
                message: 'Laravel 11+ uses bootstrap/app.php for CSRF configuration. app/Http/Middleware/VerifyCsrfToken.php may be a leftover from an older version.',
                file:    $middlewareFile,
                fix:     'Remove app/Http/Middleware/VerifyCsrfToken.php and configure CSRF exclusions in bootstrap/app.php using withMiddleware().'
            );
        }
    }
}
