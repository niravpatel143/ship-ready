<?php

namespace ShipReady\Analyzers;

final class DependencyAnalyzer
{
    /**
     * Run composer audit and return vulnerability data.
     *
     * @return VulnerabilityInfo[]
     */
    public function composerAudit(): array
    {
        if (!file_exists(base_path('composer.lock'))) {
            return [];
        }

        try {
            $json = $this->runCapture(
                'composer audit --format=json --working-dir=' . escapeshellarg(base_path())
            );

            if (empty($json)) {
                return [];
            }

            $data = json_decode($json, true);

            if (!is_array($data)) {
                return [];
            }

            $results = [];

            $advisories = $data['advisories'] ?? [];

            foreach ($advisories as $packageName => $packageAdvisories) {
                if (!is_array($packageAdvisories)) {
                    continue;
                }

                foreach ($packageAdvisories as $advisory) {
                    $results[] = new VulnerabilityInfo(
                        package:  $packageName,
                        version:  $advisory['affectedVersions'] ?? 'unknown',
                        advisory: $advisory['title'] ?? ($advisory['advisoryId'] ?? 'Unknown advisory'),
                        severity: $this->mapComposerSeverity($advisory['severity'] ?? 'medium'),
                        cve:      $advisory['cve'] ?? null
                    );
                }
            }

            return $results;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Audit npm packages using package-lock.json.
     *
     * @return VulnerabilityInfo[]
     */
    public function npmAudit(): array
    {
        $lockFile = base_path('package-lock.json');

        if (!file_exists($lockFile)) {
            return [];
        }

        try {
            $json = $this->runCapture('npm audit --json');

            if (empty($json)) {
                return [];
            }

            $data = json_decode($json, true);

            if (!is_array($data)) {
                return [];
            }

            $results        = [];
            $vulnerabilities = $data['vulnerabilities'] ?? [];

            foreach ($vulnerabilities as $packageName => $vuln) {
                if (!is_array($vuln)) {
                    continue;
                }

                $vias = $vuln['via'] ?? [];

                foreach ($vias as $via) {
                    if (!is_array($via)) {
                        continue;
                    }

                    $results[] = new VulnerabilityInfo(
                        package:  $packageName,
                        version:  $vuln['range'] ?? 'unknown',
                        advisory: $via['title'] ?? 'npm advisory',
                        severity: $via['severity'] ?? ($vuln['severity'] ?? 'medium'),
                        cve:      $via['cve'] ?? null
                    );
                }
            }

            return $results;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Combined vulnerabilities from composer and npm.
     *
     * @return VulnerabilityInfo[]
     */
    public function vulnerabilities(): array
    {
        return array_merge(
            $this->composerAudit(),
            $this->npmAudit()
        );
    }

    /**
     * Run a command, capture stdout only, discard stderr cross-platform.
     */
    private function runCapture(string $command): string
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'], // captured and discarded — avoids Windows /dev/null issue
        ];

        $process = proc_open($command, $descriptors, $pipes, base_path());

        if (!is_resource($process)) {
            return '';
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]); // drain stderr silently
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return $stdout ?: '';
    }

    private function mapComposerSeverity(string $severity): string
    {
        return match (strtolower($severity)) {
            'critical'            => 'critical',
            'high'                => 'high',
            'medium', 'moderate' => 'medium',
            'low'                 => 'low',
            default               => 'medium',
        };
    }
}
