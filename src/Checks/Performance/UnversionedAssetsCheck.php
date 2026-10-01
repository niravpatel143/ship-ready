<?php

namespace ShipReady\Checks\Performance;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'PERF013',
    title: 'Assets not versioned for cache busting',
    category: 'performance',
    severity: 'low'
)]
final class UnversionedAssetsCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        // Check if mix-manifest.json or vite manifest exists (indicates versioned assets)
        $hasMixManifest  = file_exists(public_path('mix-manifest.json'));
        $hasViteManifest = file_exists(public_path('build/manifest.json'))
            || file_exists(public_path('build/.vite/manifest.json'));

        if (!$hasMixManifest && !$hasViteManifest) {
            yield $this->finding(
                message: 'No asset versioning manifest found (mix-manifest.json or Vite manifest). Assets may be cached stale by browsers after deployment.',
                fix:     'Use Laravel Mix with mix.version() or Vite (which versions by default) to generate cache-busting file hashes.'
            );
        }
    }
}
