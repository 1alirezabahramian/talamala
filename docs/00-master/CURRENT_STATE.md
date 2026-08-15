# Talamala — Current State (2026-08-15)

## Smokes
| Check | Expect |
|-------|--------|
| http_smoke | PASS=43 FAIL=0 |
| persist_smoke | PASS=9 FAIL=0 |
| cors_smoke | PASS=10 FAIL=0 |
| logger_smoke | PASS=8 FAIL=0 |
| maintenance_smoke | PASS=7 FAIL=0 |
| spa_router_smoke | FAIL=0 |
| landing_smoke | **PASS=12 FAIL=0** |
| openapi_parity | PASS |

## Operator
- `make check` / `make serve`
- `/` landing shows VERSION + optional BUILD_SHA
- HTML demos share baseline security headers

## BLOCKED
Kimia Write · Pricing · Settlement · Payment · Delta blind port
