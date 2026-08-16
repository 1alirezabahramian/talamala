# Stage batch — 0.3.2 Phase-1 hardening pack

**Status:** CLOSED  
**Date:** 2026-08-16  
**Base HEAD:** 3c1d21c

## Stages bundled

### A) Permissions-Policy
- `backend/public/router.php` — HTML demos + SPA error pages
- `backend/app/Http/SecurityHeaders.php` — JSON API
- `cors_smoke` PASS 10 → **11**
- `landing_smoke` asserts `router_permissions_policy`

### B) robots.txt
- Block demos, `/v1/dev/`, `/app/`, `/readyz`
- `landing_smoke` PASS 13 → **16**

### C) check.php aggregate
- Includes `spa_router_smoke`

### D) Docs / release surface
- README aligned with current smokes + VERSION
- CURRENT_STATE / OPERATORS / LOCAL_RUN
- `.env.example` documents `TALAMALA_BUILD_SHA`
- VERSION → **0.3.2-phase1**

## Non-goals
No Kimia write · no pricing · no payment · no Delta · no durable tenant resolver

## Expected
```text
php backend/bin/check.php         → ALL CHECKS PASSED
php backend/bin/cors_smoke.php    → PASS=11 FAIL=0
php backend/bin/landing_smoke.php → PASS=16 FAIL=0
cat VERSION                       → 0.3.2-phase1
```
