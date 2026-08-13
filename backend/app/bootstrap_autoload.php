<?php

declare(strict_types=1);

/**
 * Simple PSR-4-ish autoload for Talamala\ without Composer (dev smoke).
 * Prefer composer dump-autoload in real deploy.
 */

spl_autoload_register(static function (string $class): void {
    $prefix = 'Talamala\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});
