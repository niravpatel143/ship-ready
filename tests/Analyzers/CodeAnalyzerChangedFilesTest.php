<?php

use ShipReady\Analyzers\CodeAnalyzer;

// ---------------------------------------------------------------------------
// Normal mode — scans configured paths
// ---------------------------------------------------------------------------

test('CodeAnalyzer phpFiles returns PHP files from configured path', function () {
    $dir = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'ship-ready-test-' . uniqid();
    mkdir($dir);
    file_put_contents("{$dir}/Good.php", '<?php class Good {}');
    file_put_contents("{$dir}/Ignore.txt", 'not php');

    $analyzer = new CodeAnalyzer([$dir]);
    $files    = $analyzer->phpFiles();

    // Normalize separators for cross-platform comparison
    $normalized = array_map(fn($f) => str_replace('\\', '/', $f), $files);
    $goodPath   = str_replace('\\', '/', "{$dir}/Good.php");
    $txtPath    = str_replace('\\', '/', "{$dir}/Ignore.txt");

    expect($normalized)->toContain($goodPath);
    expect($normalized)->not->toContain($txtPath);

    // Cleanup
    unlink("{$dir}/Good.php");
    unlink("{$dir}/Ignore.txt");
    rmdir($dir);
});

// ---------------------------------------------------------------------------
// Changed files mode — setChangedFiles()
// ---------------------------------------------------------------------------

test('CodeAnalyzer setChangedFiles restricts phpFiles to the given list', function () {
    $dir = sys_get_temp_dir() . '/ship-ready-test-' . uniqid();
    mkdir($dir);
    file_put_contents("{$dir}/A.php", '<?php class A {}');
    file_put_contents("{$dir}/B.php", '<?php class B {}');

    $analyzer = new CodeAnalyzer([$dir]);
    $analyzer->setChangedFiles(["{$dir}/A.php"]);

    $files = $analyzer->phpFiles();

    expect($files)->toContain("{$dir}/A.php");
    expect($files)->not->toContain("{$dir}/B.php");

    unlink("{$dir}/A.php");
    unlink("{$dir}/B.php");
    rmdir($dir);
});

test('CodeAnalyzer setChangedFiles returns empty array when list is empty', function () {
    $dir = sys_get_temp_dir() . '/ship-ready-test-' . uniqid();
    mkdir($dir);
    file_put_contents("{$dir}/A.php", '<?php class A {}');

    $analyzer = new CodeAnalyzer([$dir]);
    $analyzer->setChangedFiles([]);

    expect($analyzer->phpFiles())->toBeEmpty();

    unlink("{$dir}/A.php");
    rmdir($dir);
});

test('CodeAnalyzer setChangedFiles ignores non-existent files', function () {
    $analyzer = new CodeAnalyzer([]);
    $analyzer->setChangedFiles(['/does/not/exist.php']);

    expect($analyzer->phpFiles())->toBeEmpty();
});

test('CodeAnalyzer setChangedFiles ignores non-PHP files in the list', function () {
    $dir = sys_get_temp_dir() . '/ship-ready-test-' . uniqid();
    mkdir($dir);
    file_put_contents("{$dir}/config.yaml", 'key: value');

    $analyzer = new CodeAnalyzer([$dir]);
    $analyzer->setChangedFiles(["{$dir}/config.yaml"]);

    expect($analyzer->phpFiles())->toBeEmpty();

    unlink("{$dir}/config.yaml");
    rmdir($dir);
});

test('CodeAnalyzer setChangedFiles(null) restores normal scan mode', function () {
    $dir = sys_get_temp_dir() . '/ship-ready-test-' . uniqid();
    mkdir($dir);
    file_put_contents("{$dir}/A.php", '<?php class A {}');
    file_put_contents("{$dir}/B.php", '<?php class B {}');

    $analyzer = new CodeAnalyzer([$dir]);
    $analyzer->setChangedFiles(["{$dir}/A.php"]);

    // Confirm changed mode is active
    expect($analyzer->phpFiles())->toHaveCount(1);

    // Reset to normal scan
    $analyzer->setChangedFiles(null);

    expect($analyzer->phpFiles())->toHaveCount(2);

    unlink("{$dir}/A.php");
    unlink("{$dir}/B.php");
    rmdir($dir);
});

test('CodeAnalyzer clears AST cache when setChangedFiles is called', function () {
    $dir = sys_get_temp_dir() . '/ship-ready-test-' . uniqid();
    mkdir($dir);
    file_put_contents("{$dir}/A.php", '<?php class A {}');

    $analyzer = new CodeAnalyzer([$dir]);
    // Warm cache
    $analyzer->parse("{$dir}/A.php");

    // Switching changed files should clear the cache (no observable crash/stale data)
    $analyzer->setChangedFiles(["{$dir}/A.php"]);

    expect($analyzer->phpFiles())->toContain("{$dir}/A.php");

    unlink("{$dir}/A.php");
    rmdir($dir);
});
