# Talamala — Operator quick start

## One-liner checks

```bash
make check
# or: cd backend && php bin/check.php
```

Expect: http 43 · persist 9 · cors 10 · logger 8 · maintenance 7 · landing 13 · openapi parity · ALL CHECKS PASSED

## Run API + static

```bash
export TALAMALA_ENV=local
# optional: TALAMALA_DB_PATH=var/talamala.sqlite
# optional: TALAMALA_LOG_PATH=var/talamala.log
# optional: TALAMALA_CORS_ORIGINS=http://127.0.0.1:5173
cd backend && php -S 127.0.0.1:8080 -t public public/router.php
```

Open http://127.0.0.1:8080/ (landing hub).  
Tenant demos: send `Host: demo.local` or `X-Talamala-Host: demo.local`.

## SPA builds

```bash
cd frontend/customer && npm ci && npm run build
cd frontend/backoffice && npm ci && npm run build
# then /app/customer/ and /app/backoffice/
```

## Production flags

- `TALAMALA_ENV=production` → Bearer only; `/v1/dev/*` off; no X-Customer-Id fallback
- Never commit secrets; use `.env.example` as template only

## Not enabled

Kimia Write · live price · settlement · payment — BLOCKED BY GROUND TRUTH

## Build identity

- `VERSION` file at repo root
- Optional `TALAMALA_BUILD_SHA` env (short SHA on landing)
