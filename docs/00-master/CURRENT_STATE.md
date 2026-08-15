# Talamala — Current State (2026-08-15)

## HTTP smoke
`php backend/bin/http_smoke.php` → **PASS=33 FAIL=0**

## Persistence
- **P1:** SQLite customers / quotes / custody / orders
- **P2:** SQLite sessions / idempotency / audit  
  Default `:memory:`; file via `TALAMALA_DB_PATH`.  
  `php backend/bin/persist_smoke.php` → **PASS=9 FAIL=0**

## Phase 1 gates
| Gate | Status |
|------|--------|
| Persistence-1 | CLOSED |
| CI hardening + OpenAPI parity | CLOSED |
| Persistence-2 | CLOSED |

### CI jobs for a meaningful SHA
- `php-syntax`
- `http-smoke` (PASS=33 FAIL=0)
- `persist-smoke` (PASS=9 FAIL=0)
- `openapi-parity`
- `secret-scan`

## Closed stages
| Stage | Status |
|-------|--------|
| C OTP frontend → local | CLOSED |
| D Registration | CLOSED |
| 3 Kimia Read / assets | CLOSED |
| Backoffice reg queue + approve | CLOSED |
| Customer Shell continuity | CLOSED (zero-build) |
| Quote accept / Custody lifecycle | backend vertical present |
| Persistence-1 | CLOSED |
| CI + OpenAPI parity | CLOSED |
| Persistence-2 (session + idempotency + audit) | CLOSED |

## Still InMemory
- OTP rate limiter
- Tenant resolver (seeded hosts)

## Dev-only helpers (blocked in production)
- `GET /v1/dev/last-otp`
- `POST /v1/dev/seed-quote`
- `POST /v1/dev/bind-kimia`
- `POST /v1/dev/session`

## Still BLOCKED BY GROUND TRUTH
- Kimia Write / create account (Pilot AccountId=350 only with exact Ground Truth — see Delta)
- Live price provider / coefficients
- Settlement / payment

## Next
Thin frontend / remaining Phase-1 polish only after owner direction.  
Do not invent payment or Kimia write contracts.  
Delta Handoff (Store Instance / Pricing / multi-Owner) applies to GoldPlatform V2 lineage — no blind port into Talamala skeleton.
