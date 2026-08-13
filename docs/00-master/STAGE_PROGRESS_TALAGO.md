# Stage progress (talago continuous)

**Updated:** 2026-08-13

| Area | Status | Evidence |
|------|--------|----------|
| Stage 0 governance | Closed | Source register, blockers, ADR index |
| Stage 1 foundation | Skeleton | Tenant, audit, idempotency, Kernel, migrations SQL |
| Stage 2 identity | Vertical (fake) | OTP, register, staff login/rotate, sessions |
| Stage 3 Kimia read | Vertical (fake) | FakeKimia + assets Toman mapping |
| Custody | HTTP vertical | receive → ready → deliver |
| Order | Accept only | quote fixture + idempotent accept; settlement BLOCKED |
| Price / Kimia write / Payment | BLOCKED | Ground truth |

## HTTP smoke

`php backend/bin/http_smoke.php` → **PASS=25 FAIL=0**

## Local server

`php -S 127.0.0.1:8080 -t backend/public backend/public/router.php`  
Tenant Host: `demo.local`
