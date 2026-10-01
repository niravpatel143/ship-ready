<?php

namespace ShipReady\Support;

use ShipReady\Analyzers\BladeAnalyzer;
use ShipReady\Analyzers\CodeAnalyzer;
use ShipReady\Analyzers\ConfigAnalyzer;
use ShipReady\Analyzers\EnvironmentAnalyzer;
use ShipReady\Analyzers\RouteAnalyzer;
use ShipReady\Suppression\SuppressionCollection;

final class Context
{
    private array $analyzers;
    private string $targetEnv;
    private ?string $laravelVersion;

    private ?ConfigAnalyzer $configAnalyzer        = null;
    private ?RouteAnalyzer $routeAnalyzer          = null;
    private ?CodeAnalyzer $codeAnalyzer            = null;
    private ?BladeAnalyzer $bladeAnalyzer          = null;
    private ?EnvironmentAnalyzer $envAnalyzer      = null;
    private ?SuppressionCollection $suppressions   = null;

    public function __construct(
        array $analyzers,
        string $targetEnv = 'local',
        ?string $laravelVersion = null
    ) {
        $this->analyzers      = $analyzers;
        $this->targetEnv      = $targetEnv;
        $this->laravelVersion = $laravelVersion;
    }

    public function config(): ConfigAnalyzer
    {
        if ($this->configAnalyzer === null) {
            $this->configAnalyzer = $this->resolve(ConfigAnalyzer::class);
        }

        return $this->configAnalyzer;
    }

    public function routes(): RouteAnalyzer
    {
        if ($this->routeAnalyzer === null) {
            $this->routeAnalyzer = $this->resolve(RouteAnalyzer::class);
        }

        return $this->routeAnalyzer;
    }

    public function code(): CodeAnalyzer
    {
        if ($this->codeAnalyzer === null) {
            $this->codeAnalyzer = $this->resolve(CodeAnalyzer::class);
        }

        return $this->codeAnalyzer;
    }

    public function blade(): BladeAnalyzer
    {
        if ($this->bladeAnalyzer === null) {
            $this->bladeAnalyzer = $this->resolve(BladeAnalyzer::class);
        }

        return $this->bladeAnalyzer;
    }

    public function env(): EnvironmentAnalyzer
    {
        if ($this->envAnalyzer === null) {
            $this->envAnalyzer = $this->resolve(EnvironmentAnalyzer::class);
        }

        return $this->envAnalyzer;
    }

    public function targetEnv(): string
    {
        return $this->targetEnv;
    }

    public function laravelVersion(): string
    {
        if ($this->laravelVersion === null) {
            $this->laravelVersion = \ShipReady\Compat\LaravelVersion::detect();
        }

        return $this->laravelVersion;
    }

    public function targetsProduction(): bool
    {
        return in_array($this->targetEnv, ['production', 'prod'], true);
    }

    public function suppressions(): SuppressionCollection
    {
        if ($this->suppressions === null) {
            $this->suppressions = $this->resolveOrDefault(
                SuppressionCollection::class,
                new SuppressionCollection()
            );
        }

        return $this->suppressions;
    }

    private function resolve(string $class): object
    {
        foreach ($this->analyzers as $analyzer) {
            if ($analyzer instanceof $class) {
                return $analyzer;
            }
        }

        throw new \RuntimeException("Analyzer not found in context: {$class}");
    }

    private function resolveOrDefault(string $class, object $default): object
    {
        foreach ($this->analyzers as $analyzer) {
            if ($analyzer instanceof $class) {
                return $analyzer;
            }
        }

        return $default;
    }
}
