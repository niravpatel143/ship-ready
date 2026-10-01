<?php

namespace ShipReady\Checks\Performance;

use ShipReady\Checks\AbstractCheck;
use ShipReady\Checks\CheckMeta;
use ShipReady\Support\Context;

#[CheckMeta(
    id: 'PERF009',
    title: 'Lazy loading not prevented in development',
    category: 'performance',
    severity: 'low'
)]
final class LazyLoadingNotPreventedCheck extends AbstractCheck
{
    public function run(Context $context): iterable
    {
        // Check if Model::preventLazyLoading() is called in AppServiceProvider
        $providerFile = app_path('Providers/AppServiceProvider.php');

        if (!file_exists($providerFile)) {
            return;
        }

        $contents = file_get_contents($providerFile);

        if ($contents === false) {
            return;
        }

        if (!str_contains($contents, 'preventLazyLoading')) {
            yield $this->finding(
                message: 'Model::preventLazyLoading() is not called in AppServiceProvider. N+1 queries will silently occur in development.',
                file:    $providerFile,
                fix:     'Add Model::preventLazyLoading(!app()->isProduction()); in AppServiceProvider::boot() to detect N+1 queries during development.'
            );
        }
    }
}
