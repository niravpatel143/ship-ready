<?php

namespace ShipReady\Reporters;

use ShipReady\Support\Finding;
use ShipReady\Support\Report;
use ShipReady\Support\Severity;

final class HtmlReporter implements Reporter
{
    public function render(Report $report): string
    {
        $score    = $report->score();
        $findings = $report->newFindings();
        $counts   = $report->counts();

        $scoreColor = $score >= 80 ? '#22c55e' : ($score >= 50 ? '#eab308' : '#ef4444');

        $findingsHtml = '';

        foreach ($findings as $finding) {
            $severityClass = $finding->severity->value();
            $file          = '';

            if ($finding->file !== null) {
                $file = htmlspecialchars($finding->file);

                if ($finding->line !== null) {
                    $file .= ':' . $finding->line;
                }
            }

            $fix = $finding->fix
                ? '<div class="fix">💡 ' . htmlspecialchars($finding->fix) . '</div>'
                : '';

            $filePart = $file
                ? '<div class="file">' . $file . '</div>'
                : '';

            $findingsHtml .= sprintf(
                '<tr class="severity-%s">
                    <td><span class="badge badge-%s">%s</span></td>
                    <td class="check-id">%s</td>
                    <td>%s%s%s</td>
                </tr>',
                $severityClass,
                $severityClass,
                strtoupper($severityClass),
                htmlspecialchars($finding->checkId),
                htmlspecialchars($finding->message),
                $filePart,
                $fix
            );
        }

        $summaryRows = '';

        foreach ($counts as $severity => $count) {
            if ($count > 0) {
                $summaryRows .= sprintf(
                    '<tr><td><span class="badge badge-%s">%s</span></td><td>%d</td></tr>',
                    $severity,
                    strtoupper($severity),
                    $count
                );
            }
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShipReady Audit Report</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #e2e8f0; padding: 2rem; }
        .container { max-width: 960px; margin: 0 auto; }
        h1 { font-size: 1.8rem; font-weight: 700; margin-bottom: 1.5rem; }
        h2 { font-size: 1.2rem; font-weight: 600; margin: 1.5rem 0 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
        .score-box { background: #1e293b; border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1.5rem; }
        .score-number { font-size: 3rem; font-weight: 800; color: {$scoreColor}; }
        .score-label { color: #94a3b8; font-size: 0.9rem; }
        .score-bar-wrap { flex: 1; background: #334155; border-radius: 9999px; height: 12px; overflow: hidden; }
        .score-bar { height: 100%; background: {$scoreColor}; border-radius: 9999px; width: {$score}%; }
        table { width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 0.75rem; overflow: hidden; }
        th { text-align: left; padding: 0.75rem 1rem; background: #0f172a; color: #94a3b8; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; }
        td { padding: 0.75rem 1rem; border-top: 1px solid #334155; vertical-align: top; }
        .check-id { font-family: monospace; font-size: 0.85rem; color: #94a3b8; }
        .file { font-family: monospace; font-size: 0.75rem; color: #64748b; margin-top: 0.25rem; }
        .fix { font-size: 0.8rem; color: #86efac; margin-top: 0.25rem; }
        .badge { display: inline-block; padding: 0.2rem 0.5rem; border-radius: 0.25rem; font-size: 0.7rem; font-weight: 700; }
        .badge-critical { background: #7f1d1d; color: #fca5a5; }
        .badge-high { background: #7c2d12; color: #fdba74; }
        .badge-medium { background: #713f12; color: #fde047; }
        .badge-low { background: #1e3a5f; color: #93c5fd; }
        .badge-info { background: #1e2a3a; color: #94a3b8; }
        .meta { color: #475569; font-size: 0.8rem; margin-top: 1.5rem; }
        tr:hover td { background: #263248; }
    </style>
</head>
<body>
<div class="container">
    <h1>🚢 ShipReady Audit Report</h1>

    <div class="score-box">
        <div>
            <div class="score-number">{$score}</div>
            <div class="score-label">out of 100</div>
        </div>
        <div style="flex:1">
            <div class="score-bar-wrap"><div class="score-bar"></div></div>
        </div>
    </div>

    <h2>Findings</h2>

    <table>
        <thead>
            <tr>
                <th>Severity</th>
                <th>Check</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            {$findingsHtml}
        </tbody>
    </table>

    <h2>Summary</h2>

    <table>
        <thead>
            <tr><th>Severity</th><th>Count</th></tr>
        </thead>
        <tbody>
            {$summaryRows}
        </tbody>
    </table>

    <p class="meta">Generated on HTML_DATE · Duration: HTML_DURATION</p>
</div>
</body>
</html>
HTML;
    }
}
