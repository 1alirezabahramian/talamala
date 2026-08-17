# Stage batch — 0.3.5 auth & order contract negatives

**Status:** CLOSED  
**Date:** 2026-08-17  
**Base HEAD:** b45676b (0.3.4)

## Stages
1. Staff login bad password → 401
2. Staff login empty credentials → 422 credentials_required
3. OTP request empty mobile → 422 mobile_required
4. OTP verify wrong code → 401
5. Order accept missing quote_id (with idem key) → 422
6. CI http_smoke PASS **59**
7. OpenAPI customer accept documents 422
8. VERSION **0.3.5-phase1**

## Non-goals
No Kimia write · no pricing · no payment · no Delta

## Expected
```text
php backend/bin/http_smoke.php → PASS=59 FAIL=0
php backend/bin/check.php      → ALL CHECKS PASSED
```
