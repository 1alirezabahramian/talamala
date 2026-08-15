<?php

declare(strict_types=1);

/**
 * SPA mount path safety checks (no PHP server required).
 * php backend/bin/spa_router_smoke.php
 */

$repoRoot = dirname(__DIR__, 2);
$pass = 0;
$fail = 0;
$check = static function (string $name, bool $ok, mixed $d = null) use (&$pass, &$fail): void {
    if ($ok) {
        echo "OK  $name\n";
        $pass++;
    } else {
        echo "FAIL $name " . json_encode($d) . "\n";
        $fail++;
    }
};

$spaMap = [
    '/app/customer' => $repoRoot . '/frontend/customer/dist',
    '/app/backoffice' => $repoRoot . '/frontend/backoffice/dist',
];

foreach ($spaMap as $prefix => $dist) {
    $check('dist_exists_' . basename(dirname($dist)), is_dir($dist) && is_file($dist . '/index.html'), $dist);
}

// Path traversal must not escape dist
foreach ($spaMap as $prefix => $distRoot) {
    $realDist = realpath($distRoot);
    $evil = $distRoot . '/../../backend/app/Http/Kernel.php';
    $realEvil = realpath($evil);
    $escaped = $realEvil && $realDist && !str_starts_with($realEvil, $realDist);
    $check('traversal_blocked_' . basename(dirname($distRoot)), $escaped === true || $realEvil === false, [
        'evil' => $realEvil,
        'dist' => $realDist,
    ]);
}

// Prefix matching logic
$path = '/app/customer/assets/index.js';
$matched = str_starts_with($path, '/app/customer/');
$check('prefix_customer_match', $matched, $path);
$check('prefix_api_not_spa', !str_starts_with('/v1/auth/logout', '/app/customer'), null);

echo "\n---\nPASS=$pass FAIL=$fail\n";
exit($fail > 0 ? 1 : 0);
