<?php

declare(strict_types=1);

/**
 * Persistence-1+2: durable SQLite file round-trip across Kernel instances.
 * Covers customers (P1) + sessions + idempotency + audit (P2).
 * php backend/bin/persist_smoke.php
 */

require_once __DIR__ . '/../app/bootstrap_autoload.php';

use Talamala\Http\Kernel;
use Talamala\Domain\Idempotency\IdempotencyKey;
use Talamala\Domain\Session\SessionRecord;

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

// --- Persistence-2: session survives reboot ---
$token = $k3->issueSession($tenantId, 'customer', $customerId, 3600);
$k4 = new Kernel();
$session = $k4->sessions->get($token);
$check(
    'session_survives_reboot',
    $session !== null
        && $session->tenantId === $tenantId
        && $session->subjectId === $customerId
        && $session->subjectType === 'customer',
    $session?->subjectId
);

// --- Persistence-2: idempotency survives reboot ---
$idemKey = new IdempotencyKey($tenantId, 'persist-smoke-key-1', 'order.accept');
$resultPayload = ['order_id' => 'ord-persist-1', 'status' => 'accepted'];
$expires = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->modify('+1 hour');
$k4->idempotency->store($idemKey, $resultPayload, $expires);
$k5 = new Kernel();
$cached = $k5->idempotency->find($idemKey);
$check(
    'idempotency_survives_reboot',
    is_array($cached) && ($cached['order_id'] ?? '') === 'ord-persist-1',
    $cached
);

// --- Persistence-2: audit survives reboot (registration produced events) ---
$events = [];
if (method_exists($k5->audit, 'listForTenant')) {
    $events = $k5->audit->listForTenant($tenantId, 50);
}
$actions = array_map(static fn ($e) => $e->action, $events);
$check(
    'audit_survives_reboot',
    count($events) > 0 && (
        in_array('registration.completed', $actions, true)
        || in_array('customer.kimia_bound', $actions, true)
        || count(array_filter($actions, static fn ($a) => str_contains($a, 'registration') || str_contains($a, 'otp'))) > 0
    ),
    ['count' => count($events), 'actions' => array_slice($actions, 0, 8)]
);

@unlink($path);
putenv('TALAMALA_DB_PATH');
echo "\n---\nPASS=$pass FAIL=$fail\n";
exit($fail > 0 ? 1 : 0);
