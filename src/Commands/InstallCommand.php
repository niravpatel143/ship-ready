<?php

namespace ShipReady\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'ship:install
                            {--forge : Generate Laravel Forge deploy script with ShipReady gate}
                            {--cloud : Generate Laravel Cloud deploy hook}
                            {--workflow : Generate GitHub Actions workflow}
                            {--envoyer : Generate Envoyer deployment hook script}';

    protected $description = 'Generate deploy gate configuration for Forge, Laravel Cloud, Envoyer, or GitHub Actions';

    public function handle(): int
    {
        $forge   = $this->option('forge');
        $cloud   = $this->option('cloud');
        $workflow = $this->option('workflow');
        $envoyer = $this->option('envoyer');

        if (!$forge && !$cloud && !$workflow && !$envoyer) {
            $this->error('Specify a target: --forge, --cloud, --workflow, or --envoyer');
            $this->line('');
            $this->line('Examples:');
            $this->line('  php artisan ship:install --workflow    # GitHub Actions CI gate');
            $this->line('  php artisan ship:install --forge       # Forge deploy script');
            $this->line('  php artisan ship:install --cloud       # Laravel Cloud hook');
            $this->line('  php artisan ship:install --envoyer     # Envoyer hook script');

            return self::FAILURE;
        }

        if ($workflow) {
            $this->installGitHubWorkflow();
        }

        if ($forge) {
            $this->showForgeScript();
        }

        if ($cloud) {
            $this->showCloudHook();
        }

        if ($envoyer) {
            $this->showEnvoyerScript();
        }

        return self::SUCCESS;
    }

    private function installGitHubWorkflow(): void
    {
        $path    = base_path('.github/workflows/ship-ready.yml');
        $dir     = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($path)) {
            $this->warn("  .github/workflows/ship-ready.yml already exists — skipping.");

            return;
        }

        $content = <<<'YAML'
name: ShipReady Audit

on:
  push:
    branches: [main, master]
  pull_request:
    branches: [main, master]

jobs:
  ship-ready:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, pdo, pdo_mysql
          coverage: none

      - name: Install Composer dependencies
        run: composer install --no-dev --optimize-autoloader --no-interaction

      - name: Run ShipReady audit
        run: php artisan ship:check --target-env=production --fail-on=high --format=github

      - name: Upload SARIF results
        if: always()
        uses: github/codeql-action/upload-sarif@v3
        with:
          sarif_file: results.sarif
        continue-on-error: true
YAML;

        file_put_contents($path, $content);
        $this->info("  ✔ Created: .github/workflows/ship-ready.yml");
    }

    private function showForgeScript(): void
    {
        $this->line('');
        $this->line('  <fg=white;options=bold>Forge Deploy Script (add before php artisan migrate)</>');
        $this->line('');
        $script = <<<'BASH'
# --- ShipReady gate ---
php artisan ship:check --target-env=production --fail-on=high --compact
if [ $? -ne 0 ]; then
    echo "ShipReady: critical/high issues found. Aborting deploy."
    exit 1
fi
# --- end ShipReady gate ---
BASH;
        foreach (explode("\n", $script) as $line) {
            $this->line("  <fg=gray>{$line}</>");
        }

        $this->line('');
        $this->line('  Paste this into your Forge site\'s deployment script, before the migration step.');
        $this->line('');
    }

    private function showCloudHook(): void
    {
        $this->line('');
        $this->line('  <fg=white;options=bold>Laravel Cloud Deploy Hook</>');
        $this->line('');
        $this->line('  In laravel.cloud → your app → Deployments → Deploy Hooks, add:');
        $this->line('');
        $this->line('  <fg=cyan>  php artisan ship:check --target-env=production --fail-on=high --compact</>');
        $this->line('');
        $this->line('  Set "Abort on failure" = ON to prevent deploys with critical issues.');
        $this->line('');
    }

    private function showEnvoyerScript(): void
    {
        $this->line('');
        $this->line('  <fg=white;options=bold>Envoyer Deployment Hook</>');
        $this->line('');
        $this->line('  In Envoyer → your project → Deployment Hooks → Before Finishing Deployment:');
        $this->line('');
        $script = <<<'BASH'
cd {{ release }}
php artisan ship:check --target-env=production --fail-on=high --compact
BASH;
        foreach (explode("\n", $script) as $line) {
            $this->line("  <fg=gray>{$line}</>");
        }

        $this->line('');
        $this->line('  If the command exits non-zero, Envoyer will mark the deployment as failed.');
        $this->line('');
    }
}
