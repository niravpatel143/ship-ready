<?php

namespace ShipReady\Checks;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class CheckMeta
{
    public string $id;
    public string $title;
    public string $category;
    public string $severity;
    public string $docs;
    public bool $productionOnly;
    public bool $experimental;
    public ?string $minLaravel;
    public ?string $maxLaravel;

    public function __construct(
        string $id,
        string $title,
        string $category,
        string $severity,
        string $docs = '',
        bool $productionOnly = false,
        bool $experimental = false,
        ?string $minLaravel = null,
        ?string $maxLaravel = null
    ) {
        $this->id             = $id;
        $this->title          = $title;
        $this->category       = $category;
        $this->severity       = $severity;
        $this->docs           = $docs;
        $this->productionOnly = $productionOnly;
        $this->experimental   = $experimental;
        $this->minLaravel     = $minLaravel;
        $this->maxLaravel     = $maxLaravel;
    }
}
