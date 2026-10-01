<?php

namespace ShipReady\Checks\Performance;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'PERF005',
    title: 'OPcache is disabled',
    category: 'performance',
    severity: 'medium',
    productionOnly: true
)]
final class OpcacheDisabledCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        if (!$context->env()->isOpcacheEnabled()) {
            yield $this->finding(
                message: 'PHP OPcache is not enabled. OPcache caches precompiled PHP bytecode and dramatically improves performance.',
                fix:     'Enable OPcache in your php.ini: opcache.enable=1, opcache.memory_consumption=256, opcache.max_accelerated_files=20000.'
            );
        }
    }
}
