<?php

namespace ShipReady\Suppression;

use ShipReady\Support\Finding;

final class SuppressionCollection
{
    /** @var array<string, array{reason: ?string}> */
    private array $suppressions = [];

    public function add(string $checkId, ?string $reason = null): void
    {
        $this->suppressions[$checkId] = ['reason' => $reason];
    }

    public function covers(Finding $finding): bool
    {
        return isset($this->suppressions[$finding->checkId]);
    }

    public function all(): array
    {
        return $this->suppressions;
    }
}
