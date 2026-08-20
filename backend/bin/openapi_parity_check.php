<?php

declare(strict_types=1);

/**
 * Fail-closed OpenAPI ↔ runtime route parity check.
 * Production routes only. /v1/dev/* intentionally excluded.
 *
 * Inventory source: backend/app/Http/Kernel.php (handle method).
 * Exit 0 = PASS, non-zero = FAIL.
 */

$root = dirname(__DIR__, 2);
$files = [
    $root . '/openapi/auth-v1.openapi.yaml',
    $root . '/openapi/customer-v1.openapi.yaml',
    $root . '/openapi/backoffice-v1.openapi.yaml',
];

// Canonical production routes from Kernel (2026-08-15). Path params as {id}.
$expected = [
    'GET /healthz',
    'GET /readyz',
    'POST /v1/auth/customer/otp/request',
    'POST /v1/auth/customer/otp/verify',
    'POST /v1/auth/customer/register',
    'POST /v1/auth/staff/login',
    'POST /v1/auth/staff/password/rotate',
    'POST /v1/auth/logout',
    'GET /v1/customer/assets',
    'GET /v1/customer/custody',
    'POST /v1/customer/orders/accept',
    'GET /v1/customer/orders',
    'GET /v1/customer/me',
    'GET /v1/customer/quotes/{id}',
    'GET /v1/admin/registrations',
    'POST /v1/admin/registrations/{id}/approve',
    'POST /v1/admin/custody/receive',
    'POST /v1/admin/custody/{id}/ready',
    'POST /v1/admin/custody/{id}/deliver',
    'GET /v1/admin/orders',
    'GET /v1/admin/customers',
];

// Dev routes — must NOT appear in OpenAPI (local + X-Talamala-Dev only)
$devExcluded = [
    'GET /v1/dev/last-otp',
    'POST /v1/dev/seed-quote',
    'POST /v1/dev/session',
    'POST /v1/dev/bind-kimia',
];

function extractRoutesFromYaml(string $content): array
{
    $routes = [];
    $currentPath = null;
    foreach (explode("\n", $content) as $line) {
        if (preg_match('/^  (\/[^\s:]+):\s*$/', $line, $m)) {
            $currentPath = $m[1];
            continue;
        }
        if ($currentPath !== null && preg_match('/^    (get|post|put|patch|delete):\s*$/', $line, $m)) {
            $routes[strtoupper($m[1]) . ' ' . $currentPath] = true;
        }
    }
    return $routes;
}

$found = [];
foreach ($files as $file) {
    if (!is_file($file)) {
        fwrite(STDERR, "MISSING OpenAPI file: $file\n");
        exit(1);
    }
    foreach (extractRoutesFromYaml(file_get_contents($file)) as $route => $_) {
        $found[$route] = basename($file);
    }
}

$pass = 0;
$fail = 0;
$missing = [];
$extraDev = [];

echo "OpenAPI parity check (production routes only)\n";
echo str_repeat('-', 60) . "\n";

foreach ($expected as $route) {
    if (isset($found[$route])) {
        echo "OK   $route  (" . $found[$route] . ")\n";
        $pass++;
    } else {
        echo "MISS $route\n";
        $missing[] = $route;
        $fail++;
    }
}

foreach ($devExcluded as $route) {
    if (isset($found[$route])) {
        echo "LEAK $route present in OpenAPI (must stay local-only)\n";
        $extraDev[] = $route;
        $fail++;
    } else {
        echo "OK   excluded $route\n";
        $pass++;
    }
}

$authYaml = file_get_contents($files[0]);
if (preg_match("/['\"]?429['\"]?\s*:/", $authYaml)) {
    echo "OK   OTP 429 documented in auth OpenAPI\n";
    $pass++;
} else {
    echo "MISS OTP 429 response in auth OpenAPI\n";
    $fail++;
}

echo str_repeat('-', 60) . "\n";
echo "PASS=$pass FAIL=$fail\n";

if ($fail > 0) {
    if ($missing) {
        echo "Missing production routes in OpenAPI:\n  - " . implode("\n  - ", $missing) . "\n";
    }
    if ($extraDev) {
        echo "Dev routes must not be in OpenAPI:\n  - " . implode("\n  - ", $extraDev) . "\n";
    }
    exit(1);
}

echo "parity OK\n";
exit(0);
