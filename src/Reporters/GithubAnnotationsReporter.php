<?php

namespace ShipReady\Reporters;

use ShipReady\Support\Report;
use ShipReady\Support\Severity;

final class GithubAnnotationsReporter implements Reporter
{
    public function render(Report $report): string
    {
        $lines = [];

        foreach ($report->newFindings() as $finding) {
            $level = match ($finding->severity->value()) {
                Severity::CRITICAL, Severity::HIGH => 'error',
                Severity::MEDIUM                   => 'warning',
                Severity::LOW, Severity::INFO       => 'notice',
                default                             => 'warning',
            };

            $file = $finding->file ?? '';
            $line = $finding->line ?? 1;

            // Normalize file path to be relative to workspace
            $file = str_replace('\\', '/', $file);
            $base = str_replace('\\', '/', base_path());

            if (strncmp($file, $base, strlen($base)) === 0) {
                $file = ltrim(substr($file, strlen($base)), '/');
            }

            $message = str_replace('%', '%25', $finding->message);
            $message = str_replace("\r", '%0D', $message);
            $message = str_replace("\n", '%0A', $message);

            $title = sprintf('[%s] %s', $finding->checkId, strtoupper($finding->severity->value()));
            $title = str_replace('%', '%25', $title);
            $title = str_replace("\n", '%0A', $title);

            if ($file) {
                $lines[] = sprintf(
                    '::%s file=%s,line=%d,title=%s::%s',
                    $level,
                    $file,
                    $line,
                    $title,
                    $message
                );
            } else {
                $lines[] = sprintf(
                    '::%s title=%s::%s',
                    $level,
                    $title,
                    $message
                );
            }
        }

        return implode("\n", $lines);
    }
}
