# Talamala — Current State (2026-08-17)

## VERSION
`0.3.7-phase1`

## Smokes
| Check | Expect |
|-------|--------|
| domain_smoke | PASS=8 FAIL=0 |
| http_smoke | PASS=71 FAIL=0 |
| persist_smoke | PASS=9 FAIL=0 |
| cors_smoke | PASS=13 FAIL=0 |
| logger_smoke | PASS=8 FAIL=0 |
| maintenance_smoke | PASS=7 FAIL=0 |
| spa_router_smoke | PASS=6 FAIL=0 |
| landing_smoke | PASS=17 FAIL=0 |
| openapi_parity | PASS |

## Operator
- `make help` · `make check` · `make serve` · `make version` · `make domain`
- OTP 429 includes `retry_after` + `Retry-After` header
- Staff rotate / custody validation negatives gated

## Hardening (batch 0.3.7)
- staff_rotate_password_too_weak · staff_rotate_invalid_current
- custody_receive_unauthorized · custody_receive_validation
- otp_rate_limited_retry_after · healthz_time_iso
- http_smoke PASS 65 → **71**

## BLOCKED
Kimia Write · Pricing · Settlement · Payment · Delta blind port
