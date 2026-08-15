# Talamala — Current State (2026-08-15)

## HTTP smoke
`php backend/bin/http_smoke.php` → **PASS=33 FAIL=0**

## Persistence
- **P1:** customers / quotes / custody / orders (SQLite)
- **P2:** sessions / idempotency / audit (SQLite)
- **P2b:** OTP rate limiter (SQLite fixed window 5/300s — existing contract)
- Tenant resolver still InMemory (seeded hosts)
- Default `:memory:`; file via `TALAMALA_DB_PATH`
- `php backend/bin/persist_smoke.php` → **PASS=9 FAIL=0**

## Frontend (thin Vite)
- `frontend/customer` — typecheck + production build OK (OTP flow entry)
- `frontend/backoffice` — typecheck + production build OK (staff login → queue)
- Zero-build HTML demos in `backend/public/*.html` still valid
- No client financial math; tenant via `X-Talamala-Host`

## Phase 1 gates
| Gate | Status |
|------|--------|
| Persistence-1 | CLOSED |
| CI + OpenAPI parity | CLOSED |
| Persistence-2 | CLOSED |
| OTP rate limiter durable | CLOSED |
| Thin Vite customer + backoffice | CLOSED |

### CI jobs for a meaningful SHA
- `php-syntax`
- `http-smoke` (PASS=33 FAIL=0)
- `persist-smoke` (PASS=9 FAIL=0)
- `openapi-parity`
- `secret-scan`
- `frontend-typecheck`

## Still BLOCKED BY GROUND TRUTH
- Kimia Write / create account (Pilot 350 only with exact Ground Truth)
- Live price provider / coefficients / catalog defaults
- Settlement / payment
- GoldPlatform V2 Delta pricing/catalog — no blind port

## Next (engineering-safe, no owner gate required)
- Observability polish / Operational Health for outbox when present
- Additional negative HTTP tests if gaps appear
- Serve Vite `dist/` from PHP public optionally
- Do **not** invent payment, pricing levers, or Kimia write payloads
