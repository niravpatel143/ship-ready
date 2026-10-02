<?php

namespace ShipReady\Checks\Packages;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'LW001',
    title: 'Livewire public property not locked — client can manipulate server state',
    category: 'packages',
    severity: 'high'
)]
final class LivewireLockedPropertyCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        if (!$this->isLivewireV3()) {
            return;
        }

        $analyzer = $context->code();

        if ($analyzer === null) {
            return;
        }

        foreach ($analyzer->phpFiles() as $file) {
            $content = file_get_contents($file) ?: '';

            if (!preg_match('/extends\s+Component\b/', $content)) {
                continue;
            }

            if (!preg_match('/use\s+Livewire/', $content)) {
                continue;
            }

            // Find public properties that lack #[Locked] and are used in security-sensitive ways
            preg_match_all('/public\s+(?:(?:int|string|bool|array|mixed|float)\s+)?\$(\w+)/', $content, $propMatches);
            $publicProps = $propMatches[1] ?? [];

            if (empty($publicProps)) {
                continue;
            }

            // Check if any public prop is used in a query/auth context without #[Locked]
            foreach ($publicProps as $prop) {
                // Skip common non-sensitive names
                if (in_array($prop, ['message', 'search', 'query', 'name', 'email', 'title', 'content', 'text'], true)) {
                    continue;
                }

                // Flag props that look like IDs or sensitive data
                if (
                    preg_match('/Id$|_id$|Token|Secret|Key|Password/i', $prop) &&
                    !preg_match('/#\[Locked\]/', substr($content, 0, strpos($content, 'public') ?: 0) . $content)
                ) {
                    yield $this->finding(
                        message: "Livewire component has public \${$prop} that looks like a sensitive identifier. Without #[Locked], clients can override this value via HTTP.",
                        file:    $file,
                        fix:     "Add #[Locked] attribute above the property: #[Locked] public int \${$prop}; — this prevents clients from updating it."
                    );
                    break;
                }
            }
        }
    }

    private function isLivewireV3(): bool
    {
        $composerLock = base_path('composer.lock');

        if (!file_exists($composerLock)) {
            return false;
        }

        $lock     = json_decode(file_get_contents($composerLock) ?: '{}', true);
        $packages = $lock['packages'] ?? [];

        foreach ($packages as $pkg) {
            if ($pkg['name'] === 'livewire/livewire') {
                return version_compare($pkg['version'] ?? '0', '3.0', '>=');
            }
        }

        return false;
    }
}
