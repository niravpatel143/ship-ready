<?php

namespace ShipReady;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use ShipReady\Commands\BaselineCommand;
use ShipReady\Commands\DriftCommand;
use ShipReady\Commands\ExplainCommand;
use ShipReady\Commands\InstallCommand;
use ShipReady\Commands\ListChecksCommand;
use ShipReady\Commands\MakeCheckCommand;
use ShipReady\Commands\McpCommand;
use ShipReady\Commands\NextCommand;
use ShipReady\Commands\ProbeCommand;
use ShipReady\Commands\RoutesCommand;
use ShipReady\Commands\ShipCheckCommand;

class ShipReadyServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('ship-ready')
            ->hasConfigFile()
            ->hasCommands([
                ShipCheckCommand::class,
                BaselineCommand::class,
                ExplainCommand::class,
                ListChecksCommand::class,
                MakeCheckCommand::class,
                ProbeCommand::class,
                RoutesCommand::class,
                DriftCommand::class,
                InstallCommand::class,
                McpCommand::class,
                NextCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(CheckRegistry::class, function ($app) {
            $registry = new CheckRegistry($app);

            $checks = config('ship-ready.checks') ?? BuiltInChecks::all();

            $registry->registerMany($checks);

            // Auto-discover checks in app/ShipReady/
            $this->discoverAppChecks($registry);

            return $registry;
        });
    }

    private function discoverAppChecks(CheckRegistry $registry): void
    {
        $appChecksPath = app_path('ShipReady');

        if (!is_dir($appChecksPath)) {
            return;
        }

        $files = glob($appChecksPath . '/*.php');

        if (empty($files)) {
            return;
        }

        foreach ($files as $file) {
            $className = 'App\\ShipReady\\' . basename($file, '.php');

            if (class_exists($className)) {
                try {
                    $registry->register($className);
                } catch (\Throwable $e) {
                    // Skip invalid classes
                }
            }
        }
    }
}
