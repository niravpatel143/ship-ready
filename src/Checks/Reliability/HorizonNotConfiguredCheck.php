<?php

namespace ShipReady\Checks\Reliability;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'REL016',
    title: 'Redis queue used without Laravel Horizon',
    category: 'reliability',
    severity: 'low'
)]
final class HorizonNotConfiguredCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $queueDriver = (string)$context->config()->get('queue.default', 'sync');

        if ($queueDriver !== 'redis') {
            return;
        }

        if (class_exists(\Laravel\Horizon\HorizonServiceProvider::class)) {
            return;
        }

        $composerJson = base_path('composer.json');

        if (file_exists($composerJson)) {
            $composer = json_decode(file_get_contents($composerJson) ?: '{}', true);
            $packages = array_keys($composer['require'] ?? []);

            if (in_array('laravel/horizon', $packages, true)) {
                return;
            }
        }

        yield $this->finding(
            message: 'Redis queue driver is configured but Laravel Horizon is not installed. Queue monitoring, worker supervision, and retry management are unavailable.',
            fix:     'Install Laravel Horizon: composer require laravel/horizon && php artisan horizon:install'
        );
    }
}
