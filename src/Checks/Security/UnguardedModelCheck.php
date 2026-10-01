<?php

namespace ShipReady\Checks\Security;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'SEC006',
    title: 'Model::unguard() called outside seeder context',
    category: 'security',
    severity: 'high'
)]
final class UnguardedModelCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $calls = $context->code()->functionCalls(['unguard']);

        foreach ($calls as $call) {
            // Allow unguard() in seeder files
            $file = $call->file;

            if (str_contains($file, 'Seeder') || str_contains($file, 'seeder') || str_contains($file, '/seeders/')) {
                continue;
            }

            // Only flag static calls like Model::unguard()
            if (!str_contains($call->name, '::unguard')) {
                continue;
            }

            yield $this->finding(
                message: "Model::unguard() called outside of a seeder in {$file}. This disables mass assignment protection globally.",
                file:    $file,
                line:    $call->line,
                fix:     "Remove Model::unguard() or restrict its use to database seeders only. Wrap with Model::reguard() afterwards."
            );
        }
    }
}
