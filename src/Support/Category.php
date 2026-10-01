<?php

namespace ShipReady\Support;

final class Category
{
    public const SECURITY         = 'security';
    public const PERFORMANCE      = 'performance';
    public const RELIABILITY      = 'reliability';
    public const VERSION_SPECIFIC = 'version_specific';

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function security(): self
    {
        return new self(self::SECURITY);
    }

    public static function performance(): self
    {
        return new self(self::PERFORMANCE);
    }

    public static function reliability(): self
    {
        return new self(self::RELIABILITY);
    }

    public static function versionSpecific(): self
    {
        return new self(self::VERSION_SPECIFIC);
    }

    public static function fromString(string $value): self
    {
        $allowed = [
            self::SECURITY,
            self::PERFORMANCE,
            self::RELIABILITY,
            self::VERSION_SPECIFIC,
        ];

        $normalized = strtolower($value);

        if (!in_array($normalized, $allowed, true)) {
            throw new \InvalidArgumentException("Invalid category value: {$value}");
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function label(): string
    {
        return match ($this->value) {
            self::SECURITY         => 'Security',
            self::PERFORMANCE      => 'Performance',
            self::RELIABILITY      => 'Reliability',
            self::VERSION_SPECIFIC => 'Version Specific',
            default                => ucfirst($this->value),
        };
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
