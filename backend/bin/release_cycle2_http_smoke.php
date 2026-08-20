<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap_autoload.php';

use Talamala\Domain\Identity\Customer;
use Talamala\Domain\Identity\CustomerAccessStatus;
use Talamala\Http\Kernel;

$k = new Kernel();
$pass = 0;
$fail = 0;
$check = static function (string $name, bool $ok, mixed $detail = null) use (&$pass, &$fail): void {
    if ($ok) { echo "OK  $name\n"; $pass++; return; }
    echo "FAIL $name " . json_encode($detail, JSON_UNESCAPED_UNICODE) . "\n"; $fail++;
};

$demo = ['host' => 'demo.local', 'x-correlation-id' => 'release-cycle2'];
$other = ['host' => 'other.local', 'x-correlation-id' => 'release-cycle2-other'];
$tenant = $k->tenants->resolveFromHost('demo.local');
$customer = new Customer(
    id: 'cycle2-customer-1',
    tenantId: $tenant->id,
    mobile: '09120002222',
    nationalCode: '0012345678',
    fullName: 'Cycle 2 Customer',
    accessStatus: CustomerAccessStatus::Active,
    kimiaAccountId: null,
    createdAt: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
    approvedAt: new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
);
$k->customers->save($customer);

$r = $k->handle('GET', '/v1/admin/customers', $demo, null);
$check('admin_customers_requires_staff', ($r['status'] ?? 0) === 401, $r);

$r = $k->handle('GET', '/v1/admin/customers', $demo + ['x-staff-id' => 'staff-demo-1'], null);
$items = $r['body']['items'] ?? [];
$check('admin_customers_ok', ($r['status'] ?? 0) === 200 && is_array($items), $r);
$check('admin_customers_contains_tenant_customer', count(array_filter($items, static fn ($i) => ($i['customer_id'] ?? '') === 'cycle2-customer-1')) === 1, $r);
$check('admin_customers_no_balance_fields', !str_contains(json_encode($items), 'money_') && !str_contains(json_encode($items), 'weight_') && !str_contains(json_encode($items), 'kimia_account_id'), $r);

$r = $k->handle('GET', '/v1/admin/customers', $other + ['x-staff-id' => 'staff-other'], null);
$otherItems = $r['body']['items'] ?? [];
$check('admin_customers_cross_tenant_hidden', ($r['status'] ?? 0) === 200 && count(array_filter($otherItems, static fn ($i) => ($i['customer_id'] ?? '') === 'cycle2-customer-1')) === 0, $r);

$r = $k->handle('GET', '/v1/customer/me', $demo + ['x-customer-id' => 'cycle2-customer-1'], null);
$check('customer_me_still_safe', ($r['status'] ?? 0) === 200 && !array_key_exists('kimia_account_id', $r['body']) && !array_key_exists('money_rial', $r['body']), $r);

echo "\n---\nPASS=$pass FAIL=$fail\n";
exit($fail > 0 ? 1 : 0);
