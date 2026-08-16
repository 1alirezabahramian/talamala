# Local run (skeleton)

## Requirements

- PHP 8.2+ (bcmath recommended; pdo_sqlite for durable DB)
- No Composer required for smoke / Kernel path
- Node 20+ only if using frontend typecheck/build (optional)

## One-liner

```bash
make check          # ALL CHECKS PASSED
make serve          # API + static on :8080
```

Expect: http 49 · persist 9 · cors 10 · logger 8 · maintenance 7 · landing 13 · openapi parity

## Environment

Copy `.env.example` → `.env` (never commit secrets). Common vars:

| Var | Purpose |
|-----|---------|
| `TALAMALA_ENV` | `local` \| `staging` \| `production` (production = Bearer-only, no `/v1/dev/*`) |
| `TALAMALA_DB_PATH` | SQLite file (omit / `:memory:` for process-local smoke) |
| `TALAMALA_LOG_PATH` | JSON-line structured log (secrets redacted) |
| `TALAMALA_CORS_ORIGINS` | Comma-separated origins for CORS |
| `TALAMALA_LOG_MAX_BYTES` | Soft rotate threshold (default 5_000_000) |
| `TALAMALA_BUILD_SHA` | Optional short SHA shown on landing |

## HTTP server

```bash
export TALAMALA_ENV=local
# optional durable DB:
# export TALAMALA_DB_PATH=var/talamala.sqlite
make serve
# or: cd backend && php -S 127.0.0.1:8080 -t public public/router.php
```

- Landing hub: http://127.0.0.1:8080/  (VERSION + optional BUILD_SHA)
- Demo tenant: **`demo.local`** (Host or `X-Talamala-Host`)
- Other seed tenant: `other.local` (for isolation tests)

```bash
curl -sS -H 'Host: demo.local' http://127.0.0.1:8080/healthz
curl -sS -H 'Host: demo.local' http://127.0.0.1:8080/readyz
```

## Zero-build HTML demos (CSP + security headers)

| Path | Purpose |
|------|---------|
| `/` | Operator landing hub |
| `/otp-demo.html` | Customer OTP flow |
| `/customer-shell-demo.html` | Post-OTP shell |
| `/assets-demo.html` | Kimia assets read |
| `/custody-demo.html` | Customer custody |
| `/order-demo.html` | Order accept (settlement blocked) |
| `/admin-reg-demo.html` | Registration queue |
| `/admin-custody-demo.html` | Admin custody ops |

Browser cannot set `Host`; demos use `X-Talamala-Host: demo.local`.

## OTP + staff demo

```bash
curl -sS -X POST http://127.0.0.1:8080/v1/auth/customer/otp/request \
  -H 'Host: demo.local' -H 'Content-Type: application/json' \
  -d '{"mobile":"09121234567","purpose":"registration"}'

# Dev-only (never in production):
curl -sS -H 'Host: demo.local' -H 'X-Talamala-Dev: 1' \
  http://127.0.0.1:8080/v1/dev/last-otp
```

Staff (must rotate password on first login):

- username: `operator`
- password: `ChangeMe-Now-1`

## Frontend (optional)

```bash
make frontend-typecheck   # tsc only (advisory in CI)
make frontend-build       # builds → served under /app/customer /app/backoffice
```

CORS for Vite dev:

```bash
export TALAMALA_CORS_ORIGINS=http://127.0.0.1:5173,http://127.0.0.1:5174
```

## Persistence (SQLite)

```bash
export TALAMALA_DB_PATH=var/talamala.sqlite
make serve

# isolated smoke (default in-memory / process-local):
unset TALAMALA_DB_PATH
php backend/bin/http_smoke.php
php backend/bin/persist_smoke.php
```

## Structured log (optional)

```bash
export TALAMALA_LOG_PATH=var/talamala.log
mkdir -p var
make serve
# JSON lines; password / otp / token / national_code redacted
```

## Individual smokes

```bash
make http          # PASS=49
make persist       # PASS=9
make cors          # PASS=10
make logger        # PASS=8
make maintenance   # PASS=7
make landing       # PASS=13 (includes CSP check)
make spa
make parity
```

## Non-goals until ground truth

- Live Kimia write / create account
- Live price feed coefficients
- Payment gateways
- Real SMS.ir / Jibit HTTP (ports + fakes only)

Owner pushes commits / ZIP manually if connector write is 403.
