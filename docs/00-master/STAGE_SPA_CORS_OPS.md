# Stage — SPA serve + CORS smoke + ops counters

**Status:** CLOSED  
**Date:** 2026-08-15

## Delivered
1. `backend/public/router.php` serves Vite dist at `/app/customer` and `/app/backoffice` (SPA fallback, path-traversal safe)
2. `backend/bin/cors_smoke.php` — fail-closed CORS / security headers (**PASS=10**)
3. `backend/bin/spa_router_smoke.php` — dist present + traversal checks
4. Kernel process-local ops on **readyz**: `ops.rate_limited`, `ops.session_revoked`, `ops.tenant_unresolved`
5. CI job `cors-smoke`

## Expected
```text
php backend/bin/http_smoke.php        → PASS=41 FAIL=0
php backend/bin/persist_smoke.php     → PASS=9 FAIL=0
php backend/bin/cors_smoke.php        → PASS=10 FAIL=0
php backend/bin/spa_router_smoke.php  → PASS (after npm run build)
php backend/bin/openapi_parity_check.php → parity OK
```
