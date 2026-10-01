<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC016',
    title: 'Debug tools exposed in production',
    category: 'security',
    severity: 'high',
    productionOnly: true
)]
final class ExposedDebugToolsCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $config = $context->config();

        // Check Telescope
        if (class_exists('\Laravel\Telescope\TelescopeServiceProvider')) {
            $telescopeEnabled = $config->get('telescope.enabled', true);

            if ($telescopeEnabled !== false) {
                yield $this->finding(
                    message: 'Laravel Telescope is enabled in production. It collects sensitive request/query/job data.',
                    fix:     'Set TELESCOPE_ENABLED=false in your production .env, or restrict access via Telescope::auth() gate.'
                );
            }
        }

        // Check Debugbar
        if (class_exists('\Barryvdh\Debugbar\ServiceProvider')) {
            $debugbarEnabled = $config->get('debugbar.enabled');

            if ($debugbarEnabled === null) {
                $debugbarEnabled = config('app.debug');
            }

            if ($debugbarEnabled) {
                yield $this->finding(
                    message: 'Laravel Debugbar is enabled in production. It exposes SQL queries, timing data, and request details.',
                    fix:     'Set DEBUGBAR_ENABLED=false in your production .env.'
                );
            }
        }

        // Check Ignition
        if (class_exists('\Spatie\LaravelIgnition\IgnitionServiceProvider')) {
            $ignitionEnabled = $config->get('ignition.self_diagnosis_on_failure', true);

            // In production, the real concern is the editor links / runnable solutions
            if ($config->get('app.debug') === true) {
                yield $this->finding(
                    message: 'Ignition is active with APP_DEBUG=true in production, exposing detailed error pages.',
                    fix:     'Set APP_DEBUG=false in production. Ignition only shows detailed errors when debug is enabled.'
                );
            }
        }
    }
}
