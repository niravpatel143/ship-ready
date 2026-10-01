<?php

namespace ShipReady\Checks\Performance;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'PERF015',
    title: 'File or cookie session driver in production',
    category: 'performance',
    severity: 'medium',
    productionOnly: true
)]
final class FileSessionDriverCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $driver = (string)$context->config()->get('session.driver', 'file');

        if ($driver === 'file') {
            yield $this->finding(
                message: "Session driver is 'file'. File sessions do not scale across multiple server instances and degrade under high session volumes.",
                fix:     "Use 'redis', 'memcached', or 'database' for the session driver in production. Set SESSION_DRIVER=redis in your .env."
            );
        } elseif ($driver === 'cookie') {
            yield $this->finding(
                message: "Session driver is 'cookie'. Cookie sessions expose session data client-side and cannot be invalidated server-side.",
                fix:     "Use 'redis', 'memcached', or 'database' for the session driver in production. Set SESSION_DRIVER=redis in your .env."
            );
        }
    }
}
