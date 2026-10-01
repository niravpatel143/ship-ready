<?php

namespace ShipReady\Checks\VersionSpecific;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'VER005',
    title: 'Queued job class missing ShouldQueue interface',
    category: 'reliability',
    severity: 'medium'
)]
final class DeadQueueJobCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $jobsPath = app_path('Jobs');

        if (!is_dir($jobsPath)) {
            return;
        }

        $files = glob($jobsPath . '/*.php');

        foreach ($files as $file) {
            $contents = @file_get_contents($file);

            if ($contents === false) {
                continue;
            }

            // Check if the file defines a class
            if (!preg_match('/class\s+(\w+)/', $contents, $matches)) {
                continue;
            }

            $className = $matches[1];

            // Check if it implements ShouldQueue
            if (!str_contains($contents, 'ShouldQueue')) {
                // Check if it has dispatch or dispatchNow usage somewhere in the codebase
                $dispatchCalls = $context->code()->grep("/\\b{$className}::dispatch\\b/");

                if (!empty($dispatchCalls)) {
                    yield $this->finding(
                        message: "Job class {$className} is dispatched to the queue but does not implement ShouldQueue. It will run synchronously.",
                        file:    $file,
                        fix:     "Add 'implements ShouldQueue' to {$className} and use the InteractsWithQueue trait."
                    );
                }
            }
        }
    }
}
