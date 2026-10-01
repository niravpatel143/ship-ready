<?php

namespace ShipReady\Checks\Performance;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'PERF001',
    title: 'Configuration not cached',
    category: 'performance',
    severity: 'medium',
    productionOnly: true
)]
final class ConfigNotCachedCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        if (!$context->env()->isConfigCached()) {
            yield $this->finding(
                message: 'Configuration files are not cached. Laravel reads and merges all config files on each request.',
                fix:     'Run `php artisan config:cache` as part of your deployment process.'
            );
        }
    }
}
