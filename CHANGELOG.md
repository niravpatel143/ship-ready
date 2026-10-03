# Changelog

All notable changes to ShipReady are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

## [1.1.1] — 2026-10-03

### Fixed
- `--changed` flag now correctly filters CodeAnalyzer to only the modified PHP files (was computed but never wired through to the analyzer)
- `ship:baseline` no longer crashes with "An option named 'env' already exists" — renamed `--env` to `--target-env` in the command signature
- `ConsoleReporter` header now displays `v1.1.0` version and renders `VERSION SPECIFIC` without underscore
- `ship:explain` now shows real remediation content for SEC001, SEC019, SEC020, OCT001, TEN001, INF001, INF002, QUE002, LW001
- README check count corrected from 117 to 111

### Tests
- Added 63 new tests (67 total): Filter, ConsoleReporter, CodeAnalyzer, OCT001, INF001, INF002, QUE002, BaselineCommand

## [1.1.0] — 2026-10-02

### Added
- **MCP server** (`ship:mcp`) — JSON-RPC 2.0 stdio server for AI agent integration (Claude Code, Cursor, GitHub Copilot). Exposes `ship_check`, `ship_explain`, `ship_fix_preview`, and `ship_verify` tools.
- **`ship:probe`** — Live HTTP probe: checks HSTS, CSP, X-Frame-Options, cookie flags, and 7 sensitive path exposures (/.env, /.git, /telescope, etc.).
- **`ship:routes`** — Attack-surface route map showing auth guards, throttle, CSRF, and signed middleware per route.
- **`ship:drift`** — Environment variable drift detector: compares .env files without leaking secret values.
- **`ship:install`** — Deploy gate installer for GitHub Actions (`--workflow`), Laravel Forge (`--forge`), Laravel Cloud (`--cloud`), and Envoyer (`--envoyer`).
- **`ship:next`** — Laravel upgrade readiness checker (targets L13 and L14).
- **Octane checks** (OCT001–OCT006): singleton request capture, mutable static properties, max_requests config, incompatible packages, container state leak, worker reset listener.
- **Queue checks** (QUE001–QUE005): retry_after vs timeout mismatch, HTTP jobs without retry, ShouldBeUnique with non-atomic cache, scheduler overlapping, Horizon production config.
- **Tenancy checks** (TEN001–TEN006): model tenant scope, tenant-aware jobs, cache prefix isolation, withoutGlobalScopes bypass, central route separation, Octane+Tenancy flush.
- **Package-conditional checks**: Livewire (LW001–LW002), Filament (FIL001–FIL002), Payments/webhooks (PAY001).
- **Infrastructure checks** (INF001–INF004): Dockerfile root user, .env in image, composer --no-dev, expose_php.
- **`--ci` flag** on `ship:check` — excludes non-deterministic checks for CI environments.
- **`--changed=<ref>` flag** on `ship:check` — only analyzes PHP files changed since the given git ref.
- Laravel 11 support restored (PHP `^8.2`, `illuminate/*: ^11.0|^12.0|^13.0`).
- Symfony Finder `^6.0|^7.0` constraint for broader compatibility.

### Changed
- **composer.json**: PHP requirement relaxed from `^8.3` to `^8.2` to support Laravel 11 environments.
- **composer.json**: Added `homepage`, `authors`, and extended `keywords` for Packagist SEO.
- `ConsoleReporter::detectCategory()` now maps OCT, QUE, TEN, LW/FIL/PAY, INF prefixes to their categories.
- `Filter` now accepts `ciMode` parameter.

## [1.0.0] — 2026-09-01

### Added
- 86 checks across Security (32), Performance (18), Reliability (19), and Version-Specific (16) categories.
- 7 output formats: console, JSON, SARIF, JUnit, Markdown, HTML, GitHub Annotations.
- Baseline suppression with fingerprint-based diff mode.
- Inline `@ship-ignore` suppression comments.
- `make:ship-check` scaffolding command.
- `ship:baseline` with `--prune` support.
- `ship:explain {ID}` detailed check guide.
- `ship:list` check table.
- SARIF 2.1.0 output for GitHub Code Scanning upload.
- Exit code control via `--fail-on`.
- Zero-config auto-discovery of `app/ShipReady/` custom checks.
