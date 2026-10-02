<?php

namespace ShipReady\Checks\Queue;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'QUE004',
    title: 'Scheduled commands not protected against overlapping runs',
    category: 'queue',
    severity: 'medium'
)]
final class SchedulerOverlappingCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $analyzer = $context->code();

        if ($analyzer === null) {
            return;
        }

        foreach ($analyzer->phpFiles() as $file) {
            $content = file_get_contents($file) ?: '';

            // Look for schedule definitions without withoutOverlapping
            if (!preg_match('/\$schedule->/', $content)) {
                continue;
            }

            // Check if any schedule chain lacks withoutOverlapping
            if (
                preg_match('/\$schedule->(?:command|job|call)\(/', $content) &&
                !preg_match('/->withoutOverlapping\(/', $content)
            ) {
                yield $this->finding(
                    message: 'Scheduled tasks found without ->withoutOverlapping(). If a task takes longer than its schedule interval, multiple instances will overlap and compete for resources.',
                    file:    $file,
                    fix:     'Add ->withoutOverlapping() to long-running scheduled tasks, or use ->runInBackground() for non-blocking execution.'
                );
                break;
            }
        }
    }
}
