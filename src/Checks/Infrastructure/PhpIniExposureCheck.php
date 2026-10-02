<?php

namespace ShipReady\Checks\Infrastructure;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'INF004',
    title: 'expose_php = On in php.ini — PHP version fingerprinting enabled',
    category: 'infrastructure',
    severity: 'low'
)]
final class PhpIniExposureCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $phpIniFiles = [
            base_path('docker/php.ini'),
            base_path('.docker/php.ini'),
            base_path('php.ini'),
            '/etc/php/8.3/fpm/php.ini',
            '/etc/php/8.2/fpm/php.ini',
            '/usr/local/etc/php/php.ini',
        ];

        foreach ($phpIniFiles as $iniFile) {
            if (!file_exists($iniFile)) {
                continue;
            }

            $content = file_get_contents($iniFile) ?: '';

            // Check expose_php = On (not Off or 0)
            if (preg_match('/^\s*expose_php\s*=\s*(On|1|yes)/mi', $content)) {
                yield $this->finding(
                    message: "expose_php = On in {$iniFile}. PHP version is advertised in X-Powered-By headers, helping attackers target known vulnerabilities for your PHP version.",
                    file:    $iniFile,
                    fix:     'Set expose_php = Off in php.ini to remove the X-Powered-By: PHP/x.x.x header.'
                );
            }
        }

        // Also check .user.ini in public directory
        $userIni = public_path('.user.ini');

        if (file_exists($userIni)) {
            $content = file_get_contents($userIni) ?: '';

            if (preg_match('/^\s*expose_php\s*=\s*(On|1|yes)/mi', $content)) {
                yield $this->finding(
                    message: 'expose_php = On in public/.user.ini.',
                    file:    $userIni,
                    fix:     'Set expose_php = Off in public/.user.ini.'
                );
            }
        }
    }
}
