<?php

namespace ShipReady\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeCheckCommand extends Command
{
    protected $signature = 'make:ship-check
                            {name : The name of the check class (e.g. MySecurityCheck)}
                            {--category=security : Category (security|performance|reliability|version_specific)}
                            {--severity=medium : Severity (critical|high|medium|low|info)}
                            {--id= : Check ID (e.g. SEC099). Auto-generated if not provided.}';

    protected $description = 'Generate a new ShipReady check class';

    public function handle(): int
    {
        $name      = $this->argument('name');
        $category  = $this->option('category') ?? 'security';
        $severity  = $this->option('severity') ?? 'medium';
        $checkId   = $this->option('id') ?? strtoupper(substr($category, 0, 3)) . rand(100, 999);
        $namespace = 'App\\ShipReady';

        // Validate severity
        $validSeverities = ['critical', 'high', 'medium', 'low', 'info'];

        if (!in_array($severity, $validSeverities, true)) {
            $this->error("Invalid severity: {$severity}. Must be one of: " . implode(', ', $validSeverities));

            return self::FAILURE;
        }

        $validCategories = ['security', 'performance', 'reliability', 'version_specific'];

        if (!in_array($category, $validCategories, true)) {
            $this->error("Invalid category: {$category}. Must be one of: " . implode(', ', $validCategories));

            return self::FAILURE;
        }

        // Ensure class name ends with "Check"
        if (!Str::endsWith($name, 'Check')) {
            $name .= 'Check';
        }

        $stubPath = __DIR__ . '/../../stubs/check.stub';

        if (!file_exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");

            return self::FAILURE;
        }

        $stub = file_get_contents($stubPath);

        $title = Str::headline(str_replace('Check', '', $name));

        $stub = str_replace(
            ['{{ namespace }}', '{{ id }}', '{{ title }}', '{{ category }}', '{{ severity }}', '{{ class }}'],
            [$namespace, $checkId, $title, $category, $severity, $name],
            $stub
        );

        $outputDir = app_path('ShipReady');

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $outputPath = $outputDir . '/' . $name . '.php';

        if (file_exists($outputPath)) {
            if (!$this->confirm("File {$outputPath} already exists. Overwrite?")) {
                $this->line('Aborted.');

                return self::SUCCESS;
            }
        }

        file_put_contents($outputPath, $stub);

        $this->newLine();
        $this->info("Check created: {$outputPath}");
        $this->line("  ID: {$checkId}");
        $this->line("  Category: {$category}");
        $this->line("  Severity: {$severity}");
        $this->newLine();
        $this->line("Add it to your config or register it manually:");
        $this->line("  config/ship-ready.php → 'checks' array");
        $this->newLine();

        return self::SUCCESS;
    }
}
