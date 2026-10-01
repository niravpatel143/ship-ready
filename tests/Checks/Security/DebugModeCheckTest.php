<?php

use ShipReady\Analyzers\BladeAnalyzer;
use ShipReady\Analyzers\CodeAnalyzer;
use ShipReady\Analyzers\ConfigAnalyzer;
use ShipReady\Analyzers\DependencyAnalyzer;
use ShipReady\Analyzers\EnvironmentAnalyzer;
use ShipReady\Analyzers\RouteAnalyzer;
use ShipReady\Checks\Security\DebugModeCheck;
use ShipReady\Support\Context;
use ShipReady\Support\Severity;

function makeContext(array $configOverrides = [], string $env = 'production'): Context
{
    $config = array_merge([
        'app' => [
            'debug' => false,
            'env'   => $env,
            'key'   => 'base64:' . base64_encode(str_repeat('x', 32)),
            'name'  => 'TestApp',
            'url'   => 'https://example.com',
        ],
    ], $configOverrides);

    $configRepo = new \Illuminate\Config\Repository($config);

    return new Context(
        analyzers: [
            new ConfigAnalyzer($configRepo, $env),
        ],
        targetEnv:      $env,
        laravelVersion: '11.0.0'
    );
}

test('SEC001 passes when debug is false', function () {
    $context = makeContext(['app' => ['debug' => false, 'env' => 'production', 'key' => 'base64:' . base64_encode(str_repeat('x', 32)), 'name' => 'App', 'url' => 'https://example.com']]);

    $check    = new DebugModeCheck();
    $findings = iterator_to_array($check->run($context));

    expect($findings)->toBeEmpty();
});

test('SEC001 fails when debug is true in production', function () {
    $context = makeContext(['app' => ['debug' => true, 'env' => 'production', 'key' => 'base64:' . base64_encode(str_repeat('x', 32)), 'name' => 'App', 'url' => 'https://example.com']]);

    $check    = new DebugModeCheck();
    $findings = iterator_to_array($check->run($context));

    expect($findings)->toHaveCount(1);
    expect($findings[0]->checkId)->toBe('SEC001');
    expect($findings[0]->severity->value())->toBe(Severity::CRITICAL);
    expect($findings[0]->message)->toContain('APP_DEBUG');
});

test('SEC001 check ID is SEC001', function () {
    $check  = new DebugModeCheck();
    $meta   = \ShipReady\Checks\CheckMetaReader::for(DebugModeCheck::class);

    expect($meta->id)->toBe('SEC001');
    expect($meta->productionOnly)->toBeTrue();
    expect($meta->severity)->toBe('critical');
});

test('SEC001 finding has a fix suggestion', function () {
    $context = makeContext(['app' => ['debug' => true, 'env' => 'production', 'key' => 'base64:' . base64_encode(str_repeat('x', 32)), 'name' => 'App', 'url' => 'https://example.com']]);

    $check    = new DebugModeCheck();
    $findings = iterator_to_array($check->run($context));

    expect($findings[0]->fix)->not->toBeNull();
    expect($findings[0]->fix)->toContain('APP_DEBUG=false');
});
