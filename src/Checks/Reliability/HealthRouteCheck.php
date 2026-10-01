<?php

namespace ShipReady\Checks\Reliability;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'REL010',
    title: 'No health check endpoint defined',
    category: 'reliability',
    severity: 'low'
)]
final class HealthRouteCheck extends AbstractCheck
{
    private const HEALTH_URI_PATTERNS = [
        'health',
        'up',
        'ping',
        'status',
        'healthz',
        'health-check',
        'readiness',
        'liveness',
    ];

    public function run(Context $context): iterable
    {
        $routes = $context->routes()->all();

        foreach ($routes as $route) {
            $uri = ltrim($route->uri, '/');

            foreach (self::HEALTH_URI_PATTERNS as $pattern) {
                if ($uri === $pattern || str_starts_with($uri, $pattern . '/')) {
                    return; // Found a health endpoint
                }
            }
        }

        yield $this->finding(
            message: 'No health check endpoint (e.g. /health, /up, /ping) found. Load balancers and uptime monitors need a health endpoint.',
            fix:     'Add Route::get(\'/up\', fn() => response(\'OK\')) or install a health check package like spatie/laravel-health.'
        );
    }
}
