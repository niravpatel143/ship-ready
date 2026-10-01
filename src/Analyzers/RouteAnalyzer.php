<?php

namespace ShipReady\Analyzers;

use Illuminate\Routing\Router;
use Illuminate\Support\Str;

final class RouteAnalyzer
{
    private Router $router;
    private ?array $cachedRoutes = null;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @return RouteInfo[]
     */
    public function all(): array
    {
        if ($this->cachedRoutes !== null) {
            return $this->cachedRoutes;
        }

        $routes = [];

        foreach ($this->router->getRoutes() as $route) {
            $action = $route->getActionName();

            // Try to resolve the file/line for the route definition
            $file = null;
            $line = null;

            $routes[] = new RouteInfo(
                name:           $route->getName(),
                uri:            $route->uri(),
                methods:        $route->methods(),
                action:         $action,
                middleware:     $this->resolveMiddleware($route),
                definitionFile: $file,
                definitionLine: $line
            );
        }

        $this->cachedRoutes = $routes;

        return $routes;
    }

    /**
     * Return routes whose URI starts with any of the given prefixes.
     *
     * @param  string[] $prefixes
     * @return RouteInfo[]
     */
    public function withUriPrefix(array $prefixes): array
    {
        return array_filter(
            $this->all(),
            function (RouteInfo $route) use ($prefixes) {
                foreach ($prefixes as $prefix) {
                    if (strncmp(ltrim($route->uri, '/'), ltrim($prefix, '/'), strlen(ltrim($prefix, '/'))) === 0) {
                        return true;
                    }
                }

                return false;
            }
        );
    }

    /**
     * Return routes matching any of the given name patterns.
     *
     * @param  string[] $patterns
     * @return RouteInfo[]
     */
    public function withName(array $patterns): array
    {
        return array_filter(
            $this->all(),
            fn(RouteInfo $r) => $r->nameMatches($patterns)
        );
    }

    private function resolveMiddleware(\Illuminate\Routing\Route $route): array
    {
        $middleware = [];

        foreach ($route->gatherMiddleware() as $mw) {
            if (is_string($mw)) {
                $middleware[] = $mw;
            } elseif (is_array($mw)) {
                foreach ($mw as $item) {
                    if (is_string($item)) {
                        $middleware[] = $item;
                    }
                }
            }
        }

        return array_unique($middleware);
    }
}
