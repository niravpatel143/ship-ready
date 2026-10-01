<?php

namespace ShipReady\Checks\VersionSpecific;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'VER014',
    title: 'TrimStrings / ConvertEmptyStringsToNull no longer global in Laravel 11+',
    category: 'reliability',
    severity: 'low',
    minLaravel: '11.0'
)]
final class TrimStringsMiddlewareCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        // This check only matters if the app was upgraded from L9/10 and
        // is likely relying on automatic string trimming behaviour.
        // Check for migration files or code that might depend on this.
        $bootstrapApp = base_path('bootstrap/app.php');

        if (!file_exists($bootstrapApp)) {
            return;
        }

        $content = file_get_contents($bootstrapApp) ?: '';

        if (str_contains($content, 'TrimStrings') || str_contains($content, 'ConvertEmptyStrings')) {
            return;
        }

        // Check app/Http/Kernel.php — if it existed and had these, the app relied on them
        $kernelFile = app_path('Http/Kernel.php');

        if (!file_exists($kernelFile)) {
            return;
        }

        $kernelContent = file_get_contents($kernelFile) ?: '';

        if (!str_contains($kernelContent, 'TrimStrings') && !str_contains($kernelContent, 'ConvertEmptyStrings')) {
            return;
        }

        yield $this->finding(
            message: 'app/Http/Kernel.php registers TrimStrings/ConvertEmptyStringsToNull globally, but Laravel 11+ does not include these in the default middleware stack. Re-register them explicitly if your app depends on this behaviour.',
            file:    $bootstrapApp,
            fix:     'Add TrimStrings and ConvertEmptyStringsToNull to the global middleware stack in bootstrap/app.php via ->withMiddleware(function (Middleware $m) { $m->append(TrimStrings::class); }).'
        );
    }
}
