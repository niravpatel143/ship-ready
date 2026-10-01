<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC030',
    title: 'Content-Security-Policy header not configured',
    category: 'security',
    severity: 'medium',
    productionOnly: true
)]
final class ContentSecurityPolicyCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $composerJson = base_path('composer.json');

        if (file_exists($composerJson)) {
            $composer = json_decode(file_get_contents($composerJson) ?: '{}', true);
            $packages = array_merge(
                array_keys($composer['require'] ?? []),
                array_keys($composer['require-dev'] ?? [])
            );

            foreach (['spatie/laravel-csp', 'bepsvpt/secure-headers', 'paragonie/csp-builder'] as $pkg) {
                if (in_array($pkg, $packages, true)) {
                    return;
                }
            }
        }

        $middlewarePath = app_path('Http/Middleware');

        if (is_dir($middlewarePath)) {
            foreach (glob($middlewarePath . '/*.php') ?: [] as $mw) {
                $content = file_get_contents($mw) ?: '';

                if (str_contains($content, 'Content-Security-Policy')) {
                    return;
                }
            }
        }

        // Check bootstrap/app.php (Laravel 11+)
        $bootstrapApp = base_path('bootstrap/app.php');

        if (file_exists($bootstrapApp)) {
            $content = file_get_contents($bootstrapApp) ?: '';

            if (str_contains($content, 'Content-Security-Policy')) {
                return;
            }
        }

        yield $this->finding(
            message: 'No Content-Security-Policy (CSP) header is configured. Without CSP, XSS payloads can load arbitrary scripts from any origin.',
            fix:     "Install spatie/laravel-csp, or add a middleware that sets the Content-Security-Policy response header on every response."
        );
    }
}
