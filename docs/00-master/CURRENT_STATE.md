# Talamala — Current State (2026-08-15)

## HTTP smoke
`php backend/bin/http_smoke.php` → **PASS=33 FAIL=0**

## Persistence-1
SQLite PDO for customers / quotes / custody / orders.
Default `:memory:`; file via `TALAMALA_DB_PATH`.
`php backend/bin/persist_smoke.php` → **PASS=6 FAIL=0**

## Phase 1 gate: CI + OpenAPI parity
**CLOSED** — see `docs/00-master/STAGE_CI_OPENAPI_PARITY.md`

- CI fail-closed (no swallowed failures on critical jobs)
- Exact-SHA gates: http_smoke (33/0), persist_smoke (6/0)
- OpenAPI runtime parity: production routes covered; `/v1/dev/*` excluded
- Parity tool: `php backend/bin/openapi_parity_check.php` → PASS
- `pdo_sqlite` required on CI runners

### CI jobs that must be green for a meaningful SHA
- `php-syntax`
- `http-smoke`
- `persist-smoke`
- `openapi-parity`
- `secret-scan`

## Closed stages
| Stage | Status |
|-------|--------|
| C OTP frontend → local | CLOSED 100% |
| D Registration | CLOSED |
| 3 Kimia Read / assets | CLOSED |
| Backoffice reg queue + approve | CLOSED |
| Customer Shell continuity | CLOSED (zero-build) |
| Quote accept / Custody lifecycle | backend vertical present |
| Persistence-1 (SQLite Identity+Custody+Quote+Order) | CLOSED |
| CI hardening + OpenAPI parity | CLOSED |

## Customer local path
1. `otp-demo.html` — OTP → register/auth → assets peek  
2. optional staff: `admin-reg-demo.html` — approve + Bind Kimia (dev)  
3. `customer-shell-demo.html` — دارایی · امانات · سفارش‌ها · پذیرش quote  

## Dev-only helpers (blocked in production)
- `GET /v1/dev/last-otp`
- `POST /v1/dev/seed-quote`
- `POST /v1/dev/bind-kimia`
- `POST /v1/dev/session`

## Still BLOCKED BY GROUND TRUTH
- Kimia Write / create account
- Live price provider / coefficients
- Settlement / payment

## Next
**Persistence-2** — sessions + idempotency + audit on SQLite  
(Do not invent payment or Kimia write contracts)
