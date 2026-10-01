<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC022',
    title: 'Admin routes without authorization checks',
    category: 'security',
    severity: 'high'
)]
final class MissingAuthorizationCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $adminPrefixes = config('ship-ready.admin_route_prefixes', ['admin', 'dashboard', 'manage']);
        $routes        = $context->routes()->all();

        foreach ($routes as $route) {
            $isAdmin = false;

            foreach ($adminPrefixes as $prefix) {
                if (strncmp(ltrim($route->uri, '/'), $prefix, strlen($prefix)) === 0) {
                    $isAdmin = true;
                    break;
                }
            }

            if (!$isAdmin) {
                continue;
            }

            $hasAuth = false;

            // Check for auth, can, ability, role, permission middleware
            $authMiddleware = ['auth', 'can', 'ability', 'role', 'permission', 'admin', 'authorize'];

            foreach ($authMiddleware as $mw) {
                if ($route->hasMiddlewareStartingWith($mw)) {
                    $hasAuth = true;
                    break;
                }
            }

            if (!$hasAuth) {
                yield $this->finding(
                    message: "Admin route [{$route->uri}] has no authorization middleware (auth, can, role, etc.).",
                    fix:     "Add ->middleware('auth') and appropriate authorization (can/role) to all admin routes."
                );
            }
        }
    }
}
