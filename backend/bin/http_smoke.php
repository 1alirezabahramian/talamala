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

// Staff login + password rotation (before admin routes)
$r = $k->handle('POST', '/v1/auth/staff/login', $h, [
    'username' => 'operator',
    'password' => 'ChangeMe-Now-1',
]);
$check('staff_login', ($r['status'] ?? 0) === 200 && ($r['body']['must_change_password'] ?? false) === true, $r);
$staffId = $r['body']['staff_id'] ?? '';
$staffToken = $r['body']['access_token'] ?? '';
$check('staff_token_issued', $staffToken !== '', $r);

$r = $k->handle('POST', '/v1/auth/staff/password/rotate', $h + ['x-staff-id' => $staffId], [
    'current_password' => 'ChangeMe-Now-1',
    'new_password' => 'Strong-Pass-99',
]);
$check('staff_rotate', ($r['status'] ?? 0) === 200, $r);

$staffH = $h + ['authorization' => 'Bearer ' . $staffToken];

$r = $k->handle('GET', '/v1/admin/registrations', $staffH, null);
$check('admin_queue', ($r['status'] ?? 0) === 200 && is_array($r['body']['items'] ?? null), $r);

$r = $k->handle('POST', '/v1/admin/registrations/' . $customerId . '/approve', $staffH, null);
$check('admin_approve', ($r['status'] ?? 0) === 200 && ($r['body']['access_status'] ?? '') === 'active', $r);

// Admin without auth must fail
$r = $k->handle('GET', '/v1/admin/registrations', $h, null);
$check('admin_unauthorized', ($r['status'] ?? 0) === 401, $r);

// Bind kimia via dev route (local only) + optional fake balance seed
$r = $k->handle('POST', '/v1/dev/bind-kimia', $h + ['x-talamala-dev' => '1'], [
    'customer_id' => $customerId,
    'kimia_account_id' => 350,
    'seed_money_rial' => '10000000',
    'seed_gold_weight_g' => '2.0',
]);
$check('dev_bind_kimia', ($r['status'] ?? 0) === 200 && ($r['body']['kimia_bound'] ?? false) === true, $r);

$r = $k->handle('GET', '/v1/customer/assets', $h + ['x-customer-id' => $customerId], null);
$check('assets', ($r['status'] ?? 0) === 200 && ($r['body']['money_toman'] ?? '') === '1000000', $r);

// Custody lifecycle via staff Bearer
$r = $k->handle('POST', '/v1/admin/custody/receive', $staffH, [
    'customer_id' => $customerId,
    'description' => 'سکه امانت تست',
    'weight_grams' => '8.100',
    'fineness' => '900',
]);
$check('custody_receive', ($r['status'] ?? 0) === 201, $r);
$custodyId = $r['body']['id'] ?? '';

$r = $k->handle('POST', '/v1/admin/custody/' . $custodyId . '/ready', $staffH, null);
$check('custody_ready', ($r['status'] ?? 0) === 200 && ($r['body']['status'] ?? '') === 'ready_for_pickup', $r);

$r = $k->handle('POST', '/v1/admin/custody/' . $custodyId . '/deliver', $staffH, null);
$check('custody_deliver', ($r['status'] ?? 0) === 200 && ($r['body']['status'] ?? '') === 'delivered', $r);

$r = $k->handle('GET', '/v1/customer/custody', $h + ['x-customer-id' => $customerId], null);
$check('customer_custody_list', ($r['status'] ?? 0) === 200 && count($r['body']['items'] ?? []) >= 1, $r);

// Order: seed fixture quote → accept (idempotent) → list (settlement blocked)
$r = $k->handle('POST', '/v1/dev/seed-quote', $h + ['x-talamala-dev' => '1'], [
    'customer_id' => $customerId,
    'quantity' => '1.000',
    'unit_price_rial' => '350000000',
    'total_rial' => '350000000',
]);
$check('seed_quote', ($r['status'] ?? 0) === 201, $r);
$quoteId = $r['body']['quote_id'] ?? '';

$r = $k->handle('POST', '/v1/customer/orders/accept', $h + [
    'x-customer-id' => $customerId,
    'idempotency-key' => 'idem-http-1',
], ['quote_id' => $quoteId]);
$check('order_accept', ($r['status'] ?? 0) === 200 && ($r['body']['settlement'] ?? '') === 'blocked_by_ground_truth', $r);

$r2 = $k->handle('POST', '/v1/customer/orders/accept', $h + [
    'x-customer-id' => $customerId,
    'idempotency-key' => 'idem-http-1',
], ['quote_id' => $quoteId]);
$check('order_idempotent', ($r2['status'] ?? 0) === 200 && ($r2['body']['from_idempotency_cache'] ?? false) === true, $r2);

$r = $k->handle('GET', '/v1/customer/orders', $h + ['x-customer-id' => $customerId], null);
$check('order_list', ($r['status'] ?? 0) === 200 && count($r['body']['items'] ?? []) >= 1, $r);

// Session issue (skeleton)
$r = $k->handle('POST', '/v1/dev/session', $h + ['x-talamala-dev' => '1'], [
    'subject_type' => 'customer',
    'subject_id' => $customerId,
]);
$check('session_issue', ($r['status'] ?? 0) === 200 && isset($r['body']['access_token']), $r);
$token = $r['body']['access_token'] ?? '';

// Bearer auth path for assets (no X-Customer-Id)
$r = $k->handle('GET', '/v1/customer/assets', $h + [
    'authorization' => 'Bearer ' . $token,
], null);
$check('assets_bearer', ($r['status'] ?? 0) === 200 && ($r['body']['money_toman'] ?? '') === '1000000', $r);

// Unauthorized without identity
$r = $k->handle('GET', '/v1/customer/assets', $h, null);
$check('assets_unauthorized', ($r['status'] ?? 0) === 401, $r);

// OTP rate limit (5 / window) — use dedicated mobile
$limited = false;
for ($i = 0; $i < 6; $i++) {
    $r = $k->handle('POST', '/v1/auth/customer/otp/request', $h, [
        'mobile' => '09120001111',
        'purpose' => 'login',
    ]);
    if (($r['status'] ?? 0) === 429) {
        $limited = true;
        break;
    }
}
$check('otp_rate_limited', $limited, $r ?? null);
$r = $k->handle('GET', '/readyz', $h, null);
$check(
    'readyz_ops_rate_limited',
    ($r['status'] ?? 0) === 200
        && (($r['body']['ops']['rate_limited'] ?? 0) >= 1)
        && (($r['body']['checks']['sqlite'] ?? '') === 'ok'),
    $r
);


// --- Session negative / revoke ---
// Garbage bearer
$r = $k->handle('GET', '/v1/customer/assets', $h + ['authorization' => 'Bearer not-a-real-token'], null);
$check('session_garbage_bearer', ($r['status'] ?? 0) === 401, $r);

// Staff token must not access customer assets
$r = $k->handle('GET', '/v1/customer/assets', $h + ['authorization' => 'Bearer ' . $staffToken], null);
$check('session_staff_token_on_customer', ($r['status'] ?? 0) === 403, $r);

// Customer token must not list admin registrations
$r = $k->handle('GET', '/v1/admin/registrations', $h + ['authorization' => 'Bearer ' . $token], null);
$check('session_customer_token_on_staff', ($r['status'] ?? 0) === 403, $r);

// Expired / revoked session rejected (explicit revoke simulates expiry cleanup)
$expiredTok = $k->issueSession($k->tenants->resolveFromHost('demo.local')->id, 'customer', $customerId, 3600);
$k->sessions->revoke($expiredTok);
$r = $k->handle('GET', '/v1/customer/assets', $h + ['authorization' => 'Bearer ' . $expiredTok], null);
$check('session_revoked_rejected', ($r['status'] ?? 0) === 401, $r);

// Truly expired session (expires_at in the past)
$pastTok = bin2hex(random_bytes(24));
$k->sessions->put(new \Talamala\Domain\Session\SessionRecord(
    token: $pastTok,
    tenantId: $k->tenants->resolveFromHost('demo.local')->id,
    subjectType: 'customer',
    subjectId: $customerId,
    expiresAt: (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->modify('-60 seconds'),
));
$r = $k->handle('GET', '/v1/customer/assets', $h + ['authorization' => 'Bearer ' . $pastTok], null);
$check('session_expired_rejected', ($r['status'] ?? 0) === 401, $r);

// Logout revokes token
$r = $k->handle('POST', '/v1/auth/logout', $h + ['authorization' => 'Bearer ' . $token], null);
$check('logout_ok', ($r['status'] ?? 0) === 200 && ($r['body']['revoked'] ?? false) === true, $r);
$rOps = $k->handle('GET', '/readyz', $h, null);
$check(
    'readyz_ops_session_revoked',
    ($rOps['status'] ?? 0) === 200 && (($rOps['body']['ops']['session_revoked'] ?? 0) >= 1),
    $rOps
);
$r = $k->handle('GET', '/v1/customer/assets', $h + ['authorization' => 'Bearer ' . $token], null);
$check('logout_token_dead', ($r['status'] ?? 0) === 401, $r);
$r = $k->handle('POST', '/v1/auth/logout', $h, null);
$check('logout_requires_bearer', ($r['status'] ?? 0) === 401, $r);

// Re-issue token for remaining production checks that need bearer
$re = $k->handle('POST', '/v1/dev/session', $h + ['x-talamala-dev' => '1'], [
    'subject_type' => 'customer',
    'subject_id' => $customerId,
]);
$token = $re['body']['access_token'] ?? '';

// Production mode: header identity fallbacks and dev routes disabled
putenv('TALAMALA_ENV=production');
$r = $k->handle('GET', '/v1/customer/assets', $h + ['x-customer-id' => $customerId], null);
$check('production_blocks_header_fallback', ($r['status'] ?? 0) === 401, $r);
$r = $k->handle('GET', '/v1/dev/last-otp', $h + ['x-talamala-dev' => '1'], null);
$blockedLast = ($r['status'] ?? 0) === 404;
$r = $k->handle('POST', '/v1/dev/bind-kimia', $h + ['x-talamala-dev' => '1'], [
    'customer_id' => $customerId,
    'kimia_account_id' => 999,
]);
$blockedBind = ($r['status'] ?? 0) === 404;
$check('production_blocks_dev_routes', $blockedLast && $blockedBind, ['last' => $blockedLast, 'bind' => $blockedBind]);
// Bearer still works in production
$r = $k->handle('GET', '/v1/customer/assets', $h + ['authorization' => 'Bearer ' . $token], null);
$check('production_bearer_ok', ($r['status'] ?? 0) === 200, $r);
putenv('TALAMALA_ENV=local');

// --- Adversarial isolation (B) ---
$hOther = ['host' => 'other.local', 'x-correlation-id' => 'http-smoke-other'];
$r = $k->handle('POST', '/v1/auth/customer/otp/request', $hOther, [
    'mobile' => '09120001111',
    'purpose' => 'login',
]);
$check('otp_rate_limit_isolated_across_tenants', (($r['status'] ?? 0) === 200 && isset($r['body']['challenge_id'])), $r);

$r = $k->handle('GET', '/readyz', $hOther, null);
$check('tenant_other_readyz', ($r['status'] ?? 0) === 200 && ($r['body']['tenant_slug'] ?? '') === 'other', $r);

$r = $k->handle('GET', '/readyz', ['host' => 'not-a-tenant.test'], null);
$check('tenant_unknown_still_fail_closed', ($r['status'] ?? 0) === 400, $r);

// Cross-tenant bearer: demo session must not work under other.local Host
$r = $k->handle('GET', '/v1/customer/assets', $hOther + ['authorization' => 'Bearer ' . $token], null);
$check(
    'session_cross_tenant_rejected',
    ($r['status'] ?? 0) === 403 && (($r['body']['error'] ?? '') === 'tenant_session_mismatch'),
    $r
);

// Malformed Authorization (missing Bearer scheme)
$r = $k->handle('GET', '/v1/customer/assets', $h + ['authorization' => $token], null);
$check('session_auth_missing_bearer_scheme', ($r['status'] ?? 0) === 401, $r);

// Empty Bearer token
$r = $k->handle('GET', '/v1/customer/assets', $h + ['authorization' => 'Bearer '], null);
$check('session_empty_bearer', ($r['status'] ?? 0) === 401, $r);

// Double logout: second call still requires valid semantics (token already dead → 401)
$r = $k->handle('POST', '/v1/auth/logout', $h + ['authorization' => 'Bearer ' . $token], null);
$check('logout_ok_again', ($r['status'] ?? 0) === 200 && ($r['body']['revoked'] ?? false) === true, $r);
$r = $k->handle('POST', '/v1/auth/logout', $h + ['authorization' => 'Bearer ' . $token], null);
$check('logout_second_still_ok_or_dead', in_array(($r['status'] ?? 0), [200, 401], true), $r);

echo "\n---\nPASS=$pass FAIL=$fail\n";
exit($fail > 0 ? 1 : 0);
