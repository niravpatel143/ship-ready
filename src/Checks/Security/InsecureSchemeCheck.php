<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC013',
    title: 'APP_URL uses HTTP instead of HTTPS in production',
    category: 'security',
    severity: 'high',
    productionOnly: true
)]
final class InsecureSchemeCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $appUrl = $context->config()->get('app.url');

        if (empty($appUrl)) {
            yield $this->finding(
                message: 'APP_URL is not set.',
                fix:     'Set APP_URL to your full HTTPS URL in .env (e.g. APP_URL=https://yourdomain.com).'
            );

            return;
        }

        if (strncmp($appUrl, 'http://', 7) === 0) {
            yield $this->finding(
                message: "APP_URL is set to '{$appUrl}' which uses HTTP. All production traffic should use HTTPS.",
                fix:     'Change APP_URL to use https:// in your production .env file.'
            );
        }
    }
}
