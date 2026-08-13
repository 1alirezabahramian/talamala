<?php

declare(strict_types=1);

/**
 * Router script for PHP built-in server:
 *   php -S 127.0.0.1:8080 -t public public/router.php
 *
 * Passes all non-file requests to index.php.
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false; // serve static file as-is
}

require __DIR__ . '/index.php';
