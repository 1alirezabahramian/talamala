# Stage batch — 0.3.8 deep contract pack

**Status:** CLOSED  
**Date:** 2026-08-17  
**Base HEAD:** 2d2a5b1 (0.3.7)

## Stages (10+)
1. staff_rotate_requires_staff_id → 401
2. staff_rotate_password_reuse → 400
3. custody_ready_invalid_id → 400
4. order_accept_quote_not_found → 409
5. seed_quote_customer_required → 422
6. readyz via X-Talamala-Host only
7. healthz version matches VERSION file
8. robots_allows_healthz (landing_smoke)
9. Makefile `info`
10. OpenAPI customer 409 on accept
11. CI gates http **78** · landing **18**
12. VERSION **0.3.8-phase1**

## Expected
```text
php backend/bin/http_smoke.php → PASS=78 FAIL=0
php backend/bin/landing_smoke.php → PASS=18 FAIL=0
php backend/bin/check.php → ALL CHECKS PASSED
```
