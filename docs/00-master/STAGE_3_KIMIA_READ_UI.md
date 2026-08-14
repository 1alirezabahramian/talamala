# Stage 3 UI — Kimia Read (GET /v1/customer/assets)

**Scope:** read-only customer assets from existing Kernel path.

## Contract (existing)

```
GET /v1/customer/assets
Headers: X-Talamala-Host + Authorization: Bearer <token>
         (local only: X-Customer-Id)
200:
  status: ok | not_bound
  money_toman: decimal string
  gold_weight_g: decimal string
  message?: string (not_bound)
401 / 403 / 404 / 503 as Kernel today
```

## Out of scope (blocked / not in this ZIP)

- price / quote
- Kimia Write
- Order accept / custody mutations
- any client-side money math

## Files

| Path | Role |
|------|------|
| frontend/customer/src/api/assets.ts | fetchCustomerAssets |
| frontend/customer/src/screens/assets/AssetsScreen.tsx | RTL display |
| backend/public/assets-demo.html | zero-build local demo |
| docs/00-master/STAGE_3_KIMIA_READ_UI.md | this note |

## Local

```bash
cd backend && php -S 127.0.0.1:8080 -t public public/router.php
# http://127.0.0.1:8080/assets-demo.html
```
