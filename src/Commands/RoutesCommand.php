<?php

namespace ShipReady\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;

class RoutesCommand extends Command
{
    protected $signature = 'ship:routes
                            {--format=console : Output format (console|json|markdown)}
                            {--filter= : Filter routes by URI pattern}
                            {--show-safe : Also show routes that pass all checks}';

    protected $description = 'Attack-surface route map — shows auth guards, throttle, CSRF status per route';

    public function handle(Router $router): int
    {
        $routes    = $router->getRoutes();
        $format    = $this->option('format');
        $filter    = $this->option('filter');
        $showSafe  = (bool)$this->option('show-safe');

        $rows = [];

        foreach ($routes as $route) {
            $uri     = $route->uri();
            $methods = implode('|', $route->methods());

            if ($filter && !str_contains($uri, $filter)) {
                continue;
            }

            $middleware = $route->gatherMiddleware();

            $hasAuth     = $this->hasMiddleware($middleware, ['auth', 'auth:', 'sanctum', 'jwt', 'verified']);
            $hasThrottle = $this->hasMiddleware($middleware, ['throttle']);
            $hasCsrf     = !$this->isApiRoute($uri, $middleware) && !in_array('GET', $route->methods(), true);
            $hasSigned   = $this->hasMiddleware($middleware, ['signed']);

            $issues = [];

            if (!$hasAuth && $this->looksPrivate($uri)) {
                $issues[] = 'no-auth';
            }

            if (!$hasThrottle && (str_contains($uri, 'login') || str_contains($uri, 'password') || str_contains($uri, 'register'))) {
                $issues[] = 'no-throttle';
            }

            if (!empty($issues) || $showSafe) {
                $rows[] = [
                    'methods'    => $methods,
                    'uri'        => $uri,
                    'auth'       => $hasAuth ? '✔' : ($this->looksPrivate($uri) ? '✖' : '-'),
                    'throttle'   => $hasThrottle ? '✔' : '-',
                    'csrf'       => $hasCsrf ? '✔' : 'skip',
                    'signed'     => $hasSigned ? '✔' : '-',
                    'middleware' => implode(', ', array_slice($middleware, 0, 5)),
                    'issues'     => $issues,
                ];
            }
        }

        if ($format === 'json') {
            $this->output->write(json_encode($rows, JSON_PRETTY_PRINT) . "\n");

            return self::SUCCESS;
        }

        if ($format === 'markdown') {
            $this->renderMarkdown($rows);

            return self::SUCCESS;
        }

        $this->renderConsole($rows);

        return self::SUCCESS;
    }

    private function hasMiddleware(array $middleware, array $checks): bool
    {
        foreach ($middleware as $m) {
            foreach ($checks as $check) {
                if (str_starts_with($m, $check)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isApiRoute(string $uri, array $middleware): bool
    {
        return str_starts_with($uri, 'api/') || $this->hasMiddleware($middleware, ['api']);
    }

    private function looksPrivate(string $uri): bool
    {
        $privatePatterns = ['admin', 'dashboard', 'settings', 'profile', 'account', 'user/', 'users/'];

        foreach ($privatePatterns as $pattern) {
            if (str_contains($uri, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function renderConsole(array $rows): void
    {
        $this->line('');
        $this->line('  <fg=white;options=bold>ShipReady Route Attack Surface Map</>');
        $this->line('');

        if (empty($rows)) {
            $this->line('  <fg=green>✔ No high-risk routes found. Use --show-safe to see all routes.</>');
            $this->line('');

            return;
        }

        $headers = ['Methods', 'URI', 'Auth', 'Throttle', 'CSRF', 'Signed', 'Issues'];
        $tableRows = [];

        foreach ($rows as $row) {
            $auth     = $row['auth'] === '✔' ? '<fg=green>✔</>' : ($row['auth'] === '✖' ? '<fg=red>✖</>' : '<fg=gray>-</>');
            $throttle = $row['throttle'] === '✔' ? '<fg=green>✔</>' : '<fg=gray>-</>';
            $csrf     = $row['csrf'] === '✔' ? '<fg=green>✔</>' : ($row['csrf'] === 'skip' ? '<fg=gray>skip</>' : '<fg=yellow>?</>');
            $signed   = $row['signed'] === '✔' ? '<fg=green>✔</>' : '<fg=gray>-</>';
            $issues   = empty($row['issues']) ? '<fg=green>clean</>' : '<fg=red>' . implode(', ', $row['issues']) . '</>';

            $tableRows[] = [
                $row['methods'],
                $row['uri'],
                $auth,
                $throttle,
                $csrf,
                $signed,
                $issues,
            ];
        }

        $this->table($headers, $tableRows);
        $this->line('');
    }

    private function renderMarkdown(array $rows): void
    {
        $this->line('# Route Attack Surface Map');
        $this->line('');
        $this->line('| Methods | URI | Auth | Throttle | CSRF | Signed | Issues |');
        $this->line('|---------|-----|------|----------|------|--------|--------|');

        foreach ($rows as $row) {
            $this->line(sprintf(
                '| %s | %s | %s | %s | %s | %s | %s |',
                $row['methods'],
                $row['uri'],
                $row['auth'],
                $row['throttle'],
                $row['csrf'],
                $row['signed'],
                implode(', ', $row['issues']) ?: 'clean'
            ));
        }
    }
}
