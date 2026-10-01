<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC007',
    title: '$request->all() passed to mass assignment method',
    category: 'security',
    severity: 'high'
)]
final class RequestAllMassAssignmentCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        // Look for patterns like Model::create($request->all()) in code
        $files = $context->code()->phpFiles();

        foreach ($files as $file) {
            $contents = @file_get_contents($file);

            if ($contents === false) {
                continue;
            }

            $lines = explode("\n", $contents);

            foreach ($lines as $lineNo => $line) {
                // Match patterns: ::create($request->all()), ->fill($request->all()), etc.
                if (preg_match('/(?:create|update|fill|forceFill)\(\s*\$request->(?:all|input|toArray)\(\)/', $line)) {
                    yield $this->finding(
                        message: '$request->all() (or similar) is passed directly to a mass-assignment method.',
                        file:    $file,
                        line:    $lineNo + 1,
                        fix:     'Use $request->validated() or $request->only([...]) to explicitly whitelist allowed input.'
                    );
                }
            }
        }
    }
}
