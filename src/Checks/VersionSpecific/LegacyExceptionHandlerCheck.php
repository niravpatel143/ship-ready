<?php

namespace ShipReady\Checks\VersionSpecific;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'VER008',
    title: 'Legacy exception handler used (Laravel 11+)',
    category: 'reliability',
    severity: 'low',
    minLaravel: '11.0'
)]
final class LegacyExceptionHandlerCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $handlerFile = app_path('Exceptions/Handler.php');

        if (!file_exists($handlerFile)) {
            return;
        }

        $content = file_get_contents($handlerFile) ?: '';

        // Only flag if the handler has actual customizations (not just the default stub)
        $hasCustomRender  = preg_match('/public\s+function\s+render\s*\(/', $content);
        $hasCustomReport  = preg_match('/public\s+function\s+report\s*\(/', $content);
        $hasCustomRegister = preg_match('/public\s+function\s+register\s*\(/', $content);

        if (!$hasCustomRender && !$hasCustomReport && !$hasCustomRegister) {
            return;
        }

        yield $this->finding(
            message: 'app/Exceptions/Handler.php has customized render()/report()/register() methods. Laravel 11+ handles exceptions via withExceptions() in bootstrap/app.php.',
            file:    $handlerFile,
            fix:     'Migrate custom exception handling to bootstrap/app.php: $app->withExceptions(function (Exceptions $exceptions) { $exceptions->render(...); });'
        );
    }
}
