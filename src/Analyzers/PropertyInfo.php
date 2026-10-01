<?php

namespace ShipReady\Analyzers;

final class PropertyInfo
{
    public string $name;
    public string $file;
    public int $line;
    public mixed $defaultValue;
    public bool $hasDefault;

    public function __construct(
        string $name,
        string $file,
        int $line,
        mixed $defaultValue = null,
        bool $hasDefault = false
    ) {
        $this->name         = $name;
        $this->file         = $file;
        $this->line         = $line;
        $this->defaultValue = $defaultValue;
        $this->hasDefault   = $hasDefault;
    }

    public function isEmptyArray(): bool
    {
        return $this->hasDefault && is_array($this->defaultValue) && count($this->defaultValue) === 0;
    }
}
