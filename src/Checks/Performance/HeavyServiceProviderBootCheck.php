<?php

namespace ShipReady\Checks\Performance;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'PERF014',
    title: 'Heavy operations in ServiceProvider::boot()',
    category: 'performance',
    severity: 'low'
)]
final class HeavyServiceProviderBootCheck extends AbstractCheck
{
    private const HEAVY_PATTERNS = [
        '/DB::/',
        '/\$this->app\[.*\]->select/',
        '/\->query\(\)/',
        '/Http::(?:get|post|put|patch|delete)\s*\(/',
        '/file_get_contents\s*\(\s*["\']https?:/',
        '/curl_exec/',
    ];

    public function run(Context $context): iterable
    {
        $providerPath = app_path('Providers');

        if (!is_dir($providerPath)) {
            return;
        }

        $files = glob($providerPath . '/*.php');

        if (empty($files)) {
            return;
        }

        foreach ($files as $file) {
            $contents = @file_get_contents($file);

            if ($contents === false) {
                continue;
            }

            // Extract boot method body using a simple heuristic
            if (!preg_match('/function\s+boot\s*\([^)]*\)\s*\{(.+?)(?=\n\s*(?:public|protected|private)\s+function|\n\})/s', $contents, $matches)) {
                continue;
            }

            $bootBody = $matches[1];
            $lines    = explode("\n", $contents);

            // Find the boot method start line
            $bootLine = 0;

            foreach ($lines as $lineNo => $line) {
                if (preg_match('/function\s+boot\s*\(/', $line)) {
                    $bootLine = $lineNo + 1;
                    break;
                }
            }

            foreach (self::HEAVY_PATTERNS as $pattern) {
                if (preg_match($pattern, $bootBody)) {
                    yield $this->finding(
                        message: "Potential heavy operation (DB query or HTTP call) detected in ServiceProvider::boot() in " . basename($file),
                        file:    $file,
                        line:    $bootLine,
                        fix:     'Move heavy operations to deferred service providers or lazy-load them. Avoid database queries and HTTP calls in boot().'
                    );

                    break; // One finding per provider
                }
            }
        }
    }
}
