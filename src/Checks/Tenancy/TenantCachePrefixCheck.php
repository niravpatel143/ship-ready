<?php

namespace ShipReady\Checks\Tenancy;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'TEN003',
    title: 'Cache not isolated per tenant — cache leaks across tenants',
    category: 'tenancy',
    severity: 'high'
)]
final class TenantCachePrefixCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        if (!$this->isTenancyInstalled()) {
            return;
        }

        $prefix = config('cache.prefix', '');

        // A static prefix means all tenants share the same cache namespace
        if (!str_contains((string)$prefix, 'tenant') && !str_contains((string)$prefix, '{') ) {
            // Check if tenancy bootstrappers configure cache isolation
            $tenancyConfig = config_path('tenancy.php');

            if (!file_exists($tenancyConfig)) {
                return;
            }

            $content = file_get_contents($tenancyConfig) ?: '';

            if (!str_contains($content, 'PrefixCacheTenancyBootstrapper')
                && !str_contains($content, 'cache')
            ) {
                yield $this->finding(
                    message: 'Cache prefix is not tenant-aware. Tenants can read each other\'s cached data.',
                    file:    $tenancyConfig,
                    fix:     'Add PrefixCacheTenancyBootstrapper to the bootstrappers list in config/tenancy.php to isolate cache per tenant.'
                );
            }
        }
    }

    private function isTenancyInstalled(): bool
    {
        $composerLock = base_path('composer.lock');

        if (!file_exists($composerLock)) {
            return false;
        }

        $lock     = json_decode(file_get_contents($composerLock) ?: '{}', true);
        $packages = array_column($lock['packages'] ?? [], 'name');

        return in_array('stancl/tenancy', $packages, true)
            || in_array('spatie/laravel-multitenancy', $packages, true);
    }
}
