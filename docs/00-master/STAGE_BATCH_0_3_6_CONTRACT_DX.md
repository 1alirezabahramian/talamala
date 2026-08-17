# Stage batch — 0.3.6 contract depth + frontend DX

**Status:** CLOSED  
**Date:** 2026-08-17  
**Base HEAD:** 658405a (0.3.5)

## Stages (maximized pack)

1. otp_invalid_purpose → 422
2. otp_verify_fields_required → 422
3. register_validation_required → 400
4. register_already_registered → 400
5. otp_request_wrong_method (GET) → 404
6. healthz_service_name assert
7. Customer + backoffice `X-Correlation-Id` on every request
8. Makefile `help`
9. robots.txt `Allow: /healthz`
10. OpenAPI auth OTP 422 documented
11. CI http_smoke PASS **65**
12. VERSION **0.3.6-phase1**
13. docs / CURRENT_STATE / Ledger note

## Non-goals
No Kimia write · no pricing · no payment · no Delta · no durable tenant resolver

## Expected
```text
php backend/bin/http_smoke.php → PASS=65 FAIL=0
php backend/bin/check.php      → ALL CHECKS PASSED
```
