<?php

namespace ShipReady\Checks\VersionSpecific;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'VER009',
    title: 'routes/api.php exists but may not be auto-loaded (Laravel 11+)',
    category: 'reliability',
    severity: 'high',
    minLaravel: '11.0'
)]
final class ApiRoutesNotLoadedCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $apiRoutes = base_path('routes/api.php');

        if (!file_exists($apiRoutes)) {
            return;
        }

        // In Laravel 11+ routes/api.php is NOT auto-loaded — it must be
        // explicitly registered in bootstrap/app.php via withRouting().
        $bootstrapApp = base_path('bootstrap/app.php');

        if (!file_exists($bootstrapApp)) {
            return;
        }

        $content = file_get_contents($bootstrapApp) ?: '';

        // If api routes are explicitly registered, we're fine
        if (str_contains($content, 'api:') || str_contains($content, "'api'")
            || str_contains($content, '"api"') || str_contains($content, 'routes/api')) {
            return;
        }

        yield $this->finding(
            message: 'routes/api.php exists but Laravel 11+ no longer auto-loads it. Your API routes may be silently returning 404.',
            file:    $bootstrapApp,
            fix:     "Register API routes in bootstrap/app.php: ->withRouting(api: __DIR__.'/../routes/api.php', apiPrefix: 'api')"
        );
    }
}
