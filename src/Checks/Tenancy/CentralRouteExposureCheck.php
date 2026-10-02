<?php

namespace ShipReady\Checks\Tenancy;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'TEN005',
    title: 'Central domain routes not separated from tenant routes',
    category: 'tenancy',
    severity: 'medium'
)]
final class CentralRouteExposureCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        if (!$this->isTenancyInstalled()) {
            return;
        }

        $tenancyConfig = config_path('tenancy.php');

        if (!file_exists($tenancyConfig)) {
            return;
        }

        // stancl/tenancy expects routes/tenant.php for tenant routes
        $tenantRoutes  = base_path('routes/tenant.php');
        $centralRoutes = base_path('routes/web.php');

        if (!file_exists($tenantRoutes) && file_exists($centralRoutes)) {
            $webContent = file_get_contents($centralRoutes) ?: '';

            if (preg_match('/InitializeTenancyByDomain|InitializeTenancyBySubdomain/', $webContent)) {
                yield $this->finding(
                    message: 'Tenant middleware is applied in routes/web.php instead of a dedicated routes/tenant.php file. This mixes central and tenant routes and can cause initialization issues.',
                    file:    $centralRoutes,
                    fix:     'Create routes/tenant.php with tenant middleware and register it in TenancyServiceProvider. Keep routes/web.php for central domain routes only.'
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
