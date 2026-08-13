<?php

declare(strict_types=1);

/**
 * In-process HTTP smoke via Kernel (no network).
 * php backend/bin/http_smoke.php
 */

require_once __DIR__ . '/../app/bootstrap_autoload.php';

use Talamala\Http\Kernel;

$k = new Kernel();
$pass = 0;
$fail = 0;
$check = static function (string $name, bool $ok, mixed $detail = null) use (&$pass, &$fail): void {
    if ($ok) {
        echo "OK  $name\n";
        $pass++;
    } else {
        echo "FAIL $name " . json_encode($detail, JSON_UNESCAPED_UNICODE) . "\n";
        $fail++;
    }
};

$h = ['host' => 'demo.local', 'x-correlation-id' => 'http-smoke'];

$r = $k->handle('GET', '/healthz', $h, null);
$check('healthz', ($r['status'] ?? 0) === 200 && ($r['body']['status'] ?? '') === 'ok', $r);

$r = $k->handle('GET', '/readyz', $h, null);
$check('readyz', ($r['status'] ?? 0) === 200 && ($r['body']['tenant_slug'] ?? '') === 'demo', $r);

$r = $k->handle('GET', '/readyz', ['host' => 'evil.example'], null);
$check('tenant_fail_closed', ($r['status'] ?? 0) === 400, $r);

$r = $k->handle('POST', '/v1/auth/customer/otp/request', $h, [
    'mobile' => '09121234567',
    'purpose' => 'registration',
]);
$check('otp_request', ($r['status'] ?? 0) === 200 && isset($r['body']['challenge_id']), $r);
$challengeId = $r['body']['challenge_id'] ?? '';

$dev = $k->handle('GET', '/v1/dev/last-otp', $h + ['x-talamala-dev' => '1'], null);
$code = $dev['body']['code'] ?? '';
$check('dev_last_otp', $code !== '' && strlen($code) === 6, $dev);

$r = $k->handle('POST', '/v1/auth/customer/otp/verify', $h, [
    'challenge_id' => $challengeId,
    'code' => $code,
]);
$check('otp_verify_registration_required', ($r['status'] ?? 0) === 200 && ($r['body']['status'] ?? '') === 'registration_required', $r);

$r = $k->handle('POST', '/v1/auth/customer/register', $h, [
    'mobile' => '09121234567',
    'national_code' => '0012345678',
    'full_name' => 'کاربر تست',
]);
$check('register', ($r['status'] ?? 0) === 201, $r);
$customerId = $r['body']['customer_id'] ?? '';

$r = $k->handle('GET', '/v1/admin/registrations', $h, null);
$check('admin_queue', ($r['status'] ?? 0) === 200 && is_array($r['body']['items'] ?? null), $r);

$r = $k->handle('POST', '/v1/admin/registrations/' . $customerId . '/approve', $h + ['x-staff-id' => 'staff1'], null);
$check('admin_approve', ($r['status'] ?? 0) === 200 && ($r['body']['access_status'] ?? '') === 'active', $r);

// Bind kimia + seed balance for assets
$k->registration->bindKimiaAccount('00000000-0000-0000-0000-000000000001', $customerId, 350, 'bind');
$k->kimia->seedBalance(350, [
    ['Weight' => '2.0', 'Money' => '10000000', 'CurrencyId' => 11, 'CurrencySymbol' => 'ریال'],
]);

$r = $k->handle('GET', '/v1/customer/assets', $h + ['x-customer-id' => $customerId], null);
$check('assets', ($r['status'] ?? 0) === 200 && ($r['body']['money_toman'] ?? '') === '1000000', $r);

echo "\n---\nPASS=$pass FAIL=$fail\n";
exit($fail > 0 ? 1 : 0);
