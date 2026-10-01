<?php

namespace ShipReady\Commands;

use Illuminate\Console\Command;
use ShipReady\CheckRegistry;
use ShipReady\Checks\CheckMetaReader;
use ShipReady\Checks\Filter;

class ExplainCommand extends Command
{
    protected $signature = 'ship:explain {checkId : The check ID to explain (e.g. SEC001)}';

    protected $description = 'Show detailed explanation and remediation for a specific check';

    public function handle(): int
    {
        $checkId = strtoupper($this->argument('checkId'));

        // Find the check in the registry
        /** @var CheckRegistry $registry */
        $registry = app(CheckRegistry::class);

        $filter = new Filter(checkId: $checkId, includeExperimental: true);
        $found  = null;
        $meta   = null;

        foreach ($registry->matching($filter) as [$m, $check]) {
            $meta  = $m;
            $found = $check;
            break;
        }

        if ($found === null) {
            $this->error("Check '{$checkId}' not found.");
            $this->line('Run <info>ship:list</info> to see all available checks.');

            return self::FAILURE;
        }

        // Try to load docs from resources/docs/{checkId}.md
        $docsPath = __DIR__ . '/../../resources/docs/' . $checkId . '.md';

        $this->newLine();
        $this->line('<fg=cyan;options=bold>  ' . $meta->id . ': ' . $meta->title . '</>');
        $this->line('  Category: ' . ucfirst($meta->category));
        $this->line('  Severity: ' . strtoupper($meta->severity));

        if ($meta->productionOnly) {
            $this->line('  ⚠ Production-only check');
        }

        if ($meta->experimental) {
            $this->line('  🧪 Experimental check');
        }

        if ($meta->minLaravel) {
            $this->line('  Min Laravel: ' . $meta->minLaravel);
        }

        if ($meta->maxLaravel) {
            $this->line('  Max Laravel: ' . $meta->maxLaravel);
        }

        $this->newLine();

        if (file_exists($docsPath)) {
            $contents = file_get_contents($docsPath);

            // Simple markdown rendering for console
            $lines = explode("\n", $contents);

            foreach ($lines as $line) {
                if (strncmp($line, '## ', 3) === 0) {
                    $this->line('<options=bold>  ' . substr($line, 3) . '</>');
                } elseif (strncmp($line, '# ', 2) === 0) {
                    // Skip h1, already shown above
                } elseif (strncmp($line, '```', 3) === 0) {
                    $this->line('<fg=gray>  ' . $line . '</>');
                } else {
                    $this->line('  ' . $line);
                }
            }
        } else {
            $this->line('  No detailed documentation available for this check.');

            if ($meta->docs) {
                $this->newLine();
                $this->line('  Docs: ' . $meta->docs);
            }
        }

        $this->newLine();

        return self::SUCCESS;
    }
}
