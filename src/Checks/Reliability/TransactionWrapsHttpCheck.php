<?php

namespace ShipReady\Checks\Reliability;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'REL013',
    title: 'Multiple DB writes without transaction wrapping',
    category: 'reliability',
    severity: 'medium'
)]
final class TransactionWrapsHttpCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $files = $context->code()->phpFiles();

        // Only check controller/service files
        foreach ($files as $file) {
            $normalizedFile = str_replace('\\', '/', $file);

            if (!str_contains($normalizedFile, '/Http/Controllers/')
                && !str_contains($normalizedFile, '/Services/')
                && !str_contains($normalizedFile, '/Actions/')) {
                continue;
            }

            $contents = @file_get_contents($file);

            if ($contents === false) {
                continue;
            }

            // Look for multiple ->save() or ->create() calls without DB::transaction
            $saveCount = preg_match_all('/->(?:save|create|update|delete|insert)\s*\(/', $contents);

            if ($saveCount < 2) {
                continue;
            }

            if (str_contains($contents, 'DB::transaction') || str_contains($contents, 'DB::beginTransaction')) {
                continue;
            }

            yield $this->finding(
                message: "Multiple database write operations in " . basename($file) . " without DB::transaction wrapping. A failure midway will leave data in an inconsistent state.",
                file:    $file,
                fix:     'Wrap related DB write operations in DB::transaction(function () { ... }) to ensure atomicity.'
            );
        }
    }
}
