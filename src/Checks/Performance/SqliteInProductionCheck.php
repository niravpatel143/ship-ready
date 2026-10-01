<?php

namespace ShipReady\Checks\Performance;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'PERF018',
    title: 'SQLite database driver in production',
    category: 'performance',
    severity: 'high',
    productionOnly: true
)]
final class SqliteInProductionCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $default = (string)$context->config()->get('database.default', 'mysql');
        $driver  = (string)$context->config()->get("database.connections.{$default}.driver", '');

        if ($driver === 'sqlite') {
            yield $this->finding(
                message: "The default database connection uses the SQLite driver. SQLite has no user-level access control, no network connectivity, and poor write concurrency — it is not suitable for production.",
                fix:     "Switch to MySQL or PostgreSQL. Update DB_CONNECTION, DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD in your .env."
            );
        }
    }
}
