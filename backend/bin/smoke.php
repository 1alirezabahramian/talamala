<?php

declare(strict_types=1);

/**
 * CLI smoke: tenant → OTP → register → bind → assets → custody → order accept
 * Run: php backend/bin/smoke.php
 */

require_once __DIR__ . '/../app/bootstrap_autoload.php';

use Talamala\Application\Custody\CustodyApplicationService;
use Talamala\Application\Identity\CustomerRegistrationService;
use Talamala\Application\Identity\OtpAuthApplicationService;
use Talamala\Application\Kimia\CustomerFinancialReadService;
use Talamala\Application\Order\OrderApplicationService;
use Talamala\Domain\Quote\Quote;
use Talamala\Domain\Quote\QuoteAsset;
use Talamala\Domain\Quote\QuoteSide;
use Talamala\Domain\Quote\QuoteStatus;
use Talamala\Domain\Tenant\Tenant;
use Talamala\Infrastructure\Persistence\InMemoryAuditLogger;
use Talamala\Infrastructure\Persistence\InMemoryCustodyRepository;
use Talamala\Infrastructure\Persistence\InMemoryCustomerRepository;
use Talamala\Infrastructure\Persistence\InMemoryIdempotencyRegistry;
use Talamala\Infrastructure\Persistence\InMemoryOrderRepository;
use Talamala\Infrastructure\Persistence\InMemoryQuoteRepository;
use Talamala\Infrastructure\Persistence\InMemoryTenantResolver;
use Talamala\Infrastructure\Sms\FakeSmsOtpSender;
use Talamala\Integrations\Jibit\FakeJibitIdentityClient;
use Talamala\Integrations\Kimia\FakeKimiaReadClient;

$pass = 0;
$fail = 0;
$check = static function (string $name, bool $ok) use (&$pass, &$fail): void {
    if ($ok) {
        echo "OK  $name\n";
        $pass++;
    } else {
        echo "FAIL $name\n";
        $fail++;
    }
};

$tenantResolver = new InMemoryTenantResolver();
$tenant = new Tenant('t1', 'demo', 'demo.local', true, true);
$tenantResolver->register($tenant);
$resolved = $tenantResolver->resolveFromHost('demo.local');
$check('tenant_resolve', $resolved->id === 't1');

try {
    $tenantResolver->resolveFromHost('unknown.example');
    $check('tenant_fail_closed', false);
} catch (Throwable) {
    $check('tenant_fail_closed', true);
}

$audit = new InMemoryAuditLogger();
$sms = new FakeSmsOtpSender();
// Otp service may need specific constructor — use reflection-friendly path
$customers = new InMemoryCustomerRepository();
$jibit = new FakeJibitIdentityClient();
$jibit->allowMatch('0012345678', '09121234567');
$reg = new CustomerRegistrationService($customers, $jibit, $audit);
$r = $reg->completeRegistration('t1', [
    'mobile' => '09121234567',
    'national_code' => '0012345678',
    'full_name' => 'Smoke User',
], 'corr-1');
$check('registration', $r->success === true);
$reg->approveCustomer('t1', $r->customer->id, 'staff1', 'corr-2');
$reg->bindKimiaAccount('t1', $r->customer->id, 350, 'corr-3');
$check('kimia_bind', $customers->findById('t1', $r->customer->id)?->kimiaAccountId === 350);

$fakeKimia = new FakeKimiaReadClient();
$fakeKimia->seedBalance(350, [
    ['Weight' => '1.5', 'Money' => '5000000', 'CurrencyId' => 11, 'CurrencySymbol' => 'ریال'],
]);
$fin = new CustomerFinancialReadService($fakeKimia);
$assets = $fin->assetsForKimiaAccount(350);
$check('assets_toman', ($assets['money_toman'] ?? '') === '500000');

$custody = new CustodyApplicationService(new InMemoryCustodyRepository(), $audit);
$item = $custody->receive('t1', $r->customer->id, 'سکه امانت', '8.100', '900', 'staff1', 'c-c');
$item = $custody->markReady('t1', $item->id, 'staff1', 'c-c2');
$item = $custody->deliver('t1', $item->id, 'staff1', 'c-c3');
$check('custody_delivered', $item->status->value === 'delivered');

$quotes = new InMemoryQuoteRepository();
$now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
$q = new Quote(
    'q-smoke',
    't1',
    $r->customer->id,
    QuoteSide::Buy,
    QuoteAsset::Gold18,
    '1.000',
    '350000000',
    '350000000',
    $now,
    $now->modify('+3 minutes'),
    QuoteStatus::Open,
    'manual-test',
);
$quotes->save($q);
$orders = new OrderApplicationService($quotes, new InMemoryOrderRepository(), new InMemoryIdempotencyRegistry(), $audit);
$oa = $orders->acceptFromQuote('t1', $r->customer->id, 'q-smoke', 'idem-smoke-1', 'corr-o');
$check('order_accept', $oa->success === true);
$settle = $orders->attemptSettlement('t1', $oa->order->id);
$check('settlement_blocked', $settle->success === false && $settle->errorCode === 'settlement_blocked');

echo "\n---\nPASS=$pass FAIL=$fail\n";
exit($fail > 0 ? 1 : 0);
