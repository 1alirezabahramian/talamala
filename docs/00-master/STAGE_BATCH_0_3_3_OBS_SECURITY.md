# Stage batch — 0.3.3 observability + security pack

**Status:** CLOSED  
**Date:** 2026-08-17  
**Base HEAD:** e9dd2e6 (0.3.2)

## Stages

### A) X-Correlation-Id on API
- `backend/public/index.php` always sets/echoes `X-Correlation-Id`

### B) X-Permitted-Cross-Domain-Policies: none
- HTML `router.php` (demos + error pages)
- JSON `SecurityHeaders.php`
- cors_smoke + landing_smoke asserts

### C) http_smoke negatives
- `unknown_path_404` → 404 not_found
- `healthz_no_tenant` → 200 without Host
- PASS 49 → **51**

### D) Aggregate check
- `domain_smoke` (`bin/smoke.php` PASS=8) included in `check.php`

### E) Makefile
- `make version` → cat VERSION

### F) Gates / docs
- cors 11 → **13** · landing 16 → **17**
- VERSION **0.3.3-phase1**

## Non-goals
No Kimia write · no pricing · no payment · no Delta · no durable tenant resolver

## Expected
```text
php backend/bin/check.php         → ALL CHECKS PASSED
php backend/bin/http_smoke.php    → PASS=51 FAIL=0
php backend/bin/cors_smoke.php    → PASS=13 FAIL=0
php backend/bin/landing_smoke.php → PASS=17 FAIL=0
cat VERSION                       → 0.3.3-phase1
```
