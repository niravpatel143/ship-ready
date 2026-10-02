<?php

namespace ShipReady\Commands;

use Illuminate\Console\Command;

class ProbeCommand extends Command
{
    protected $signature = 'ship:probe
                            {--url= : Base URL to probe (defaults to APP_URL)}
                            {--format=console : Output format (console|json)}
                            {--timeout=10 : HTTP timeout in seconds}';

    protected $description = 'Live HTTP probe: checks HSTS, CSP, cookie flags, and exposed sensitive paths';

    private array $results = [];

    public function handle(): int
    {
        $url = $this->option('url') ?? config('app.url');

        if (!$url) {
            $this->error('Provide --url or set APP_URL in .env');

            return self::FAILURE;
        }

        $url     = rtrim((string)$url, '/');
        $timeout = (int)($this->option('timeout') ?? 10);

        $this->line('');
        $this->line("  <fg=white;options=bold>ShipReady HTTP Probe</>");
        $this->line("  Target: <fg=cyan>{$url}</>");
        $this->line('');

        $this->probeHeaders($url, $timeout);
        $this->probeSensitivePaths($url, $timeout);

        $format = $this->option('format');

        if ($format === 'json') {
            $this->output->write(json_encode($this->results, JSON_PRETTY_PRINT) . "\n");

            return $this->hasFailures() ? self::FAILURE : self::SUCCESS;
        }

        $this->renderConsole();

        return $this->hasFailures() ? self::FAILURE : self::SUCCESS;
    }

    private function probeHeaders(string $url, int $timeout): void
    {
        $headers = $this->fetchHeaders($url, $timeout);

        if ($headers === null) {
            $this->addResult('headers', 'error', "Could not connect to {$url}");

            return;
        }

        $normalised = [];

        foreach ($headers as $k => $v) {
            $normalised[strtolower($k)] = $v;
        }

        // HSTS
        if (isset($normalised['strict-transport-security'])) {
            $hsts = $normalised['strict-transport-security'];
            $this->addResult('HSTS', 'pass', 'Strict-Transport-Security: ' . $hsts);
        } else {
            $this->addResult('HSTS', 'fail', 'Strict-Transport-Security header missing — HTTPS not enforced by browser');
        }

        // CSP
        if (isset($normalised['content-security-policy'])) {
            $this->addResult('CSP', 'pass', 'Content-Security-Policy header present');
        } else {
            $this->addResult('CSP', 'warn', 'Content-Security-Policy header missing — XSS not mitigated by browser policy');
        }

        // X-Frame-Options
        if (isset($normalised['x-frame-options'])) {
            $this->addResult('X-Frame-Options', 'pass', 'X-Frame-Options: ' . $normalised['x-frame-options']);
        } else {
            $this->addResult('X-Frame-Options', 'warn', 'X-Frame-Options header missing — clickjacking possible');
        }

        // X-Content-Type-Options
        if (isset($normalised['x-content-type-options'])) {
            $this->addResult('X-Content-Type-Options', 'pass', 'X-Content-Type-Options: ' . $normalised['x-content-type-options']);
        } else {
            $this->addResult('X-Content-Type-Options', 'warn', 'X-Content-Type-Options header missing — MIME sniffing enabled');
        }

        // Set-Cookie flags
        if (isset($normalised['set-cookie'])) {
            $cookie = $normalised['set-cookie'];

            if (!str_contains(strtolower($cookie), 'httponly')) {
                $this->addResult('Cookie:HttpOnly', 'fail', 'Session cookie missing HttpOnly flag — accessible via JavaScript');
            } else {
                $this->addResult('Cookie:HttpOnly', 'pass', 'Cookie HttpOnly flag present');
            }

            if (!str_contains(strtolower($cookie), 'secure')) {
                $this->addResult('Cookie:Secure', 'fail', 'Session cookie missing Secure flag — sent over HTTP connections');
            } else {
                $this->addResult('Cookie:Secure', 'pass', 'Cookie Secure flag present');
            }

            if (!str_contains(strtolower($cookie), 'samesite')) {
                $this->addResult('Cookie:SameSite', 'warn', 'Session cookie missing SameSite attribute — CSRF risk');
            } else {
                $this->addResult('Cookie:SameSite', 'pass', 'Cookie SameSite attribute present');
            }
        }

        // X-Powered-By (PHP version exposure)
        if (isset($normalised['x-powered-by'])) {
            $this->addResult('X-Powered-By', 'warn', 'X-Powered-By: ' . $normalised['x-powered-by'] . ' — technology fingerprinting enabled');
        } else {
            $this->addResult('X-Powered-By', 'pass', 'X-Powered-By header not exposed');
        }
    }

    private function probeSensitivePaths(string $url, int $timeout): void
    {
        $paths = [
            '/.env'          => 'Environment file with secrets exposed',
            '/.git/HEAD'     => 'Git repository HEAD exposed — source code leakage',
            '/telescope'     => 'Laravel Telescope debug tool accessible',
            '/horizon'       => 'Laravel Horizon queue dashboard accessible',
            '/_debugbar'     => 'Laravel Debugbar accessible',
            '/phpinfo.php'   => 'phpinfo() file accessible — server config exposed',
            '/storage/logs/laravel.log' => 'Laravel log file publicly accessible',
        ];

        foreach ($paths as $path => $description) {
            $status = $this->checkPath($url . $path, $timeout);

            if ($status === 200) {
                $this->addResult('path:' . $path, 'fail', "HTTP 200 — {$description}");
            } elseif ($status === 301 || $status === 302) {
                $this->addResult('path:' . $path, 'warn', "HTTP {$status} redirect — {$description}");
            } else {
                $this->addResult('path:' . $path, 'pass', "HTTP {$status} — {$path} not exposed");
            }
        }
    }

    private function fetchHeaders(string $url, int $timeout): ?array
    {
        $context = stream_context_create([
            'http' => [
                'method'          => 'GET',
                'timeout'         => $timeout,
                'follow_location' => false,
                'ignore_errors'   => true,
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ]);

        $headers = @get_headers($url, true, $context);

        return $headers ?: null;
    }

    private function checkPath(string $url, int $timeout): int
    {
        $context = stream_context_create([
            'http' => [
                'method'          => 'GET',
                'timeout'         => $timeout,
                'follow_location' => false,
                'ignore_errors'   => true,
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ]);

        $headers = @get_headers($url, false, $context);

        if (!$headers) {
            return 0;
        }

        preg_match('/HTTP\/\d\.?\d?\s+(\d+)/', $headers[0] ?? '', $m);

        return (int)($m[1] ?? 0);
    }

    private function addResult(string $check, string $status, string $message): void
    {
        $this->results[] = compact('check', 'status', 'message');
    }

    private function hasFailures(): bool
    {
        foreach ($this->results as $r) {
            if ($r['status'] === 'fail') {
                return true;
            }
        }

        return false;
    }

    private function renderConsole(): void
    {
        foreach ($this->results as $r) {
            $icon  = match ($r['status']) {
                'pass'  => '<fg=green>  ✔ PASS </>',
                'warn'  => '<fg=yellow>  ▲ WARN </>',
                'fail'  => '<fg=red>  ✖ FAIL </>',
                default => '<fg=white>  ? INFO </>',
            };

            $check = str_pad($r['check'], 22);
            $this->line("  {$icon} <fg=white>{$check}</> {$r['message']}");
        }

        $failures = count(array_filter($this->results, fn($r) => $r['status'] === 'fail'));
        $warnings = count(array_filter($this->results, fn($r) => $r['status'] === 'warn'));

        $this->line('');

        if ($failures === 0 && $warnings === 0) {
            $this->line('  <fg=green>✔ All checks passed.</> Your headers and paths look secure.');
        } else {
            $this->line("  Found <fg=red>{$failures} failure(s)</> and <fg=yellow>{$warnings} warning(s)</>.");
        }

        $this->line('');
    }
}
