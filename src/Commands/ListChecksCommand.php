<?php

namespace ShipReady\Commands;

use Illuminate\Console\Command;
use ShipReady\CheckRegistry;
use ShipReady\Checks\Filter;

class ListChecksCommand extends Command
{
    protected $signature = 'ship:list
                            {--category= : Filter by category}
                            {--experimental : Include experimental checks}';

    protected $description = 'List all available ShipReady checks';

    public function handle(): int
    {
        /** @var CheckRegistry $registry */
        $registry    = app(CheckRegistry::class);
        $category    = $this->option('category');
        $experimental = (bool)$this->option('experimental');

        $filter = new Filter(
            category:            $category,
            includeExperimental: $experimental
        );

        $disabled = config('ship-ready.disabled', []);
        $rows     = [];

        foreach ($registry->matching($filter) as [$meta, $check]) {
            $enabled = !in_array($meta->id, $disabled, true) ? '<fg=green>✔</>' : '<fg=red>✖</>';
            $exp     = $meta->experimental ? ' 🧪' : '';
            $prod    = $meta->productionOnly ? ' (prod)' : '';

            $rows[] = [
                $meta->id,
                $meta->title . $exp,
                ucfirst($meta->category),
                strtoupper($meta->severity) . $prod,
                $enabled,
            ];
        }

        if (empty($rows)) {
            $this->warn('No checks found matching the given criteria.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->table(
            ['ID', 'Title', 'Category', 'Severity', 'Enabled'],
            $rows
        );

        $this->newLine();
        $this->line(sprintf(
            '  <info>%d</info> check(s) listed. Use <info>ship:explain {ID}</info> for details.',
            count($rows)
        ));
        $this->newLine();

        return self::SUCCESS;
    }
}
