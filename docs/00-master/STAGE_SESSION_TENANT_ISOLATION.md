# Stage — Session ↔ Tenant isolation + auth negatives

**Status:** CLOSED  
**Date:** 2026-08-15  
**Base HEAD:** 408fd953cdfdc43b12d0ecca03aa75985f62977b

## Goal
Close a real isolation gap: Bearer sessions were accepted without matching Host-resolved tenant. Add negative smokes so regression is CI-gated.

## Code
`backend/app/Http/Kernel.php`
- `resolveCustomerId($headers, $tenantId)` / `resolveStaffId($headers, $tenantId)`
- If session present and `session.tenantId !== $tenantId` → **403** `tenant_session_mismatch`
- Subject-type checks unchanged (customer vs staff)

## Smokes (`backend/bin/http_smoke.php`)
New checks (PASS 43 → **48**):
1. `session_cross_tenant_rejected` — demo token under `other.local` → 403 mismatch
2. `session_auth_missing_bearer_scheme` — raw token without `Bearer ` → 401
3. `session_empty_bearer` — `Bearer ` empty → 401
4. `logout_ok_again` / `logout_second_still_ok_or_dead` — revoke idempotency shape

## Gates / docs
- CI `http-smoke` exact PASS=48
- CURRENT_STATE / OPERATORS / LOCAL_RUN numbers updated

## Non-goals
- No durable multi-tenant resolver (still InMemory seed)
- No invent financial APIs / Kimia / pricing
- No change to OpenAPI contracts beyond existing error shapes

## Expected
```text
php backend/bin/http_smoke.php → PASS=48 FAIL=0
php backend/bin/check.php      → ALL CHECKS PASSED
```
