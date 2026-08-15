# Stage — Thin Vite (Customer + Backoffice)

**Status:** CLOSED  
**Date:** 2026-08-15

## Goal
Production-capable frontend toolchain without inventing business rules or financial UI.

## Delivered
| App | Entry | Scripts |
|-----|-------|---------|
| customer | `AppOtpFlow` | `npm run typecheck` / `build` / `dev` |
| backoffice | `AppBackoffice` (login → rotate → queue) | same |

## Constraints preserved
- RTL / FA
- Tenant from Host header (`X-Talamala-Host`) — no client tenant selector authority
- Decimal strings opaque from API
- No Weight750 / Rial↔Toman in frontend
- Existing zero-build HTML demos unchanged

## Local
```bash
# API
cd backend && php -S 127.0.0.1:8080 -t public public/router.php

# Customer UI (proxies /v1 to 8080)
cd frontend/customer && npm install && npm run dev

# Backoffice UI
cd frontend/backoffice && npm install && npm run dev
```

## CI
`frontend-typecheck` runs `npm ci && npm run typecheck` for both apps.
