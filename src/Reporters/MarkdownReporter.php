<?php

namespace ShipReady\Reporters;

use ShipReady\Support\Finding;
use ShipReady\Support\Report;
use ShipReady\Support\Severity;

final class MarkdownReporter implements Reporter
{
    public function render(Report $report): string
    {
        $lines    = [];
        $score    = $report->score();
        $findings = $report->newFindings();
        $counts   = $report->counts();

        $lines[] = '## ShipReady Audit Report';
        $lines[] = '';
        $lines[] = sprintf('**Score: %d/100**', $score);
        $lines[] = '';

        // Badge-style summary
        $badge = $this->scoreBadge($score);
        $lines[] = $badge;
        $lines[] = '';

        if (empty($findings)) {
            $lines[] = '> ✅ No issues found. Your app is ship-ready!';
            $lines[] = '';
        } else {
            $lines[] = '### Findings';
            $lines[] = '';
            $lines[] = '| Severity | Check | Message | File |';
            $lines[] = '|----------|-------|---------|------|';

            foreach ($findings as $finding) {
                $severity = $this->severityBadge($finding->severity->value());
                $file     = '';

                if ($finding->file !== null) {
                    $file = '`' . basename($finding->file) . '`';

                    if ($finding->line !== null) {
                        $file .= ':' . $finding->line;
                    }
                }

                $message  = str_replace('|', '\\|', $finding->message);
                $lines[] = sprintf(
                    '| %s | `%s` | %s | %s |',
                    $severity,
                    $finding->checkId,
                    $message,
                    $file
                );
            }

            $lines[] = '';

            // Fixes section
            $withFixes = array_filter($findings, fn(Finding $f) => $f->fix !== null);

            if (!empty($withFixes)) {
                $lines[] = '### Suggested Fixes';
                $lines[] = '';

                foreach ($withFixes as $finding) {
                    $lines[] = sprintf('- **[%s]** %s', $finding->checkId, $finding->fix);
                }

                $lines[] = '';
            }
        }

        // Counts summary
        $lines[] = '### Summary';
        $lines[] = '';
        $lines[] = '| Severity | Count |';
        $lines[] = '|----------|-------|';

        foreach ($counts as $severity => $count) {
            $lines[] = sprintf('| %s | %d |', $this->severityBadge($severity), $count);
        }

        $lines[] = '';
        $lines[] = sprintf(
            '*Generated at %s in %.2fs*',
            date('Y-m-d H:i:s'),
            $report->duration()
        );

        return implode("\n", $lines);
    }

    private function severityBadge(string $severity): string
    {
        return match ($severity) {
            Severity::CRITICAL => '🔴 Critical',
            Severity::HIGH     => '🟠 High',
            Severity::MEDIUM   => '🟡 Medium',
            Severity::LOW      => '🔵 Low',
            Severity::INFO     => 'ℹ️ Info',
            default            => $severity,
        };
    }

    private function scoreBadge(int $score): string
    {
        if ($score >= 80) {
            return sprintf('![Score](https://img.shields.io/badge/ShipReady%%20Score-%d%%2F100-brightgreen)', $score);
        }

        if ($score >= 50) {
            return sprintf('![Score](https://img.shields.io/badge/ShipReady%%20Score-%d%%2F100-yellow)', $score);
        }

        return sprintf('![Score](https://img.shields.io/badge/ShipReady%%20Score-%d%%2F100-red)', $score);
    }
}
