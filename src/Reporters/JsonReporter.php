<?php

namespace ShipReady\Reporters;

use ShipReady\Support\Report;

final class JsonReporter implements Reporter
{
    public function render(Report $report): string
    {
        $findings = [];

        foreach ($report->newFindings() as $finding) {
            $findings[] = [
                'check_id'    => $finding->checkId,
                'message'     => $finding->message,
                'severity'    => $finding->severity->value(),
                'file'        => $finding->file,
                'line'        => $finding->line,
                'fix'         => $finding->fix,
                'fingerprint' => $finding->fingerprint(),
                'context'     => $finding->context,
            ];
        }

        $data = [
            'score'    => $report->score(),
            'findings' => $findings,
            'counts'   => $report->counts(),
            'metadata' => [
                'laravel_version' => \ShipReady\Compat\LaravelVersion::detect(),
                'php_version'     => PHP_VERSION,
                'generated_at'    => date('c'),
                'duration'        => $report->duration(),
                'baselined_count' => count($report->baselined()),
            ],
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
