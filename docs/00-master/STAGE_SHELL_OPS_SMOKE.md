# Stage — CustomerShell after auth + readyz ops smokes

**Status:** CLOSED  
**Date:** 2026-08-15

## Steps
1. Authenticated OTP success → `CustomerShell` (existing screens only)
2. Logout from shell header
3. `readyz_ops_rate_limited` after OTP 429
4. `readyz_ops_session_revoked` after logout
5. http_smoke **PASS=43** + CI gate
6. LOCAL_RUN: log path + current smoke counts

## Expected
```text
php backend/bin/http_smoke.php → PASS=43 FAIL=0
cd frontend/customer && npm run typecheck
```
