<?php

namespace ShipReady\Checks\Octane;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'OCT004',
    title: 'Packages incompatible with Laravel Octane detected',
    category: 'octane',
    severity: 'high'
)]
final class IncompatibleOctanePackagesCheck extends AbstractCheck
{
    private const INCOMPATIBLE = [
        'barryvdh/laravel-debugbar'    => 'Debugbar stores data in static properties — leaks between requests under Octane. Use Telescope instead.',
        'beyondcode/laravel-query-detector' => 'laravel-query-detector uses static state incompatible with Octane worker recycling.',
        'spatie/laravel-ray'           => 'Ray may work but sends data via static handlers; test thoroughly before enabling in Octane.',
        'tymon/jwt-auth'               => 'tymon/jwt-auth caches the user in a static property — can serve the wrong user under Octane. Upgrade to a maintained fork or use Sanctum.',
    ];

    public function run(Context $context): iterable
    {
        $octaneConfig = config_path('octane.php');

        if (!file_exists($octaneConfig)) {
            return; // Octane not installed
        }

        $composerLock = base_path('composer.lock');

        if (!file_exists($composerLock)) {
            return;
        }

        $lock = json_decode(file_get_contents($composerLock) ?: '{}', true);
        $packages = array_column($lock['packages'] ?? [], 'name');

        foreach (self::INCOMPATIBLE as $package => $reason) {
            if (in_array($package, $packages, true)) {
                yield $this->finding(
                    message: "Package {$package} is installed and may be incompatible with Octane. {$reason}",
                    fix:     'Review Octane compatibility docs: https://laravel.com/docs/octane#dependency-injection-and-octane'
                );
            }
        }
    }
}
