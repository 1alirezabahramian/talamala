# Talamala — Current State (2026-08-17)

## VERSION
`0.3.4-phase1`

## Smokes
| Check | Expect |
|-------|--------|
| domain_smoke | PASS=8 FAIL=0 |
| http_smoke | PASS=54 FAIL=0 |
| persist_smoke | PASS=9 FAIL=0 |
| cors_smoke | PASS=13 FAIL=0 |
| logger_smoke | PASS=8 FAIL=0 |
| maintenance_smoke | PASS=7 FAIL=0 |
| spa_router_smoke | PASS=6 FAIL=0 |
| landing_smoke | PASS=17 FAIL=0 |
| openapi_parity | PASS |

## Operator
- `make check` / `make serve` / `make version` / `make domain`
- healthz + readyz expose non-secret `version`
- API: X-Correlation-Id · security headers baseline

## Hardening (batch 0.3.4)
- healthz/readyz `version` from VERSION file
- order accept missing Idempotency-Key → 422 (gated)
- ADR_INDEX statuses aligned with skeleton reality
- OpenAPI auth notes correlation + version
- Makefile `domain` target

## BLOCKED
Kimia Write · Pricing · Settlement · Payment · Delta blind port
