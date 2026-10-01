<?php

namespace ShipReady\Analyzers;

use Illuminate\Contracts\Config\Repository;

final class ConfigAnalyzer
{
    private Repository $config;
    private string $targetEnv;

    public function __construct(Repository $config, string $targetEnv = 'local')
    {
        $this->config    = $config;
        $this->targetEnv = $targetEnv;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config->get($key, $default);
    }

    public function all(): array
    {
        return $this->config->all();
    }

    public function isProduction(): bool
    {
        return in_array($this->targetEnv, ['production', 'prod'], true);
    }

    public function targetEnv(): string
    {
        return $this->targetEnv;
    }
}
