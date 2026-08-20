<?php

declare(strict_types=1);

/**
 * Release Cycle 1 HTTP evidence for customer profile + staff order visibility.
 * No network. No Live Kimia Write/Create. Settlement must remain blocked.
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
        return;
    }
    echo "FAIL $name " . json_encode($detail, JSON_UNESCAPED_UNICODE) . "\n";
    $fail++;
};

$demo = ['host' => 'demo.local', 'x-correlation-id' => 'release-cycle1'];
$other = ['host' => 'other.local', 'x-correlation-id' => 'release-cycle1-other'];

$r = $k->handle('GET', '/v1/customer/me', $demo, null);
$check('customer_me_requires_identity', ($r['status'] ?? 0) === 401, $r);

$r = $k->handle('POST', '/v1/auth/customer/register', $demo, [
    'mobile' => '09121234567',
    'national_code' => '0012345678',
    'full_name' => 'کاربر تست انتشار',
]);
$check('register_fixture', ($r['status'] ?? 0) === 201 && !empty($r['body']['customer_id']), $r);
$customerId = (string) ($r['body']['customer_id'] ?? '');

$r = $k->handle('GET', '/v1/customer/me', $demo + ['x-customer-id' => $customerId], null);
$check(
    'customer_me_profile_safe',
    ($r['status'] ?? 0) === 200
        && (($r['body']['customer_id'] ?? '') === $customerId)
        && array_key_exists('kimia_bound', $r['body'] ?? [])
        && !array_key_exists('kimia_account_id', $r['body'] ?? [])
        && !array_key_exists('money_toman', $r['body'] ?? [])
        && !array_key_exists('gold_weight_g', $r['body'] ?? []),
    $r
);

$r = $k->handle('GET', '/v1/customer/me', $other + ['x-customer-id' => $customerId], null);
$check(
    'customer_me_cross_tenant_hidden',
    ($r['status'] ?? 0) === 404 && (($r['body']['error'] ?? '') === 'customer_not_found'),
    $r
);

$r = $k->handle('GET', '/v1/admin/orders', $demo, null);
$check('admin_orders_requires_staff', ($r['status'] ?? 0) === 401, $r);

$r = $k->handle('POST', '/v1/dev/seed-quote', $demo + ['x-talamala-dev' => '1'], [
    'customer_id' => $customerId,
    'quantity' => '1.000',
    'unit_price_rial' => '350000000',
    'total_rial' => '350000000',
]);
$check('seed_quote_fixture', ($r['status'] ?? 0) === 201 && !empty($r['body']['quote_id']), $r);
$quoteId = (string) ($r['body']['quote_id'] ?? '');

$r = $k->handle('POST', '/v1/customer/orders/accept', $demo + [
    'x-customer-id' => $customerId,
    'idempotency-key' => 'release-cycle1-order',
], ['quote_id' => $quoteId]);
$check(
    'accept_order_settlement_blocked',
    ($r['status'] ?? 0) === 200 && (($r['body']['settlement'] ?? '') === 'blocked_by_ground_truth'),
    $r
);
$orderId = (string) ($r['body']['order_id'] ?? '');

$r = $k->handle('GET', '/v1/admin/orders', $demo + ['x-staff-id' => 'release-cycle1-staff'], null);
$items = $r['body']['items'] ?? [];
$found = false;
foreach (is_array($items) ? $items : [] as $item) {
    if (($item['order_id'] ?? '') === $orderId
        && ($item['customer_id'] ?? '') === $customerId
        && ($item['settlement'] ?? '') === 'blocked_by_ground_truth') {
        $found = true;
        break;
    }
}
$check('admin_orders_contains_tenant_order', ($r['status'] ?? 0) === 200 && $found, $r);

$r = $k->handle('GET', '/v1/admin/orders', $other + ['x-staff-id' => 'release-cycle1-staff'], null);
$otherItems = $r['body']['items'] ?? [];
$leaked = false;
foreach (is_array($otherItems) ? $otherItems : [] as $item) {
    if (($item['order_id'] ?? '') === $orderId) {
        $leaked = true;
        break;
    }
}
$check('admin_orders_cross_tenant_isolated', ($r['status'] ?? 0) === 200 && !$leaked, $r);

echo "\n---\nPASS=$pass FAIL=$fail\n";
exit($fail > 0 ? 1 : 0);
