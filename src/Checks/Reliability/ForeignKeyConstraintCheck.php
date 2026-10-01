<?php

namespace ShipReady\Checks\Reliability;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'REL019',
    title: 'Foreign key column without database constraint',
    category: 'reliability',
    severity: 'medium'
)]
final class ForeignKeyConstraintCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $migrationsPath = database_path('migrations');

        if (!is_dir($migrationsPath)) {
            return;
        }

        foreach (glob($migrationsPath . '/*.php') ?: [] as $file) {
            $content = file_get_contents($file);

            if ($content === false) {
                continue;
            }

            // Find unsignedBigInteger/unsignedInteger/bigInteger/integer columns named *_id
            if (!preg_match_all(
                '/\->(unsignedBigInteger|unsignedInteger|bigInteger|integer)\s*\(\s*[\'"]([a-z_]+_id)[\'"]\s*\)([^;]*);/m',
                $content,
                $matches,
                PREG_SET_ORDER
            )) {
                continue;
            }

            foreach ($matches as $match) {
                $column  = $match[2];
                $chained = $match[3];

                if (str_contains($chained, 'constrained') || str_contains($chained, 'references')) {
                    continue;
                }

                // Check for standalone $table->foreign('column_name') elsewhere in the migration
                if (preg_match('/foreign\s*\(\s*[\'"]' . preg_quote($column, '/') . '[\'"]\s*\)/', $content)) {
                    continue;
                }

                // foreignId() sets up the FK automatically — skip if that pattern is used
                if (str_contains($content, "foreignId('{$column}')") || str_contains($content, "foreignId(\"{$column}\")")) {
                    continue;
                }

                yield $this->finding(
                    message: "Column '{$column}' looks like a foreign key but has no database-level FK constraint. Orphaned rows can accumulate silently.",
                    file:    $file,
                    fix:     "Chain ->constrained() after the column definition, or replace with \$table->foreignId('{$column}')->constrained()."
                );
            }
        }
    }
}
