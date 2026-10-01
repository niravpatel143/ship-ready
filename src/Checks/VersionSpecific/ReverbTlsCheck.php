<?php

namespace ShipReady\Checks\VersionSpecific;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'VER004',
    title: 'Laravel Reverb not configured with TLS in production',
    category: 'security',
    severity: 'high',
    minLaravel: '11.0',
    productionOnly: true
)]
final class ReverbTlsCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        // Check if Reverb is installed
        if (!class_exists('\Laravel\Reverb\ReverbServiceProvider')) {
            return;
        }

        $reverbConfig = config('reverb', null);

        if ($reverbConfig === null) {
            return;
        }

        $scheme = $reverbConfig['servers']['reverb']['scheme'] ?? 'http';

        if ($scheme !== 'https') {
            yield $this->finding(
                message: 'Laravel Reverb is configured to use HTTP (scheme: http) in production. WebSocket traffic will be unencrypted.',
                fix:     "Set REVERB_SCHEME=https and configure SSL certificates for Reverb. See: https://reverb.laravel.com/docs/reverb/production"
            );
        }

        $port = $reverbConfig['servers']['reverb']['port'] ?? 8080;

        if ((int)$port === 8080 && $scheme === 'https') {
            yield $this->finding(
                message: "Reverb is using port 8080 with HTTPS. The standard WSS port is 443.",
                fix:     'Set REVERB_PORT=443 for standard WSS/HTTPS or configure your reverse proxy appropriately.'
            );
        }
    }
}
