<?php

namespace ShipReady\Checks\Queue;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'QUE005',
    title: 'Laravel Horizon installed but not configured for production',
    category: 'queue',
    severity: 'medium'
)]
final class HorizonConfigCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $composerLock = base_path('composer.lock');

        if (!file_exists($composerLock)) {
            return;
        }

        $lock     = json_decode(file_get_contents($composerLock) ?: '{}', true);
        $packages = array_column($lock['packages'] ?? [], 'name');

        if (!in_array('laravel/horizon', $packages, true)) {
            return;
        }

        $horizonConfig = config_path('horizon.php');

        if (!file_exists($horizonConfig)) {
            yield $this->finding(
                message: 'Laravel Horizon is installed but config/horizon.php does not exist.',
                fix:     'Run: php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider"'
            );

            return;
        }

        $config  = @include $horizonConfig;
        $envs    = is_array($config) ? ($config['environments'] ?? []) : [];
        $prodEnv = $envs['production'] ?? null;

        if ($prodEnv === null) {
            yield $this->finding(
                message: "Horizon config/horizon.php has no 'production' environment block. Horizon will use default worker counts that may be insufficient.",
                file:    $horizonConfig,
                fix:     "Add a 'production' key under 'environments' with appropriate supervisor minProcesses and maxProcesses values."
            );
        }
    }
}
