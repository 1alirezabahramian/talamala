<?php

declare(strict_types=1);

/**
 * Router for PHP built-in server:
 *   php -S 127.0.0.1:8080 -t public public/router.php
 *
 * - Static files under public/ served as-is
 * - Optional Vite production builds:
 *     /app/customer/*  → frontend/customer/dist/*
 *     /app/backoffice/* → frontend/backoffice/dist/*
 *   SPA fallback to index.html when dist exists
 * - Everything else → index.php (API Kernel)
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false; // serve static file as-is
}

$repoRoot = dirname(__DIR__, 2);
$spaMap = [
    '/app/customer' => $repoRoot . '/frontend/customer/dist',
    '/app/backoffice' => $repoRoot . '/frontend/backoffice/dist',
];

foreach ($spaMap as $prefix => $distRoot) {
    if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
        if (!is_dir($distRoot)) {
            http_response_code(503);
            header('Content-Type: text/plain; charset=utf-8');
            echo "SPA build missing. Run: cd frontend/" . basename(dirname($distRoot)) . " && npm ci && npm run build\n";
            exit;
        }
        $rel = substr($path, strlen($prefix));
        $rel = $rel === '' || $rel === false ? '/index.html' : $rel;
        $candidate = $distRoot . $rel;
        // Prevent path traversal
        $realDist = realpath($distRoot);
        $realFile = is_file($candidate) ? realpath($candidate) : false;
        if ($realFile && $realDist && str_starts_with($realFile, $realDist)) {
            $ext = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));
            $types = [
                'html' => 'text/html; charset=utf-8',
                'js' => 'application/javascript; charset=utf-8',
                'css' => 'text/css; charset=utf-8',
                'json' => 'application/json',
                'svg' => 'image/svg+xml',
                'png' => 'image/png',
                'ico' => 'image/x-icon',
                'map' => 'application/json',
            ];
            header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: ' . ($ext === 'html' ? 'no-store' : 'public, max-age=3600'));
            readfile($realFile);
            exit;
        }
        // SPA fallback
        $index = $distRoot . '/index.html';
        if (is_file($index)) {
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-store');
            header('X-Content-Type-Options: nosniff');
            readfile($index);
            exit;
        }
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Not found\n";
        exit;
    }
}

require __DIR__ . '/index.php';
