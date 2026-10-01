<?php

namespace ShipReady\Compat;

final class LaravelVersion
{
    public static function detect(): string
    {
        if (function_exists('app')) {
            try {
                $app = app();
                if (method_exists($app, 'version')) {
                    return $app->version();
                }
            } catch (\Throwable $e) {
                // Fall through to constant check
            }
        }

        if (defined('\Illuminate\Foundation\Application::VERSION')) {
            return \Illuminate\Foundation\Application::VERSION;
        }

        return '0.0.0';
    }
}
