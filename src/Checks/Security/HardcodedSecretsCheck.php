<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC019',
    title: 'Hardcoded secrets or API keys in source code',
    category: 'security',
    severity: 'critical'
)]
final class HardcodedSecretsCheck extends AbstractCheck
{
    private const PATTERNS = [
        // AWS keys
        '/(?:aws|AWS)(?:_ACCESS_KEY|_SECRET|_KEY)\s*[=:]\s*["\'][A-Za-z0-9\/+]{20,}["\']/'   => 'AWS credential',
        '/AKIA[0-9A-Z]{16}/'                                                                    => 'AWS Access Key ID',
        // Generic passwords
        '/["\']password["\']\s*=>\s*["\'][^$\{][^"\']{6,}["\']/'                               => 'Hardcoded password',
        // Private keys
        '/-----BEGIN (?:RSA |EC |DSA )?PRIVATE KEY-----/'                                        => 'Private key',
        // Generic API keys
        '/(?:api_key|apikey|API_KEY)\s*[=:]\s*["\'][A-Za-z0-9_\-]{20,}["\']/'                 => 'Hardcoded API key',
        // Stripe
        '/sk_live_[0-9a-zA-Z]{24,}/'                                                            => 'Stripe live secret key',
        '/pk_live_[0-9a-zA-Z]{24,}/'                                                            => 'Stripe live publishable key',
        // GitHub tokens
        '/ghp_[0-9a-zA-Z]{36}/'                                                                 => 'GitHub personal access token',
        '/github_pat_[0-9a-zA-Z_]{82}/'                                                         => 'GitHub fine-grained PAT',
        // Generic secrets
        '/(?:secret|token|password)\s*=\s*["\'][A-Za-z0-9!@#$%^&*()_+\-=]{12,}["\']/'        => 'Possible hardcoded secret',
    ];

    // Files to skip
    private const SKIP_PATTERNS = [
        '/test/',
        '/tests/',
        '/spec/',
        '/fixture/',
        '/config/',
        '/lang/',
        '/vendor/',
    ];

    public function run(Context $context): iterable
    {
        $files = $context->code()->phpFiles();

        foreach ($files as $file) {
            $normalizedFile = str_replace('\\', '/', $file);

            $skip = false;

            foreach (self::SKIP_PATTERNS as $skipPattern) {
                if (str_contains($normalizedFile, trim($skipPattern, '/'))) {
                    $skip = true;
                    break;
                }
            }

            if ($skip) {
                continue;
            }

            $contents = @file_get_contents($file);

            if ($contents === false) {
                continue;
            }

            $lines = explode("\n", $contents);

            foreach ($lines as $lineNo => $line) {
                // Skip comments
                $trimmed = trim($line);

                if (strncmp($trimmed, '//', 2) === 0 || strncmp($trimmed, '*', 1) === 0 || strncmp($trimmed, '#', 1) === 0) {
                    continue;
                }

                foreach (self::PATTERNS as $pattern => $label) {
                    if (preg_match($pattern, $line)) {
                        yield $this->finding(
                            message: "{$label} detected in source file. Credentials must not be committed to version control.",
                            file:    $file,
                            line:    $lineNo + 1,
                            fix:     'Move secrets to .env or a secrets manager (AWS Secrets Manager, Vault, etc.).'
                        );

                        break; // One finding per line is enough
                    }
                }
            }
        }
    }
}
