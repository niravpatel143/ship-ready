<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC027',
    title: 'API routes missing rate limiting',
    category: 'security',
    severity: 'medium'
)]
final class ApiRateLimitCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $routes = $context->routes()->all();

        foreach ($routes as $route) {
            $isApiRoute = str_starts_with($route->uri, 'api/')
                || $route->hasMiddlewareStartingWith('api');

            if (!$isApiRoute) {
                continue;
            }

            $isWriteMethod = $route->acceptsMethod('POST')
                || $route->acceptsMethod('PUT')
                || $route->acceptsMethod('PATCH')
                || $route->acceptsMethod('DELETE');

            if (!$isWriteMethod) {
                continue;
            }

            if (!$route->hasMiddlewareStartingWith('throttle')) {
                yield $this->finding(
                    message: "API route [{$route->uri}] has no rate limiting. Abuse and DoS attacks are possible.",
                    fix:     "Add throttle middleware: ->middleware('throttle:60,1') or define a named rate limit via RateLimiter::for()."
                );
            }
        }
    }
}
