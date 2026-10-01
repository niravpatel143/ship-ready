<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC010',
    title: 'Auth routes missing throttle middleware',
    category: 'security',
    severity: 'high'
)]
final class UnthrottledAuthRoutesCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $authPatterns = config('ship-ready.auth_route_names', ['login', 'register', 'password.*', 'auth.*', '*.login', '*.register']);

        $routes = $context->routes()->all();

        foreach ($routes as $route) {
            // Only check POST routes (actual form submissions)
            if (!$route->acceptsMethod('POST')) {
                continue;
            }

            // Check if route name matches auth patterns
            $isAuthRoute = false;

            foreach ($authPatterns as $pattern) {
                if ($route->name !== null && fnmatch($pattern, $route->name)) {
                    $isAuthRoute = true;
                    break;
                }
            }

            // Also check URI
            if (!$isAuthRoute) {
                $authUriKeywords = ['login', 'register', 'password', 'forgot', 'reset', 'two-factor'];

                foreach ($authUriKeywords as $keyword) {
                    if (str_contains($route->uri, $keyword)) {
                        $isAuthRoute = true;
                        break;
                    }
                }
            }

            if (!$isAuthRoute) {
                continue;
            }

            // Check for throttle middleware
            if (!$route->hasMiddlewareStartingWith('throttle')) {
                yield $this->finding(
                    message: "Auth route [{$route->uri}] (POST) has no throttle middleware. Brute-force attacks are possible.",
                    fix:     "Add throttle middleware: Route::post('{$route->uri}', ...)->middleware('throttle:5,1');"
                );
            }
        }
    }
}
