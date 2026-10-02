<?php

use ShipReady\Reporters\ConsoleReporter;
use ShipReady\Support\Finding;
use ShipReady\Support\Report;
use ShipReady\Support\Severity;

function makeReport(array $findings = [], array $baselined = []): Report
{
    $report = new Report(
        findings:          $findings,
        newFindings:       $findings,
        baselinedFindings: $baselined
    );
    $report->setDuration(0.5);

    return $report;
}

function makeFinding(string $id, string $severity, ?string $fix = null): Finding
{
    return new Finding(
        checkId:  $id,
        message:  "Test finding for {$id}",
        severity: Severity::fromString($severity),
        fix:      $fix
    );
}

// ---------------------------------------------------------------------------
// Header
// ---------------------------------------------------------------------------

test('ConsoleReporter header contains v1.1.0', function () {
    $reporter = new ConsoleReporter(noAnsi: true);
    $output   = $reporter->render(makeReport());

    expect($output)->toContain('v1.1.0');
});

test('ConsoleReporter header contains ShipReady Audit Report', function () {
    $reporter = new ConsoleReporter(noAnsi: true);
    $output   = $reporter->render(makeReport());

    expect($output)->toContain('ShipReady Audit Report');
});

// ---------------------------------------------------------------------------
// Category labels — no underscores
// ---------------------------------------------------------------------------

test('ConsoleReporter renders VERSION SPECIFIC without underscore', function () {
    $finding  = makeFinding('VER001', 'medium');
    $reporter = new ConsoleReporter(noAnsi: true);
    $output   = $reporter->render(makeReport([$finding]));

    expect($output)->toContain('VERSION SPECIFIC');
    expect($output)->not->toContain('VERSION_SPECIFIC');
});

test('ConsoleReporter renders INFRASTRUCTURE in uppercase', function () {
    $finding  = makeFinding('INF001', 'high');
    $reporter = new ConsoleReporter(noAnsi: true);
    $output   = $reporter->render(makeReport([$finding]));

    expect($output)->toContain('INFRASTRUCTURE');
});

test('ConsoleReporter renders SECURITY in uppercase', function () {
    $finding  = makeFinding('SEC001', 'critical');
    $reporter = new ConsoleReporter(noAnsi: true);
    $output   = $reporter->render(makeReport([$finding]));

    expect($output)->toContain('SECURITY');
});

// ---------------------------------------------------------------------------
// Score
// ---------------------------------------------------------------------------

test('ConsoleReporter shows 0/100 when penalties exceed 100', function () {
    $findings = array_map(
        fn($i) => makeFinding("SEC0{$i}", 'critical'),
        range(10, 17)
    ); // 8 × critical(15) = 120 penalty → 0/100
    $reporter = new ConsoleReporter(noAnsi: true);
    $output   = $reporter->render(makeReport($findings));

    expect($output)->toContain('0/100');
});

test('ConsoleReporter shows 100/100 with no findings', function () {
    $reporter = new ConsoleReporter(noAnsi: true);
    $output   = $reporter->render(makeReport([]));

    expect($output)->toContain('100/100');
});

test('ConsoleReporter score decrements correctly for one high finding', function () {
    $findings = [makeFinding('SEC001', 'high')]; // -8
    $reporter = new ConsoleReporter(noAnsi: true);
    $output   = $reporter->render(makeReport($findings));

    expect($output)->toContain('92/100');
});

// ---------------------------------------------------------------------------
// Summary line
// ---------------------------------------------------------------------------

test('ConsoleReporter summary shows no issues message when empty', function () {
    $reporter = new ConsoleReporter(noAnsi: true);
    $output   = $reporter->render(makeReport([]));

    expect($output)->toContain('No issues found');
});

test('ConsoleReporter summary counts findings correctly', function () {
    $findings = [
        makeFinding('SEC001', 'critical'),
        makeFinding('SEC002', 'high'),
        makeFinding('PERF001', 'medium'),
    ];
    $reporter = new ConsoleReporter(noAnsi: true);
    $output   = $reporter->render(makeReport($findings));

    expect($output)->toContain('Found 3 issue(s)');
    expect($output)->toContain('1 critical');
    expect($output)->toContain('1 high');
    expect($output)->toContain('1 medium');
});

// ---------------------------------------------------------------------------
// Compact mode suppresses fix hints
// ---------------------------------------------------------------------------

test('ConsoleReporter compact mode omits fix hints', function () {
    $finding  = makeFinding('SEC001', 'critical', 'Set APP_DEBUG=false');
    $reporter = new ConsoleReporter(compact: true, noAnsi: true);
    $output   = $reporter->render(makeReport([$finding]));

    expect($output)->not->toContain('Set APP_DEBUG=false');
});

test('ConsoleReporter non-compact mode includes fix hints', function () {
    $finding  = makeFinding('SEC001', 'critical', 'Set APP_DEBUG=false');
    $reporter = new ConsoleReporter(compact: false, noAnsi: true);
    $output   = $reporter->render(makeReport([$finding]));

    expect($output)->toContain('Set APP_DEBUG=false');
});
