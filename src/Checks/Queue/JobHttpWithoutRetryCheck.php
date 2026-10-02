<?php

namespace ShipReady\Checks\Queue;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'QUE002',
    title: 'Queued job makes HTTP call without retry logic',
    category: 'queue',
    severity: 'medium'
)]
final class JobHttpWithoutRetryCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $analyzer = $context->code();

        if ($analyzer === null) {
            return;
        }

        foreach ($analyzer->phpFiles() as $file) {
            $content = file_get_contents($file) ?: '';

            if (!preg_match('/implements\s+ShouldQueue/', $content)) {
                continue;
            }

            // Check for Http::get/post/put/patch/delete without retry
            if (!preg_match('/Http::|Guzzle|GuzzleHttp|curl_exec/', $content)) {
                continue;
            }

            if (preg_match('/->retry\(|Http::retry\(/', $content)) {
                continue; // Has retry
            }

            if (preg_match('/public\s+(?:int\s+)?\$tries\s*=\s*([2-9]\d*|[1-9]\d+)/', $content)) {
                continue; // $tries > 1, job-level retry is acceptable
            }

            yield $this->finding(
                message: 'This queued job makes an HTTP call but has no retry configuration. Transient network failures will permanently fail the job.',
                file:    $file,
                fix:     'Add Http::retry(3, 100) before the request, or set public $tries = 3 and public $backoff = [5, 30, 60] on the job class.'
            );
        }
    }
}
