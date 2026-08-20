#!/usr/bin/env python3
from pathlib import Path


def patch_once(path: str, old: str, new: str) -> None:
    p = Path(path)
    text = p.read_text()
    if new in text:
        return
    if old not in text:
        raise SystemExit(f'baseline anchor missing: {path}')
    text2 = text.replace(old, new, 1)
    if text2 == text:
        raise SystemExit(f'patch no-op: {path}')
    p.write_text(text2)

# Kernel: immutable quote snapshot route only; no pricing calculation.
quote_block = r'''        // Quote by id — immutable snapshot only; never computes or refreshes price.
        if ($method === 'GET' && preg_match('#^/v1/customer/quotes/([^/]+)$#', $path, $m)) {
            [$customerId, $err] = $this->resolveCustomerId($headers, $tenant->id);
            if ($err !== null) {
                return $err;
            }
            $quote = $this->quotes->findById($tenant->id, $m[1]);
            if ($quote === null) {
                return ['status' => 404, 'body' => ['error' => 'quote_not_found']];
            }
            if ($quote->customerId !== $customerId) {
                return ['status' => 403, 'body' => ['error' => 'quote_owner_mismatch']];
            }
            return [
                'status' => 200,
                'body' => [
                    'quote_id' => $quote->id,
                    'side' => $quote->side->value,
                    'asset' => $quote->asset->value,
                    'quantity' => $quote->quantity,
                    'unit_price_rial' => $quote->unitPriceRial,
                    'total_rial' => $quote->totalRial,
                    'status' => $quote->status->value,
                    'issued_at' => $quote->issuedAt->format(\DateTimeInterface::ATOM),
                    'expires_at' => $quote->expiresAt->format(\DateTimeInterface::ATOM),
                    'price_source_ref' => $quote->priceSourceRef,
                    'pricing_note' => 'Immutable snapshot only — live price provider blocked until GT-004 grounded',
                ],
            ];
        }

'''
patch_once(
    'backend/app/Http/Kernel.php',
    '        // Customer profile — no balances; Kimia binding flag only\n',
    quote_block + '        // Customer profile — no balances; Kimia binding flag only\n',
)

# Customer OpenAPI.
openapi_block = '''  /v1/customer/quotes/{id}:\n    get:\n      operationId: customerQuoteById\n      security: [{ bearerAuth: [] }]\n      summary: Immutable quote snapshot; no live pricing or refresh\n      parameters:\n        - name: id\n          in: path\n          required: true\n          schema: { type: string }\n      responses:\n        '200': { description: Stored quote snapshot with decimal-string values }\n        '401': { description: Unauthorized }\n        '403': { description: quote_owner_mismatch }\n        '404': { description: quote_not_found }\n'''
patch_once(
    'openapi/customer-v1.openapi.yaml',
    '  /v1/customer/me:\n',
    openapi_block + '  /v1/customer/me:\n',
)

# OpenAPI parity expected route.
patch_once(
    'backend/bin/openapi_parity_check.php',
    "    'GET /v1/customer/me',\n",
    "    'GET /v1/customer/me',\n    'GET /v1/customer/quotes/{id}',\n",
)

# Makefile targets without replacing current Release Authority semantics.
patch_once(
    'Makefile',
    "\t@echo '  make frontend-typecheck|frontend-build|serve|version|php-syntax|kimia-write-contract|kimia-create-customer-contract|pricing-contract|release-build'\n",
    "\t@echo '  make frontend-typecheck|frontend-build|serve|version|php-syntax|kimia-write-contract|kimia-create-customer-contract|pricing-contract|settlement-payment-contract|release-build'\n",
)
patch_once(
    'Makefile',
    "\t@echo '  make release-cycle2-http # exact PASS=6 FAIL=0'\n",
    "\t@echo '  make release-cycle2-http # exact PASS=6 FAIL=0'\n\t@echo '  make release-cycle6-http # quote snapshot/isolation exact PASS=9 FAIL=0'\n",
)
patch_once(
    'Makefile',
    "\tfrontend-typecheck frontend-build serve version kimia-write-contract kimia-create-customer-contract pricing-contract release-build verify-frontend \\\n",
    "\tfrontend-typecheck frontend-build serve version kimia-write-contract kimia-create-customer-contract pricing-contract settlement-payment-contract release-build verify-frontend \\\n",
)
patch_once(
    'Makefile',
    "\trelease-cycle1-http release-cycle2-http\n",
    "\trelease-cycle1-http release-cycle2-http release-cycle6-http\n",
)
patch_once(
    'Makefile',
    "pricing-contract:\n\tphp backend/bin/pricing_contract_smoke.php\n",
    "pricing-contract:\n\tphp backend/bin/pricing_contract_smoke.php\nsettlement-payment-contract:\n\tphp backend/bin/settlement_payment_contract_smoke.php\n",
)
patch_once(
    'Makefile',
    "release-cycle2-http:\n\tphp backend/bin/release_cycle2_http_smoke.php\n",
    "release-cycle2-http:\n\tphp backend/bin/release_cycle2_http_smoke.php\nrelease-cycle6-http:\n\tphp backend/bin/release_cycle6_http_smoke.php\n",
)

# Matrix: explanatory only; exact 19 rows stay untouched.
matrix = Path('docs/audit/RELEASE_BLOCKER_MATRIX.md')
mt = matrix.read_text()
section = '''\n## Cycle 6 progress\n\n- **GT-005 / FA-060, FA-078:** offline Settlement contract + hard-stop wire guard added. Flags alone cannot enable wiring; complete Owner policy, evidence refs, semantics, Kimia side-effect model and zero unknowns are required. Release rows remain Open.\n- **GT-006 / FA-096–098:** offline Payment contract added. Capture remains blocked unless official gateway contract, callback/signature/refund/reverse rules, Owner policy, evidence refs and zero unknowns are present. Release rows remain Open.\n- **GT-008/009 / FA-026, FA-039, FA-099:** production integration stub only; Fake SMS/Jibit remain unchanged.\n- **GT-004 / FA-047–049:** customer Quote-by-id exposes only an already-stored immutable snapshot. It does not fetch, calculate, refresh or authorize live pricing.\n- **No score gaming:** machine Release Authority remains the only blocker/verdict source.\n'''
if '## Cycle 6 progress' not in mt:
    matrix.write_text(mt.rstrip() + '\n' + section)

# Ground Truth register: record scaffolds only, never resolution.
gt = Path('docs/00-master/GROUND_TRUTH_BLOCKERS.md')
g = gt.read_text()
g = g.replace(
    '| GT-005 | Settlement / reconciliation / hold / freeze / credit semantics | Settlement, Credit trading | Explicit business rules + Kimia behavior evidence | Owner decision + evidence |',
    '| GT-005 | Settlement / reconciliation / hold / freeze / credit semantics | Settlement, Credit trading | Explicit business rules + Kimia behavior evidence. **Scaffold:** `SETTLEMENT_CONTRACT.json` + Owner template + hard-stop `SettlementWireGuard`; still NOT_GROUNDED. | Fill Owner template + controlled evidence before any wire |',
)
g = g.replace(
    '| GT-006 | BehPardakht Mellat current merchant contract + sandbox process | Online payments | Official merchant docs + credentials process |',
    '| GT-006 | BehPardakht Mellat current merchant contract + sandbox process | Online payments | Official merchant docs + credentials process. **Scaffold:** `PAYMENT_CONTRACT.json` + Owner template; capture remains blocked. |',
)
if 'SMS_JIBIT_CONTRACT.json' not in g:
    g = g.replace(
        '| GT-009 | Jibit sandbox/live credentials + current version + rate/error behavior | Live onboarding | Credentials + controlled test results |',
        '| GT-009 | Jibit sandbox/live credentials + current version + rate/error behavior | Live onboarding | Credentials + controlled test results. **Scaffold:** `SMS_JIBIT_CONTRACT.json`; Fake path unchanged. |',
    )
gt.write_text(g)

print('CYCLE6_APPLY_OK')
