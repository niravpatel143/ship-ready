<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC024',
    title: 'Weak password hashing configuration',
    category: 'security',
    severity: 'high'
)]
final class WeakPasswordHashingCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $config = $context->config();

        $driver = $config->get('hashing.driver', 'bcrypt');

        if ($driver === 'bcrypt') {
            $rounds = $config->get('hashing.bcrypt.rounds', 10);

            if ($rounds < 10) {
                yield $this->finding(
                    message: "Bcrypt rounds is set to {$rounds}, which is below the recommended minimum of 10.",
                    fix:     'Set BCRYPT_ROUNDS to at least 10 (12 recommended for modern hardware) in your .env.'
                );
            }
        }

        if ($driver === 'argon') {
            $memory = $config->get('hashing.argon.memory', 65536);

            if ($memory < 65536) {
                yield $this->finding(
                    message: "Argon2 memory cost is {$memory}KB, below the recommended 64MB.",
                    fix:     'Increase hashing.argon.memory to at least 65536 (64MB) in config/hashing.php.'
                );
            }
        }

        // Scan code for md5/sha1 usage near password-related context
        $files = $context->code()->phpFiles();

        foreach ($files as $file) {
            $contents = @file_get_contents($file);

            if ($contents === false) {
                continue;
            }

            $lines = explode("\n", $contents);

            foreach ($lines as $lineNo => $line) {
                if (preg_match('/\b(?:md5|sha1)\s*\(/', $line)) {
                    // Check if it's password-related
                    $context10 = implode(' ', array_slice($lines, max(0, $lineNo - 3), 6));

                    if (preg_match('/password|passwd|pass|credentials|secret/i', $context10)) {
                        yield $this->finding(
                            message: 'md5() or sha1() used in a password-related context. These are not suitable for password hashing.',
                            file:    $file,
                            line:    $lineNo + 1,
                            fix:     'Use Laravel\'s Hash::make() (bcrypt/argon) for password storage.'
                        );
                    }
                }
            }
        }
    }
}
