<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC011',
    title: 'Routes excluded from CSRF protection',
    category: 'security',
    severity: 'medium'
)]
final class CsrfExclusionCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        // Check VerifyCsrfToken middleware for exclusions
        $middlewareFile = app_path('Http/Middleware/VerifyCsrfToken.php');

        if (!file_exists($middlewareFile)) {
            // Laravel 11+ uses bootstrap/app.php
            $bootstrapFile = base_path('bootstrap/app.php');

            if (!file_exists($bootstrapFile)) {
                return;
            }

            $contents = file_get_contents($bootstrapFile);

            if ($contents === false) {
                return;
            }

            // Look for csrfExclude or similar patterns
            if (preg_match('/csrfExclude|VerifyCsrfToken.*except/', $contents)) {
                yield $this->finding(
                    message: 'Some routes appear to be excluded from CSRF protection in bootstrap/app.php.',
                    file:    $bootstrapFile,
                    fix:     'Review CSRF exclusions and ensure they are intentional and limited to API routes using token-based auth.'
                );
            }

            return;
        }

        $contents = file_get_contents($middlewareFile);

        if ($contents === false) {
            return;
        }

        // Look for $except array with entries
        if (preg_match('/protected\s+\$except\s*=\s*\[([^\]]*)\]/s', $contents, $matches)) {
            $exceptContent = trim($matches[1]);

            if (!empty($exceptContent) && $exceptContent !== '') {
                // Count non-comment, non-empty lines
                $lines = array_filter(
                    explode("\n", $exceptContent),
                    fn($l) => trim($l) !== '' && strncmp(trim($l), '//', 2) !== 0
                );

                if (!empty($lines)) {
                    yield $this->finding(
                        message: count($lines) . ' route(s) are excluded from CSRF protection in VerifyCsrfToken.',
                        file:    $middlewareFile,
                        fix:     'Ensure CSRF exclusions are only for API routes protected by token-based auth (e.g., Sanctum, Passport).'
                    );
                }
            }
        }
    }
}
