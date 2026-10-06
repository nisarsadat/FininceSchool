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

require __DIR__.'/../public/index.php';
