from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

def replace_exact(path: str, old: str, new: str) -> None:
    p = ROOT / path
    text = p.read_text(encoding='utf-8')
    if new in text:
        return
    if old not in text:
        raise SystemExit(f'baseline mismatch: {path}')
    p.write_text(text.replace(old, new, 1), encoding='utf-8')

# Customer repository contract + implementations.
replace_exact(
    'backend/app/Domain/Identity/CustomerRepository.php',
    "    /** @return list<Customer> */\n    public function listPendingRegistration(string $tenantId, int $limit = 50): array;\n",
    "    /** @return list<Customer> */\n    public function listPendingRegistration(string $tenantId, int $limit = 50): array;\n\n    /**\n     * Staff/admin — tenant-scoped customer directory (no balances).\n     * @return list<Customer>\n     */\n    public function listForTenant(string $tenantId, int $limit = 100): array;\n",
)
replace_exact(
    'backend/app/Infrastructure/Persistence/Sqlite/SqliteCustomerRepository.php',
    "    /** @param array<string, mixed> $row */\n    private function map(array $row): Customer\n",
    "    public function listForTenant(string $tenantId, int $limit = 100): array\n    {\n        $st = $this->pdo->prepare(\n            'SELECT * FROM customers WHERE tenant_id = :t\n             ORDER BY created_at DESC LIMIT :lim'\n        );\n        $st->bindValue('t', $tenantId);\n        $st->bindValue('lim', $limit, PDO::PARAM_INT);\n        $st->execute();\n        $out = [];\n        while ($row = $st->fetch()) {\n            $out[] = $this->map($row);\n        }\n        return $out;\n    }\n\n    /** @param array<string, mixed> $row */\n    private function map(array $row): Customer\n",
)
replace_exact(
    'backend/app/Infrastructure/Persistence/InMemoryCustomerRepository.php',
    "        return $out;\n    }\n}\n",
    "        return $out;\n    }\n\n    public function listForTenant(string $tenantId, int $limit = 100): array\n    {\n        $out = [];\n        foreach ($this->byId as $c) {\n            if ($c->tenantId === $tenantId) {\n                $out[] = $c;\n                if (count($out) >= $limit) {\n                    break;\n                }\n            }\n        }\n        return $out;\n    }\n}\n",
)

# Kernel route inserted after existing admin/orders route and before dev helpers.
marker = "        // Dev-only helpers (never enable in production)\n"
route = "        // Admin customer directory — no balances; kimia_bound flag only\n        if ($method === 'GET' && $path === '/v1/admin/customers') {\n            [$staffId, $err] = $this->resolveStaffId($headers, $tenant->id);\n            if ($err !== null) {\n                return $err;\n            }\n            $list = $this->customers->listForTenant($tenant->id, 100);\n            $out = array_map(static fn ($c) => [\n                'customer_id' => $c->id,\n                'mobile' => $c->mobile,\n                'full_name' => $c->fullName,\n                'access_status' => $c->accessStatus->value,\n                'kimia_bound' => $c->isBoundToKimia(),\n                'created_at' => $c->createdAt->format(\\DateTimeInterface::ATOM),\n            ], $list);\n            return ['status' => 200, 'body' => ['items' => $out]];\n        }\n\n"
replace_exact('backend/app/Http/Kernel.php', marker, route + marker)

# OpenAPI parity inventory.
replace_exact(
    'backend/bin/openapi_parity_check.php',
    "    'GET /v1/admin/orders',\n];\n",
    "    'GET /v1/admin/orders',\n    'GET /v1/admin/customers',\n];\n",
)

# Backoffice OpenAPI.
replace_exact(
    'openapi/backoffice-v1.openapi.yaml',
    "components:\n  securitySchemes:\n",
    "  /v1/admin/customers:\n    get:\n      operationId: adminListCustomers\n      security: [{ bearerAuth: [] }]\n      summary: Tenant-scoped customer directory (no balances)\n      responses:\n        '200':\n          description: Customers (kimia_bound flag only; no money/weight)\n        '401': { description: Unauthorized }\ncomponents:\n  securitySchemes:\n",
)

# Backoffice API + screen.
(ROOT / 'frontend/backoffice/src/api/customers.ts').write_text("""/**\n * Staff customer directory — no balances.\n */\n\nimport { apiGet, type ApiResult } from './client';\n\nexport type AdminCustomerItem = {\n  customer_id: string;\n  mobile: string;\n  full_name: string | null;\n  access_status: string;\n  kimia_bound: boolean;\n  created_at: string;\n};\n\nexport type AdminCustomersResponse = { items: AdminCustomerItem[] };\n\nexport async function fetchAdminCustomers(token: string): Promise<ApiResult<AdminCustomersResponse>> {\n  return apiGet<AdminCustomersResponse>('/v1/admin/customers', token);\n}\n""", encoding='utf-8')

(ROOT / 'frontend/backoffice/src/screens/CustomersListScreen.tsx').write_text("""import { useCallback, useEffect, useState } from 'react';\nimport { fetchAdminCustomers, type AdminCustomerItem } from '../api/customers';\nimport { EmptyBlock, ErrorBlock, LoadingBlock, NoticeBanner, StatusBadge } from '../ui';\n\nexport type CustomersListScreenProps = { token: string };\n\nexport function CustomersListScreen(props: CustomersListScreenProps) {\n  const [loading, setLoading] = useState(true);\n  const [error, setError] = useState<string | null>(null);\n  const [items, setItems] = useState<AdminCustomerItem[]>([]);\n  const [reload, setReload] = useState(0);\n\n  const load = useCallback(async () => {\n    setLoading(true);\n    setError(null);\n    const res = await fetchAdminCustomers(props.token);\n    if (!res.ok) {\n      setError(res.message || res.error || 'خطا در خواندن مشتریان');\n      setItems([]);\n    } else {\n      setItems(res.data.items ?? []);\n    }\n    setLoading(false);\n  }, [props.token]);\n\n  useEffect(() => { void load(); }, [load, reload]);\n\n  if (loading) return <LoadingBlock label=\"در حال بارگذاری مشتریان…\" />;\n  if (error) return <div className=\"tal-screen\" dir=\"rtl\" lang=\"fa\"><h1>مشتریان</h1><ErrorBlock message={error} onRetry={() => setReload((n) => n + 1)} /></div>;
\n  return (\n    <div className=\"tal-screen\" dir=\"rtl\" lang=\"fa\">\n      <header className=\"tal-header\">\n        <h1>مشتریان tenant</h1>\n        <NoticeBanner tone=\"info\">فقط پروفایل — بدون نمایش موجودی Kimia. اتصال مالی فقط با flag.</NoticeBanner>\n      </header>\n      <p style={{ marginBottom: '0.75rem' }}><button type=\"button\" className=\"tal-btn-ghost\" onClick={() => setReload((n) => n + 1)}>تازه‌سازی</button></p>\n      {items.length === 0 ? <EmptyBlock title=\"مشتری‌ای نیست\">هنوز مشتری در این tenant ثبت نشده است.</EmptyBlock> : (\n        <ul className=\"tal-list\">{items.map((it) => <li key={it.customer_id} className=\"tal-list-item\">\n          <div className=\"tal-list-title\">{it.full_name || it.mobile}</div>\n          <div className=\"tal-list-meta\"><span dir=\"ltr\">{it.mobile}</span><br />وضعیت: <StatusBadge value={it.access_status} />{' · '}Kimia: {it.kimia_bound ? 'متصل' : 'بدون اتصال'}</div>\n        </li>)}</ul>\n      )}\n    </div>\n  );\n}\n""", encoding='utf-8')

# Backoffice shell additions.
replace_exact(
    'frontend/backoffice/src/AppBackoffice.tsx',
    "import { OrdersListScreen } from './screens/OrdersListScreen';\n",
    "import { OrdersListScreen } from './screens/OrdersListScreen';\nimport { CustomersListScreen } from './screens/CustomersListScreen';\n",
)
replace_exact(
    'frontend/backoffice/src/AppBackoffice.tsx',
    "| { name: 'app'; session: Session; tab: 'queue' | 'custody' | 'orders' };",
    "| { name: 'app'; session: Session; tab: 'queue' | 'custody' | 'orders' | 'customers' };",
)
replace_exact(
    'frontend/backoffice/src/AppBackoffice.tsx',
    "        <button\n          type=\"button\"\n          className={tab === 'orders' ? 'active' : ''}\n          onClick={() => setPhase({ name: 'app', session, tab: 'orders' })}\n        >\n          سفارش‌ها\n        </button>\n",
    "        <button\n          type=\"button\"\n          className={tab === 'orders' ? 'active' : ''}\n          onClick={() => setPhase({ name: 'app', session, tab: 'orders' })}\n        >\n          سفارش‌ها\n        </button>\n        <button\n          type=\"button\"\n          className={tab === 'customers' ? 'active' : ''}\n          onClick={() => setPhase({ name: 'app', session, tab: 'customers' })}\n        >\n          مشتریان\n        </button>\n",
)
replace_exact(
    'frontend/backoffice/src/AppBackoffice.tsx',
    "        {tab === 'orders' ? <OrdersListScreen token={session.token} /> : null}\n",
    "        {tab === 'orders' ? <OrdersListScreen token={session.token} /> : null}\n        {tab === 'customers' ? <CustomersListScreen token={session.token} /> : null}\n",
)

# Dedicated Cycle2 evidence: do not mutate legacy 78/17 baselines.
(ROOT / 'backend/bin/release_cycle2_http_smoke.php').write_text(r'''<?php

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
''', encoding='utf-8')

(ROOT / '.github/workflows/release-cycle2.yml').write_text("""name: Talamala Release Cycle 2\n\non:\n  push:\n    branches: [main]\n  pull_request:\n    branches: [main]\n\njobs:\n  release-cycle2-http:\n    name: Release Cycle 2 HTTP/customer-directory gate\n    runs-on: ubuntu-latest\n    steps:\n      - uses: actions/checkout@v4\n      - name: Setup PHP\n        uses: shivammathur/setup-php@v2\n        with:\n          php-version: '8.3'\n          extensions: mbstring, xml, curl, pdo_sqlite\n          coverage: none\n      - name: Exact Cycle2 route/isolation smoke\n        run: |\n          set -euo pipefail\n          out=$(php backend/bin/release_cycle2_http_smoke.php)\n          echo \"$out\"\n          echo \"$out\" | grep -q 'PASS=6 FAIL=0' || {\n            echo 'release_cycle2_http gate failed: expected PASS=6 FAIL=0'\n            exit 1\n          }\n      - name: OpenAPI parity\n        run: php backend/bin/openapi_parity_check.php\n""", encoding='utf-8')

print('CYCLE2_APPLY_OK')
