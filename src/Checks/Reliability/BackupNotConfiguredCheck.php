<?php

namespace ShipReady\Checks\Reliability;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'REL017',
    title: 'No database backup solution configured',
    category: 'reliability',
    severity: 'medium',
    productionOnly: true
)]
final class BackupNotConfiguredCheck extends AbstractCheck
{
    private const BACKUP_PACKAGES = [
        'spatie/laravel-backup',
        'backup-manager/laravel',
    ];

    public function run(Context $context): iterable
    {
        if (file_exists(config_path('backup.php'))) {
            return;
        }

        $composerJson = base_path('composer.json');

        if (!file_exists($composerJson)) {
            return;
        }

        $composer = json_decode(file_get_contents($composerJson) ?: '{}', true);

        if (!is_array($composer)) {
            return;
        }

        $packages = array_keys($composer['require'] ?? []);

        foreach (self::BACKUP_PACKAGES as $pkg) {
            if (in_array($pkg, $packages, true)) {
                return;
            }
        }

        yield $this->finding(
            message: 'No database backup solution is configured. A server failure or accidental deletion without backups means permanent data loss.',
            fix:     'Install spatie/laravel-backup and schedule php artisan backup:run in your crontab.'
        );
    }
}
