<?php

namespace ShipReady\Checks\Reliability;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'REL011',
    title: '.env.example is missing or out of sync with .env',
    category: 'reliability',
    severity: 'low'
)]
final class MissingEnvExampleCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        if (!$context->env()->envExampleExists()) {
            yield $this->finding(
                message: '.env.example does not exist. New team members and deployment pipelines cannot determine required environment variables.',
                fix:     'Create a .env.example file with all required keys (values can be blank or example values).'
            );

            return;
        }

        // Check for keys in .env that are missing from .env.example
        $envFile     = base_path('.env');
        $exampleFile = base_path('.env.example');

        if (!file_exists($envFile)) {
            return;
        }

        $envKeys     = $this->extractKeys($envFile);
        $exampleKeys = $this->extractKeys($exampleFile);

        $missingFromExample = array_diff($envKeys, $exampleKeys);

        if (!empty($missingFromExample)) {
            $missing = implode(', ', array_slice($missingFromExample, 0, 5));
            $extra   = count($missingFromExample) > 5 ? ' (and ' . (count($missingFromExample) - 5) . ' more)' : '';

            yield $this->finding(
                message: ".env has keys not present in .env.example: {$missing}{$extra}",
                fix:     'Add these keys to .env.example so deployments can reference all required variables.'
            );
        }
    }

    private function extractKeys(string $file): array
    {
        $contents = file_get_contents($file);

        if ($contents === false) {
            return [];
        }

        $keys  = [];
        $lines = explode("\n", $contents);

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line) || strncmp($line, '#', 1) === 0) {
                continue;
            }

            $pos = strpos($line, '=');

            if ($pos !== false) {
                $keys[] = substr($line, 0, $pos);
            }
        }

        return $keys;
    }
}
