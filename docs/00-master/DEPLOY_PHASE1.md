# Deploy Phase-1 (pilot)

## 0. Offline preflight (recommended first)

```bash
make pilot-preflight   # VERSION · freeze docs · write-deny · domain · parity · typecheck
```

Does not require pdo_sqlite or Iran runner. See `PILOT_CHECKLIST.md`.

## 1. Build

```bash
git checkout <release-sha>
cd frontend/customer && npm ci && npm run build
cd ../backoffice && npm ci && npm run build
php backend/bin/check.php   # ALL CHECKS PASSED (needs pdo_sqlite)
```

## 2. Environment (never commit secrets)

Copy `.env.example` → `.env`:

- `TALAMALA_ENV=production`
- `TALAMALA_CORS_ORIGINS=` exact SPA origins
- `TALAMALA_DB_PATH=` durable sqlite or future DSN
- Kimia Read: `KIMIA_BASE_URL` / user / password
- `KIMIA_WRITE_VERIFY_ENABLE=0` unless Owner session

## 3. Serve

```bash
cd backend && php -S 0.0.0.0:8080 -t public public/router.php
```

- Landing: `/`
- Customer SPA: `/app/customer/`
- Backoffice SPA: `/app/backoffice/`
- API: `/v1/...`

## 4. Smoke after deploy

- `GET /healthz` and `/readyz` with version  
- OTP request on customer Host  
- Staff login + registration queue  
- Assets for a **bound** test customer (Read only)  

## 5. Rollback

Redeploy previous exact SHA; keep DB file backup before migrate.
