<?php

namespace ShipReady\Checks\Infrastructure;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'INF002',
    title: '.env file copied into Docker image',
    category: 'infrastructure',
    severity: 'critical'
)]
final class DockerEnvFileCheck extends AbstractCheck
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

            // COPY .env or ADD .env
            if (preg_match('/^\s*(?:COPY|ADD)\s+\.env\b/m', $content)) {
                yield $this->finding(
                    message: "The Dockerfile copies .env into the image. Production secrets are baked into the image layer and will be exposed via docker history or a registry compromise.",
                    file:    $dockerfile,
                    fix:     'Remove the COPY .env line. Pass secrets at runtime via environment variables, Docker secrets, or a secrets manager (AWS SSM, Vault, Laravel Cloud secrets).'
                );
            }
        }

        // Also check .dockerignore
        $dockerignore = base_path('.dockerignore');

        if (file_exists($dockerignore)) {
            $content = file_get_contents($dockerignore) ?: '';

            if (!preg_match('/^\.env$/m', $content) && !preg_match('/^\.env\b/m', $content)) {
                yield $this->finding(
                    message: '.env is not listed in .dockerignore. If a COPY . . instruction is used, your .env file will be included in the image.',
                    file:    $dockerignore,
                    fix:     'Add .env to .dockerignore to prevent accidental inclusion of secrets in the image.'
                );
            }
        }
    }
}
