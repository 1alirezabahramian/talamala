<?php

declare(strict_types=1);

/**
 * CORS / SecurityHeaders fail-closed checks (no network).
 * php backend/bin/cors_smoke.php
 */

require_once __DIR__ . '/../app/bootstrap_autoload.php';

use Talamala\Http\SecurityHeaders;

$pass = 0;
$fail = 0;
$check = static function (string $name, bool $ok, mixed $d = null) use (&$pass, &$fail): void {
    if ($ok) {
        echo "OK  $name\n";
        $pass++;
    } else {
        echo "FAIL $name " . json_encode($d, JSON_UNESCAPED_UNICODE) . "\n";
        $fail++;
    }
};

putenv('TALAMALA_CORS_ORIGINS');
$h = SecurityHeaders::defaults('https://evil.example');
$check('cors_deny_unknown_origin', !isset($h['Access-Control-Allow-Origin']), $h);
$check('security_nosniff', ($h['X-Content-Type-Options'] ?? '') === 'nosniff', $h);
$check('security_frame_deny', ($h['X-Frame-Options'] ?? '') === 'DENY', $h);
$check('security_no_store', ($h['Cache-Control'] ?? '') === 'no-store', $h);
$check('security_permissions_policy', str_contains($h['Permissions-Policy'] ?? '', 'camera=()'), $h);
$check('security_referrer', ($h['Referrer-Policy'] ?? '') === 'no-referrer', $h);
$check('security_cross_domain_policies', ($h['X-Permitted-Cross-Domain-Policies'] ?? '') === 'none', $h);

putenv('TALAMALA_CORS_ORIGINS=https://app.example,http://127.0.0.1:5173');
$h = SecurityHeaders::defaults('https://app.example');
$check('cors_allow_listed', ($h['Access-Control-Allow-Origin'] ?? '') === 'https://app.example', $h);
$check('cors_allow_methods_include_options', str_contains($h['Access-Control-Allow-Methods'] ?? '', 'OPTIONS'), $h);
$check('cors_allow_auth_header', str_contains($h['Access-Control-Allow-Headers'] ?? '', 'Authorization'), $h);
$check('cors_vary_origin', ($h['Vary'] ?? '') === 'Origin', $h);

$h = SecurityHeaders::defaults('https://not-listed.example');
$check('cors_deny_unlisted_when_env_set', !isset($h['Access-Control-Allow-Origin']), $h);

$h = SecurityHeaders::defaults(null);
$check('cors_null_origin_no_acao', !isset($h['Access-Control-Allow-Origin']), $h);

putenv('TALAMALA_CORS_ORIGINS');
echo "\n---\nPASS=$pass FAIL=$fail\n";
exit($fail > 0 ? 1 : 0);
