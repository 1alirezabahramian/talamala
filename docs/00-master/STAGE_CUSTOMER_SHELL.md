# Stage — Customer Shell (zero-build continuity)

**Status:** Closed (local vertical)  
**Date:** 2026-08-15

Composes existing customer read screens into one runnable page.

## Contracts used (existing only)

| Tab | Method | Path |
|-----|--------|------|
| دارایی | GET | `/v1/customer/assets` |
| امانات | GET | `/v1/customer/custody` |
| سفارش‌ها | GET | `/v1/customer/orders` |
| پذیرش | POST | `/v1/customer/orders/accept` + `Idempotency-Key` |
| Seed (dev) | POST | `/v1/dev/seed-quote` + `X-Talamala-Dev: 1` |

Identity: Bearer **or** local `X-Customer-Id`  
Tenant: `X-Talamala-Host: demo.local`

## Delivered

| Item | Path |
|------|------|
| React shell | `frontend/customer/src/CustomerShell.tsx` |
| Zero-build demo | `backend/public/customer-shell-demo.html` |
| Link-in from OTP | `otp-demo.html` → Customer Shell (query: customer_id / token) |

## Rules held

- No client-side money/weight math
- Settlement remains `blocked_by_ground_truth`
- Seed quote is fixture only — not live price
- No Kimia Write

## Verify

```bash
cd backend
php bin/http_smoke.php          # PASS=33
php -S 127.0.0.1:8080 -t public public/router.php
# http://127.0.0.1:8080/customer-shell-demo.html
# or finish otp-demo → «باز کردن Customer Shell»
```
