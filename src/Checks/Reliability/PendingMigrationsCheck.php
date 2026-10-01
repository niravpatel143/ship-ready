<?php

namespace ShipReady\Checks\Reliability;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'REL001',
    title: 'Pending database migrations',
    category: 'reliability',
    severity: 'high'
)]
final class PendingMigrationsCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        if ($context->env()->hasPendingMigrations()) {
            yield $this->finding(
                message: 'There are pending database migrations that have not been run.',
                fix:     'Run `php artisan migrate` before deploying to production.'
            );
        }
    }
}
