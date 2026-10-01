<?php

namespace ShipReady\Analyzers;

final class ClassInfo
{
    public string $name;
    public string $file;
    public int $line;

    /** @var PropertyInfo[] */
    private array $properties;

    /** @var array<string, bool> */
    private array $attributes;

    public function __construct(
        string $name,
        string $file,
        int $line,
        array $properties = [],
        array $attributes = []
    ) {
        $this->name       = $name;
        $this->file       = $file;
        $this->line       = $line;
        $this->properties = $properties;
        $this->attributes = $attributes;
    }

    public function property(string $name): ?PropertyInfo
    {
        foreach ($this->properties as $property) {
            if ($property->name === $name) {
                return $property;
            }
        }

        return null;
    }

    public function hasAttribute(string $name): bool
    {
        return isset($this->attributes[$name]) && $this->attributes[$name];
    }

    public function isMassAssigned(CodeAnalyzer $analyzer): bool
    {
        $calls = $analyzer->functionCalls(['create', 'update', 'fill', 'forceFill']);

        foreach ($calls as $call) {
            if (strpos($call->name, $this->name) !== false) {
                return true;
            }
        }

        return false;
    }

    public function properties(): array
    {
        return $this->properties;
    }
}
