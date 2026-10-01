<?php

namespace ShipReady\Checks;

final class Filter
{
    public ?string $category;
    public ?string $checkId;
    public bool $productionOnly;
    public bool $includeExperimental;

    public function __construct(
        ?string $category = null,
        ?string $checkId = null,
        bool $productionOnly = false,
        bool $includeExperimental = false
    ) {
        $this->category           = $category;
        $this->checkId            = $checkId;
        $this->productionOnly     = $productionOnly;
        $this->includeExperimental = $includeExperimental;
    }

    public function matches(CheckMeta $meta): bool
    {
        if ($this->category !== null && $meta->category !== $this->category) {
            return false;
        }

        if ($this->checkId !== null && $meta->id !== $this->checkId) {
            return false;
        }

        if (!$this->includeExperimental && $meta->experimental) {
            return false;
        }

        return true;
    }
}
