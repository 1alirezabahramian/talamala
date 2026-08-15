# Talamala — Current State (2026-08-15)

## Smokes
| Check | Expect |
|-------|--------|
| http_smoke | PASS=48 FAIL=0 |
| persist_smoke | PASS=9 FAIL=0 |
| cors_smoke | PASS=10 FAIL=0 |
| logger_smoke | PASS=8 FAIL=0 |
| maintenance_smoke | PASS=7 FAIL=0 |
| spa_router_smoke | FAIL=0 |
| landing_smoke | **PASS=13 FAIL=0** |
| openapi_parity | PASS |

## Operator
- `make check` / `make serve`
- `/` landing shows VERSION + optional BUILD_SHA
- HTML demos share baseline security headers + minimal CSP (inline allowed for zero-build demos)

## Hardening (this stage)
- Bearer session must match Host tenant (`tenant_session_mismatch` → 403)
- http_smoke PASS=48 (cross-tenant + auth scheme negatives)

## BLOCKED
Kimia Write · Pricing · Settlement · Payment · Delta blind port
