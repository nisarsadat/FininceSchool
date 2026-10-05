<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

try {
    require __DIR__ . '/../public/index.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo '<pre>';
    echo get_class($e) . "\n\n";
    echo $e->getMessage() . "\n\n";
    echo $e->getTraceAsString();
    echo '</pre>';
}
