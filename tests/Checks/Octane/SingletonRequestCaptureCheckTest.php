<?php

use ShipReady\Analyzers\CodeAnalyzer;
use ShipReady\Checks\CheckMetaReader;
use ShipReady\Checks\Octane\SingletonRequestCaptureCheck;
use ShipReady\Support\Context;
use ShipReady\Support\Severity;

function makeOctaneContext(string $phpContent): Context
{
    $dir = sys_get_temp_dir() . '/ship-ready-oct-' . uniqid();
    mkdir($dir);
    file_put_contents("{$dir}/AppServiceProvider.php", $phpContent);

    $analyzer = new CodeAnalyzer([$dir]);

    return new Context(
        analyzers:      [$analyzer],
        targetEnv:      'production',
        laravelVersion: '11.0.0'
    );
}

// ---------------------------------------------------------------------------
// OCT001: metadata
// ---------------------------------------------------------------------------

test('OCT001 meta id is OCT001', function () {
    $meta = CheckMetaReader::for(SingletonRequestCaptureCheck::class);

    expect($meta->id)->toBe('OCT001');
    expect($meta->category)->toBe('octane');
    expect($meta->severity)->toBe(Severity::CRITICAL);
});

// ---------------------------------------------------------------------------
// OCT001: true positives
// ---------------------------------------------------------------------------

test('OCT001 triggers when singleton captures request() call', function () {
    $context = makeOctaneContext('<?php
class AppServiceProvider {
    public function register() {
        $this->app->singleton("service", function ($app) {
            $request = request();
            return new MyService($request);
        });
    }
}');

    $check    = new SingletonRequestCaptureCheck();
    $findings = iterator_to_array($check->run($context));

    expect($findings)->not->toBeEmpty();
    expect($findings[0]->checkId)->toBe('OCT001');
    expect($findings[0]->severity->value())->toBe(Severity::CRITICAL);
});

test('OCT001 triggers when singleton captures auth()->user()', function () {
    $context = makeOctaneContext('<?php
class AppServiceProvider {
    public function register() {
        $this->app->singleton("svc", function ($app) {
            $user = auth()->user();
            return new UserService($user);
        });
    }
}');

    $check    = new SingletonRequestCaptureCheck();
    $findings = iterator_to_array($check->run($context));

    expect($findings)->not->toBeEmpty();
    expect($findings[0]->checkId)->toBe('OCT001');
});

test('OCT001 triggers when singleton captures Auth::user()', function () {
    $context = makeOctaneContext('<?php
class ServiceProvider {
    public function register() {
        $this->app->singleton("svc", function ($app) {
            return new MyService(Auth::user());
        });
    }
}');

    $check    = new SingletonRequestCaptureCheck();
    $findings = iterator_to_array($check->run($context));

    expect($findings)->not->toBeEmpty();
});

// ---------------------------------------------------------------------------
// OCT001: true negatives
// ---------------------------------------------------------------------------

test('OCT001 passes when singleton does not capture request', function () {
    $context = makeOctaneContext('<?php
class AppServiceProvider {
    public function register() {
        $this->app->singleton(MyService::class, function ($app) {
            return new MyService($app->make(Config::class));
        });
    }
}');

    $check    = new SingletonRequestCaptureCheck();
    $findings = iterator_to_array($check->run($context));

    expect($findings)->toBeEmpty();
});

test('OCT001 passes for files with no singleton calls', function () {
    $context = makeOctaneContext('<?php
class MyController {
    public function index() {
        $request = request();
        return view("home");
    }
}');

    $check    = new SingletonRequestCaptureCheck();
    $findings = iterator_to_array($check->run($context));

    expect($findings)->toBeEmpty();
});

// ---------------------------------------------------------------------------
// OCT001: finding has fix suggestion
// ---------------------------------------------------------------------------

test('OCT001 finding includes a fix suggestion', function () {
    $context = makeOctaneContext('<?php
class Provider {
    public function register() {
        $this->app->singleton("svc", function ($app) {
            $request = request();
            return new Svc($request);
        });
    }
}');

    $check    = new SingletonRequestCaptureCheck();
    $findings = iterator_to_array($check->run($context));

    expect($findings[0]->fix)->not->toBeNull();
});
