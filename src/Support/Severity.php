<?php

namespace ShipReady\Support;

final class Severity
{
    public const CRITICAL = 'critical';
    public const HIGH     = 'high';
    public const MEDIUM   = 'medium';
    public const LOW      = 'low';
    public const INFO     = 'info';

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function critical(): self
    {
        return new self(self::CRITICAL);
    }

    public static function high(): self
    {
        return new self(self::HIGH);
    }

    public static function medium(): self
    {
        return new self(self::MEDIUM);
    }

    public static function low(): self
    {
        return new self(self::LOW);
    }

    public static function info(): self
    {
        return new self(self::INFO);
    }

    public static function fromString(string $value): self
    {
        $map = [
            self::CRITICAL => self::CRITICAL,
            self::HIGH     => self::HIGH,
            self::MEDIUM   => self::MEDIUM,
            self::LOW      => self::LOW,
            self::INFO     => self::INFO,
        ];

        $normalized = strtolower($value);

        if (!isset($map[$normalized])) {
            throw new \InvalidArgumentException("Invalid severity value: {$value}");
        }

        return new self($normalized);
    }

    public function penalty(): int
    {
        return match ($this->value) {
            self::CRITICAL => 15,
            self::HIGH     => 8,
            self::MEDIUM   => 4,
            self::LOW      => 1,
            self::INFO     => 0,
            default        => 0,
        };
    }

    public function atLeast(self $other): bool
    {
        $order = [
            self::INFO     => 0,
            self::LOW      => 1,
            self::MEDIUM   => 2,
            self::HIGH     => 3,
            self::CRITICAL => 4,
        ];

        return ($order[$this->value] ?? 0) >= ($order[$other->value] ?? 0);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
