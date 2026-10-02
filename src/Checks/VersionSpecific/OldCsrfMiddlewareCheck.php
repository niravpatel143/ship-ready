<?php

namespace ShipReady\Checks\VersionSpecific;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'VER001',
    title: 'Leftover VerifyCsrfToken middleware class (deprecated since Laravel 11)',
    category: 'security',
    severity: 'medium',
    minLaravel: '11.0'
)]
final class OldCsrfMiddlewareCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $middlewareFile = app_path('Http/Middleware/VerifyCsrfToken.php');

        if (!file_exists($middlewareFile)) {
            return;
        }

        $content = file_get_contents($middlewareFile) ?: '';

        // Only flag if the file has actual exclusions — a completely empty $except is harmless
        $hasExclusions = preg_match('/\$except\s*=\s*\[[^\]]+\]/', $content);

        if ($hasExclusions) {
            yield $this->finding(
                message: 'app/Http/Middleware/VerifyCsrfToken.php has CSRF exclusions that are no longer read in Laravel 11+. Routes in $except are NOT actually excluded.',
                file:    $middlewareFile,
                fix:     'Move CSRF exclusions to bootstrap/app.php: ->withMiddleware(fn(Middleware $m) => $m->validateCsrfTokens(except: [\'/route\']))'
            );
        } else {
            yield $this->finding(
                message: 'app/Http/Middleware/VerifyCsrfToken.php is a leftover from Laravel 10 or earlier. Laravel 11+ does not load it.',
                file:    $middlewareFile,
                fix:     'Safe to delete. Configure CSRF exclusions in bootstrap/app.php if needed: ->withMiddleware(fn(Middleware $m) => $m->validateCsrfTokens(except: [...]))'
            );
        }
    }
}
