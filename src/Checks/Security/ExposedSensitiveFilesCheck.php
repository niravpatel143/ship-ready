<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC017',
    title: 'Sensitive files publicly accessible',
    category: 'security',
    severity: 'critical'
)]
final class ExposedSensitiveFilesCheck extends AbstractCheck
{
    private const SENSITIVE_FILES = [
        '.env'            => '.env file — contains all secrets and credentials',
        '.git'            => '.git directory — exposes full source code history',
        'storage'         => 'storage/ directory — may contain logs, sessions, uploads',
        'composer.json'   => 'composer.json — reveals dependencies and versions',
        'composer.lock'   => 'composer.lock — reveals exact dependency versions (used for targeted attacks)',
        '.htaccess'       => '.htaccess — server configuration',
    ];

    public function run(Context $context): iterable
    {
        $publicPath = public_path();

        foreach (self::SENSITIVE_FILES as $file => $description) {
            $fullPath = $publicPath . DIRECTORY_SEPARATOR . $file;

            ob_start();
            $exists = @file_exists($fullPath) || @is_dir($fullPath) || @is_link($fullPath);
            ob_end_clean();

            if ($exists) {
                yield $this->finding(
                    message: "Sensitive path accessible from public/: {$file} — {$description}",
                    file:    $fullPath,
                    fix:     "Ensure {$file} is not inside the public/ directory and web server is not configured to serve it."
                );
            }
        }

        // Also check if .env exists at public root
        if (file_exists($publicPath . '/../.env')) {
            // Check if the web server's document root might accidentally serve the parent
            // This is a heuristic check
        }
    }
}
