<?php

namespace ShipReady\Commands;

use Illuminate\Console\Command;
use ShipReady\Analyzers\BladeAnalyzer;
use ShipReady\Analyzers\CodeAnalyzer;
use ShipReady\Analyzers\ConfigAnalyzer;
use ShipReady\Analyzers\DependencyAnalyzer;
use ShipReady\Analyzers\EnvironmentAnalyzer;
use ShipReady\Analyzers\RouteAnalyzer;
use ShipReady\Baseline\Baseline;
use ShipReady\CheckRegistry;
use ShipReady\Checks\Filter;
use ShipReady\Compat\LaravelVersion;
use ShipReady\Runner;
use ShipReady\Support\Context;

/**
 * MCP (Model Context Protocol) stdio server.
 *
 * Exposes ShipReady tools to AI agents (Claude Code, Cursor, GitHub Copilot)
 * via JSON-RPC 2.0 over stdin/stdout.
 *
 * Tools exposed:
 *   ship_check         — run the full audit, returns findings array
 *   ship_explain       — explain a specific check ID
 *   ship_fix_preview   — return the fix hint for a finding ID
 *   ship_verify        — re-run a single check and confirm it passes
 */
class McpCommand extends Command
{
    protected $signature = 'ship:mcp';

    protected $description = 'Start ShipReady MCP stdio server for AI agent integration (Claude Code, Cursor, Copilot)';

    private bool $running = true;

    public function handle(): int
    {
        // Announce the server on startup (MCP spec: server sends initialize result)
        $this->sendResponse(null, $this->serverInfo());

        while ($this->running) {
            $line = fgets(STDIN);

            if ($line === false) {
                break;
            }

            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            $request = json_decode($line, true);

            if (!is_array($request)) {
                $this->sendError(null, -32700, 'Parse error');

                continue;
            }

            $id     = $request['id'] ?? null;
            $method = $request['method'] ?? '';
            $params = $request['params'] ?? [];

            try {
                $result = $this->dispatch($method, $params);
                $this->sendResponse($id, $result);
            } catch (\InvalidArgumentException $e) {
                $this->sendError($id, -32602, $e->getMessage());
            } catch (\Throwable $e) {
                $this->sendError($id, -32603, 'Internal error: ' . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function dispatch(string $method, array $params): mixed
    {
        return match ($method) {
            'initialize'         => $this->serverInfo(),
            'tools/list'         => $this->toolsList(),
            'tools/call'         => $this->toolsCall($params),
            'notifications/initialized' => [],
            default              => throw new \InvalidArgumentException("Unknown method: {$method}"),
        };
    }

    private function serverInfo(): array
    {
        return [
            'protocolVersion' => '2024-11-05',
            'capabilities'    => ['tools' => []],
            'serverInfo'      => [
                'name'    => 'shipready',
                'version' => '1.0.0',
            ],
        ];
    }

    private function toolsList(): array
    {
        return [
            'tools' => [
                [
                    'name'        => 'ship_check',
                    'description' => 'Run ShipReady security and production-readiness audit on this Laravel app. Returns structured findings with severity, check ID, message, and fix hint.',
                    'inputSchema' => [
                        'type'       => 'object',
                        'properties' => [
                            'category'   => ['type' => 'string', 'description' => 'Filter by category: security, performance, reliability, version_specific, octane, queue, tenancy, packages, infrastructure'],
                            'severity'   => ['type' => 'string', 'description' => 'Minimum severity: critical, high, medium, low'],
                            'check_id'   => ['type' => 'string', 'description' => 'Run a single check by ID, e.g. SEC001'],
                        ],
                        'required'   => [],
                    ],
                ],
                [
                    'name'        => 'ship_explain',
                    'description' => 'Get a detailed explanation of what a specific ShipReady check does and why it matters.',
                    'inputSchema' => [
                        'type'       => 'object',
                        'properties' => [
                            'check_id' => ['type' => 'string', 'description' => 'The check ID, e.g. SEC001, PERF007, OCT001'],
                        ],
                        'required'   => ['check_id'],
                    ],
                ],
                [
                    'name'        => 'ship_fix_preview',
                    'description' => 'Return the fix hint for one or more findings from ship_check output.',
                    'inputSchema' => [
                        'type'       => 'object',
                        'properties' => [
                            'check_id' => ['type' => 'string', 'description' => 'The check ID to get the fix for'],
                        ],
                        'required'   => ['check_id'],
                    ],
                ],
                [
                    'name'        => 'ship_verify',
                    'description' => 'Re-run a single ShipReady check to verify a fix was applied correctly.',
                    'inputSchema' => [
                        'type'       => 'object',
                        'properties' => [
                            'check_id' => ['type' => 'string', 'description' => 'The check ID to verify, e.g. SEC001'],
                        ],
                        'required'   => ['check_id'],
                    ],
                ],
            ],
        ];
    }

    private function toolsCall(array $params): array
    {
        $name      = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];

        return match ($name) {
            'ship_check'       => $this->toolShipCheck($arguments),
            'ship_explain'     => $this->toolShipExplain($arguments),
            'ship_fix_preview' => $this->toolShipFixPreview($arguments),
            'ship_verify'      => $this->toolShipVerify($arguments),
            default            => throw new \InvalidArgumentException("Unknown tool: {$name}"),
        };
    }

    private function toolShipCheck(array $args): array
    {
        $env      = 'production';
        $analyzers = $this->buildAnalyzers($env);

        $context = new Context(
            analyzers:      $analyzers,
            targetEnv:      $env,
            laravelVersion: LaravelVersion::detect()
        );

        $baseline = new Baseline('/dev/null');
        $filter   = new Filter(
            category:           $args['category'] ?? null,
            checkId:            $args['check_id'] ?? null,
            productionOnly:     false,
            includeExperimental: false
        );

        /** @var CheckRegistry $registry */
        $registry = app(CheckRegistry::class);
        $runner   = new Runner($registry, $baseline);
        $report   = $runner->run($context, $filter);

        $findings = [];

        foreach ($report->newFindings() as $f) {
            $finding = [
                'check_id' => $f->checkId,
                'severity' => $f->severity->value(),
                'message'  => $f->message,
                'fix'      => $f->fix,
            ];

            if ($f->file) {
                $finding['file'] = $f->file;
            }

            if ($f->line) {
                $finding['line'] = $f->line;
            }

            $findings[] = $finding;
        }

        $counts = $report->counts();

        return [
            'content' => [[
                'type' => 'text',
                'text' => json_encode([
                    'summary'  => [
                        'total'    => array_sum($counts),
                        'critical' => $counts['critical'] ?? 0,
                        'high'     => $counts['high'] ?? 0,
                        'medium'   => $counts['medium'] ?? 0,
                        'low'      => $counts['low'] ?? 0,
                        'score'    => $report->score(),
                    ],
                    'findings' => $findings,
                ], JSON_PRETTY_PRINT),
            ]],
        ];
    }

    private function toolShipExplain(array $args): array
    {
        $checkId = strtoupper($args['check_id'] ?? '');

        if (empty($checkId)) {
            throw new \InvalidArgumentException('check_id is required');
        }

        /** @var CheckRegistry $registry */
        $registry = app(CheckRegistry::class);
        $check    = $registry->findById($checkId);

        if ($check === null) {
            return [
                'content' => [[
                    'type' => 'text',
                    'text' => "Check {$checkId} not found. Run ship_check to see available check IDs.",
                ]],
                'isError' => true,
            ];
        }

        $meta = $check->meta();

        return [
            'content' => [[
                'type' => 'text',
                'text' => implode("\n", [
                    "**{$meta->id}: {$meta->title}**",
                    '',
                    "Category: {$meta->category}",
                    "Severity: {$meta->severity}",
                    $meta->minLaravel ? "Minimum Laravel: {$meta->minLaravel}" : '',
                    '',
                    "This check detects: {$meta->title}",
                    '',
                    'Use ship_fix_preview to get the specific fix for this check.',
                ]),
            ]],
        ];
    }

    private function toolShipFixPreview(array $args): array
    {
        $checkId  = strtoupper($args['check_id'] ?? '');
        $env      = 'production';
        $analyzers = $this->buildAnalyzers($env);

        $context = new Context(
            analyzers:      $analyzers,
            targetEnv:      $env,
            laravelVersion: LaravelVersion::detect()
        );

        $baseline = new Baseline('/dev/null');
        $filter   = new Filter(
            category:           null,
            checkId:            $checkId,
            productionOnly:     false,
            includeExperimental: false
        );

        /** @var CheckRegistry $registry */
        $registry = app(CheckRegistry::class);
        $runner   = new Runner($registry, $baseline);
        $report   = $runner->run($context, $filter);

        $findings = $report->newFindings();

        if (empty($findings)) {
            return [
                'content' => [[
                    'type' => 'text',
                    'text' => "No findings for {$checkId}. Either the check passed or the check ID was not found.",
                ]],
            ];
        }

        $fixes = [];

        foreach ($findings as $f) {
            if ($f->fix) {
                $fixes[] = "**{$f->checkId}**: {$f->message}\n\nFix: {$f->fix}" . ($f->file ? "\nFile: {$f->file}" : '');
            }
        }

        return [
            'content' => [[
                'type' => 'text',
                'text' => implode("\n\n---\n\n", $fixes) ?: "No fix hints available for {$checkId}.",
            ]],
        ];
    }

    private function toolShipVerify(array $args): array
    {
        $result   = $this->toolShipCheck(['check_id' => $args['check_id'] ?? '']);
        $data     = json_decode($result['content'][0]['text'] ?? '{}', true);
        $total    = $data['summary']['total'] ?? 0;
        $checkId  = strtoupper($args['check_id'] ?? '');

        if ($total === 0) {
            return [
                'content' => [[
                    'type' => 'text',
                    'text' => "✔ {$checkId} passed — no findings detected. The fix was applied successfully.",
                ]],
            ];
        }

        return [
            'content' => [[
                'type' => 'text',
                'text' => "✖ {$checkId} still failing ({$total} finding(s)). Review the findings:\n\n" . json_encode($data['findings'], JSON_PRETTY_PRINT),
            ]],
            'isError' => true,
        ];
    }

    private function buildAnalyzers(string $env): array
    {
        $paths     = config('ship-ready.paths', [app_path()]);
        $exclude   = config('ship-ready.exclude', []);
        $viewPaths = config('ship-ready.view_paths', [resource_path('views')]);

        return [
            new ConfigAnalyzer(app('config'), $env),
            new RouteAnalyzer(app('router')),
            new CodeAnalyzer($paths, $exclude),
            new BladeAnalyzer($viewPaths),
            new EnvironmentAnalyzer(),
            new DependencyAnalyzer(),
        ];
    }

    private function sendResponse(mixed $id, mixed $result): void
    {
        $response = [
            'jsonrpc' => '2.0',
            'id'      => $id,
            'result'  => $result,
        ];

        fwrite(STDOUT, json_encode($response) . "\n");
        fflush(STDOUT);
    }

    private function sendError(mixed $id, int $code, string $message): void
    {
        $response = [
            'jsonrpc' => '2.0',
            'id'      => $id,
            'error'   => ['code' => $code, 'message' => $message],
        ];

        fwrite(STDOUT, json_encode($response) . "\n");
        fflush(STDOUT);
    }
}
