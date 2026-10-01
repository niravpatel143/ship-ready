<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paths to Scan
    |--------------------------------------------------------------------------
    | Directories that the code analyzer will scan for PHP files.
    */
    'paths' => [
        app_path(),
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Paths
    |--------------------------------------------------------------------------
    | Glob patterns for paths to exclude from scanning.
    */
    'exclude' => [
        '**/vendor/**',
        '**/node_modules/**',
        '**/storage/**',
        '**/bootstrap/cache/**',
    ],

    /*
    |--------------------------------------------------------------------------
    | Checks to Run
    |--------------------------------------------------------------------------
    | Set to null to run all built-in checks, or provide an explicit list of
    | check class names to run only those checks.
    */
    'checks' => null,

    /*
    |--------------------------------------------------------------------------
    | Disabled Checks
    |--------------------------------------------------------------------------
    | Check IDs listed here will be skipped even if included in 'checks'.
    */
    'disabled' => [
        // 'SEC001',
    ],

    /*
    |--------------------------------------------------------------------------
    | Severity Overrides
    |--------------------------------------------------------------------------
    | Override the default severity for specific checks.
    | Keys are check IDs, values are severity strings.
    */
    'severity_overrides' => [
        // 'SEC001' => 'high',
    ],

    /*
    |--------------------------------------------------------------------------
    | Fail On
    |--------------------------------------------------------------------------
    | The minimum severity level that will cause ship:check to exit with
    | a non-zero exit code. Accepts: critical, high, medium, low, info.
    */
    'fail_on' => env('SHIP_READY_FAIL_ON', 'high'),

    /*
    |--------------------------------------------------------------------------
    | Baseline Path
    |--------------------------------------------------------------------------
    | Path to the baseline JSON file. Baseline findings are suppressed from
    | output and do not affect the exit code.
    */
    'baseline' => base_path('.ship-ready-baseline.json'),

    /*
    |--------------------------------------------------------------------------
    | Editor
    |--------------------------------------------------------------------------
    | The editor to open files in when clicking on file links.
    | Supported: phpstorm, vscode, sublime, atom
    */
    'editor' => env('SHIP_READY_EDITOR', null),

    /*
    |--------------------------------------------------------------------------
    | Admin Route Prefixes
    |--------------------------------------------------------------------------
    | URI prefixes considered admin routes for authorization checks.
    */
    'admin_route_prefixes' => [
        'admin',
        'dashboard',
        'manage',
        'backoffice',
        'panel',
    ],

    /*
    |--------------------------------------------------------------------------
    | Auth Route Names
    |--------------------------------------------------------------------------
    | Route name patterns considered authentication routes for throttle checks.
    */
    'auth_route_names' => [
        'login',
        'register',
        'password.*',
        'auth.*',
        '*.login',
        '*.register',
    ],

    /*
    |--------------------------------------------------------------------------
    | View Paths
    |--------------------------------------------------------------------------
    | Directories containing Blade view files to analyze.
    */
    'view_paths' => [
        resource_path('views'),
    ],

];
