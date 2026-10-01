<?php

namespace ShipReady\Checks\Reliability;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'REL012',
    title: 'Routes pointing to non-existent controllers',
    category: 'reliability',
    severity: 'medium'
)]
final class DeadRoutesCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $routes = $context->routes()->all();

        foreach ($routes as $route) {
            $action = $route->action;

            // Skip closures and built-in actions
            if (in_array($action, ['Closure', 'Illuminate\Routing\RedirectController', 'Illuminate\Routing\ViewController'], true)) {
                continue;
            }

            if (strncmp($action, 'Closure', 7) === 0) {
                continue;
            }

            // Parse Controller@method or [Controller::class, 'method']
            if (str_contains($action, '@')) {
                [$controller, $method] = explode('@', $action, 2);

                if (!class_exists($controller)) {
                    yield $this->finding(
                        message: "Route [{$route->uri}] points to non-existent controller: {$controller}",
                        fix:     "Create the controller class or update the route definition."
                    );
                } elseif (!method_exists($controller, $method)) {
                    yield $this->finding(
                        message: "Route [{$route->uri}] points to non-existent method: {$controller}@{$method}",
                        fix:     "Add the '{$method}' method to {$controller} or update the route."
                    );
                }
            }
        }
    }
}
