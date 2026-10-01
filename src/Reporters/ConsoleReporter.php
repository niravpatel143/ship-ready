<?php

namespace ShipReady\Reporters;

use ShipReady\Support\Finding;
use ShipReady\Support\Report;
use ShipReady\Support\Severity;

final class ConsoleReporter implements Reporter
{
    private bool $compact;
    private bool $noAnsi;
    private ?string $editor;

    public function __construct(
        bool $compact = false,
        bool $noAnsi = false,
        ?string $editor = null
    ) {
        $this->compact = $compact;
        $this->noAnsi  = $noAnsi;
        $this->editor  = $editor;
    }

    public function render(Report $report): string
    {
        $lines = [];

        $lines[] = '';
        $lines[] = $this->bold('  ShipReady Audit Report');
        $lines[] = '';

        // Score bar
        $score   = $report->score();
        $lines[] = $this->renderScoreBar($score);
        $lines[] = '';

        $findings = $report->newFindings();

        if (empty($findings)) {
            $lines[] = $this->green('  ✔ No issues found. Your app is ship-ready!');
            $lines[] = '';
        } else {
            // Group by category
            $grouped = $this->groupByCategory($findings);

            foreach ($grouped as $category => $catFindings) {
                $lines[] = $this->bold('  ' . strtoupper($category));
                $lines[] = '';

                foreach ($catFindings as $finding) {
                    $lines[] = $this->renderFinding($finding);

                    if (!$this->compact && $finding->fix !== null) {
                        $lines[] = $this->dim('    Fix: ' . $finding->fix);
                    }

                    if (!$this->compact && $finding->file !== null) {
                        $fileRef = $finding->file;

                        if ($finding->line !== null) {
                            $fileRef .= ':' . $finding->line;
                        }

                        $lines[] = $this->dim('    ' . $fileRef);
                    }

                    $lines[] = '';
                }
            }
        }

        // Baseline notice
        $baselined = $report->baselined();

        if (!empty($baselined)) {
            $lines[] = $this->dim(sprintf(
                '  %d finding(s) suppressed by baseline.',
                count($baselined)
            ));
            $lines[] = '';
        }

        // Summary
        $counts = $report->counts();
        $total  = array_sum($counts);

        $lines[] = $this->renderSummaryLine($counts, $total, $report->duration());
        $lines[] = '';

        return implode("\n", $lines);
    }

    private function renderScoreBar(int $score): string
    {
        $width  = 40;
        $filled = (int)round($score / 100 * $width);
        $empty  = $width - $filled;

        $color = $score >= 80 ? 'green' : ($score >= 50 ? 'yellow' : 'red');

        // Color only the filled portion; empty portion is always dim
        $bar  = $filled > 0 ? $this->colorize(str_repeat('█', $filled), $color) : '';
        $bar .= $empty  > 0 ? $this->dim(str_repeat('░', $empty)) : '';

        $label = $this->colorize("{$score}/100", $color);

        return sprintf('  Score  %s %s', $bar, $label);
    }

    private function renderFinding(Finding $finding): string
    {
        $severityLabel = strtoupper($finding->severity->value());
        $icon          = $this->severityIcon($finding->severity->value());
        $coloredIcon   = $this->colorizeSeverity($icon . ' ' . $severityLabel, $finding->severity->value());

        return sprintf(
            '  %s [%s] %s',
            $coloredIcon,
            $finding->checkId,
            $finding->message
        );
    }

    private function severityIcon(string $severity): string
    {
        return match ($severity) {
            Severity::CRITICAL => '✖',
            Severity::HIGH     => '✖',
            Severity::MEDIUM   => '▲',
            Severity::LOW      => '●',
            Severity::INFO     => 'ℹ',
            default            => '?',
        };
    }

    private function renderSummaryLine(array $counts, int $total, float $duration): string
    {
        if ($total === 0) {
            return $this->green(sprintf('  ✔ 0 issues found in %.2fs', $duration));
        }

        $parts = [];

        if ($counts[Severity::CRITICAL] > 0) {
            $parts[] = $this->red($counts[Severity::CRITICAL] . ' critical');
        }

        if ($counts[Severity::HIGH] > 0) {
            $parts[] = $this->red($counts[Severity::HIGH] . ' high');
        }

        if ($counts[Severity::MEDIUM] > 0) {
            $parts[] = $this->yellow($counts[Severity::MEDIUM] . ' medium');
        }

        if ($counts[Severity::LOW] > 0) {
            $parts[] = $this->dim($counts[Severity::LOW] . ' low');
        }

        if ($counts[Severity::INFO] > 0) {
            $parts[] = $this->dim($counts[Severity::INFO] . ' info');
        }

        return sprintf(
            '  Found %d issue(s): %s in %.2fs',
            $total,
            implode(', ', $parts),
            $duration
        );
    }

    private function groupByCategory(array $findings): array
    {
        $grouped = [];

        foreach ($findings as $finding) {
            $category = $this->detectCategory($finding->checkId);

            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }

            $grouped[$category][] = $finding;
        }

        ksort($grouped);

        return $grouped;
    }

    private function detectCategory(string $checkId): string
    {
        if (strncmp($checkId, 'SEC', 3) === 0) {
            return 'security';
        }

        if (strncmp($checkId, 'PERF', 4) === 0) {
            return 'performance';
        }

        if (strncmp($checkId, 'REL', 3) === 0) {
            return 'reliability';
        }

        if (strncmp($checkId, 'VER', 3) === 0) {
            return 'version_specific';
        }

        return 'other';
    }

    private function colorize(string $text, string $color): string
    {
        if ($this->noAnsi) {
            return $text;
        }

        $codes = [
            'red'    => "\e[31m",
            'green'  => "\e[32m",
            'yellow' => "\e[33m",
            'blue'   => "\e[34m",
            'cyan'   => "\e[36m",
            'white'  => "\e[37m",
            'dim'    => "\e[2m",
            'bold'   => "\e[1m",
            'reset'  => "\e[0m",
        ];

        return ($codes[$color] ?? '') . $text . $codes['reset'];
    }

    private function colorizeSeverity(string $text, string $severity): string
    {
        $color = match ($severity) {
            Severity::CRITICAL => 'red',
            Severity::HIGH     => 'red',
            Severity::MEDIUM   => 'yellow',
            Severity::LOW      => 'cyan',
            Severity::INFO     => 'dim',
            default            => 'white',
        };

        return $this->colorize($text, $color);
    }

    private function bold(string $text): string
    {
        return $this->colorize($text, 'bold');
    }

    private function dim(string $text): string
    {
        return $this->colorize($text, 'dim');
    }

    private function green(string $text): string
    {
        return $this->colorize($text, 'green');
    }

    private function red(string $text): string
    {
        return $this->colorize($text, 'red');
    }

    private function yellow(string $text): string
    {
        return $this->colorize($text, 'yellow');
    }
}
