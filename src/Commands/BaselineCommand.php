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
use ShipReady\Runner;
use ShipReady\Support\Context;

class BaselineCommand extends Command
{
    protected $signature = 'ship:baseline
                            {--prune : Remove findings from baseline that no longer exist}
                            {--env=production : Target environment}';

    protected $description = 'Record current findings as baseline (suppress in future runs)';

    public function handle(): int
    {
        $env          = $this->option('env') ?? 'production';
        $prune        = (bool)$this->option('prune');
        $baselinePath = config('ship-ready.baseline', base_path('.ship-ready-baseline.json'));

        $paths     = config('ship-ready.paths', [app_path()]);
        $exclude   = config('ship-ready.exclude', []);
        $viewPaths = config('ship-ready.view_paths', [resource_path('views')]);

        $analyzers = [
            new ConfigAnalyzer(app('config'), $env),
            new RouteAnalyzer(app('router')),
            new CodeAnalyzer($paths, $exclude),
            new BladeAnalyzer($viewPaths),
            new EnvironmentAnalyzer(),
            new DependencyAnalyzer(),
        ];

        $context = new Context(
            analyzers:      $analyzers,
            targetEnv:      $env,
            laravelVersion: LaravelVersion::detect()
        );

        // Use an empty baseline so all findings come through as "new"
        $emptyBaseline = new Baseline('/dev/null');
        $filter        = new Filter();

        /** @var CheckRegistry $registry */
        $registry = app(CheckRegistry::class);
        $runner   = new Runner($registry, $emptyBaseline);

        $report = $runner->run($context, $filter);

        $baseline = new Baseline($baselinePath);

        if ($prune) {
            $baseline->load();
            $baseline->prune($report->allFindings());
            $this->info('Baseline pruned. Removed findings that no longer exist.');
        } else {
            $baseline->write($report->allFindings());

            $count = count($report->allFindings());
            $this->info("Baseline recorded: {$count} finding(s) written to {$baselinePath}");
        }

        return self::SUCCESS;
    }
}
