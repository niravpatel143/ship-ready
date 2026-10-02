<?php

namespace ShipReady\Checks\Octane;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'OCT002',
    title: 'Mutable static property — leaks between Octane requests',
    category: 'octane',
    severity: 'high'
)]
final class MutableStaticPropertyCheck extends AbstractCheck
{
    private const IGNORED_PATTERNS = [
        'private static \$instance',   // Singleton pattern — expected
        'protected static \$instance', // Singleton pattern — expected
        'public static \$booted',      // Laravel model booted — reset by framework
        'public static \$resolver',    // Eloquent connection resolver — framework-managed
        'protected static \$booted',
        'protected static \$traitInitializers',
        'protected static \$globalScopes',
    ];

    public function run(Context $context): iterable
    {
        $analyzer = $context->code();

        if ($analyzer === null) {
            return;
        }

        foreach ($analyzer->phpFiles() as $file) {
            $content = file_get_contents($file) ?: '';

            // Only look at non-model, non-migration, non-test files
            if (preg_match('/extends\s+(?:Model|Migration|TestCase|Test)\b/', $content)) {
                continue;
            }

            // Find static properties that look mutable (non-constant, non-readonly)
            if (!preg_match_all(
                '/^\s*(public|protected|private)\s+static\s+(?!readonly\s+)(?!final\s+)\$(\w+)\s*=\s*(?!\[\s*\]|null|false|true|\'\'|""|0)/m',
                $content,
                $matches,
                PREG_SET_ORDER
            )) {
                continue;
            }

            foreach ($matches as $match) {
                $declaration = $match[0];

                $ignored = false;
                foreach (self::IGNORED_PATTERNS as $pattern) {
                    if (str_contains($declaration, substr($pattern, 8))) { // strip "private static "
                        $ignored = true;
                        break;
                    }
                }

                if (!$ignored) {
                    yield $this->finding(
                        message: "Static property \${$match[2]} has a mutable default value. Under Octane this state persists between requests and can corrupt data for different users.",
                        file:    $file,
                        fix:     'Move per-request state to instance properties, or reset static state in an Octane RequestReceived listener.'
                    );
                    break; // one finding per file is enough
                }
            }
        }
    }
}
