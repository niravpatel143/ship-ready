<?php

namespace ShipReady\Checks\Reliability;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'REL007',
    title: 'Public storage symlink missing',
    category: 'reliability',
    severity: 'medium'
)]
final class StorageLinkCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        if (!$context->env()->hasStorageLink()) {
            yield $this->finding(
                message: 'The public/storage symlink is missing. Publicly stored files will return 404.',
                fix:     'Run `php artisan storage:link` after deployment.'
            );
        }
    }
}
