<?php

namespace ShipReady\Checks\VersionSpecific;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'VER016',
    title: 'Carbon immutable dates not configured (Laravel 11+)',
    category: 'reliability',
    severity: 'low',
    minLaravel: '11.0'
)]
final class CarbonImmutableCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        // Laravel 11+ ships Carbon 3 and recommends using immutable dates
        // to prevent accidental mutation bugs (e.g. $user->created_at->addDays(7)
        // modifying the underlying date on the model).

        $serviceProviderFile = app_path('Providers/AppServiceProvider.php');

        if (!file_exists($serviceProviderFile)) {
            return;
        }

        $content = file_get_contents($serviceProviderFile) ?: '';

        if (str_contains($content, 'useImmutableDates') || str_contains($content, 'CarbonImmutable')) {
            return;
        }

        // Also check bootstrap/app.php
        $bootstrapApp = base_path('bootstrap/app.php');

        if (file_exists($bootstrapApp)) {
            $bootstrap = file_get_contents($bootstrapApp) ?: '';

            if (str_contains($bootstrap, 'useImmutableDates') || str_contains($bootstrap, 'CarbonImmutable')) {
                return;
            }
        }

        yield $this->finding(
            message: 'Carbon immutable dates are not enabled. Mutable Carbon instances can be accidentally modified when passed between methods, causing subtle date-mutation bugs.',
            fix:     "Call Date::use(CarbonImmutable::class) in AppServiceProvider::boot() to make all model date casts return immutable Carbon instances."
        );
    }
}
