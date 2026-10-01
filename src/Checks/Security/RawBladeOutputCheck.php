<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC008',
    title: 'Unescaped raw blade output ({!! !!})',
    category: 'security',
    severity: 'medium'
)]
final class RawBladeOutputCheck extends AbstractCheck
{
    // Expressions that are safe to render raw (trusted HTML)
    private const SAFE_PATTERNS = [
        '/^\$slot$/',
        '/^\$__env->renderComponent/',
        '/^isset\(\$slot\)/',
        '/->toHtml\(\)$/',
        '/^\$errors->/',
    ];

    public function run(Context $context): iterable
    {
        $echoes = $context->blade()->rawEchoes();

        foreach ($echoes as $echo) {
            $expression = trim($echo->expression);

            $safe = false;

            foreach (self::SAFE_PATTERNS as $pattern) {
                if (preg_match($pattern, $expression)) {
                    $safe = true;
                    break;
                }
            }

            if (!$safe) {
                yield $this->finding(
                    message: "Raw (unescaped) Blade output: {!! {$expression} !!}. This may expose XSS vulnerabilities if the value contains user input.",
                    file:    $echo->file,
                    line:    $echo->line,
                    fix:     'Use {{ $var }} for automatic HTML escaping unless you are intentionally rendering trusted HTML.'
                );
            }
        }
    }
}
