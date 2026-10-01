<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC018',
    title: 'env() called outside of config files',
    category: 'security',
    severity: 'medium'
)]
final class EnvCallOutsideConfigCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $files = $context->code()->phpFiles();

        foreach ($files as $file) {
            // Only check non-config files
            $normalizedFile = str_replace('\\', '/', $file);

            if (str_contains($normalizedFile, '/config/')) {
                continue;
            }

            $contents = @file_get_contents($file);

            if ($contents === false) {
                continue;
            }

            $lines = explode("\n", $contents);

            foreach ($lines as $lineNo => $line) {
                // Match env() function calls (not in comments)
                $trimmed = trim($line);

                if (strncmp($trimmed, '//', 2) === 0 || strncmp($trimmed, '*', 1) === 0) {
                    continue;
                }

                if (preg_match('/\benv\s*\(/', $line)) {
                    yield $this->finding(
                        message: "env() called outside of a config file. When config is cached, env() returns null.",
                        file:    $file,
                        line:    $lineNo + 1,
                        fix:     "Move this value to a config file and use config('your-key') instead of env() directly."
                    );
                }
            }
        }
    }
}
