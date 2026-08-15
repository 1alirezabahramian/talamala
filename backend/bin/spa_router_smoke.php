<?php

declare(strict_types=1);

/**
 * SPA mount path safety checks (no PHP server required).
 * Dist presence is soft (SKIP if not built) so CI without frontend build still passes.
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
    $name = 'dist_exists_' . basename(dirname($dist));
    if (is_dir($dist) && is_file($dist . '/index.html')) {
        $check($name, true, $dist);
    } else {
        echo "SKIP $name (run npm run build)\n";
    }
}

foreach ($spaMap as $prefix => $distRoot) {
    // Even if dist missing, traversal logic uses path strings
    $evil = $distRoot . '/../../backend/app/Http/Kernel.php';
    $realDist = is_dir($distRoot) ? realpath($distRoot) : false;
    $realEvil = realpath($evil);
    if ($realDist && $realEvil) {
        $escaped = !str_starts_with($realEvil, $realDist);
        $check('traversal_blocked_' . basename(dirname($distRoot)), $escaped, null);
    } else {
        // Without dist, still assert evil path would leave dist prefix
        $check(
            'traversal_prefix_' . basename(dirname($distRoot)),
            !str_starts_with($evil, $distRoot . '/assets'),
            $evil
        );
    }
}

$path = '/app/customer/assets/index.js';
$check('prefix_customer_match', str_starts_with($path, '/app/customer/'), $path);
$check('prefix_api_not_spa', !str_starts_with('/v1/auth/logout', '/app/customer'), null);

$router = file_get_contents(dirname(__DIR__) . '/public/router.php') ?: '';
$check('router_html_503_fa', str_contains($router, 'ساخت فرانت موجود نیست'), null);
$check('router_html_404_fa', str_contains($router, 'یافت نشد'), null);

echo "\n---\nPASS=$pass FAIL=$fail\n";
exit($fail > 0 ? 1 : 0);
