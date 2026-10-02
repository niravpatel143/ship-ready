<?php

namespace ShipReady\Tests\Checks\Infrastructure;

use ShipReady\Checks\CheckMetaReader;
use ShipReady\Checks\Infrastructure\DockerEnvFileCheck;
use ShipReady\Support\Context;
use ShipReady\Support\Severity;
use ShipReady\Tests\TestCase;

class DockerEnvFileCheckTest extends TestCase
{
    private string $dockerfilePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dockerfilePath = base_path('Dockerfile');
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
    public function meta_id_is_INF002(): void
    {
        $meta = CheckMetaReader::for(DockerEnvFileCheck::class);

        $this->assertSame('INF002', $meta->id);
        $this->assertSame('infrastructure', $meta->category);
        $this->assertSame(Severity::CRITICAL, $meta->severity);
    }

    // ---------------------------------------------------------------------------
    // True positives
    // ---------------------------------------------------------------------------

    /** @test */
    public function triggers_when_dockerfile_copies_env_dot(): void
    {
        $this->writeDockerfile("FROM php:8.2\nCOPY .env .\nRUN composer install\n");

        $findings = iterator_to_array((new DockerEnvFileCheck())->run($this->makeContext()));

        $this->assertNotEmpty($findings);
        $this->assertSame('INF002', $findings[0]->checkId);
        $this->assertSame(Severity::CRITICAL, $findings[0]->severity->value());
    }

    /** @test */
    public function triggers_when_dockerfile_copies_env_to_path(): void
    {
        $this->writeDockerfile("FROM php:8.2\nCOPY .env /var/www/\n");

        $findings = iterator_to_array((new DockerEnvFileCheck())->run($this->makeContext()));

        $this->assertNotEmpty($findings);
    }

    // ---------------------------------------------------------------------------
    // True negatives
    // ---------------------------------------------------------------------------

    /** @test */
    public function passes_when_dockerfile_does_not_copy_env(): void
    {
        $this->writeDockerfile("FROM php:8.2\nCOPY . .\nRUN composer install --no-dev\nUSER www-data\n");

        $findings = iterator_to_array((new DockerEnvFileCheck())->run($this->makeContext()));

        $this->assertEmpty($findings);
    }

    /** @test */
    public function passes_when_no_dockerfile_exists(): void
    {
        $findings = iterator_to_array((new DockerEnvFileCheck())->run($this->makeContext()));

        $this->assertEmpty($findings);
    }

    // ---------------------------------------------------------------------------
    // Fix suggestion
    // ---------------------------------------------------------------------------

    /** @test */
    public function finding_includes_fix_that_mentions_env(): void
    {
        $this->writeDockerfile("FROM php:8.2\nCOPY .env .\n");

        $findings = iterator_to_array((new DockerEnvFileCheck())->run($this->makeContext()));

        $this->assertNotNull($findings[0]->fix);
        $this->assertStringContainsString('.env', $findings[0]->fix);
    }
}
