<?php

namespace ShipReady;

use ShipReady\Baseline\Baseline;
use ShipReady\Checks\Filter;
use ShipReady\Compat\VersionGuard;
use ShipReady\Support\Context;
use ShipReady\Support\Finding;
use ShipReady\Support\Report;

final class Runner
{
    private CheckRegistry $registry;
    private Baseline $baseline;

    public function __construct(CheckRegistry $registry, Baseline $baseline)
    {
        $this->registry = $registry;
        $this->baseline = $baseline;
    }

    public function run(Context $context, Filter $filter): Report
    {
        $start    = microtime(true);
        $findings = [];

        $laravelVersion  = $context->laravelVersion();
        $suppressions    = $context->suppressions();

        foreach ($this->registry->matching($filter) as [$meta, $check]) {
            // Skip production-only checks when not targeting production
            if ($meta->productionOnly && !$context->targetsProduction()) {
                continue;
            }

            // Skip checks that don't match the current Laravel version
            if (!VersionGuard::passes($meta, $laravelVersion)) {
                continue;
            }

            try {
                $checkFindings = $check->run($context);

                foreach ($checkFindings as $finding) {
                    if (!($finding instanceof Finding)) {
                        continue;
                    }

                    // Skip suppressed findings
                    if ($suppressions->covers($finding)) {
                        continue;
                    }

                    $findings[] = $finding;
                }
            } catch (\Throwable $e) {
                $findings[] = Finding::internalError($meta->id, $e);
            }
        }

        $split = $this->baseline->split($findings);

        $report = new Report(
            findings:           $findings,
            newFindings:        $split['new'],
            baselinedFindings:  $split['baselined']
        );

        $report->setDuration(microtime(true) - $start);

        return $report;
    }
}
