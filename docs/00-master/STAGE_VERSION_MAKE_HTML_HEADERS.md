# Stage — VERSION + Makefile + HTML security headers

**Status:** CLOSED  
**Date:** 2026-08-15

## Steps
1. `VERSION` at repo root
2. Landing injects `{{VERSION}}` / `{{BUILD_SHA}}`
3. `router.php` HTML security headers (nosniff, DENY, no-store) for landing + `*.html` demos
4. Root `Makefile` (`make check`, `make serve`, …)
5. `landing_smoke` PASS=12
6. OPERATORS.md + CI gate 12

## Expected
```text
php backend/bin/landing_smoke.php → PASS=12 FAIL=0
make check                        → ALL CHECKS PASSED
php backend/bin/http_smoke.php    → PASS=43 FAIL=0
```
