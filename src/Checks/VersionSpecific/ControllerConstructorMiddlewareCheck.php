<?php

namespace ShipReady\Checks\VersionSpecific;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;
use Symfony\Component\Finder\Finder;

#[CheckMeta(
    id: 'VER007',
    title: 'Controller constructor middleware deprecated (Laravel 11+)',
    category: 'reliability',
    severity: 'medium',
    minLaravel: '11.0'
)]
final class ControllerConstructorMiddlewareCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $controllerPath = app_path('Http/Controllers');

        if (!is_dir($controllerPath)) {
            return;
        }

        $finder = new Finder();
        $finder->files()->name('*.php')->in($controllerPath);

        foreach ($finder as $file) {
            $content = $file->getContents();

            if (!str_contains($content, '__construct')) {
                continue;
            }

            if (!str_contains($content, '$this->middleware(')) {
                continue;
            }

            // Make sure it's inside a constructor, not a random method
            if (!preg_match('/function\s+__construct[^{]*\{[^}]*\$this->middleware\s*\(/s', $content)) {
                continue;
            }

            yield $this->finding(
                message: "Controller '{$file->getFilenameWithoutExtension()}' uses \$this->middleware() in its constructor, which is deprecated in Laravel 11+.",
                file:    $file->getRealPath(),
                fix:     'Move middleware to your route definitions using ->middleware(), or use the #[Middleware] attribute on the controller class/methods.'
            );
        }
    }
}
