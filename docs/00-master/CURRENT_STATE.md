# Talamala — Current State (2026-08-15)

## Smokes
| Check | Expect |
|-------|--------|
| http_smoke | **PASS=41 FAIL=0** |
| persist_smoke | **PASS=9 FAIL=0** |
| openapi_parity | PASS |
| frontend typecheck | customer + backoffice PASS |

## Persistence
SQLite: customers, quotes, custody, orders, sessions, idempotency, audit, rate_limits  
Tenant resolver still InMemory (seeded hosts)

## Auth / session
- Bearer required in production
- **POST /v1/auth/logout** revokes session
- Cross-role session use → 403
- Dev routes require non-production + `X-Talamala-Dev: 1`

## Frontend
Thin Vite customer (OTP flow) + backoffice (login → queue)  
Zero-build HTML demos still in `backend/public/`

## BLOCKED BY GROUND TRUTH
Kimia Write (except Pilot 350 with exact GT) · Price/Catalog · Settlement · Payment  
No blind port from GoldPlatform V2 Delta

## Next (safe engineering)
- Optional static serve of Vite `dist/` under public
- More observability metrics without financial invent
- Tenant resolver durability only when multi-store model lands in this repo
