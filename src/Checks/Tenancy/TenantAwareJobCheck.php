<?php

namespace ShipReady\Checks\Tenancy;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'TEN002',
    title: 'Queued jobs in multi-tenant app missing tenant context',
    category: 'tenancy',
    severity: 'high'
)]
final class TenantAwareJobCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        if (!$this->isTenancyInstalled()) {
            return;
        }

        $analyzer = $context->code();

        if ($analyzer === null) {
            return;
        }

        foreach ($analyzer->phpFiles() as $file) {
            if (!str_contains($file, '/Jobs/')) {
                continue;
            }

            $content = file_get_contents($file) ?: '';

            if (!preg_match('/implements\s+ShouldQueue/', $content)) {
                continue;
            }

            // Check for tenant-aware traits or properties
            $isTenantAware = preg_match('/TenantAwareJob|TenantAware|ShouldRunForTenants|tenancy\(\)|InitializeTenancyByJobTenant/', $content)
                || preg_match('/\$tenantId|\$tenant\b/', $content);

            if (!$isTenantAware) {
                yield $this->finding(
                    message: basename($file, '.php') . ' is a queued job in a tenanted app but has no tenant context. It will run without a tenant, causing data isolation failures.',
                    file:    $file,
                    fix:     'Add the TenantAwareJob middleware or implement a tenant property and initialize tenancy in the handle() method.'
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
