<?php

namespace ShipReady\Checks\Performance;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'PERF002',
    title: 'Routes not cached',
    category: 'performance',
    severity: 'medium',
    productionOnly: true
)]
final class RoutesNotCachedCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        if (!$context->env()->isRouteCached()) {
            yield $this->finding(
                message: 'Routes are not cached. Laravel re-registers all routes on every request.',
                fix:     'Run `php artisan route:cache` as part of your deployment process.'
            );
        }
    }
}
