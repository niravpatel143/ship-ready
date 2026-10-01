<?php

namespace ShipReady\Analyzers;

use Symfony\Component\Finder\Finder;

final class EnvironmentAnalyzer
{
    public function isConfigCached(): bool
    {
        return file_exists(base_path('bootstrap/cache/config.php'));
    }

    public function isRouteCached(): bool
    {
        $cacheDir = base_path('bootstrap/cache');

        if (!is_dir($cacheDir)) {
            return false;
        }

        $files = glob($cacheDir . '/routes*.php');

        return !empty($files);
    }

    public function isViewsCached(): bool
    {
        $storagePath = storage_path('framework/views');

        if (!is_dir($storagePath)) {
            return false;
        }

        $files = glob($storagePath . '/*.php');

        return !empty($files);
    }

    public function isOpcacheEnabled(): bool
    {
        if (!function_exists('opcache_get_status')) {
            return false;
        }

        $status = @opcache_get_status(false);

        return is_array($status) && isset($status['opcache_enabled']) && $status['opcache_enabled'];
    }

    public function hasPendingMigrations(): bool
    {
        try {
            $output = [];
            $code   = 0;

            exec('php ' . escapeshellarg(base_path('artisan')) . ' migrate:status --no-ansi 2>&1', $output, $code);

            $outputStr = implode("\n", $output);

            return str_contains($outputStr, 'Pending') || str_contains($outputStr, 'pending');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function phpVersion(): string
    {
        return PHP_VERSION;
    }

    public function isPubliclyExposed(string $path): bool
    {
        // Avoid realpath() on Windows — it emits CRT-level warnings on junctions.
        // Normalise both paths to forward-slashes for a safe string comparison.
        $normalize  = static fn(string $p): string => rtrim(str_replace('\\', '/', $p), '/');
        $realPublic = $normalize(public_path());
        $testPath   = $normalize($path);

        return $testPath === $realPublic
            || str_starts_with($testPath, $realPublic . '/');
    }

    public function hasStorageLink(): bool
    {
        $link = public_path('storage');

        // On Windows a storage junction is a directory, not a symlink.
        // Suppress CRT output from is_link() via output buffering.
        ob_start();
        $result = @is_link($link) || @is_dir($link);
        ob_end_clean();

        return $result;
    }

    public function storageWritable(): bool
    {
        return is_writable(storage_path());
    }

    public function composerLockExists(): bool
    {
        return file_exists(base_path('composer.lock'));
    }

    public function npmLockExists(): bool
    {
        return file_exists(base_path('package-lock.json'))
            || file_exists(base_path('yarn.lock'))
            || file_exists(base_path('pnpm-lock.yaml'));
    }

    public function envExampleExists(): bool
    {
        return file_exists(base_path('.env.example'));
    }

    public function isAutoloaderOptimized(): bool
    {
        return file_exists(base_path('vendor/composer/autoload_classmap.php'))
            && filesize(base_path('vendor/composer/autoload_classmap.php')) > 500;
    }
}
