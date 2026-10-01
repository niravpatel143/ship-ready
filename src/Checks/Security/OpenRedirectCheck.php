<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC026',
    title: 'Potential open redirect via user-controlled URL',
    category: 'security',
    severity: 'high'
)]
final class OpenRedirectCheck extends AbstractCheck
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
                // Match redirect($request->input(...)) and similar patterns
                $patterns = [
                    '/redirect\s*\(\s*\$request->(?:input|get|query|post)\s*\(/',
                    '/return\s+redirect\s*\(\s*\$[a-zA-Z_]+\s*\)/',
                    '/Redirect::to\s*\(\s*\$request->(?:input|get|query)\s*\(/',
                ];

                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $line)) {
                        yield $this->finding(
                            message: 'Potential open redirect: redirect() called with user-controlled URL input.',
                            file:    $file,
                            line:    $lineNo + 1,
                            fix:     'Validate redirect URLs against an allowlist of trusted domains before redirecting. Use redirect()->route() or redirect()->intended() instead.'
                        );

                        break;
                    }
                }
            }
        }
    }
}
