# Talamala — Current State (2026-08-15)

## Smokes
| Check | Expect |
|-------|--------|
| http_smoke | PASS=43 FAIL=0 |
| persist_smoke | PASS=9 FAIL=0 |
| cors_smoke | PASS=10 FAIL=0 |
| logger_smoke | **PASS=8 FAIL=0** |
| maintenance_smoke | PASS=7 FAIL=0 |
| spa_router_smoke | FAIL=0 |
| openapi_parity | PASS |

## Ops
- Log stream + soft rotate via env
- readyz purge + ops counters
- SPA mounts with FA HTML errors when dist missing

## BLOCKED
Kimia Write · Pricing · Settlement · Payment · Delta blind port
