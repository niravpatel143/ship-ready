<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC001',
    title: 'Debug mode enabled in production',
    category: 'security',
    severity: 'critical',
    docs: '',
    productionOnly: true
)]
final class DebugModeCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        if ($context->config()->get('app.debug') === true) {
            yield $this->finding(
                message: 'APP_DEBUG is enabled in production. This exposes stack traces, environment variables, and internal application details to users.',
                fix: 'Set APP_DEBUG=false in your production .env file.'
            );
        }
    }
}
