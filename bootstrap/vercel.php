<?php

$temporaryPath = sys_get_temp_dir() . '/veltrix';
$storagePath = $temporaryPath . '/storage';
$cachePath = $temporaryPath . '/bootstrap/cache';

foreach (
    [
        $cachePath,
        $storagePath . '/app/private',
        $storagePath . '/framework/cache/data',
        $storagePath . '/framework/cache/dompdf',
        $storagePath . '/framework/sessions',
        $storagePath . '/framework/views',
        $storagePath . '/logs',
    ]
    as $directory
) {
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Cannot create Laravel runtime directory.');
    }
}

foreach (
    [
        'LARAVEL_STORAGE_PATH' => $storagePath,
        'VIEW_COMPILED_PATH' => $storagePath . '/framework/views',
        'APP_CONFIG_CACHE' => $cachePath . '/config.php',
        'APP_EVENTS_CACHE' => $cachePath . '/events.php',
        'APP_PACKAGES_CACHE' => $cachePath . '/packages.php',
        'APP_SERVICES_CACHE' => $cachePath . '/services.php',
        'APP_ROUTES_CACHE' => $cachePath . '/routes.php',
    ]
    as $key => $value
) {
    $_ENV[$key] = $_SERVER[$key] = $value;
    putenv($key . '=' . $value);
}

foreach (['packages.php', 'services.php'] as $manifest) {
    $source = __DIR__ . '/cache/' . $manifest;
    $destination = $cachePath . '/' . $manifest;

    if (is_file($source) && !is_file($destination) && !copy($source, $destination)) {
        throw new RuntimeException('Cannot initialize Laravel package manifests.');
    }
}
