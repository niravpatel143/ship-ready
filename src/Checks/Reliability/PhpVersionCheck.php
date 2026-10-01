<?php

namespace ShipReady\Checks\Reliability;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'REL014',
    title: 'PHP version is below recommended minimum',
    category: 'reliability',
    severity: 'high'
)]
final class PhpVersionCheck extends AbstractCheck
{
    // PHP versions that are end-of-life
    private const EOL_VERSIONS = [
        '8.0', '8.1', '8.2',
        '7.4', '7.3', '7.2', '7.1', '7.0',
    ];

    // Recommended minimum
    private const RECOMMENDED_MIN = '8.2';

    public function run(Context $context): iterable
    {
        $phpVersion = $context->env()->phpVersion();
        $majorMinor = implode('.', array_slice(explode('.', $phpVersion), 0, 2));

        if (in_array($majorMinor, self::EOL_VERSIONS, true)) {
            yield $this->finding(
                message: "PHP {$phpVersion} is end-of-life and no longer receives security updates.",
                fix:     'Upgrade to PHP ' . self::RECOMMENDED_MIN . ' or newer. See https://www.php.net/supported-versions.php'
            );

            return;
        }

        if (version_compare($phpVersion, self::RECOMMENDED_MIN, '<')) {
            yield $this->finding(
                message: "PHP {$phpVersion} is below the recommended minimum version " . self::RECOMMENDED_MIN . ".",
                fix:     'Upgrade to PHP ' . self::RECOMMENDED_MIN . ' or newer for security patches and performance improvements.'
            );
        }
    }
}
