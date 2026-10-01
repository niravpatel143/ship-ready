<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC029',
    title: 'HTTPS redirect not configured',
    category: 'security',
    severity: 'high',
    productionOnly: true
)]
final class MissingHttpsRedirectCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $appUrl = (string)$context->config()->get('app.url', '');

        if (!str_starts_with(strtolower($appUrl), 'https://')) {
            return;
        }

        $httpsKeywords = ['forcehttps', 'redirecthttps', 'httpsonly', 'requirehttps', 'httpsredirect'];

        $middlewarePath = app_path('Http/Middleware');

        if (is_dir($middlewarePath)) {
            foreach (glob($middlewarePath . '/*.php') ?: [] as $mw) {
                $content = strtolower(file_get_contents($mw) ?: '');

                foreach ($httpsKeywords as $keyword) {
                    if (str_contains($content, $keyword)) {
                        return;
                    }
                }

                if (str_contains($content, 'issecure') && str_contains($content, 'redirect')) {
                    return;
                }
            }
        }

        // Bootstrap app (Laravel 11+)
        $bootstrapApp = base_path('bootstrap/app.php');

        if (file_exists($bootstrapApp)) {
            $content = strtolower(file_get_contents($bootstrapApp) ?: '');

            foreach ($httpsKeywords as $keyword) {
                if (str_contains($content, $keyword)) {
                    return;
                }
            }
        }

        // TrustProxies with X-Forwarded-Proto means HTTPS is terminated upstream
        $trustProxiesFile = app_path('Http/Middleware/TrustProxies.php');

        if (file_exists($trustProxiesFile)) {
            $content = file_get_contents($trustProxiesFile) ?: '';

            if (str_contains($content, 'X-Forwarded-Proto') || str_contains($content, 'HEADER_X_FORWARDED_ALL')) {
                return;
            }
        }

        yield $this->finding(
            message: 'APP_URL uses HTTPS but no HTTP→HTTPS redirect middleware was detected. Visitors on plain HTTP will not be redirected.',
            fix:     'Add an HTTPS redirect middleware to your global middleware stack, or configure your web server / load balancer to handle HTTP→HTTPS redirection.'
        );
    }
}
