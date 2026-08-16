# Talamala — Current State (2026-08-16)

## VERSION
`0.3.1-phase1`

## Smokes
| Check | Expect |
|-------|--------|
| http_smoke | PASS=49 FAIL=0 |
| persist_smoke | PASS=9 FAIL=0 |
| cors_smoke | PASS=10 FAIL=0 |
| logger_smoke | PASS=8 FAIL=0 |
| maintenance_smoke | PASS=7 FAIL=0 |
| spa_router_smoke | **PASS=6 FAIL=0** |
| landing_smoke | PASS=13 FAIL=0 |
| openapi_parity | PASS |

## Operator
- `make check` / `make serve`
- `make frontend-typecheck` / `make frontend-build` (optional; Node required)
- `/` landing shows VERSION + optional BUILD_SHA
- HTML demos: baseline security headers + minimal CSP

## Hardening
- Bearer session ↔ Host tenant match for **customer and staff** (`tenant_session_mismatch` → 403)
- http_smoke PASS=49 · spa_router exact PASS=6 in CI
- Frontend typecheck **optional** in CI (`continue-on-error`)

## BLOCKED
Kimia Write · Pricing · Settlement · Payment · Delta blind port
