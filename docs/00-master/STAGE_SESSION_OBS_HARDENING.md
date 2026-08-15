# Stage — Session negative tests + logout + readiness hardening

**Status:** CLOSED  
**Date:** 2026-08-15

## Delivered
1. **POST `/v1/auth/logout`** — revoke Bearer (production-safe; OpenAPI + parity)
2. **http_smoke** session negatives:
   - garbage bearer → 401
   - staff token on customer route → 403
   - customer token on staff route → 403
   - revoked token → 401
   - expired token → 401
   - logout ok / token dead / logout requires bearer
3. **readyz** probes SQLite (`checks.sqlite`); 503 if DB fail (no path leak)
4. **issueSession** TTL modifier hardened for non-positive values
5. **StructuredLogger** redacts access_token / national_code / national_id / bearer
6. Frontend `logout()` helpers (customer + backoffice)

## Expected
```text
php backend/bin/http_smoke.php           → PASS=41 FAIL=0
php backend/bin/persist_smoke.php        → PASS=9 FAIL=0
php backend/bin/openapi_parity_check.php → PASS (includes logout)
```

## CI
http-smoke gate **PASS=41 FAIL=0**
