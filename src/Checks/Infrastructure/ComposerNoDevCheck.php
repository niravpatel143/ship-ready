<?php

namespace ShipReady\Checks\Infrastructure;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'INF003',
    title: 'Dockerfile runs composer install without --no-dev',
    category: 'infrastructure',
    severity: 'medium'
)]
final class ComposerNoDevCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $dockerfiles = [
            base_path('Dockerfile'),
            base_path('docker/Dockerfile'),
            base_path('.docker/Dockerfile'),
        ];

        foreach ($dockerfiles as $dockerfile) {
            if (!file_exists($dockerfile)) {
                continue;
            }

            $content = file_get_contents($dockerfile) ?: '';

            // Find composer install without --no-dev
            if (
                preg_match('/composer\s+install(?!\s[^\n]*--no-dev)/m', $content) &&
                !preg_match('/composer\s+install\s[^\n]*--no-dev/m', $content)
            ) {
                yield $this->finding(
                    message: 'Dockerfile runs composer install without --no-dev. Development dependencies (debugbar, pest, phpunit) are included in the production image, increasing attack surface and image size.',
                    file:    $dockerfile,
                    fix:     'Change to: RUN composer install --no-dev --optimize-autoloader --no-interaction'
                );
            }
        }
    }
}
