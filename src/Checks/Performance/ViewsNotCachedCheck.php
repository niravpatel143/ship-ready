<?php

namespace ShipReady\Checks\Performance;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'PERF003',
    title: 'Views not pre-compiled',
    category: 'performance',
    severity: 'low',
    productionOnly: true
)]
final class ViewsNotCachedCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        if (!$context->env()->isViewsCached()) {
            yield $this->finding(
                message: 'Blade views are not pre-compiled. First-request compilation adds latency.',
                fix:     'Run `php artisan view:cache` as part of your deployment process.'
            );
        }
    }
}
