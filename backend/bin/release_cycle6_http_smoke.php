<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap_autoload.php';

use Talamala\Domain\Quote\Quote;
use Talamala\Domain\Quote\QuoteAsset;
use Talamala\Domain\Quote\QuoteSide;
use Talamala\Domain\Quote\QuoteStatus;
use Talamala\Http\Kernel;

$k = new Kernel();
$pass = 0;
$fail = 0;
$check = static function (string $name, bool $condition, mixed $detail = null) use (&$pass, &$fail): void {
    if ($condition) { echo "OK  {$name}\n"; ++$pass; return; }
    echo "FAIL {$name} " . json_encode($detail, JSON_UNESCAPED_UNICODE) . "\n"; ++$fail;
};

$demo = ['host' => 'demo.local', 'x-correlation-id' => 'release-cycle6'];
$other = ['host' => 'other.local', 'x-correlation-id' => 'release-cycle6-other'];
$demoTenant = $k->tenants->resolveFromHost('demo.local');
$now = new \DateTimeImmutable('2026-08-20T00:00:00+00:00');
$quote = new Quote(
    id: 'cycle6-q-1',
    tenantId: $demoTenant->id,
    customerId: 'cycle6-customer-1',
    side: QuoteSide::Buy,
    asset: QuoteAsset::Gold18,
    quantity: '1.250',
    unitPriceRial: '350000000',
    totalRial: '437500000',
    issuedAt: $now,
    expiresAt: $now->modify('+5 minutes'),
    status: QuoteStatus::Open,
    priceSourceRef: 'cycle6-offline-snapshot',
);
$k->quotes->save($quote);

$r = $k->handle('GET', '/v1/customer/quotes/cycle6-q-1', $demo, null);
$check('quote_requires_customer_auth', ($r['status'] ?? 0) === 401, $r);

$r = $k->handle('GET', '/v1/customer/quotes/missing', $demo + ['x-customer-id' => 'cycle6-customer-1'], null);
$check('quote_missing_404', ($r['status'] ?? 0) === 404 && ($r['body']['error'] ?? '') === 'quote_not_found', $r);

$r = $k->handle('GET', '/v1/customer/quotes/cycle6-q-1', $demo + ['x-customer-id' => 'wrong-owner'], null);
$check('quote_owner_mismatch_403', ($r['status'] ?? 0) === 403 && ($r['body']['error'] ?? '') === 'quote_owner_mismatch', $r);

$r = $k->handle('GET', '/v1/customer/quotes/cycle6-q-1', $demo + ['x-customer-id' => 'cycle6-customer-1'], null);
$body = $r['body'] ?? [];
$check('quote_snapshot_ok', ($r['status'] ?? 0) === 200 && ($body['quote_id'] ?? '') === 'cycle6-q-1', $r);
$check('quote_decimal_snapshot_exact', ($body['quantity'] ?? '') === '1.250' && ($body['unit_price_rial'] ?? '') === '350000000' && ($body['total_rial'] ?? '') === '437500000', $body);
$check('quote_snapshot_metadata_exact', ($body['price_source_ref'] ?? '') === 'cycle6-offline-snapshot' && ($body['status'] ?? '') === 'open', $body);
$check('quote_pricing_note_fail_closed', str_contains((string) ($body['pricing_note'] ?? ''), 'live price provider blocked'), $body);

$r = $k->handle('GET', '/v1/customer/quotes/cycle6-q-1', $other + ['x-customer-id' => 'cycle6-customer-1'], null);
$check('quote_cross_tenant_hidden', ($r['status'] ?? 0) === 404 && ($r['body']['error'] ?? '') === 'quote_not_found', $r);

$stored = $k->quotes->findById($demoTenant->id, 'cycle6-q-1');
$check('quote_read_does_not_mutate_status', $stored !== null && $stored->status === QuoteStatus::Open, $stored?->status->value);

echo "\n---\nPASS={$pass} FAIL={$fail}\n";
exit($fail > 0 ? 1 : 0);
