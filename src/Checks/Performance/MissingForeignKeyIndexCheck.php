<?php

namespace ShipReady\Checks\Performance;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'PERF010',
    title: 'Possible missing index on foreign key column',
    category: 'performance',
    severity: 'medium'
)]
final class MissingForeignKeyIndexCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        // Scan migration files for foreign keys without indexes
        $migrationsPath = database_path('migrations');

        if (!is_dir($migrationsPath)) {
            return;
        }

        $files = glob($migrationsPath . '/*.php');

        if (empty($files)) {
            return;
        }

        foreach ($files as $file) {
            $contents = @file_get_contents($file);

            if ($contents === false) {
                continue;
            }

            $lines = explode("\n", $contents);

            foreach ($lines as $lineNo => $line) {
                // Look for foreignId / foreign() calls
                if (!preg_match('/->(?:foreignId|foreignIdFor|foreign)\s*\(/', $line)) {
                    continue;
                }

                // Check if this line or the next few lines call ->index() or ->constrained()
                $context5 = implode(' ', array_slice($lines, $lineNo, 4));

                if (!str_contains($context5, '->index()') && !str_contains($context5, '->constrained(')) {
                    // foreignId()->constrained() adds an index automatically
                    // foreign() without constrained may not
                    if (!str_contains($context5, '->constrained(')) {
                        preg_match('/["\']([a-z_]+)["\']/', $line, $matches);
                        $column = $matches[1] ?? 'foreign key column';

                        yield $this->finding(
                            message: "Migration uses foreign() on '{$column}' without an explicit index. Foreign key columns should be indexed.",
                            file:    $file,
                            line:    $lineNo + 1,
                            fix:     "Add ->index() after the foreign key definition, or use foreignId('{$column}')->constrained()."
                        );
                    }
                }
            }
        }
    }
}
