# Talamala — Current State (2026-08-17)

## VERSION
`0.3.3-phase1`

## Smokes
| Check | Expect |
|-------|--------|
| domain_smoke (smoke.php) | PASS=8 FAIL=0 |
| http_smoke | PASS=51 FAIL=0 |
| persist_smoke | PASS=9 FAIL=0 |
| cors_smoke | PASS=13 FAIL=0 |
| logger_smoke | PASS=8 FAIL=0 |
| maintenance_smoke | PASS=7 FAIL=0 |
| spa_router_smoke | PASS=6 FAIL=0 |
| landing_smoke | PASS=17 FAIL=0 |
| openapi_parity | PASS |

## Operator
- `make check` / `make serve` / `make version`
- `make frontend-typecheck` / `make frontend-build` (optional)
- API responses emit `X-Correlation-Id`
- HTML + JSON: nosniff, DENY, Referrer-Policy, Permissions-Policy, X-Permitted-Cross-Domain-Policies

## Hardening (batch 0.3.3)
- Correlation-Id on API responses
- X-Permitted-Cross-Domain-Policies: none
- http negatives: unknown_path_404 + healthz_no_tenant
- domain_smoke in aggregate check
- cors/landing gate bumps

## BLOCKED
Kimia Write · Pricing · Settlement · Payment · Delta blind port
