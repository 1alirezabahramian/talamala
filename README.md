# Talamala

White-label multi-tenant platform for gold & precious-metal businesses.

**Slogan:** Complex Backend — Simple Frontend

## Status (talago continuous build)

| Stage | Status |
|-------|--------|
| 0 Truth & Governance | Closed |
| 1 Foundation | Skeleton complete |
| 2 Identity | OTP + Staff + Registration vertical (fakes) |
| 3 Kimia Read | Http client + FinancialReadService + /assets |
| Quote / Order / Custody | Domain + services; price & settlement blocked |

## Non-negotiables

- Kimia = sole truth for Money / Gold / Coin / Currency
- Talamala = sole truth for Physical Custody (Amanat)
- Tenant from verified Host only (fail-closed)
- Decimal strings only — no binary floats for money/weight
- Rial↔Toman conversion only on backend
- Quotes immutable; orders reference `quote_id`
- After any Kimia write → readback (writes not enabled yet)
- Never invent Action codes, price coefficients, or payment contracts

## Working vertical (in-memory)

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

## Snapshot

`talamala-snapshot.zip` in artifacts parent directory.

Proprietary — owner.

## Local run

See `docs/00-master/LOCAL_RUN.md`.

```bash
cd backend
php bin/http_smoke.php          # PASS=25
php -S 127.0.0.1:8080 -t public public/router.php
```

Host for demo tenant: `demo.local`

