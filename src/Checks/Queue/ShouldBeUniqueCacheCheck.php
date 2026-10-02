<?php

namespace ShipReady\Checks\Queue;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'QUE003',
    title: 'ShouldBeUnique job with non-atomic cache driver',
    category: 'queue',
    severity: 'medium'
)]
final class ShouldBeUniqueCacheCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        $analyzer = $context->code();

        if ($analyzer === null) {
            return;
        }

        $hasUniqueJob = false;

        foreach ($analyzer->phpFiles() as $file) {
            $content = file_get_contents($file) ?: '';

            if (preg_match('/implements\s+.*ShouldBeUnique/', $content)) {
                $hasUniqueJob = true;
                break;
            }
        }

        if (!$hasUniqueJob) {
            return;
        }

        $cacheDriver = config('cache.default', 'file');
        $nonAtomic   = ['file', 'array', 'null'];

        if (in_array($cacheDriver, $nonAtomic, true)) {
            yield $this->finding(
                message: "ShouldBeUnique jobs require an atomic cache store, but the default cache driver is '{$cacheDriver}'. Uniqueness is not guaranteed — duplicate jobs will run concurrently.",
                fix:     'Switch CACHE_DRIVER to redis or memcached to ensure ShouldBeUnique locks are atomic across workers.'
            );
        }
    }
}
