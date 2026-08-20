# Stage — Expand HTTP adversarial negatives

**Base HEAD:** `b9f18b5`  
**Date:** 2026-08-20  
**Scope:** security boundary tests + custody weight guard — no Live Kimia, no GT invent, no VERSION bump.

## Changes
1. `backend/bin/http_negative_smoke.php` — exact **PASS=17 FAIL=0** gate:
   - order accept requires idempotency + quote_id
   - assets/custody/admin queue require identity
   - custody receive requires staff
   - invalid custody weight rejected with exact 422 `invalid_weight_grams`
   - Kimia write HTTP route absent (404)
   - OTP empty-body validation → exact 422 `mobile_required`
   - production seed-quote remains hidden
2. `Kernel` custody receive: `DecimalString::assertCanonical` → 422 `invalid_weight_grams`
3. `pilot_preflight` runs exact `PASS=17 FAIL=0`
4. CI and Final Audit Authority pin the same exact count.
5. Backoffice OpenAPI documents canonical decimal `weight_grams` and the 422 error.

## Explicit non-goals
Live Kimia · Settlement unlock · VERSION bump

## Closure rule
No Human Green remains unchanged. This SHA must independently regain `ACCEPTED_FOR_PILOT` from `make final-audit` before it may replace the currently green main.
