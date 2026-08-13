# Local run (skeleton)

## Requirements

- PHP 8.2+ (bcmath recommended)
- No Composer required for smoke / Kernel path

## Smokes (no network)

```bash
cd backend
php bin/smoke.php        # domain vertical — expect PASS=8
php bin/http_smoke.php   # HTTP Kernel — expect PASS=25
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
