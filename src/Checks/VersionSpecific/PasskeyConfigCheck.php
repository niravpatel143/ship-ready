<?php

namespace ShipReady\Checks\VersionSpecific;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'VER006',
    title: 'Passkey authentication missing required configuration (Laravel 13+)',
    category: 'security',
    severity: 'medium',
    minLaravel: '13.0'
)]
final class PasskeyConfigCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        // Check if passkeys are being used (Laravel 13+ built-in passkey support)
        $passkeyConfig = config('fortify.features', []);

        if (!in_array('passkeys', $passkeyConfig, true)) {
            // Also check if a custom passkey package is installed
            if (!class_exists('\Laragear\WebAuthn\WebAuthnServiceProvider')
                && !class_exists('\Asbiin\LaravelWebauthn\LaravelWebauthnServiceProvider')) {
                return;
            }
        }

        $appUrl  = $context->config()->get('app.url');
        $appName = $context->config()->get('app.name');

        if (empty($appName) || $appName === 'Laravel') {
            yield $this->finding(
                message: 'APP_NAME is not customized. Passkey authentication uses APP_NAME as the Relying Party name shown to users.',
                fix:     'Set a descriptive APP_NAME in your .env file.'
            );
        }

        if (empty($appUrl) || strncmp($appUrl, 'http://', 7) === 0) {
            yield $this->finding(
                message: 'Passkeys require HTTPS. APP_URL is either missing or uses HTTP.',
                fix:     'Set APP_URL to your HTTPS URL. Passkeys (WebAuthn) only work over HTTPS.'
            );
        }
    }
}
