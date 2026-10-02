<?php

namespace ShipReady\Commands;

use Illuminate\Console\Command;
use ShipReady\Compat\LaravelVersion;

class NextCommand extends Command
{
    protected $signature = 'ship:next
                            {--target=14 : Target Laravel major version to check readiness for}
                            {--format=console : Output format (console|json)}';

    protected $description = 'Check Laravel upgrade readiness — deprecated APIs, PHP version, and breaking changes';

    public function handle(): int
    {
        $target  = (int)($this->option('target') ?? 14);
        $format  = $this->option('format');
        $current = LaravelVersion::detect();

        $issues = [];

        if ($target === 14) {
            $issues = $this->checkLaravel14Readiness($current);
        } elseif ($target === 13) {
            $issues = $this->checkLaravel13Readiness($current);
        } else {
            $this->error("Unsupported target version: {$target}. Supported: 13, 14");

            return self::FAILURE;
        }

        if ($format === 'json') {
            $this->output->write(json_encode(['target' => "Laravel {$target}", 'issues' => $issues], JSON_PRETTY_PRINT) . "\n");

            return empty(array_filter($issues, fn($i) => $i['severity'] === 'breaking')) ? self::SUCCESS : self::FAILURE;
        }

        $this->renderConsole($current, $target, $issues);

        return empty(array_filter($issues, fn($i) => $i['severity'] === 'breaking')) ? self::SUCCESS : self::FAILURE;
    }

    private function checkLaravel14Readiness(string $currentVersion): array
    {
        $issues = [];

        // PHP 8.4+ required for Laravel 14
        $phpVersion = PHP_VERSION;

        if (version_compare($phpVersion, '8.4.0', '<')) {
            $issues[] = [
                'severity' => 'breaking',
                'title'    => 'PHP 8.4+ required',
                'detail'   => "Current PHP: {$phpVersion}. Laravel 14 will require PHP 8.4 minimum.",
                'fix'      => 'Upgrade PHP to 8.4 or later.',
            ];
        }

        // Check for deprecated patterns in code
        $deprecatedPatterns = [
            [
                'pattern' => '/Route::middleware\([\'"]web[\'"]\)/',
                'title'   => 'Route::middleware() with string may change in L14',
                'fix'     => 'Use array syntax: Route::middleware([\'web\'])',
                'severity' => 'warning',
            ],
            [
                'pattern' => '/\$this->middleware\(/',
                'title'   => 'Controller constructor middleware deprecated',
                'fix'     => 'Move middleware to route definitions or use #[Middleware] attribute',
                'severity' => 'warning',
            ],
            [
                'pattern' => '/Response::macro\(/',
                'title'   => 'Response macros may be removed',
                'fix'     => 'Evaluate if these macros conflict with new L14 response methods',
                'severity' => 'info',
            ],
        ];

        $appPath = app_path();

        if (is_dir($appPath)) {
            foreach ($deprecatedPatterns as $check) {
                $found = $this->searchPattern($check['pattern'], $appPath);

                if ($found) {
                    $issues[] = [
                        'severity' => $check['severity'],
                        'title'    => $check['title'],
                        'detail'   => "Found in: {$found}",
                        'fix'      => $check['fix'],
                    ];
                }
            }
        }

        // Check composer.json for L14-conflicting packages
        $composerJson = base_path('composer.json');

        if (file_exists($composerJson)) {
            $composer = json_decode(file_get_contents($composerJson) ?: '{}', true);
            $require  = $composer['require'] ?? [];

            $constraintIssues = [
                'laravel/framework' => '^12.0|^13.0',
            ];

            foreach ($constraintIssues as $pkg => $constraint) {
                if (isset($require[$pkg]) && !str_contains($require[$pkg], '14')) {
                    $issues[] = [
                        'severity' => 'warning',
                        'title'    => "composer.json constraint for {$pkg} does not include L14",
                        'detail'   => "Current constraint: {$require[$pkg]}",
                        'fix'      => "Update to: {$constraint}|^14.0 once Laravel 14 is released.",
                    ];
                }
            }
        }

        return $issues;
    }

    private function checkLaravel13Readiness(string $currentVersion): array
    {
        $issues = [];

        if (version_compare($currentVersion, '13.0', '>=')) {
            $issues[] = [
                'severity' => 'info',
                'title'    => 'Already on Laravel 13+',
                'detail'   => "Current version: {$currentVersion}",
                'fix'      => 'No upgrade needed.',
            ];

            return $issues;
        }

        // PHP 8.2+ required
        $phpVersion = PHP_VERSION;

        if (version_compare($phpVersion, '8.2.0', '<')) {
            $issues[] = [
                'severity' => 'breaking',
                'title'    => 'PHP 8.2+ required for Laravel 13',
                'detail'   => "Current PHP: {$phpVersion}",
                'fix'      => 'Upgrade PHP to 8.2 or later.',
            ];
        }

        // Check for L12 deprecated patterns
        $checks = [
            ['pattern' => '/app\/Http\/Kernel\.php/', 'title' => 'Deprecated app/Http/Kernel.php', 'severity' => 'warning', 'fix' => 'Move middleware to bootstrap/app.php'],
        ];

        foreach ($checks as $check) {
            if (file_exists(app_path('Http/Kernel.php'))) {
                $issues[] = [
                    'severity' => $check['severity'],
                    'title'    => $check['title'],
                    'detail'   => 'app/Http/Kernel.php exists',
                    'fix'      => $check['fix'],
                ];
            }
        }

        return $issues;
    }

    private function searchPattern(string $pattern, string $dir): ?string
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname()) ?: '';

            if (preg_match($pattern, $content)) {
                return $file->getPathname();
            }
        }

        return null;
    }

    private function renderConsole(string $current, int $target, array $issues): void
    {
        $this->line('');
        $this->line("  <fg=white;options=bold>ShipReady: Laravel {$target} Readiness Check</>");
        $this->line("  Current version: <fg=cyan>Laravel {$current}</>");
        $this->line('');

        if (empty($issues)) {
            $this->line("  <fg=green>✔ No issues found. Your app looks ready for Laravel {$target}.</>");
            $this->line('');

            return;
        }

        foreach ($issues as $issue) {
            $color = match ($issue['severity']) {
                'breaking' => 'red',
                'warning'  => 'yellow',
                default    => 'gray',
            };

            $icon = match ($issue['severity']) {
                'breaking' => '✖',
                'warning'  => '▲',
                default    => 'ℹ',
            };

            $this->line("  <fg={$color}>{$icon} {$issue['title']}</>");
            $this->line("    <fg=gray>{$issue['detail']}</>");
            $this->line("    <fg=white>Fix: {$issue['fix']}</>");
            $this->line('');
        }

        $breaking = count(array_filter($issues, fn($i) => $i['severity'] === 'breaking'));
        $warnings = count(array_filter($issues, fn($i) => $i['severity'] === 'warning'));

        $this->line("  Found <fg=red>{$breaking} breaking</> and <fg=yellow>{$warnings} warning(s)</> before upgrading to Laravel {$target}.");
        $this->line('');
    }
}
