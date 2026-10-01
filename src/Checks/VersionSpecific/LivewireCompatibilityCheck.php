<?php

namespace ShipReady\Checks\VersionSpecific;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'VER010',
    title: 'Livewire v2 incompatible with Laravel 11+',
    category: 'reliability',
    severity: 'high',
    minLaravel: '11.0'
)]
final class LivewireCompatibilityCheck extends AbstractCheck
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

        if (!isset($require['livewire/livewire'])) {
            return;
        }

        $constraint = $require['livewire/livewire'];

        // Livewire v2 constraints: ^2.x, ~2.x, 2.x.x
        if (preg_match('/^\^?~?2\./', $constraint)) {
            yield $this->finding(
                message: "Livewire constraint '{$constraint}' targets v2, which is incompatible with Laravel 11+. Livewire v3 is required.",
                fix:     'Upgrade Livewire: composer require livewire/livewire:^3.0 — then follow the v2→v3 migration guide at livewire.laravel.com/docs/upgrading'
            );
        }
    }
}
