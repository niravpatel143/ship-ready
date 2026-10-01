<?php

namespace ShipReady\Support;

final class Report
{
    private array $findings;
    private array $newFindings;
    private array $baselinedFindings;
    private float $duration = 0.0;

    public function __construct(
        array $findings,
        array $newFindings,
        array $baselinedFindings = []
    ) {
        $this->findings          = $findings;
        $this->newFindings       = $newFindings;
        $this->baselinedFindings = $baselinedFindings;
    }

    public function score(): int
    {
        $score = 100;

        foreach ($this->newFindings as $finding) {
            $score -= $finding->severity->penalty();
        }

        return max(0, $score);
    }

    public function allFindings(): array
    {
        return $this->findings;
    }

    public function newFindings(): array
    {
        return $this->newFindings;
    }

    public function baselined(): array
    {
        return $this->baselinedFindings;
    }

    public function bySeverity(string $severity): array
    {
        return array_filter(
            $this->newFindings,
            fn(Finding $f) => $f->severity->value() === $severity
        );
    }

    public function shouldFail(Severity $threshold): bool
    {
        foreach ($this->newFindings as $finding) {
            if ($finding->severity->atLeast($threshold)) {
                return true;
            }
        }

        return false;
    }

    public function counts(): array
    {
        $counts = [
            Severity::CRITICAL => 0,
            Severity::HIGH     => 0,
            Severity::MEDIUM   => 0,
            Severity::LOW      => 0,
            Severity::INFO     => 0,
        ];

        foreach ($this->newFindings as $finding) {
            $key = $finding->severity->value();
            if (isset($counts[$key])) {
                $counts[$key]++;
            }
        }

        return $counts;
    }

    public function duration(): float
    {
        return $this->duration;
    }

    public function setDuration(float $seconds): void
    {
        $this->duration = $seconds;
    }
}
