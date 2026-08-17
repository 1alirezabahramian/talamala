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

$htmlSecurityHeaders = static function (): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('X-Permitted-Cross-Domain-Policies: none');
    header('Cache-Control: no-store');
    // Minimal CSP for static HTML demos (inline style/script required by zero-build demos)
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "style-src 'self' 'unsafe-inline'; "
        . "script-src 'self' 'unsafe-inline'; "
        . "img-src 'self' data:; "
        . "connect-src 'self'; "
        . "frame-ancestors 'none'; "
        . "base-uri 'self'; "
        . "form-action 'self'"
    );
};

$buildMeta = static function (): array {
    $repoRoot = dirname(__DIR__, 2);
    $version = 'dev';
    $verFile = $repoRoot . '/VERSION';
    if (is_file($verFile)) {
        $version = trim((string) file_get_contents($verFile)) ?: 'dev';
    }
    $sha = getenv('TALAMALA_BUILD_SHA') ?: 'local';
    if (is_string($sha)) {
        $sha = substr(preg_replace('/[^a-fA-F0-9]/', '', $sha) ?: 'local', 0, 12);
    }
    return ['version' => $version, 'sha' => $sha === '' ? 'local' : $sha];
};

$serveHtmlFile = static function (string $absolutePath, bool $injectMeta = false) use ($htmlSecurityHeaders, $buildMeta): void {
    header('Content-Type: text/html; charset=utf-8');
    $htmlSecurityHeaders();
    $body = (string) file_get_contents($absolutePath);
    if ($injectMeta) {
        $meta = $buildMeta();
        $body = str_replace(
            ['{{VERSION}}', '{{BUILD_SHA}}'],
            [htmlspecialchars($meta['version'], ENT_QUOTES, 'UTF-8'), htmlspecialchars($meta['sha'], ENT_QUOTES, 'UTF-8')],
            $body
        );
    }
    echo $body;
};

// Local operator hub
if ($path === '/' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $landing = __DIR__ . '/landing.html';
    if (is_file($landing)) {
        $serveHtmlFile($landing, true);
        exit;
    }
}

// Static HTML demos under public/ — same baseline security headers
if ($path !== '/' && is_file($file) && str_ends_with(strtolower($path), '.html')) {
    $serveHtmlFile($file, false);
    exit;
}

if ($path !== '/' && is_file($file)) {
    return false; // non-HTML static (css/js/img) as-is
}

/**
 * Minimal RTL HTML error page (no framework).
 */
$spaHtml = static function (int $status, string $title, string $message, string $hint = ''): void {
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('X-Permitted-Cross-Domain-Policies: none');
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "style-src 'self' 'unsafe-inline'; "
        . "script-src 'self' 'unsafe-inline'; "
        . "img-src 'self' data:; "
        . "connect-src 'self'; "
        . "frame-ancestors 'none'; "
        . "base-uri 'self'; "
        . "form-action 'self'"
    );
    $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeMsg = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeHint = htmlspecialchars($hint, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"/>';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1"/>';
    echo '<title>' . $safeTitle . '</title>';
    echo '<style>body{margin:0;font-family:system-ui,Tahoma,sans-serif;background:#0f1419;color:#e7ecf3;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:1.5rem}';
    echo '.card{max-width:420px;background:#151b24;border:1px solid #2a3548;border-radius:12px;padding:1.25rem}';
    echo 'h1{font-size:1.2rem;margin:0 0 .5rem}.muted{color:#9aa4b2;font-size:.9rem;line-height:1.5}';
    echo 'code{background:#0f1419;padding:.1rem .35rem;border-radius:4px;font-size:.85rem}</style></head><body>';
    echo '<div class="card"><h1>' . $safeTitle . '</h1><p class="muted">' . $safeMsg . '</p>';
    if ($safeHint !== '') {
        echo '<p class="muted"><code>' . $safeHint . '</code></p>';
    }
    echo '</div></body></html>';
};

$repoRoot = dirname(__DIR__, 2);
$spaMap = [
    '/app/customer' => $repoRoot . '/frontend/customer/dist',
    '/app/backoffice' => $repoRoot . '/frontend/backoffice/dist',
];

foreach ($spaMap as $prefix => $distRoot) {
    if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
        $appName = basename(dirname($distRoot));
        if (!is_dir($distRoot)) {
            $spaHtml(
                503,
                'ساخت فرانت موجود نیست',
                'برای سرو این مسیر باید Vite build گرفته شود.',
                'cd frontend/' . $appName . ' && npm ci && npm run build'
            );
            exit;
        }
        $rel = substr($path, strlen($prefix));
        $rel = $rel === '' || $rel === false ? '/index.html' : $rel;
        $candidate = $distRoot . $rel;
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
        $index = $distRoot . '/index.html';
        if (is_file($index)) {
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-store');
            header('X-Content-Type-Options: nosniff');
            readfile($index);
            exit;
        }
        $spaHtml(404, 'یافت نشد', 'فایل یا صفحه در build فرانت پیدا نشد.', $prefix);
        exit;
    }
}

require __DIR__ . '/index.php';
