# ShipReady

<p align="center">
  <img src="art/banner.svg" alt="ShipReady — Laravel Security & Production-Readiness Auditor" width="100%">
</p>

<p align="center">
  <a href="https://github.com/nivoin/ship-ready/actions/workflows/tests.yml"><img src="https://github.com/nivoin/ship-ready/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
  <a href="https://packagist.org/packages/nivoin/ship-ready"><img src="https://img.shields.io/packagist/v/nivoin/ship-ready.svg" alt="Latest Version"></a>
  <a href="https://www.php.net"><img src="https://img.shields.io/badge/PHP-8.3%2B-blue" alt="PHP"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-12%20%7C%2013-red" alt="Laravel"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-green" alt="License"></a>
</p>

**Static security, performance, and production-readiness auditor for Laravel 12 and 13.**

ShipReady runs 86 checks against your codebase without touching your database or making HTTP requests. It reads your routes, config, source files, migrations, and Blade views — then tells you exactly what to fix before you deploy.

```
  ShipReady Audit Report

  Score  ████████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ 20/100

  SECURITY

  ✖ CRITICAL [SEC001] APP_DEBUG is enabled in production. Stack traces and env vars are exposed to users.
    Fix: Set APP_DEBUG=false in your production .env file.

  ✖ HIGH     [SEC010] Auth route [/login] (POST) has no throttle middleware. Brute-force attacks are possible.
    Fix: Add ->middleware('throttle:5,1') to your login route.

  PERFORMANCE

  ▲ MEDIUM   [PERF001] Configuration files are not cached.
    Fix: Run `php artisan config:cache` as part of your deployment process.

  RELIABILITY

  ▲ MEDIUM   [REL015] No error monitoring service is installed. Exceptions are silently swallowed.
    Fix: Install sentry/sentry-laravel or spatie/laravel-flare.

  Found 24 issue(s): 2 critical, 6 high, 12 medium, 4 low in 3.2s
```

## Requirements

- PHP **8.3+**
- Laravel **12** or **13**

## Installation

```bash
composer require nivoin/ship-ready --dev
```

Laravel auto-discovers the service provider. No configuration publishing required.

## Usage

```bash
# Run all checks (targets production by default)
php artisan ship:check --target-env=production

# Security checks only
php artisan ship:check --category=security

# Fail CI on critical issues only
php artisan ship:check --fail-on=critical

# JSON output for tooling
php artisan ship:check --format=json --output=report.json

# SARIF for GitHub Code Scanning
php artisan ship:check --format=sarif --output=results.sarif

# Explain a specific check
php artisan ship:explain SEC001
```

## Exit Codes

| Code | Meaning |
|------|---------|
| `0`  | No findings at or above `--fail-on` threshold |
| `1`  | One or more findings meet or exceed the threshold |

Default `--fail-on` is `high`. Override in `config/ship-ready.php` or via `SHIP_READY_FAIL_ON=critical`.

## Commands

| Command | Description |
|---------|-------------|
| `ship:check` | Run all checks and report findings |
| `ship:baseline` | Record current findings as baseline to suppress them |
| `ship:explain {ID}` | Detailed explanation and fix guide for a check |
| `ship:list` | Table of all available checks with severity and status |
| `make:ship-check {Name}` | Scaffold a custom check class |

## Options

| Option | Default | Description |
|--------|---------|-------------|
| `--target-env` | `production` | Environment to evaluate config against |
| `--category` | all | Filter: `security`, `performance`, `reliability` |
| `--check` | all | Run a single check by ID (e.g. `SEC001`) |
| `--format` | `console` | `console`, `json`, `sarif`, `junit`, `markdown`, `html`, `github` |
| `--output` | stdout | Write report to file path |
| `--fail-on` | `high` | Minimum severity to exit non-zero |
| `--compact` | false | Hide fix hints |
| `--ignore-baseline` | false | Run all checks, ignoring saved baseline |
| `--experimental` | false | Include experimental checks |

## Checks (86 total)

### Security (32)

| ID | Title | Severity |
|----|-------|----------|
| SEC001 | Debug mode enabled in production | critical |
| SEC002 | Application key missing or insecure | critical |
| SEC003 | Vulnerable Composer packages (composer audit) | high |
| SEC004 | Vulnerable npm packages | high |
| SEC005 | Model with no `$fillable` or `$guarded` | high |
| SEC006 | `Model::unguard()` called outside seeder context | high |
| SEC007 | `$request->all()` passed to mass-assignment method | high |
| SEC008 | Raw Blade output `{!! !!}` | medium |
| SEC009 | SQL injection via raw query with variable interpolation | critical |
| SEC010 | Auth routes missing throttle middleware | high |
| SEC011 | Routes excluded from CSRF protection | medium |
| SEC012 | Insecure session cookie (secure/httpOnly/sameSite) | high |
| SEC013 | APP_URL uses HTTP instead of HTTPS | high |
| SEC014 | Missing security headers (HSTS, X-Frame-Options, etc.) | medium |
| SEC015 | CORS wildcard origin with credentials | critical |
| SEC016 | Debug tools exposed in production (Telescope, Debugbar) | high |
| SEC017 | Sensitive files accessible from public/ | critical |
| SEC018 | `env()` called outside config files | medium |
| SEC019 | Hardcoded secrets or API keys in source | critical |
| SEC020 | Dangerous PHP functions with user-controlled input | critical |
| SEC021 | File upload stored to public disk without validation | high |
| SEC022 | Admin routes without authorization middleware | high |
| SEC023 | Sanctum token expiration not configured | medium |
| SEC024 | Weak password hashing configuration | high |
| SEC025 | `signedRoute()` used without `signed` middleware | medium |
| SEC026 | Potential open redirect via user-controlled URL | high |
| SEC027 | API write routes missing rate limiting | medium |
| SEC028 | Sensitive data (password/token) passed to logger | high |
| SEC029 | No HTTP→HTTPS redirect configured | high |
| SEC030 | No Content-Security-Policy header | medium |
| SEC031 | Model exposes sensitive attributes in JSON output | high |
| SEC032 | Timing attack via direct token/hash comparison | high |

### Performance (18)

| ID | Title | Severity |
|----|-------|----------|
| PERF001 | Config not cached | medium |
| PERF002 | Routes not cached | medium |
| PERF003 | Views not pre-compiled | low |
| PERF004 | Composer autoloader not optimized | medium |
| PERF005 | OPcache disabled | medium |
| PERF006 | Slow cache driver in production | medium |
| PERF007 | `Model::all()` called inside a loop | high |
| PERF008 | Possible N+1 query (relation accessed in loop) | high |
| PERF009 | `Model::preventLazyLoading()` not called | low |
| PERF010 | Missing index on foreign key column | medium |
| PERF011 | `->count()` on loaded collection instead of DB query | low |
| PERF012 | Log level set to debug in production | medium |
| PERF013 | Assets not versioned for cache busting | low |
| PERF014 | Heavy operations in `ServiceProvider::boot()` | low |
| PERF015 | File/cookie session driver in production | medium |
| PERF016 | Notification class not queued | low |
| PERF017 | Mailable class not queued | low |
| PERF018 | SQLite driver in production | high |

### Reliability (19)

| ID | Title | Severity |
|----|-------|----------|
| REL001 | Pending database migrations | high |
| REL002 | Task scheduler not configured | medium |
| REL003 | Queued jobs used but queue driver is `sync` | medium |
| REL004 | Failed jobs table not configured | medium |
| REL005 | Mail driver set to `log` or `array` in production | high |
| REL006 | APP_ENV is not `production` | medium |
| REL007 | Public storage symlink missing | medium |
| REL008 | Storage directory not writable | high |
| REL009 | Log channel not configured for rotation | low |
| REL010 | No health check endpoint defined | low |
| REL011 | `.env.example` missing or out of sync | low |
| REL012 | Routes pointing to non-existent controllers | medium |
| REL013 | Multiple DB writes without transaction | medium |
| REL014 | PHP version is end-of-life | high |
| REL015 | No error monitoring (Sentry/Flare/Bugsnag) | medium |
| REL016 | Redis queue without Laravel Horizon | low |
| REL017 | No database backup solution configured | medium |
| REL018 | TrustProxies not configured for HTTPS app | high |
| REL019 | Foreign key column without DB constraint | medium |

### Version-Specific (16)

| ID | Title | Laravel | Severity |
|----|-------|---------|----------|
| VER001 | Old `VerifyCsrfToken` middleware class present | 11+ | medium |
| VER002 | Deprecated `app/Http/Kernel.php` in use | 11+ | low |
| VER003 | AI SDK API key not configured | 13+ | medium |
| VER004 | Reverb not configured with TLS in production | 11+ | high |
| VER005 | Queued job missing `ShouldQueue` interface | any | medium |
| VER006 | Passkey auth missing required configuration | 13+ | medium |
| VER007 | `$this->middleware()` in controller constructor deprecated | 11+ | medium |
| VER008 | Legacy `app/Exceptions/Handler.php` with custom methods | 11+ | low |
| VER009 | `routes/api.php` exists but not registered in bootstrap | 11+ | high |
| VER010 | Livewire v2 installed — incompatible with Laravel 11+ | 11+ | high |
| VER011 | Sanctum SPA missing `SANCTUM_STATEFUL_DOMAINS` | any | high |
| VER012 | `routes/channels.php` defined but broadcasting not enabled | 11+ | medium |
| VER013 | Fortify installed without two-factor authentication | any | medium |
| VER014 | App relies on `TrimStrings` removed from L11 defaults | 11+ | low |
| VER015 | Inertia v0/v1 on Laravel 12+ — needs v2 | 12+ | medium |
| VER016 | Carbon immutable dates not configured | 11+ | low |

## Baseline

Suppress known findings without fixing them:

```bash
# Save current findings as baseline
php artisan ship:baseline

# Remove fixed findings from baseline
php artisan ship:baseline --prune
```

Future runs only report new findings (`--diff` mode is automatic when a baseline exists).

## Inline Suppression

```php
// @ship-ignore SEC018 env() needed here for early boot
$value = env('APP_KEY');
```

## Custom Checks

```bash
php artisan make:ship-check MyCustomCheck --category=security --severity=high
```

Place the generated class in `app/ShipReady/` — it is auto-discovered on every run.

## Configuration

```bash
php artisan vendor:publish --tag=ship-ready-config
```

Key options in `config/ship-ready.php`:

| Key | Description |
|-----|-------------|
| `disabled` | Array of check IDs to skip globally |
| `severity_overrides` | Override severity per check ID |
| `fail_on` | Minimum severity for non-zero exit (env: `SHIP_READY_FAIL_ON`) |
| `baseline` | Path to baseline JSON file |
| `editor` | File link protocol: `vscode`, `phpstorm`, `sublime` |

## CI — GitHub Actions

```yaml
- name: Run ShipReady
  run: php artisan ship:check --target-env=production --fail-on=high --format=github
```

Upload SARIF to GitHub Code Scanning:

```yaml
- name: Run ShipReady
  run: php artisan ship:check --format=sarif --output=results.sarif --fail-on=critical

- name: Upload SARIF
  uses: github/codeql-action/upload-sarif@v3
  with:
    sarif_file: results.sarif
```

## What ShipReady gives you

| Feature | |
|---------|---|
| 86 checks across 4 categories | Security, Performance, Reliability, Version-Specific |
| 7 output formats | Console, JSON, SARIF, JUnit, Markdown, HTML, GitHub Annotations |
| GitHub Code Scanning | Upload SARIF results directly to the Security tab |
| Baseline suppression | Record known findings and only surface new ones |
| Inline `@ship-ignore` | Suppress individual findings with a comment |
| Custom check scaffolding | `make:ship-check` generates a ready-to-use check class |
| `composer audit` integration | Live CVE data from the Packagist security advisory database |
| Exit code control | `--fail-on=critical\|high\|medium\|low` for precise CI gates |
| Zero config | Works out of the box — no publishing required |
| Laravel 12 &amp; 13 | Full version-specific checks for modern app structures |

## License

MIT — see [LICENSE](LICENSE)
