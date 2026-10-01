<?php

namespace ShipReady\Reporters;

use ShipReady\Support\Finding;
use ShipReady\Support\Report;

final class SarifReporter implements Reporter
{
    public function render(Report $report): string
    {
        $rules   = [];
        $results = [];
        $seen    = [];

        foreach ($report->newFindings() as $finding) {
            if (!isset($seen[$finding->checkId])) {
                $rules[]              = $this->buildRule($finding);
                $seen[$finding->checkId] = true;
            }

            $results[] = $this->buildResult($finding);
        }

        $sarif = [
            '$schema' => 'https://json.schemastore.org/sarif-2.1.0.json',
            'version' => '2.1.0',
            'runs'    => [
                [
                    'tool'    => [
                        'driver' => [
                            'name'            => 'ShipReady',
                            'version'         => '1.0.0',
                            'informationUri'  => 'https://github.com/nivoin/ship-ready',
                            'rules'           => $rules,
                        ],
                    ],
                    'results'     => $results,
                    'invocations' => [
                        [
                            'executionSuccessful' => true,
                            'endTimeUtc'          => date('Y-m-d\TH:i:s\Z'),
                        ],
                    ],
                ],
            ],
        ];

        return json_encode($sarif, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function buildRule(Finding $finding): array
    {
        return [
            'id'               => $finding->checkId,
            'name'             => $finding->checkId,
            'shortDescription' => ['text' => $finding->message],
            'defaultConfiguration' => [
                'level' => $this->sarifLevel($finding->severity->value()),
            ],
        ];
    }

    private function buildResult(Finding $finding): array
    {
        $result = [
            'ruleId'  => $finding->checkId,
            'level'   => $this->sarifLevel($finding->severity->value()),
            'message' => ['text' => $finding->message],
        ];

        if ($finding->file !== null) {
            $location = [
                'physicalLocation' => [
                    'artifactLocation' => [
                        'uri' => $this->toUri($finding->file),
                    ],
                ],
            ];

            if ($finding->line !== null) {
                $location['physicalLocation']['region'] = [
                    'startLine' => $finding->line,
                ];
            }

            $result['locations'] = [$location];
        }

        if ($finding->fix !== null) {
            $result['fixes'] = [
                ['description' => ['text' => $finding->fix]],
            ];
        }

        return $result;
    }

    private function sarifLevel(string $severity): string
    {
        return match ($severity) {
            'critical', 'high' => 'error',
            'medium'           => 'warning',
            'low', 'info'      => 'note',
            default            => 'warning',
        };
    }

    private function toUri(string $path): string
    {
        $base     = base_path();
        $relative = str_replace('\\', '/', $path);
        $base     = str_replace('\\', '/', $base);

        if (strncmp($relative, $base, strlen($base)) === 0) {
            $relative = ltrim(substr($relative, strlen($base)), '/');
        }

        return $relative;
    }
}
