<?php

namespace ShipReady\Analyzers;

use Illuminate\Support\Str;

final class RouteInfo
{
    public ?string $name;
    public string $uri;
    public array $methods;
    public string $action;
    public array $middleware;
    public ?string $definitionFile;
    public ?int $definitionLine;

    public function __construct(
        ?string $name,
        string $uri,
        array $methods,
        string $action,
        array $middleware,
        ?string $definitionFile = null,
        ?int $definitionLine = null
    ) {
        $this->name           = $name;
        $this->uri            = $uri;
        $this->methods        = $methods;
        $this->action         = $action;
        $this->middleware     = $middleware;
        $this->definitionFile = $definitionFile;
        $this->definitionLine = $definitionLine;
    }

    public function nameMatches(array $patterns): bool
    {
        if ($this->name === null) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if (Str::is($pattern, $this->name)) {
                return true;
            }
        }

        return false;
    }

    public function hasMiddlewareStartingWith(string $prefix): bool
    {
        foreach ($this->middleware as $mw) {
            if (strncmp($mw, $prefix, strlen($prefix)) === 0) {
                return true;
            }
        }

        return false;
    }

    public function acceptsMethod(string $method): bool
    {
        $upperMethod = strtoupper($method);

        return in_array($upperMethod, $this->methods, true);
    }

    public function hasMiddleware(string $name): bool
    {
        foreach ($this->middleware as $mw) {
            if ($mw === $name || strncmp($mw, $name . ':', strlen($name) + 1) === 0) {
                return true;
            }
        }

        return false;
    }
}
