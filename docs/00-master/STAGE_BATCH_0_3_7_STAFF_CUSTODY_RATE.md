# Stage batch — 0.3.7 staff rotate, custody, rate-limit headers

**Status:** CLOSED  
**Date:** 2026-08-17  
**Base HEAD:** a487d31 (0.3.6)

## Stages
1. staff_rotate_password_too_weak → 400
2. staff_rotate_invalid_current → 400
3. custody_receive_unauthorized → 401
4. custody_receive_validation → 422
5. otp_rate_limited_retry_after (body + headers.Retry-After)
6. healthz_time_iso
7. CI http_smoke PASS **71**
8. VERSION **0.3.7-phase1**

## Non-goals
No Kimia write · no pricing · no payment · no Delta

## Expected
```text
php backend/bin/http_smoke.php → PASS=71 FAIL=0
php backend/bin/check.php → ALL CHECKS PASSED
```
