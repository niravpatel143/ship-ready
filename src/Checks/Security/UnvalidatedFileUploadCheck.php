<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC021',
    title: 'File upload stored to public disk without validation',
    category: 'security',
    severity: 'high'
)]
final class UnvalidatedFileUploadCheck extends AbstractCheck
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
                // Look for file storage on public disk
                if (!preg_match('/->store(?:As|PubliclyAs|Publicly)?\s*\(.*["\']public["\']/', $line)
                    && !preg_match('/Storage::disk\s*\(\s*["\']public["\'].*->put/', $line)) {
                    continue;
                }

                // Check if there's validation nearby (within ±10 lines)
                $hasValidation = false;
                $start         = max(0, $lineNo - 10);
                $end           = min(count($lines) - 1, $lineNo + 10);

                for ($i = $start; $i <= $end; $i++) {
                    if (preg_match('/validate|mimes|mimetypes|file|image|max:|size:/', $lines[$i])) {
                        $hasValidation = true;
                        break;
                    }
                }

                if (!$hasValidation) {
                    yield $this->finding(
                        message: 'File appears to be stored on the public disk without nearby MIME type or size validation.',
                        file:    $file,
                        line:    $lineNo + 1,
                        fix:     "Add validation rules: 'file' => 'required|file|mimes:jpg,png,pdf|max:2048'. Always validate MIME type, size, and extension."
                    );
                }
            }
        }
    }
}
