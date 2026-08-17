# Talamala

White-label multi-tenant platform for gold & precious-metal businesses.

**Slogan:** Complex Backend — Simple Frontend  
**VERSION:** see `VERSION` file (`0.3.3-phase1`)

## Status (talago continuous build)

| Stage | Status |
|-------|--------|
| 0 Truth & Governance | Closed |
| 1 Foundation | Skeleton complete + Phase-1 hardening |
| 2 Identity | OTP + Staff + Registration vertical (fakes) |
| 3 Kimia Read | Http client + FinancialReadService + /assets |
| Quote / Order / Custody | Domain + services; price & settlement blocked |

## Smokes (exact CI gates)

| Check | Expect |
|-------|--------|
| domain_smoke | PASS=8 FAIL=0 |
| http_smoke | PASS=51 FAIL=0 |
| persist_smoke | PASS=9 FAIL=0 |
| cors_smoke | PASS=13 FAIL=0 |
| logger_smoke | PASS=8 FAIL=0 |
| maintenance_smoke | PASS=7 FAIL=0 |
| spa_router_smoke | PASS=6 FAIL=0 |
| landing_smoke | PASS=17 FAIL=0 |
| openapi_parity | PASS |

Frontend typecheck is **optional** in CI (`continue-on-error`).

## Non-negotiables

- Kimia = sole truth for Money / Gold / Coin / Currency
- Talamala = sole truth for Physical Custody (Amanat)
- Tenant from verified Host only (fail-closed)
- Decimal strings only — no binary floats for money/weight
- Rial↔Toman conversion only on backend
- Quotes immutable; orders reference `quote_id`
- After any Kimia write → readback (writes not enabled yet)
- Never invent Action codes, price coefficients, or payment contracts

## Working vertical

```
OTP → verify → register (Jibit gate) → staff approve
    → bind Kimia account id → GET /assets
Custody: receive → ready → delivered
Order: accept quote (idempotent) → settlement BLOCKED
```

## Layout

```
backend/     Domain · Application · Integrations · Http
frontend/    customer · backoffice · shared
openapi/     auth · customer · backoffice
docs/        ADR · registers · progress
infra/       containers
```

## Blockers (production)

See `docs/00-master/GROUND_TRUTH_BLOCKERS.md`  
Price provider · Kimia write · Payment · live credentials

## Local run / operators

- `docs/00-master/LOCAL_RUN.md`
- `docs/00-master/OPERATORS.md`

```bash
make check
make serve
# open http://127.0.0.1:8080/   Host: demo.local
```

Proprietary — owner.
