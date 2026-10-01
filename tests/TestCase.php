<?php

namespace ShipReady\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use ShipReady\ShipReadyServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ShipReadyServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
        $app['config']->set('app.env', 'testing');
        $app['config']->set('app.debug', false);
        $app['config']->set('ship-ready.baseline', storage_path('testing-baseline.json'));
    }

    protected function getPackageAliases($app): array
    {
        return [];
    }
}
