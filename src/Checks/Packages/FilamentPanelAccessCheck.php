<?php

namespace ShipReady\Checks\Packages;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'FIL001',
    title: 'Filament panel missing canAccessPanel() authorization',
    category: 'packages',
    severity: 'critical'
)]
final class FilamentPanelAccessCheck extends AbstractCheck
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

        foreach ($analyzer->phpFiles() as $file) {
            $content = file_get_contents($file) ?: '';

            if (!preg_match('/implements\s+HasAvatar|implements\s+FilamentUser|use\s+HasFilamentUser/', $content)) {
                continue;
            }

            if (!preg_match('/canAccessPanel\s*\(/', $content)) {
                yield $this->finding(
                    message: 'A Filament user model does not implement canAccessPanel(). Without this, any authenticated user can access the admin panel.',
                    file:    $file,
                    fix:     'Implement canAccessPanel(Panel $panel): bool on your User model: return $this->hasRole(\'admin\');'
                );
            }
        }

        // Also check panel providers
        foreach ($analyzer->phpFiles() as $file) {
            $content = file_get_contents($file) ?: '';

            if (!preg_match('/extends\s+PanelProvider/', $content)) {
                continue;
            }

            if (!preg_match('/->authMiddleware\(|->auth\(|->login\(/', $content)) {
                yield $this->finding(
                    message: 'Filament PanelProvider has no authentication middleware configured. The panel may be publicly accessible.',
                    file:    $file,
                    fix:     'Call ->authMiddleware([Authenticate::class]) in your PanelProvider::panel() method.'
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
