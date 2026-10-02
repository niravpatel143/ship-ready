<?php

namespace ShipReady\Checks\Infrastructure;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'INF001',
    title: 'Dockerfile runs application as root user',
    category: 'infrastructure',
    severity: 'high'
)]
final class DockerRootUserCheck extends AbstractCheck
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

            // Check if there is no USER instruction, or USER root
            if (preg_match('/^\s*USER\s+(?!root)(\S+)/m', $content)) {
                continue; // Has a non-root USER directive
            }

            yield $this->finding(
                message: 'The Dockerfile does not set a non-root USER. The container runs as root, which amplifies the impact of any container escape or RCE vulnerability.',
                file:    $dockerfile,
                fix:     "Add to your Dockerfile: RUN addgroup -S appgroup && adduser -S appuser -G appgroup\nUSER appuser"
            );
        }
    }
}
