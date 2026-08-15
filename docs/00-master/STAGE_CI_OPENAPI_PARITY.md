# Stage: CI hardening + OpenAPI parity (Phase 1 gate)

**Status:** CLOSED  
**Date:** 2026-08-15  
**SHA meaning:** A green CI SHA on this gate means all required jobs passed at that exact commit.

## Required CI jobs (fail-closed)

| Job | Expectation |
|-----|-------------|
| `php-syntax` | All `app/**/*.php` and `bin/**/*.php` pass `php -l` |
| `http-smoke` | `php bin/http_smoke.php` → **PASS=33 FAIL=0** |
| `persist-smoke` | `php bin/persist_smoke.php` → **PASS=6 FAIL=0** |
| `openapi-parity` | `php backend/bin/openapi_parity_check.php` → PASS (exit 0) |
| `secret-scan` | No obvious hardcoded secrets |

`composer-check` is informational only (skeleton may not install full Laravel deps). Smokes do **not** depend on `vendor/`.

## Fail-closed rules

- No `continue-on-error: true` on critical steps.
- Non-zero exit fails the job (and therefore the SHA).
- Smoke gates assert exact `PASS=N FAIL=0` strings.
- `pdo_sqlite` is installed on runners for Persistence-1 smokes.

## OpenAPI ↔ runtime parity

Source of truth for routes: `backend/app/Http/Kernel.php` `handle()`.

| Runtime route | OpenAPI file | Notes |
|---------------|--------------|-------|
| GET /healthz | auth-v1 | also accepts /v1/healthz |
| GET /readyz | auth-v1 | also accepts /v1/readyz |
| POST /v1/auth/customer/otp/request | auth-v1 | 429 + Retry-After documented |
| POST /v1/auth/customer/otp/verify | auth-v1 | |
| POST /v1/auth/customer/register | auth-v1 | |
| POST /v1/auth/staff/login | auth-v1 + backoffice-v1 | |
| POST /v1/auth/staff/password/rotate | auth-v1 + backoffice-v1 | |
| GET /v1/customer/assets | customer-v1 | |
| GET /v1/customer/custody | customer-v1 | |
| POST /v1/customer/orders/accept | customer-v1 | |
| GET /v1/customer/orders | customer-v1 | |
| GET /v1/admin/registrations | backoffice-v1 | |
| POST /v1/admin/registrations/{id}/approve | backoffice-v1 | |
| POST /v1/admin/custody/receive | backoffice-v1 | |
| POST /v1/admin/custody/{id}/ready | backoffice-v1 | |
| POST /v1/admin/custody/{id}/deliver | backoffice-v1 | |

### Intentional exclude

| Route | Reason |
|-------|--------|
| GET /v1/dev/last-otp | local only + `X-Talamala-Dev: 1` |
| POST /v1/dev/seed-quote | local only |
| POST /v1/dev/session | local only |
| POST /v1/dev/bind-kimia | local only — not Kimia Write |

Parity tool: `backend/bin/openapi_parity_check.php` (fail-closed, excludes `/v1/dev/*`).

## Out of scope (next gate)

- Persistence-2 (sessions, idempotency, audit on SQLite)
- Kimia Write / price / settlement / payment
- Any new API or financial rule

## Expected local commands

```bash
php backend/bin/http_smoke.php          # PASS=33 FAIL=0
php backend/bin/persist_smoke.php       # PASS=6 FAIL=0
php backend/bin/openapi_parity_check.php # parity OK
```
