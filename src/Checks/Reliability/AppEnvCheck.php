<?php

namespace ShipReady\Checks\Reliability;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'REL006',
    title: 'APP_ENV is not set to production',
    category: 'reliability',
    severity: 'medium',
    productionOnly: true
)]
final class AppEnvCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $appEnv = $context->config()->get('app.env');

        if ($appEnv !== 'production') {
            yield $this->finding(
                message: "APP_ENV is set to '{$appEnv}' instead of 'production'. Some Laravel optimizations and behaviors only activate in production.",
                fix:     'Set APP_ENV=production in your production .env file.'
            );
        }
    }
}
