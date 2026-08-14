# Stage C — Customer OTP frontend wired to local server

**Date:** 2026-08-14  
**Status:** Closed (vertical)

## Goal
Connect customer OTP screens to local PHP Kernel with real architecture (no invented contracts).

## Delivered

| Item | Path |
|------|------|
| API client (Host tenant) | `frontend/customer/src/api/client.ts` |
| Auth API helpers | `frontend/customer/src/api/auth.ts` |
| OTP request screen | `frontend/customer/src/screens/auth/OtpRequestScreen.tsx` |
| OTP verify screen | `frontend/customer/src/screens/auth/OtpVerifyScreen.tsx` |
| Flow shell | `frontend/customer/src/AppOtpFlow.tsx` |
| Zero-build runnable demo | `backend/public/otp-demo.html` |

## Contracts used (existing backend only)

- `POST /v1/auth/customer/otp/request` → `{ challenge_id, expires_at, purpose }`
- `POST /v1/auth/customer/otp/verify` → `authenticated` \| `registration_required`
- `GET /v1/dev/last-otp` + `X-Talamala-Dev: 1` (local FakeSms only)
- Tenant: `X-Talamala-Host: demo.local` (fail-closed)

## Non-goals (still blocked / later)

- Real SMS.ir
- High-fidelity design system
- Vite production bundle
- Registration form UI (next small step after C)

## How to verify

```bash
cd backend
php -S 127.0.0.1:8080 -t public public/router.php
# browser → http://127.0.0.1:8080/otp-demo.html
```

Smoke remains: `php bin/http_smoke.php` → PASS=32
