# Talamala — Current State (2026-08-17)

## VERSION
`0.3.8-phase1`

## Smokes
| Check | Expect |
|-------|--------|
| domain_smoke | PASS=8 FAIL=0 |
| http_smoke | PASS=78 FAIL=0 |
| persist_smoke | PASS=9 FAIL=0 |
| cors_smoke | PASS=13 FAIL=0 |
| logger_smoke | PASS=8 FAIL=0 |
| maintenance_smoke | PASS=7 FAIL=0 |
| spa_router_smoke | PASS=6 FAIL=0 |
| landing_smoke | PASS=18 FAIL=0 |
| openapi_parity | PASS |

## Operator
- `make help` · `make info` · `make check` · `make serve`
- X-Talamala-Host works for tenant resolve (readyz)
- healthz version matches VERSION file

## Hardening (batch 0.3.8)
- staff_rotate_requires_staff_id · password_reuse
- custody_ready_invalid_id
- order_accept_quote_not_found (409)
- seed_quote_customer_required
- readyz_x_talamala_host · healthz_version_matches_file
- robots_allows_healthz
- http 71→**78** · landing 17→**18**

## BLOCKED
Kimia Write · Pricing · Settlement · Payment · Delta blind port
