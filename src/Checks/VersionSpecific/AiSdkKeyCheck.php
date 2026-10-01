<?php

namespace ShipReady\Checks\VersionSpecific;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'VER003',
    title: 'AI SDK API key not configured (Laravel 13+)',
    category: 'security',
    severity: 'medium',
    minLaravel: '13.0'
)]
final class AiSdkKeyCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        // Check if Laravel AI SDK is being used (Laravel 13+)
        $aiConfig = config('ai', null);

        if ($aiConfig === null) {
            return;
        }

        $defaultProvider = $aiConfig['default'] ?? null;

        if ($defaultProvider === null) {
            return;
        }

        $apiKey = $aiConfig['providers'][$defaultProvider]['api_key'] ?? null;

        if (empty($apiKey) || $apiKey === 'sk-...') {
            yield $this->finding(
                message: "Laravel AI SDK provider '{$defaultProvider}' has no API key configured.",
                fix:     "Set the AI API key in your .env: OPENAI_API_KEY=sk-... or the relevant provider key."
            );
        }

        // Warn if API key looks like it's hardcoded (not from env)
        if (!empty($apiKey) && !str_starts_with($apiKey, '$') && strlen($apiKey) > 10) {
            // Check if this was pulled from env or is static
            $envValue = env('OPENAI_API_KEY') ?? env('AI_API_KEY');

            if ($envValue === null && $apiKey !== null) {
                yield $this->finding(
                    message: "AI SDK API key may be hardcoded in config instead of loaded from .env.",
                    fix:     'Use env(\'OPENAI_API_KEY\') in config/ai.php and set the key in .env.'
                );
            }
        }
    }
}
