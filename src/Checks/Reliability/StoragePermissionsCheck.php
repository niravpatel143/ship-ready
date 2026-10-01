<?php

namespace ShipReady\Checks\Reliability;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'REL008',
    title: 'Storage directory not writable',
    category: 'reliability',
    severity: 'high'
)]
final class StoragePermissionsCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        if (!$context->env()->storageWritable()) {
            yield $this->finding(
                message: 'The storage/ directory is not writable by the web server. Logs, sessions, and file uploads will fail.',
                fix:     'Run `chmod -R 775 storage bootstrap/cache` and ensure the web server user owns these directories.'
            );
        }

        // Check bootstrap/cache is writable
        $bootstrapCache = base_path('bootstrap/cache');

        if (is_dir($bootstrapCache) && !is_writable($bootstrapCache)) {
            yield $this->finding(
                message: 'bootstrap/cache/ is not writable. Artisan cache commands will fail.',
                file:    $bootstrapCache,
                fix:     'Run `chmod -R 775 bootstrap/cache` and check ownership.'
            );
        }
    }
}
