# Stage — Staff cross-tenant session negative

**Status:** CLOSED  
**Date:** 2026-08-16  
**Base HEAD:** ebc84da (after Gap 1–2)

## Goal
Close residual isolation gap: staff Bearer under wrong Host was not smoke-gated (code path already fail-closed via `resolveStaffId`).

## Changes
1. `backend/bin/http_smoke.php`
   - New check `session_staff_cross_tenant_rejected`: staff token from `demo.local` under `other.local` → 403 `tenant_session_mismatch`
   - PASS 48 → **49**
2. CI gate `http-smoke` exact PASS=49
3. Docs: CURRENT_STATE / OPERATORS / LOCAL_RUN numbers + note

## Non-goals
- No invent financial APIs
- No durable multi-tenant resolver
- No OpenAPI contract invent

## Expected
```text
php backend/bin/http_smoke.php → PASS=49 FAIL=0
php backend/bin/check.php      → ALL CHECKS PASSED
```
