<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC014',
    title: 'Missing HTTP security headers middleware',
    category: 'security',
    severity: 'medium',
    productionOnly: true
)]
final class MissingSecurityHeadersCheck extends AbstractCheck
{
    private const SECURITY_HEADERS = [
        'Strict-Transport-Security' => 'HSTS',
        'X-Content-Type-Options'    => 'X-Content-Type-Options',
        'X-Frame-Options'           => 'X-Frame-Options',
        'X-XSS-Protection'          => 'X-XSS-Protection',
    ];

    public function run(Context $context): iterable
    {
        // Scan middleware files for security header setting
        $middlewarePaths = [
            app_path('Http/Middleware'),
        ];

        $foundHeaders = [];

        foreach ($middlewarePaths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $files = glob($path . '/*.php');

            foreach ($files as $file) {
                $contents = @file_get_contents($file);

                if ($contents === false) {
                    continue;
                }

                foreach (self::SECURITY_HEADERS as $header => $label) {
                    if (str_contains($contents, $header)) {
                        $foundHeaders[$header] = true;
                    }
                }
            }
        }

        // Also check bootstrap/app.php for middleware pipelines
        $bootstrapFile = base_path('bootstrap/app.php');

        if (file_exists($bootstrapFile)) {
            $contents = file_get_contents($bootstrapFile);

            foreach (self::SECURITY_HEADERS as $header => $label) {
                if (str_contains($contents, $header)) {
                    $foundHeaders[$header] = true;
                }
            }
        }

        foreach (self::SECURITY_HEADERS as $header => $label) {
            if (!isset($foundHeaders[$header])) {
                yield $this->finding(
                    message: "Security header '{$header}' does not appear to be set in any global middleware.",
                    fix:     "Add a middleware that sets the {$header} header on all responses, or use a package like spatie/laravel-csp."
                );
            }
        }
    }
}
