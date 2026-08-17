# Talamala — Current State (2026-08-17)

## VERSION
`0.3.6-phase1`

## Smokes
| Check | Expect |
|-------|--------|
| domain_smoke | PASS=8 FAIL=0 |
| http_smoke | PASS=65 FAIL=0 |
| persist_smoke | PASS=9 FAIL=0 |
| cors_smoke | PASS=13 FAIL=0 |
| logger_smoke | PASS=8 FAIL=0 |
| maintenance_smoke | PASS=7 FAIL=0 |
| spa_router_smoke | PASS=6 FAIL=0 |
| landing_smoke | PASS=17 FAIL=0 |
| openapi_parity | PASS |

## Operator
- `make help` · `make check` · `make serve` · `make version` · `make domain`
- Customer/backoffice clients send `X-Correlation-Id`
- Contract negatives expanded (OTP purpose/fields, register validation/duplicate, wrong method)

## Hardening (batch 0.3.6)
- http_smoke PASS 59 → **65**
- Frontend correlation-id on all API calls
- Makefile `help` · robots Allow /healthz
- OpenAPI auth 422 for OTP validation

## BLOCKED
Kimia Write · Pricing · Settlement · Payment · Delta blind port
