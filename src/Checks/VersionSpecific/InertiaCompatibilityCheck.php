<?php

namespace ShipReady\Checks\VersionSpecific;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'VER015',
    title: 'Inertia.js v1 may be incompatible with Laravel 12+',
    category: 'reliability',
    severity: 'medium',
    minLaravel: '12.0'
)]
final class InertiaCompatibilityCheck extends AbstractCheck
{
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

        $require = array_merge($composer['require'] ?? [], $composer['require-dev'] ?? []);

        if (!isset($require['inertiajs/inertia-laravel'])) {
            return;
        }

        $constraint = $require['inertiajs/inertia-laravel'];

        // v0.x and ^1.x constraints are old; v2.x is compatible with Laravel 12+
        if (preg_match('/^\^?~?0\./', $constraint) || preg_match('/^\^?~?1\./', $constraint)) {
            yield $this->finding(
                message: "Inertia.js adapter constraint '{$constraint}' targets an older version. Laravel 12+ works best with inertiajs/inertia-laravel v2.x.",
                fix:     'Upgrade the Inertia adapter: composer require inertiajs/inertia-laravel:^2.0 — then review the v1→v2 migration notes at inertiajs.com/releases'
            );
        }
    }
}
