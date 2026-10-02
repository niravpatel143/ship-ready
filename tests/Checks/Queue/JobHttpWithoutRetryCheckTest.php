<?php

use ShipReady\Analyzers\CodeAnalyzer;
use ShipReady\Checks\CheckMetaReader;
use ShipReady\Checks\Queue\JobHttpWithoutRetryCheck;
use ShipReady\Support\Context;
use ShipReady\Support\Severity;

function makeQueueContext(string $phpContent): Context
{
    $dir = sys_get_temp_dir() . '/ship-ready-que-' . uniqid();
    mkdir($dir);
    file_put_contents("{$dir}/Job.php", $phpContent);

    $analyzer = new CodeAnalyzer([$dir]);

    return new Context(
        analyzers:      [$analyzer],
        targetEnv:      'production',
        laravelVersion: '11.0.0'
    );
}

// ---------------------------------------------------------------------------
// QUE002 metadata
// ---------------------------------------------------------------------------

test('QUE002 meta id is QUE002', function () {
    $meta = CheckMetaReader::for(JobHttpWithoutRetryCheck::class);

    expect($meta->id)->toBe('QUE002');
    expect($meta->category)->toBe('queue');
});

// ---------------------------------------------------------------------------
// QUE002 true positives
// ---------------------------------------------------------------------------

test('QUE002 triggers on ShouldQueue job with Http:: and no retry', function () {
    $context = makeQueueContext('<?php
class SendWebhookJob implements ShouldQueue {
    public function handle() {
        Http::post("https://api.example.com/webhook", $this->payload);
    }
}');

    $check    = new JobHttpWithoutRetryCheck();
    $findings = iterator_to_array($check->run($context));

    expect($findings)->not->toBeEmpty();
    expect($findings[0]->checkId)->toBe('QUE002');
});

test('QUE002 triggers on ShouldQueue job with Guzzle and no retry', function () {
    $context = makeQueueContext('<?php
class CallApiJob implements ShouldQueue {
    public function handle(\GuzzleHttp\Client $client) {
        $client->get("https://api.example.com/resource");
    }
}');

    $check    = new JobHttpWithoutRetryCheck();
    $findings = iterator_to_array($check->run($context));

    expect($findings)->not->toBeEmpty();
});

// ---------------------------------------------------------------------------
// QUE002 true negatives
// ---------------------------------------------------------------------------

test('QUE002 passes when job uses Http::retry()', function () {
    $context = makeQueueContext('<?php
class SendWebhookJob implements ShouldQueue {
    public function handle() {
        Http::retry(3, 100)->post("https://api.example.com/webhook", $this->payload);
    }
}');

    $check    = new JobHttpWithoutRetryCheck();
    $findings = iterator_to_array($check->run($context));

    expect($findings)->toBeEmpty();
});

test('QUE002 passes when job has $tries > 1', function () {
    $context = makeQueueContext('<?php
class SendWebhookJob implements ShouldQueue {
    public int $tries = 3;

    public function handle() {
        Http::post("https://api.example.com/webhook", $this->payload);
    }
}');

    $check    = new JobHttpWithoutRetryCheck();
    $findings = iterator_to_array($check->run($context));

    expect($findings)->toBeEmpty();
});

test('QUE002 passes for non-queued classes with Http calls', function () {
    $context = makeQueueContext('<?php
class NotAJob {
    public function call() {
        Http::post("https://api.example.com", []);
    }
}');

    $check    = new JobHttpWithoutRetryCheck();
    $findings = iterator_to_array($check->run($context));

    expect($findings)->toBeEmpty();
});

test('QUE002 passes for ShouldQueue job with no Http calls', function () {
    $context = makeQueueContext('<?php
class SendEmailJob implements ShouldQueue {
    public function handle() {
        Mail::to($this->user)->send(new WelcomeMail($this->user));
    }
}');

    $check    = new JobHttpWithoutRetryCheck();
    $findings = iterator_to_array($check->run($context));

    expect($findings)->toBeEmpty();
});

// ---------------------------------------------------------------------------
// QUE002 finding has fix suggestion
// ---------------------------------------------------------------------------

test('QUE002 finding includes fix suggestion', function () {
    $context = makeQueueContext('<?php
class Job implements ShouldQueue {
    public function handle() {
        Http::post("https://hook.example.com", []);
    }
}');

    $check    = new JobHttpWithoutRetryCheck();
    $findings = iterator_to_array($check->run($context));

    expect($findings[0]->fix)->not->toBeNull();
    expect($findings[0]->fix)->toContain('retry');
});
