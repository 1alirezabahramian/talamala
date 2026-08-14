# Talamala — Current State (2026-08-14)

## HTTP smoke
`php backend/bin/http_smoke.php` → **PASS=32 FAIL=0**

Includes: tenant isolation (demo + other + unknown), OTP rate-limit + cross-tenant isolation,
full identity vertical, custody lifecycle, order accept+idempotency, production bearer hardening.

## OpenAPI
v1.3.0 aligned with working routes (429, register, rotate, custody ready/deliver).

## Stage C (frontend OTP → local server) — DONE
- `frontend/customer/src/api/client.ts` — baseUrl + X-Talamala-Host (fail-closed tenant)
- `frontend/customer/src/api/auth.ts` — requestOtp / verifyOtp / fetchDevLastOtp
- `OtpRequestScreen.tsx` + `OtpVerifyScreen.tsx` — real React UI (RTL, loading/error)
- `AppOtpFlow.tsx` — request → verify → done shell
- **Runnable zero-build demo:** `backend/public/otp-demo.html`
  - same-origin with `php -S 127.0.0.1:8080 -t public public/router.php`
  - Host tenant via `X-Talamala-Host: demo.local`
  - Dev helper: GET `/v1/dev/last-otp` with `X-Talamala-Dev: 1`

## Next
Stage 3 polish / Quote domain (no price invention) / Custody UI if needed.
Registration form screen after OTP `registration_required`.
