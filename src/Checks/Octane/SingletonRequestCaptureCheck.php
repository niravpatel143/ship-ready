<?php

namespace ShipReady\Checks\Octane;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'OCT001',
    title: 'Singleton captures Request or auth state (Octane memory leak)',
    category: 'octane',
    severity: 'critical'
)]
final class SingletonRequestCaptureCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $analyzer = $context->code();

        if ($analyzer === null) {
            return;
        }

        foreach ($analyzer->phpFiles() as $file) {
            $content = file_get_contents($file) ?: '';

            // Skip files that aren't service providers or singletons
            if (!preg_match('/singleton|bind|app\(/', $content)) {
                continue;
            }

            // Detect request/auth captured inside a singleton closure
            if (preg_match(
                '/singleton\s*\(.*?function.*?\$request\s*=\s*(?:request\(\)|app\([\'"]request[\'"]\)|resolve\([\'"]request[\'"]\))/s',
                $content
            )) {
                yield $this->finding(
                    message: 'A singleton closure captures the Request object. Under Octane, singletons persist across requests, so the first request\'s data leaks into all subsequent ones.',
                    file:    $file,
                    fix:     'Inject Request inside the method body, not in the singleton closure. Use request() helper inside methods instead of storing it as a property.'
                );
                continue;
            }

            // Detect auth()->user() stored in singleton
            if (preg_match(
                '/singleton\s*\(.*?function.*?(?:auth\(\)->user\(\)|Auth::user\(\))/s',
                $content
            )) {
                yield $this->finding(
                    message: 'A singleton closure captures auth state (Auth::user() or auth()->user()). Under Octane this leaks authentication across requests.',
                    file:    $file,
                    fix:     'Never resolve auth state inside a singleton binding. Call auth()->user() at call-time inside service methods.'
                );
            }
        }
    }
}
