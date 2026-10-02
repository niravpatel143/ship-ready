<?php

use ShipReady\Checks\CheckMeta;
use ShipReady\Checks\Filter;

// ---------------------------------------------------------------------------
// Category filtering
// ---------------------------------------------------------------------------

test('Filter passes check matching the requested category', function () {
    $filter = new Filter(category: 'security');
    $meta   = new CheckMeta('SEC001', 'Debug mode', 'security', 'critical');

    expect($filter->matches($meta))->toBeTrue();
});

test('Filter blocks check not in the requested category', function () {
    $filter = new Filter(category: 'security');
    $meta   = new CheckMeta('PERF001', 'Config not cached', 'performance', 'medium');

    expect($filter->matches($meta))->toBeFalse();
});

test('Filter passes any category when no category constraint given', function () {
    $filter = new Filter();
    $meta   = new CheckMeta('REL001', 'Pending migrations', 'reliability', 'high');

    expect($filter->matches($meta))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Check ID filtering
// ---------------------------------------------------------------------------

test('Filter passes check matching the requested checkId', function () {
    $filter = new Filter(checkId: 'SEC001');
    $meta   = new CheckMeta('SEC001', 'Debug mode', 'security', 'critical');

    expect($filter->matches($meta))->toBeTrue();
});

test('Filter blocks check with different checkId', function () {
    $filter = new Filter(checkId: 'SEC001');
    $meta   = new CheckMeta('SEC002', 'App key missing', 'security', 'critical');

    expect($filter->matches($meta))->toBeFalse();
});

// ---------------------------------------------------------------------------
// Experimental checks
// ---------------------------------------------------------------------------

test('Filter blocks experimental checks by default', function () {
    $filter = new Filter();
    $meta   = new CheckMeta('SEC099', 'Experimental', 'security', 'info', experimental: true);

    expect($filter->matches($meta))->toBeFalse();
});

test('Filter passes experimental checks when includeExperimental is true', function () {
    $filter = new Filter(includeExperimental: true);
    $meta   = new CheckMeta('SEC099', 'Experimental', 'security', 'info', experimental: true);

    expect($filter->matches($meta))->toBeTrue();
});

// ---------------------------------------------------------------------------
// CI mode — infrastructure excluded
// ---------------------------------------------------------------------------

test('Filter excludes infrastructure checks in CI mode', function () {
    $filter = new Filter(ciMode: true);
    $meta   = new CheckMeta('INF001', 'Root user', 'infrastructure', 'high');

    expect($filter->matches($meta))->toBeFalse();
});

test('Filter passes security checks in CI mode', function () {
    $filter = new Filter(ciMode: true);
    $meta   = new CheckMeta('SEC001', 'Debug mode', 'security', 'critical');

    expect($filter->matches($meta))->toBeTrue();
});

test('Filter passes performance checks in CI mode', function () {
    $filter = new Filter(ciMode: true);
    $meta   = new CheckMeta('PERF001', 'Config cache', 'performance', 'medium');

    expect($filter->matches($meta))->toBeTrue();
});

test('Filter passes reliability checks in CI mode', function () {
    $filter = new Filter(ciMode: true);
    $meta   = new CheckMeta('REL001', 'Pending migrations', 'reliability', 'high');

    expect($filter->matches($meta))->toBeTrue();
});

test('Filter passes infrastructure checks when ciMode is false', function () {
    $filter = new Filter(ciMode: false);
    $meta   = new CheckMeta('INF001', 'Root user', 'infrastructure', 'high');

    expect($filter->matches($meta))->toBeTrue();
});

// ---------------------------------------------------------------------------
// Combined constraints
// ---------------------------------------------------------------------------

test('Filter can combine category and ciMode constraints', function () {
    $filter = new Filter(category: 'infrastructure', ciMode: true);
    $meta   = new CheckMeta('INF002', '.env in image', 'infrastructure', 'critical');

    // ciMode blocks infrastructure even when category filter matches
    expect($filter->matches($meta))->toBeFalse();
});
