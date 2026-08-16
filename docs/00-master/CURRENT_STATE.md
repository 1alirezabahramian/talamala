# Talamala — Current State (2026-08-16)

## VERSION
`0.3.2-phase1`

## Smokes
| Check | Expect |
|-------|--------|
| http_smoke | PASS=49 FAIL=0 |
| persist_smoke | PASS=9 FAIL=0 |
| cors_smoke | PASS=11 FAIL=0 |
| logger_smoke | PASS=8 FAIL=0 |
| maintenance_smoke | PASS=7 FAIL=0 |
| spa_router_smoke | PASS=6 FAIL=0 |
| landing_smoke | PASS=16 FAIL=0 |
| openapi_parity | PASS |

## Operator
- `make check` / `make serve` (check includes spa_router_smoke)
- `make frontend-typecheck` / `make frontend-build` (optional)
- `/` landing shows VERSION + optional BUILD_SHA
- HTML + JSON: nosniff, DENY, Referrer-Policy, Permissions-Policy; minimal CSP on HTML

## Hardening (batch 0.3.2)
- Session↔tenant isolation (customer + staff)
- Permissions-Policy on API + static HTML
- robots.txt blocks demos, `/v1/dev/`, `/app/`
- Frontend typecheck optional in CI
- Exact SPA / landing / cors gates

## BLOCKED
Kimia Write · Pricing · Settlement · Payment · Delta blind port
