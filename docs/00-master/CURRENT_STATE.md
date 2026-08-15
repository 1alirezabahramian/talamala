# Talamala — Current State (2026-08-15)

## HTTP smoke
`php backend/bin/http_smoke.php` → **PASS=33 FAIL=0**

## Persistence-1
SQLite PDO for customers / quotes / custody / orders.
Default `:memory:`; file via `TALAMALA_DB_PATH`.
`php backend/bin/persist_smoke.php` → PASS=6.

## Closed stages
| Stage | Status |
|-------|--------|
| C OTP frontend → local | CLOSED 100% |
| D Registration | CLOSED |
| 3 Kimia Read / assets | CLOSED |
| Backoffice reg queue + approve | CLOSED |
| Customer Shell continuity | CLOSED (zero-build) |
| Quote accept / Custody lifecycle | backend vertical present |

## Customer local path
1. `otp-demo.html` — OTP → register/auth → assets peek  
2. optional staff: `admin-reg-demo.html` — approve + Bind Kimia (dev)  
3. `customer-shell-demo.html` — دارایی · امانات · سفارش‌ها · پذیرش quote  

## Dev-only helpers (blocked in production)
- `GET /v1/dev/last-otp`
- `POST /v1/dev/seed-quote`
- `POST /v1/dev/bind-kimia`
- `POST /v1/dev/session`

## Still BLOCKED BY GROUND TRUTH
- Kimia Write / create account
- Live price provider / coefficients
- Settlement / payment

## Next
- Backoffice custody ops polish if needed
- Do not invent payment or Kimia write contracts
