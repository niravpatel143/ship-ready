<?php

namespace ShipReady\Tests\Checks\Infrastructure;

use ShipReady\Checks\CheckMetaReader;
use ShipReady\Checks\Infrastructure\DockerRootUserCheck;
use ShipReady\Support\Context;
use ShipReady\Support\Severity;
use ShipReady\Tests\TestCase;

class DockerRootUserCheckTest extends TestCase
{
    private string $dockerfilePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dockerfilePath = base_path('Dockerfile');
        // Remove any leftover Dockerfile from a previous test
        @unlink($this->dockerfilePath);
    }

    protected function tearDown(): void
    {
        @unlink($this->dockerfilePath);
        parent::tearDown();
    }

    private function writeDockerfile(string $content): void
    {
        file_put_contents($this->dockerfilePath, $content);
    }

    private function makeContext(): Context
    {
        return new Context(
            analyzers:      [],
            targetEnv:      'production',
            laravelVersion: '11.0.0'
        );
    }

    // ---------------------------------------------------------------------------
    // Metadata
    // ---------------------------------------------------------------------------

    /** @test */
    public function meta_id_is_INF001(): void
    {
        $meta = CheckMetaReader::for(DockerRootUserCheck::class);

        $this->assertSame('INF001', $meta->id);
        $this->assertSame('infrastructure', $meta->category);
        $this->assertSame(Severity::HIGH, $meta->severity);
    }

    // ---------------------------------------------------------------------------
    // True positives
    // ---------------------------------------------------------------------------

    /** @test */
    public function triggers_when_dockerfile_has_no_user_directive(): void
    {
        $this->writeDockerfile("FROM php:8.2-fpm\nRUN apt-get update\nEXPOSE 9000\n");

        $findings = iterator_to_array((new DockerRootUserCheck())->run($this->makeContext()));

        $this->assertNotEmpty($findings);
        $this->assertSame('INF001', $findings[0]->checkId);
    }

    /** @test */
    public function triggers_when_dockerfile_has_user_root(): void
    {
        $this->writeDockerfile("FROM php:8.2-fpm\nUSER root\n");

        $findings = iterator_to_array((new DockerRootUserCheck())->run($this->makeContext()));

        $this->assertNotEmpty($findings);
    }

    // ---------------------------------------------------------------------------
    // True negatives
    // ---------------------------------------------------------------------------

    /** @test */
    public function passes_when_dockerfile_sets_a_non_root_user(): void
    {
        $this->writeDockerfile("FROM php:8.2-fpm\nRUN adduser -D appuser\nUSER appuser\n");

        $findings = iterator_to_array((new DockerRootUserCheck())->run($this->makeContext()));

        $this->assertEmpty($findings);
    }

    /** @test */
    public function passes_when_dockerfile_sets_www_data(): void
    {
        $this->writeDockerfile("FROM php:8.2-fpm\nUSER www-data\n");

        $findings = iterator_to_array((new DockerRootUserCheck())->run($this->makeContext()));

        $this->assertEmpty($findings);
    }

    /** @test */
    public function passes_when_no_dockerfile_exists(): void
    {
        // Dockerfile was already removed in setUp
        $findings = iterator_to_array((new DockerRootUserCheck())->run($this->makeContext()));

        $this->assertEmpty($findings);
    }
}
