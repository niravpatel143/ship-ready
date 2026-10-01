<?php

namespace ShipReady\Baseline;

use ShipReady\Support\Finding;

final class Baseline
{
    private string $path;
    private array $fingerprints = [];
    private bool $loaded = false;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function load(): void
    {
        $data = BaselineFile::read($this->path);

        $this->fingerprints = [];

        if (isset($data['findings']) && is_array($data['findings'])) {
            foreach ($data['findings'] as $entry) {
                if (isset($entry['fingerprint'])) {
                    $this->fingerprints[$entry['fingerprint']] = true;
                }
            }
        }

        $this->loaded = true;
    }

    public function split(array $findings): array
    {
        if (!$this->loaded) {
            $this->load();
        }

        $new        = [];
        $baselined  = [];

        foreach ($findings as $finding) {
            if ($this->has($finding)) {
                $baselined[] = $finding;
            } else {
                $new[] = $finding;
            }
        }

        return [
            'new'        => $new,
            'baselined'  => $baselined,
        ];
    }

    public function has(Finding $finding): bool
    {
        if (!$this->loaded) {
            $this->load();
        }

        return isset($this->fingerprints[$finding->fingerprint()]);
    }

    public function write(array $findings): void
    {
        BaselineFile::write($this->path, $findings);
        $this->loaded = false;
    }

    public function prune(array $currentFindings): void
    {
        if (!$this->loaded) {
            $this->load();
        }

        $currentFingerprints = [];

        foreach ($currentFindings as $finding) {
            $currentFingerprints[$finding->fingerprint()] = true;
        }

        $prunedFingerprints = array_intersect_key(
            $this->fingerprints,
            $currentFingerprints
        );

        $this->fingerprints = $prunedFingerprints;

        // Re-build findings array from pruned fingerprints
        $keptFindings = array_filter(
            $currentFindings,
            fn(Finding $f) => isset($prunedFingerprints[$f->fingerprint()])
        );

        $this->write(array_values($keptFindings));
    }
}
