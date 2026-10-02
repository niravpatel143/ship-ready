<?php

namespace ShipReady\Checks\Tenancy;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'TEN004',
    title: 'withoutGlobalScopes() bypasses tenant isolation',
    category: 'tenancy',
    severity: 'critical'
)]
final class GlobalScopeBypassCheck extends AbstractCheck
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
            if (str_contains($file, '/tests/') || str_contains($file, '/database/seeders/')) {
                continue;
            }

            $content = file_get_contents($file) ?: '';

            if (preg_match('/->withoutGlobalScopes?\(\)/', $content)) {
                yield $this->finding(
                    message: 'withoutGlobalScopes() is called outside of tests/seeders. In a multi-tenant app this removes the tenant scope and may expose all tenants\' data.',
                    file:    $file,
                    fix:     'If cross-tenant access is intentional, use explicit tenant filtering: Model::query()->where(\'tenant_id\', ...). Never expose withoutGlobalScopes() to user-controlled code paths.'
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
