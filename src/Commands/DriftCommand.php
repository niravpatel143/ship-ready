<?php

namespace ShipReady\Commands;

use Illuminate\Console\Command;

class DriftCommand extends Command
{
    protected $signature = 'ship:drift
                            {--from=.env.example : Reference env file (source of truth)}
                            {--to=.env : Actual env file to check}
                            {--show-values : Show values (WARNING: may expose secrets)}
                            {--format=console : Output format (console|json)}';

    protected $description = 'Detect environment variable drift between .env files without leaking secret values';

    public function handle(): int
    {
        $fromPath = base_path($this->option('from') ?? '.env.example');
        $toPath   = base_path($this->option('to') ?? '.env');
        $format   = $this->option('format');

        if (!file_exists($fromPath)) {
            $this->error("Reference file not found: {$fromPath}");

            return self::FAILURE;
        }

        if (!file_exists($toPath)) {
            $this->error("Target file not found: {$toPath}");

            return self::FAILURE;
        }

        $from = $this->parseEnvFile($fromPath);
        $to   = $this->parseEnvFile($toPath);

        $missing  = array_diff_key($from, $to);
        $extra    = array_diff_key($to, $from);
        $changed  = [];

        foreach ($from as $key => $value) {
            if (isset($to[$key]) && $to[$key] !== $value && !empty($from[$key])) {
                $changed[$key] = ['from' => $from[$key], 'to' => $to[$key]];
            }
        }

        $emptyRequired = [];

        foreach ($from as $key => $value) {
            if (!empty($value) && isset($to[$key]) && empty($to[$key])) {
                $emptyRequired[$key] = $value;
            }
        }

        if ($format === 'json') {
            $this->output->write(json_encode(compact('missing', 'extra', 'emptyRequired'), JSON_PRETTY_PRINT) . "\n");

            return empty($missing) && empty($emptyRequired) ? self::SUCCESS : self::FAILURE;
        }

        $this->renderConsole($fromPath, $toPath, $missing, $extra, $emptyRequired);

        return empty($missing) && empty($emptyRequired) ? self::SUCCESS : self::FAILURE;
    }

    private function parseEnvFile(string $path): array
    {
        $lines  = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $result = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if (str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $result[trim($key)] = trim($value);
        }

        return $result;
    }

    private function maskValue(string $value): string
    {
        if (empty($value)) {
            return '(empty)';
        }

        $len = strlen($value);

        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return substr($value, 0, 2) . str_repeat('*', min($len - 4, 8)) . substr($value, -2);
    }

    private function renderConsole(
        string $fromPath,
        string $toPath,
        array $missing,
        array $extra,
        array $emptyRequired
    ): void {
        $this->line('');
        $this->line('  <fg=white;options=bold>ShipReady Environment Drift Report</>');
        $this->line("  Comparing: <fg=cyan>{$fromPath}</> → <fg=cyan>{$toPath}</>");
        $this->line('');

        if (empty($missing) && empty($emptyRequired)) {
            $this->line('  <fg=green>✔ No missing variables found.</>');
        }

        if (!empty($missing)) {
            $this->line('  <fg=red;options=bold>MISSING KEYS</> (in example but not in target):');

            foreach ($missing as $key => $value) {
                $this->line("  <fg=red>  ✖ {$key}</>");
            }

            $this->line('');
        }

        if (!empty($emptyRequired)) {
            $this->line('  <fg=yellow;options=bold>EMPTY REQUIRED KEYS</> (defined in example with a value, but empty in target):');

            foreach ($emptyRequired as $key => $exampleValue) {
                $masked = $this->maskValue($exampleValue);
                $this->line("  <fg=yellow>  ▲ {$key}</> <fg=gray>(example: {$masked})</>");
            }

            $this->line('');
        }

        if (!empty($extra)) {
            $this->line('  <fg=gray;options=bold>EXTRA KEYS</> (in target but not in example — update .env.example):');

            foreach (array_keys($extra) as $key) {
                $this->line("  <fg=gray>  + {$key}</>");
            }

            $this->line('');
        }

        $totalIssues = count($missing) + count($emptyRequired);

        if ($totalIssues > 0) {
            $this->line("  Found <fg=red>{$totalIssues}</> issue(s) requiring attention.");
        } else {
            $extraCount = count($extra);
            $this->line("  <fg=green>✔ All required variables present.</>" . ($extraCount > 0 ? " ({$extraCount} extra key(s) — update .env.example)" : ''));
        }

        $this->line('');
    }
}
