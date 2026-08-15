# Local run (skeleton)

## Requirements

- PHP 8.2+ (bcmath recommended)
- No Composer required for smoke / Kernel path

## Smokes (no network)

```bash
cd backend
php bin/smoke.php        # domain vertical — expect PASS=8
php bin/http_smoke.php   # HTTP Kernel — expect PASS=43
php bin/check.php         # smoke + domain + logger redact
```

## HTTP server

```bash
cd backend
php -S 127.0.0.1:8080 -t public public/router.php
```

Demo tenant host: **`demo.local`**

Send Host header (or X-Talamala-Host):

```bash
curl -sS -H 'Host: demo.local' http://127.0.0.1:8080/healthz
curl -sS -H 'Host: demo.local' http://127.0.0.1:8080/readyz
```

OTP request example:

```bash
curl -sS -X POST http://127.0.0.1:8080/v1/auth/customer/otp/request \
  -H 'Host: demo.local' -H 'Content-Type: application/json' \
  -d '{"mobile":"09121234567","purpose":"registration"}'
```

Dev-only last OTP (never enable in production):

```bash
curl -sS -H 'Host: demo.local' -H 'X-Talamala-Dev: 1' \
  http://127.0.0.1:8080/v1/dev/last-otp
```

Staff demo (must change password on first login):

- username: `operator`
- password: `ChangeMe-Now-1`

## Non-goals until ground truth

- Live Kimia write / create account
- Live price feed coefficients
- Payment gateways
- Real SMS.ir / Jibit HTTP (ports + fakes only)

## GitHub write from Grok connector

Still may be 403; owner pushes ZIP/commits manually.


## Customer OTP demo (Stage C)

With the HTTP server running:

1. Open in browser: http://127.0.0.1:8080/otp-demo.html
2. Enter mobile e.g. `09121234567` → Request OTP
3. Click «خواندن کد از FakeSms» (dev) or use code from server logs path
4. Verify → expect `registration_required` (new mobile) or `authenticated`

Tenant header is sent as `X-Talamala-Host: demo.local` (browser cannot set Host).

TypeScript sources (for future Vite):

- `frontend/customer/src/api/client.ts`
- `frontend/customer/src/api/auth.ts`
- `frontend/customer/src/screens/auth/Otp*.tsx`
- `frontend/customer/src/AppOtpFlow.tsx`


## Persistence (SQLite)

```bash
# durable local DB
export TALAMALA_DB_PATH=var/talamala.sqlite
php -S 127.0.0.1:8080 -t public public/router.php

# isolated smoke (default)
unset TALAMALA_DB_PATH
php bin/http_smoke.php
php bin/persist_smoke.php
```

## Production-ish SPA mounts (after Vite build)

```bash
cd frontend/customer && npm ci && npm run build
cd frontend/backoffice && npm ci && npm run build
cd backend && php -S 127.0.0.1:8080 -t public public/router.php
```

- Customer: http://127.0.0.1:8080/app/customer/
- Backoffice: http://127.0.0.1:8080/app/backoffice/
- API unchanged under `/v1/*`, `/healthz`, `/readyz`

CORS for Vite dev (optional):

```bash
export TALAMALA_CORS_ORIGINS=http://127.0.0.1:5173,http://127.0.0.1:5174
```


## Structured log file (optional)

```bash
export TALAMALA_LOG_PATH=var/talamala.log
mkdir -p var
php -S 127.0.0.1:8080 -t public public/router.php
# JSON lines appended; secrets redacted (password, otp, token, national_code, …)
```

## Aggregate check

```bash
php backend/bin/check.php   # http + persist + cors + logger + openapi parity
```
