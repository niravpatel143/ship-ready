<?php

namespace ShipReady\Checks\Tenancy;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'TEN001',
    title: 'Eloquent model in multi-tenant app missing tenant scope',
    category: 'tenancy',
    severity: 'critical'
)]
final class ModelTenantScopeCheck extends AbstractCheck
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
            if (!str_contains($file, '/Models/')) {
                continue;
            }

            $content = file_get_contents($file) ?: '';

            if (!preg_match('/extends\s+Model\b/', $content)) {
                continue;
            }

            // Skip pivot models, tenant model itself, central domain models
            if (preg_match('/extends\s+(?:Pivot|MorphPivot|Authenticatable)\b/', $content)) {
                continue;
            }

            if (preg_match('/class\s+Tenant\b/', $content)) {
                continue;
            }

            // Check for BelongsToTenant, HasTenancy, tenant_id scope, or global scope
            $hasTenantScope = preg_match('/BelongsToTenant|HasTenancy|tenant_id|InitializesLaravelMultitenancy/', $content)
                || preg_match('/addGlobalScope|GlobalScope/', $content)
                || preg_match('/ScopedByTenant|TenantScope/', $content);

            if (!$hasTenantScope) {
                yield $this->finding(
                    message: basename($file, '.php') . ' model has no tenant scope. In a multi-tenant app this allows cross-tenant data access.',
                    file:    $file,
                    fix:     'Add the BelongsToTenant trait (stancl/tenancy) or a global scope that filters by the current tenant ID.'
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
