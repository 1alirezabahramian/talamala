<?php

declare(strict_types=1);

/**
 * Persistence-1: durable SQLite file round-trip across Kernel instances.
 * php backend/bin/persist_smoke.php
 */

require_once __DIR__ . '/../app/bootstrap_autoload.php';

use Talamala\Http\Kernel;

$path = sys_get_temp_dir() . '/talamala_persist_smoke.sqlite';
@unlink($path);
putenv('TALAMALA_DB_PATH=' . $path);

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

$h = ['host' => 'demo.local', 'x-correlation-id' => 'persist-smoke'];
$tenantId = '00000000-0000-0000-0000-000000000001';

$k1 = new Kernel();
$r = $k1->handle('POST', '/v1/auth/customer/otp/request', $h, [
    'mobile' => '09121234567',
    'purpose' => 'registration',
]);
$check('otp_request', ($r['status'] ?? 0) === 200, $r);
$challenge = $r['body']['challenge_id'] ?? '';
$last = $k1->sms->sent[array_key_last($k1->sms->sent)] ?? null;
$code = $last['parameters']['Code'] ?? '';
$r = $k1->handle('POST', '/v1/auth/customer/otp/verify', $h, [
    'challenge_id' => $challenge,
    'code' => $code,
]);
$check('otp_verify', ($r['status'] ?? 0) === 200 && ($r['body']['status'] ?? '') === 'registration_required', $r);

$r = $k1->handle('POST', '/v1/auth/customer/register', $h, [
    'mobile' => '09121234567',
    'national_code' => '0012345678',
    'full_name' => 'Persist Test',
]);
$check('register', ($r['status'] ?? 0) === 201, $r);
$customerId = $r['body']['customer_id'] ?? '';

$k2 = new Kernel();
$found = $k2->customers->findById($tenantId, $customerId);
$check('customer_survives_reboot', $found !== null && $found->mobile === '09121234567', $found?->mobile);

$r = $k2->handle('POST', '/v1/dev/bind-kimia', $h + ['x-talamala-dev' => '1'], [
    'customer_id' => $customerId,
    'kimia_account_id' => 350,
    'seed_money_rial' => '50000000',
    'seed_gold_weight_g' => '3.5',
]);
$check('bind', ($r['status'] ?? 0) === 200, $r);

$k3 = new Kernel();
$bound = $k3->customers->findById($tenantId, $customerId);
$check('bind_survives_reboot', $bound?->kimiaAccountId === 350, $bound?->kimiaAccountId);

@unlink($path);
putenv('TALAMALA_DB_PATH');
echo "\n---\nPASS=$pass FAIL=$fail\n";
exit($fail > 0 ? 1 : 0);
