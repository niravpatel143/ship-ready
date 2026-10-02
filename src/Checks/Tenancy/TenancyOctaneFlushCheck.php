<?php

namespace ShipReady\Checks\Tenancy;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'TEN006',
    title: 'Tenancy + Octane: tenant state not flushed between requests',
    category: 'tenancy',
    severity: 'high'
)]
final class TenancyOctaneFlushCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        if (!$this->isTenancyInstalled()) {
            return;
        }

        $octaneConfig = config_path('octane.php');

        if (!file_exists($octaneConfig)) {
            return; // Not using Octane
        }

        $octaneContent = file_get_contents($octaneConfig) ?: '';

        // Check if tenancy flushing is configured for Octane
        if (
            !str_contains($octaneContent, 'tenancy') &&
            !str_contains($octaneContent, 'Tenancy') &&
            !str_contains($octaneContent, 'RevertToCentralContext')
        ) {
            // Also check event listeners for Octane+Tenancy flush
            $appServiceProvider = app_path('Providers/AppServiceProvider.php');

            if (
                file_exists($appServiceProvider) &&
                str_contains(file_get_contents($appServiceProvider) ?: '', 'RevertToCentralContext')
            ) {
                return;
            }

            yield $this->finding(
                message: 'Both Tenancy and Octane are installed, but no tenant flush is configured for Octane. Tenant context from one request will bleed into the next worker cycle.',
                fix:     'In config/octane.php listeners, add RequestReceived::class => [RevertToCentralContext::class]. See: https://tenancyforlaravel.com/docs/v3/octane'
            );
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
