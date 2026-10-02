<?php

use ShipReady\Commands\BaselineCommand;
use ShipReady\Tests\TestCase;

uses(TestCase::class);

// ---------------------------------------------------------------------------
// ship:baseline --target-env (bug fix: was --env which conflicts with Artisan)
// ---------------------------------------------------------------------------

test('ship:baseline accepts --target-env without crashing', function () {
    // Before fix: crashed "An option named 'env' already exists" because Artisan
    // reserves --env globally. --target-env must be used in the signature instead.
    $this->artisan('ship:baseline', ['--target-env' => 'production'])
        ->assertExitCode(0);
});

test('BaselineCommand signature uses --target-env not --env', function () {
    $command    = app(BaselineCommand::class);
    $definition = $command->getDefinition();

    expect($definition->hasOption('target-env'))->toBeTrue();
    expect($definition->hasOption('env'))->toBeFalse();
});

test('ship:baseline records findings to configured baseline path', function () {
    $path = storage_path('testing-baseline.json');
    @unlink($path);

    $this->artisan('ship:baseline', ['--target-env' => 'production'])
        ->assertExitCode(0);

    expect(file_exists($path))->toBeTrue();
});

test('ship:baseline --prune succeeds without crashing', function () {
    // First create a baseline
    $this->artisan('ship:baseline', ['--target-env' => 'production'])->assertExitCode(0);

    // Then prune it
    $this->artisan('ship:baseline', ['--prune' => true, '--target-env' => 'production'])
        ->assertExitCode(0);
});
