<?php

namespace ShipReady\Checks\Performance;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'PERF004',
    title: 'Composer autoloader not optimized',
    category: 'performance',
    severity: 'medium',
    productionOnly: true
)]
final class AutoloaderNotOptimizedCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        if (!$context->env()->isAutoloaderOptimized()) {
            yield $this->finding(
                message: 'Composer autoloader is not optimized. Class resolution is slower without a classmap.',
                fix:     'Run `composer install --optimize-autoloader --no-dev` or `composer dump-autoload --optimize` during deployment.'
            );
        }
    }
}
