<?php

// Vercel's serverless filesystem is read-only outside /tmp — route every
// path Laravel writes at runtime (caches, manifests, logs) into /tmp.
// This entry point is only used on Vercel; local development is unaffected.
$writableDirs = [
    '/tmp/cache',
    '/tmp/storage/framework/views',
];

foreach ($writableDirs as $dir) {
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

putenv('APP_CONFIG_CACHE=/tmp/config.php');
putenv('APP_ROUTES_CACHE=/tmp/routes.php');
putenv('APP_EVENTS_CACHE=/tmp/events.php');
putenv('APP_PACKAGES_CACHE=/tmp/cache/packages.php');
putenv('APP_SERVICES_CACHE=/tmp/cache/services.php');
putenv('VIEW_COMPILED_PATH=/tmp/storage/framework/views');
putenv('LOG_CHANNEL=stderr');

// Vercel terminates TLS at the edge; the PHP runtime sees plain HTTP.
// Without this, Laravel generates http:// asset URLs (mixed content).
$_SERVER['HTTPS'] = 'on';

// TEMP DIAGNOSTIC: report how the runtime hands the path to PHP.
header('X-Probe-Request-Uri: '.($_SERVER['REQUEST_URI'] ?? ''));
header('X-Probe-Script-Name: '.($_SERVER['SCRIPT_NAME'] ?? ''));
header('X-Probe-Php-Self: '.($_SERVER['PHP_SELF'] ?? ''));
header('X-Probe-Script-Filename: '.($_SERVER['SCRIPT_FILENAME'] ?? ''));
header('X-Probe-Path-Info: '.($_SERVER['PATH_INFO'] ?? ''));
header('X-Probe-Query-String: '.($_SERVER['QUERY_STRING'] ?? ''));

require __DIR__.'/../public/index.php';
