<?php

declare(strict_types=1);

/**
 * Adversarial HTTP boundary checks for Phase-1.
 * No network, no Live Kimia, no Write/Create.
 *
 * php backend/bin/http_negative_smoke.php
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

$demo = ['host' => 'demo.local', 'x-correlation-id' => 'http-negative-smoke'];
$other = ['host' => 'other.local', 'x-correlation-id' => 'http-negative-smoke-other'];
$dev = $demo + ['x-talamala-dev' => '1'];

$r = $k->handle('GET', '/readyz', [], null);
$check(
    'tenant_missing_fail_closed',
    ($r['status'] ?? 0) === 400 && (($r['body']['error'] ?? '') === 'tenant_unresolved'),
    $r,
);

$r = $k->handle('POST', '/v1/dev/seed-quote', $dev, [
    'customer_id' => 'customer-a',
    'quantity' => '1.000',
    'unit_price_rial' => '350000000',
    'total_rial' => '350000000',
]);
$check('seed_quote_fixture', ($r['status'] ?? 0) === 201 && !empty($r['body']['quote_id']), $r);
$quoteId = (string) ($r['body']['quote_id'] ?? '');

$r = $k->handle('POST', '/v1/customer/orders/accept', $demo + [
    'x-customer-id' => 'customer-b',
    'idempotency-key' => 'negative-owner-mismatch',
], ['quote_id' => $quoteId]);
$check(
    'quote_owner_mismatch_rejected',
    ($r['status'] ?? 0) === 409 && (($r['body']['error'] ?? '') === 'quote_owner_mismatch'),
    $r,
);

$r = $k->handle('POST', '/v1/customer/orders/accept', $other + [
    'x-customer-id' => 'customer-a',
    'idempotency-key' => 'negative-cross-tenant-quote',
], ['quote_id' => $quoteId]);
$check(
    'quote_cross_tenant_hidden',
    ($r['status'] ?? 0) === 409 && (($r['body']['error'] ?? '') === 'quote_not_found'),
    $r,
);

$r = $k->handle('POST', '/v1/customer/orders/not-a-real-order/settle', $demo + [
    'x-customer-id' => 'customer-a',
], []);
$check(
    'settlement_route_absent',
    ($r['status'] ?? 0) === 404 && (($r['body']['error'] ?? '') === 'not_found'),
    $r,
);

$r = $k->handle('GET', '/v1/customer/orders/accept', $demo + ['x-customer-id' => 'customer-a'], null);
$check(
    'order_accept_wrong_method_404',
    ($r['status'] ?? 0) === 404 && (($r['body']['error'] ?? '') === 'not_found'),
    $r,
);

$r = $k->handle('GET', '/v1/customer/orders', $demo, null);
$check('order_list_requires_identity', ($r['status'] ?? 0) === 401, $r);

putenv('TALAMALA_ENV=production');
$r = $k->handle('POST', '/v1/dev/seed-quote', $dev, [
    'customer_id' => 'customer-a',
]);
$check(
    'production_blocks_seed_quote',
    ($r['status'] ?? 0) === 404 && (($r['body']['error'] ?? '') === 'not_found'),
    $r,
);
putenv('TALAMALA_ENV=local');

echo "\n---\nPASS=$pass FAIL=$fail\n";
exit($fail > 0 ? 1 : 0);
