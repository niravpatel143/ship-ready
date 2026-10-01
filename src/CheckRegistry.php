<?php

namespace ShipReady;

use Illuminate\Contracts\Container\Container;
use ShipReady\Checks\CheckMeta;
use ShipReady\Checks\CheckMetaReader;
use ShipReady\Checks\Filter;

final class CheckRegistry
{
    private Container $app;
    private array $checkClasses = [];

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    public function register(string $checkClass): static
    {
        if (!in_array($checkClass, $this->checkClasses, true)) {
            $this->checkClasses[] = $checkClass;
        }

        return $this;
    }

    public function registerMany(array $checkClasses): static
    {
        foreach ($checkClasses as $checkClass) {
            $this->register($checkClass);
        }

        return $this;
    }

    /**
     * Yield [CheckMeta, Check] pairs matching the given filter.
     *
     * @return \Generator<array{0: CheckMeta, 1: \ShipReady\Checks\Check}>
     */
    public function matching(Filter $filter): \Generator
    {
        $disabled = config('ship-ready.disabled', []);
        $severityOverrides = config('ship-ready.severity_overrides', []);

        foreach ($this->checkClasses as $checkClass) {
            if (!class_exists($checkClass)) {
                continue;
            }

            try {
                $meta = CheckMetaReader::for($checkClass);
            } catch (\Throwable $e) {
                continue;
            }

            if (in_array($meta->id, $disabled, true)) {
                continue;
            }

            if (isset($severityOverrides[$meta->id])) {
                $meta->severity = $severityOverrides[$meta->id];
            }

            if (!$filter->matches($meta)) {
                continue;
            }

            $check = $this->app->make($checkClass);

            yield [$meta, $check];
        }
    }

    public function all(): array
    {
        return $this->checkClasses;
    }
}
