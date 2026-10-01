<?php

namespace ShipReady\Checks\Reliability;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'REL015',
    title: 'No error monitoring service configured',
    category: 'reliability',
    severity: 'medium',
    productionOnly: true
)]
final class ErrorMonitoringCheck extends AbstractCheck
{
    private const MONITORING_PACKAGES = [
        'sentry/sentry-laravel',
        'spatie/laravel-flare',
        'bugsnag/bugsnag-laravel',
        'rollbar/rollbar-laravel',
        'honeybadger-io/honeybadger-laravel',
        'inspector-apm/inspector-laravel',
        'facade/flare-client-php',
    ];

    public function run(Context $context): iterable
    {
        $composerJson = base_path('composer.json');

        if (!file_exists($composerJson)) {
            return;
        }

        $composer = json_decode(file_get_contents($composerJson) ?: '{}', true);

        if (!is_array($composer)) {
            return;
        }

        $installed = array_merge(
            array_keys($composer['require'] ?? []),
            array_keys($composer['require-dev'] ?? [])
        );

        foreach (self::MONITORING_PACKAGES as $package) {
            if (in_array($package, $installed, true)) {
                return;
            }
        }

        yield $this->finding(
            message: 'No error monitoring service is installed. Production exceptions will be silently swallowed with no on-call alerting.',
            fix:     'Install an error monitoring service: Sentry (sentry/sentry-laravel), Flare (spatie/laravel-flare), or Bugsnag (bugsnag/bugsnag-laravel).'
        );
    }
}
