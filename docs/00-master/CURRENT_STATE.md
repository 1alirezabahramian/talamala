# Talamala — Current State (2026-08-15)

## Smokes
| Check | Expect |
|-------|--------|
| http_smoke | **PASS=41 FAIL=0** |
| persist_smoke | **PASS=9 FAIL=0** |
| cors_smoke | **PASS=10 FAIL=0** |
| openapi_parity | PASS |
| frontend typecheck/build | customer + backoffice PASS |

## Runtime
- API: Kernel via `public/index.php`
- SPA (optional): `/app/customer`, `/app/backoffice` from Vite `dist/` (router.php)
- readyz: sqlite check + process ops counters
- CORS: allow-list via `TALAMALA_CORS_ORIGINS` only

## Persistence
SQLite: customers, quotes, custody, orders, sessions, idempotency, audit, rate_limits  
Tenant resolver: InMemory seeded hosts

## BLOCKED BY GROUND TRUTH
Kimia Write · Price/Catalog · Settlement · Payment · Delta blind port

## Next (safe)
- Wire logout button in UI shells
- Structured log stream path via env for operators
- Keep smokes exact-SHA gated
