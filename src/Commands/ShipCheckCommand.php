<?php

namespace ShipReady\Commands;

use Illuminate\Console\Command;
use ShipReady\Analyzers\BladeAnalyzer;
use ShipReady\Analyzers\CodeAnalyzer;
use ShipReady\Analyzers\ConfigAnalyzer;
use ShipReady\Analyzers\DependencyAnalyzer;
use ShipReady\Analyzers\EnvironmentAnalyzer;
use ShipReady\Analyzers\RouteAnalyzer;
use ShipReady\Baseline\Baseline;
use ShipReady\CheckRegistry;
use ShipReady\Checks\Filter;
use ShipReady\Compat\LaravelVersion;
use ShipReady\Reporters\ConsoleReporter;
use ShipReady\Reporters\GithubAnnotationsReporter;
use ShipReady\Reporters\HtmlReporter;
use ShipReady\Reporters\JsonReporter;
use ShipReady\Reporters\JunitReporter;
use ShipReady\Reporters\MarkdownReporter;
use ShipReady\Reporters\SarifReporter;
use ShipReady\Runner;
use ShipReady\Support\Context;
use ShipReady\Support\Severity;

class ShipCheckCommand extends Command
{
    protected $signature = 'ship:check
                            {--target-env=production : Target environment to evaluate config against (production, staging, local)}
                            {--category= : Filter by category (security, performance, reliability)}
                            {--check= : Run a single check by ID}
                            {--format=console : Output format (console|json|sarif|junit|markdown|html|github)}
                            {--output= : Write output to this file path}
                            {--fail-on= : Minimum severity to fail on (critical|high|medium|low|info)}
                            {--diff : Only show new findings (not in baseline)}
                            {--compact : Compact output, no fix hints}
                            {--ignore-baseline : Ignore the baseline file}
                            {--editor= : Editor for file links (phpstorm|vscode|sublime)}
                            {--experimental : Include experimental checks}';

    protected $description = 'Run all security and production-readiness checks on your Laravel app';

    public function handle(): int
    {
        $env           = $this->option('target-env') ?? config('app.env', 'local');
        $format        = $this->option('format') ?? 'console';
        $outputFile    = $this->option('output');
        $compact       = (bool)$this->option('compact');
        $noAnsi        = (bool)$this->option('no-ansi');
        $editor        = $this->option('editor') ?? config('ship-ready.editor');
        $ignoreBaseline = (bool)$this->option('ignore-baseline');
        $experimental  = (bool)$this->option('experimental');
        $categoryFilter = $this->option('category');
        $checkFilter    = $this->option('check');

        // Fail-on threshold
        $failOnStr = $this->option('fail-on') ?? config('ship-ready.fail_on', 'high');

        try {
            $failThreshold = Severity::fromString($failOnStr);
        } catch (\InvalidArgumentException $e) {
            $this->error("Invalid --fail-on value: {$failOnStr}");

            return self::FAILURE;
        }

        // Build analyzers
        $analyzers = $this->buildAnalyzers($env);

        // Build context
        $context = new Context(
            analyzers:      $analyzers,
            targetEnv:      $env,
            laravelVersion: LaravelVersion::detect()
        );

        // Build baseline
        $baselinePath = $ignoreBaseline ? '' : config('ship-ready.baseline', base_path('.ship-ready-baseline.json'));
        $baseline     = new Baseline($baselinePath ?: '/dev/null');

        if (!$ignoreBaseline && $baselinePath && file_exists($baselinePath)) {
            $baseline->load();
        }

        // Build filter
        $filter = new Filter(
            category:           $categoryFilter,
            checkId:            $checkFilter,
            productionOnly:     false,
            includeExperimental: $experimental
        );

        // Run checks
        /** @var CheckRegistry $registry */
        $registry = app(CheckRegistry::class);
        $runner   = new Runner($registry, $baseline);

        $report = $runner->run($context, $filter);

        // Pick reporter
        $reporter = match ($format) {
            'json'    => new JsonReporter(),
            'sarif'   => new SarifReporter(),
            'junit'   => new JunitReporter(),
            'markdown' => new MarkdownReporter(),
            'html'    => new HtmlReporter(),
            'github'  => new GithubAnnotationsReporter(),
            default   => new ConsoleReporter($compact, $noAnsi, $editor),
        };

        $output = $reporter->render($report);

        if ($outputFile) {
            $dir = dirname($outputFile);

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($outputFile, $output);
            $this->line("Report written to: {$outputFile}");
        } else {
            $this->output->write($output);
        }

        // Exit code
        if ($report->shouldFail($failThreshold)) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function buildAnalyzers(string $env): array
    {
        $paths     = config('ship-ready.paths', [app_path()]);
        $exclude   = config('ship-ready.exclude', []);
        $viewPaths = config('ship-ready.view_paths', [resource_path('views')]);

        return [
            new ConfigAnalyzer(app('config'), $env),
            new RouteAnalyzer(app('router')),
            new CodeAnalyzer($paths, $exclude),
            new BladeAnalyzer($viewPaths),
            new EnvironmentAnalyzer(),
            new DependencyAnalyzer(),
        ];
    }
}
