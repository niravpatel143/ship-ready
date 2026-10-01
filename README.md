# ShipReady

Security, performance, and production-readiness auditor for Laravel 9–13+.

## Installation

```bash
composer require yourvendor/ship-ready --dev
```

## Usage

Run all checks against production:

```bash
php artisan ship:check --env=production
```

Run only security checks:

```bash
php artisan ship:check --category=security
```

Output as JSON for CI:

```bash
php artisan ship:check --format=json --output=ship-ready-report.json
```

Output SARIF for GitHub Code Scanning:

```bash
php artisan ship:check --format=sarif --output=results.sarif
```

## Commands

| Command | Description |
|---------|-------------|
| `ship:check` | Run all checks and report findings |
| `ship:baseline` | Record current findings as baseline (suppress them) |
| `ship:explain {ID}` | Show detailed explanation for a check |
| `ship:list` | List all available checks |
| `make:ship-check {Name}` | Generate a custom check class |

## Options for `ship:check`

| Option | Default | Description |
|--------|---------|-------------|
| `--env` | `production` | Target environment |
| `--category` | all | Filter by category |
| `--check` | all | Run a single check by ID |
| `--format` | `console` | Output format: console, json, sarif, junit, markdown, html, github |
| `--output` | stdout | Write report to file |
| `--fail-on` | `high` | Minimum severity to exit non-zero |
| `--compact` | false | Compact output without fix hints |
| `--ignore-baseline` | false | Run all checks ignoring baseline |

## Checks

### Security (26 checks)

| ID | Title | Severity |
|----|-------|----------|
| SEC001 | Debug mode enabled in production | critical |
| SEC002 | Application key missing or insecure | critical |
| SEC003 | Vulnerable Composer packages | high |
| SEC004 | Vulnerable npm packages | high |
| SEC005 | Model with no fillable/guarded | high |
| SEC006 | Model::unguard() outside seeders | high |
| SEC007 | $request->all() mass assignment | high |
| SEC008 | Raw Blade output {!! !!} | medium |
| SEC009 | SQL injection in raw queries | critical |
| SEC010 | Auth routes without throttle | high |
| SEC011 | CSRF exclusions | medium |
| SEC012 | Insecure session cookie config | high |
| SEC013 | APP_URL using HTTP | high |
| SEC014 | Missing security headers | medium |
| SEC015 | CORS wildcard + credentials | critical |
| SEC016 | Debug tools exposed in production | high |
| SEC017 | Sensitive files in public/ | critical |
| SEC018 | env() outside config files | medium |
| SEC019 | Hardcoded secrets in source | critical |
| SEC020 | Dangerous PHP functions | critical |
| SEC021 | Unvalidated file uploads | high |
| SEC022 | Admin routes without authorization | high |
| SEC023 | Sanctum token never expires | medium |
| SEC024 | Weak password hashing | high |
| SEC025 | signedRoute without signed middleware | medium |
| SEC026 | Open redirect vulnerability | high |

### Performance (14 checks)

PERF001–PERF014: Config/Route/View caching, OPcache, autoloader optimization, slow cache drivers, N+1 queries, missing indexes, and more.

### Reliability (14 checks)

REL001–REL014: Pending migrations, scheduler, queue config, mail driver, storage permissions, health routes, .env.example sync, and more.

### Version-Specific (6 checks)

VER001–VER006: Laravel 11+ middleware changes, Reverb TLS, AI SDK keys, passkey config, and more.

## Baseline

Suppress known issues without fixing them immediately:

```bash
# Record all current findings as baseline
php artisan ship:baseline

# Remove resolved findings from baseline
php artisan ship:baseline --prune
```

## Custom Checks

Generate a check:

```bash
php artisan make:ship-check MyCustomCheck --category=security --severity=high
```

This creates `app/ShipReady/MyCustomCheck.php`. Custom checks are auto-discovered.

## Configuration

Publish the config:

```bash
php artisan vendor:publish --tag=ship-ready-config
```

Key options in `config/ship-ready.php`:

- `checks` — null (all) or array of check class names
- `disabled` — array of check IDs to skip
- `severity_overrides` — override severity per check ID
- `fail_on` — minimum severity to exit non-zero (env: `SHIP_READY_FAIL_ON`)
- `baseline` — path to baseline JSON file
- `editor` — editor for file links (env: `SHIP_READY_EDITOR`)

## CI Integration

GitHub Actions:

```yaml
- name: Run ShipReady
  run: php artisan ship:check --env=production --format=github
```

SARIF upload:

```yaml
- name: Run ShipReady
  run: php artisan ship:check --format=sarif --output=results.sarif

- name: Upload SARIF
  uses: github/codeql-action/upload-sarif@v3
  with:
    sarif_file: results.sarif
```

## License

MIT
