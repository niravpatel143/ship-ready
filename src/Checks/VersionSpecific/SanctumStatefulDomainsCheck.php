<?php

namespace ShipReady\Checks\VersionSpecific;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'VER011',
    title: 'Sanctum SPA authentication missing stateful domains',
    category: 'security',
    severity: 'high'
)]
final class SanctumStatefulDomainsCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        // Only relevant if Sanctum is installed
        if (!class_exists(\Laravel\Sanctum\SanctumServiceProvider::class)) {
            return;
        }

        // Only relevant if SPA middleware (sanctum) is in use on web routes
        $hasSpaMiddleware = false;

        foreach ($context->routes()->all() as $route) {
            if ($route->hasMiddlewareStartingWith('auth:sanctum')) {
                $hasSpaMiddleware = true;
                break;
            }
        }

        if (!$hasSpaMiddleware) {
            return;
        }

        $statefulDomains = config('sanctum.stateful', []);

        if (empty($statefulDomains)) {
            yield $this->finding(
                message: 'Sanctum is used for SPA authentication but sanctum.stateful domains are not configured. Cookie-based authentication will fail for all origins.',
                fix:     'Set SANCTUM_STATEFUL_DOMAINS=yourdomain.com in your .env, or publish and configure config/sanctum.php.'
            );
        }
    }
}
