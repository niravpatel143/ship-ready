<?php

namespace ShipReady\Checks\VersionSpecific;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'VER013',
    title: 'Fortify installed without two-factor authentication enabled',
    category: 'security',
    severity: 'medium'
)]
final class FortifyTwoFactorCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        // Only relevant if Fortify is installed
        if (!class_exists(\Laravel\Fortify\FortifyServiceProvider::class)) {
            return;
        }

        $features = config('fortify.features', []);

        if (!in_array('two-factor-authentication', $features, true)
            && !in_array(\Laravel\Fortify\Features::twoFactorAuthentication(), $features, true)) {
            yield $this->finding(
                message: 'Laravel Fortify is installed but two-factor authentication is not enabled in config/fortify.php. Users have no MFA option.',
                fix:     "Add Features::twoFactorAuthentication(['confirm' => true]) to the features array in config/fortify.php."
            );
        }
    }
}
