<?php

namespace ShipReady\Checks;

final class Filter
{
    public ?string $category;
    public ?string $checkId;
    public bool $productionOnly;
    public bool $includeExperimental;
    public bool $ciMode;

    // Categories that perform live filesystem/network probes — excluded in --ci mode
    private const NON_DETERMINISTIC_CATEGORIES = ['infrastructure'];

    public function __construct(
        ?string $category = null,
        ?string $checkId = null,
        bool $productionOnly = false,
        bool $includeExperimental = false,
        bool $ciMode = false
    ) {
        $this->category           = $category;
        $this->checkId            = $checkId;
        $this->productionOnly     = $productionOnly;
        $this->includeExperimental = $includeExperimental;
        $this->ciMode             = $ciMode;
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

        if ($this->ciMode && in_array($meta->category, self::NON_DETERMINISTIC_CATEGORIES, true)) {
            return false;
        }

        return true;
    }
}
