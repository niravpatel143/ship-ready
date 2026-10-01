<?php

namespace ShipReady\Checks\Reliability;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'REL018',
    title: 'TrustProxies not configured for HTTPS app',
    category: 'reliability',
    severity: 'high',
    productionOnly: true
)]
final class TrustProxiesCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $appUrl = (string)$context->config()->get('app.url', '');

        if (!str_starts_with(strtolower($appUrl), 'https://')) {
            return;
        }

        // Laravel 11+: check bootstrap/app.php
        $bootstrapApp = base_path('bootstrap/app.php');

        if (file_exists($bootstrapApp)) {
            $content = file_get_contents($bootstrapApp) ?: '';

            if (str_contains($content, 'TrustProxies') || str_contains($content, 'trustProxies')) {
                return;
            }
        }

        // Laravel 9/10: check Http/Kernel.php
        $kernelFile = app_path('Http/Kernel.php');

        if (file_exists($kernelFile)) {
            $content = file_get_contents($kernelFile) ?: '';

            if (str_contains($content, 'TrustProxies')) {
                return;
            }
        }

        // Check if custom TrustProxies file has non-null proxies configured
        $trustProxiesFile = app_path('Http/Middleware/TrustProxies.php');

        if (file_exists($trustProxiesFile)) {
            $content = file_get_contents($trustProxiesFile) ?: '';

            if (str_contains($content, "'*'") || str_contains($content, '"*"')
                || str_contains($content, 'HEADER_X_FORWARDED_ALL')) {
                return;
            }
        }

        yield $this->finding(
            message: "APP_URL is HTTPS but TrustProxies middleware does not appear to be configured. Behind a load balancer, \$request->isSecure() and IP detection will be unreliable.",
            fix:     "Configure TrustProxies: set \$proxies = '*' (or your load balancer CIDR) and include \$headers = Request::HEADER_X_FORWARDED_ALL in the middleware."
        );
    }
}
