<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC002',
    title: 'Application key missing or insecure',
    category: 'security',
    severity: 'critical'
)]
final class AppKeyCheck extends AbstractCheck
{
    private const EXAMPLE_KEYS = [
        'base64:SomeRandomStringHere==',
        'SomeRandomString',
    ];

    public function run(Context $context): iterable
    {
        $key = $context->config()->get('app.key');

        if (empty($key)) {
            yield $this->finding(
                message: 'Application key (APP_KEY) is not set. All encrypted data is at risk.',
                fix: 'Run `php artisan key:generate` to generate a secure application key.'
            );

            return;
        }

        // Remove base64: prefix for length check
        $rawKey = $key;

        if (strncmp($key, 'base64:', 7) === 0) {
            $rawKey = base64_decode(substr($key, 7));
        }

        if (strlen($rawKey) < 32) {
            yield $this->finding(
                message: 'Application key is too short (less than 32 bytes). Encryption may be weak.',
                fix: 'Run `php artisan key:generate` to replace with a proper 32-byte key.'
            );
        }

        if (in_array($key, self::EXAMPLE_KEYS, true)) {
            yield $this->finding(
                message: 'Application key appears to be an example or placeholder value.',
                fix: 'Run `php artisan key:generate` to generate a secure key.'
            );
        }
    }
}
