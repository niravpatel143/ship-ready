<?php

namespace ShipReady\Checks\Performance;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'PERF006',
    title: 'Slow cache driver in production',
    category: 'performance',
    severity: 'medium',
    productionOnly: true
)]
final class SlowCacheDriverCheck extends AbstractCheck
{
    private const SLOW_DRIVERS = ['file', 'array', 'null'];

    public function run(Context $context): iterable
    {
        $driver = $context->config()->get('cache.default');

        if (in_array($driver, self::SLOW_DRIVERS, true)) {
            yield $this->finding(
                message: "Cache driver is set to '{$driver}' which is not suitable for production.",
                fix:     "Switch to a high-performance driver like Redis or Memcached. Set CACHE_DRIVER=redis in your production .env."
            );
        }

        // Also check session driver
        $sessionDriver = $context->config()->get('session.driver');

        if (in_array($sessionDriver, ['file', 'cookie'], true)) {
            yield $this->finding(
                message: "Session driver is '{$sessionDriver}'. File sessions don't scale across multiple servers and are slow under load.",
                fix:     'Use SESSION_DRIVER=redis or SESSION_DRIVER=database for scalable session storage.'
            );
        }
    }
}
