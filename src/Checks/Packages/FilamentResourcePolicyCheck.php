<?php

namespace ShipReady\Checks\Packages;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'FIL002',
    title: 'Filament resource without an authorization policy',
    category: 'packages',
    severity: 'high'
)]
final class FilamentResourcePolicyCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        if (!$this->isFilamentInstalled()) {
            return;
        }

        $analyzer = $context->code();

        if ($analyzer === null) {
            return;
        }

        $policyFiles = [];
        $policyDir   = app_path('Policies');

        if (is_dir($policyDir)) {
            foreach (glob($policyDir . '/*.php') ?: [] as $pf) {
                $policyFiles[] = basename($pf, '.php');
            }
        }

        foreach ($analyzer->phpFiles() as $file) {
            $content = file_get_contents($file) ?: '';

            if (!preg_match('/extends\s+Resource\b/', $content)) {
                continue;
            }

            // Extract the model name from the resource
            if (!preg_match('/protected\s+static\s+(?:string\s+)?\$model\s*=\s*(\w+)::class/', $content, $m)) {
                continue;
            }

            $modelName = $m[1];
            $policyName = $modelName . 'Policy';

            if (!in_array($policyName, $policyFiles, true)) {
                yield $this->finding(
                    message: "Filament resource for {$modelName} has no corresponding {$policyName}. All CRUD operations are authorized by Filament's default (allow-all) policy.",
                    file:    $file,
                    fix:     "Create app/Policies/{$policyName}.php and register it in AuthServiceProvider. Run: php artisan make:policy {$policyName} --model={$modelName}"
                );
            }
        }
    }

    private function isFilamentInstalled(): bool
    {
        $composerLock = base_path('composer.lock');

        if (!file_exists($composerLock)) {
            return false;
        }

        $lock     = json_decode(file_get_contents($composerLock) ?: '{}', true);
        $packages = array_column($lock['packages'] ?? [], 'name');

        return in_array('filament/filament', $packages, true);
    }
}
