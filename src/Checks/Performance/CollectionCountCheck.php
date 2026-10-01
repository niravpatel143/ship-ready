<?php

namespace ShipReady\Checks\Performance;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'PERF011',
    title: 'Collection loaded to check count when DB query would suffice',
    category: 'performance',
    severity: 'low'
)]
final class CollectionCountCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $files = $context->code()->phpFiles();

        foreach ($files as $file) {
            $contents = @file_get_contents($file);

            if ($contents === false) {
                continue;
            }

            $lines = explode("\n", $contents);

            foreach ($lines as $lineNo => $line) {
                // Match patterns like: Model::get()->count(), ->all()->count()
                if (preg_match('/->(?:get|all)\s*\(\s*\)\s*->\s*count\s*\(/', $line)) {
                    yield $this->finding(
                        message: 'Collection loaded with get()/all() then count() called. Use ->count() directly on the query builder.',
                        file:    $file,
                        line:    $lineNo + 1,
                        fix:     'Replace ->get()->count() with ->count() to execute SELECT COUNT(*) instead of fetching all rows.'
                    );
                }

                // Match: count(Model::get()), count(Model::all())
                if (preg_match('/\bcount\s*\(\s*\w+::(?:get|all)\s*\(/', $line)) {
                    yield $this->finding(
                        message: 'count() wrapping a full collection load. Use ->count() query builder method instead.',
                        file:    $file,
                        line:    $lineNo + 1,
                        fix:     'Replace count(Model::get()) with Model::count().'
                    );
                }
            }
        }
    }
}
