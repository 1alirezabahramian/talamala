<?php

declare(strict_types=1);

/**
 * Aggregate local checks (no Composer required).
 * php backend/bin/check.php
 */

$root = dirname(__DIR__);
$bins = [
    'http_smoke' => $root . '/bin/http_smoke.php',
    'persist_smoke' => $root . '/bin/persist_smoke.php',
    'cors_smoke' => $root . '/bin/cors_smoke.php',
    'logger_smoke' => $root . '/bin/logger_smoke.php',
    'maintenance_smoke' => $root . '/bin/maintenance_smoke.php',
    'openapi_parity' => $root . '/bin/openapi_parity_check.php',
];

$failed = [];
foreach ($bins as $name => $path) {
    if (!is_file($path)) {
        echo "SKIP $name (missing)\n";
        continue;
    }
    echo "=== $name ===\n";
    passthru('php ' . escapeshellarg($path), $code);
    if ($code !== 0) {
        $failed[] = $name;
    }
    echo "\n";
}

echo $failed === [] ? "ALL CHECKS PASSED\n" : ("CHECKS FAILED: " . implode(', ', $failed) . "\n");
exit($failed === [] ? 0 : 1);
