<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC023',
    title: 'Sanctum token expiration not configured',
    category: 'security',
    severity: 'medium'
)]
final class SanctumTokenExpiryCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        // Only run if Sanctum is installed
        if (!class_exists('\Laravel\Sanctum\SanctumServiceProvider')) {
            return;
        }

        $expiration = $context->config()->get('sanctum.expiration');

        if ($expiration === null) {
            yield $this->finding(
                message: 'Sanctum token expiration (sanctum.expiration) is null. Tokens never expire, increasing the window for compromised token abuse.',
                fix:     'Set sanctum.expiration to a reasonable value (e.g., 1440 minutes = 24 hours) in config/sanctum.php or via SANCTUM_EXPIRATION env var.'
            );
        }
    }
}
