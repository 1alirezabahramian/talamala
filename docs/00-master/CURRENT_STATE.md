# Talamala — Current State (2026-08-17)

## VERSION
`0.3.5-phase1`

## Smokes
| Check | Expect |
|-------|--------|
| domain_smoke | PASS=8 FAIL=0 |
| http_smoke | PASS=59 FAIL=0 |
| persist_smoke | PASS=9 FAIL=0 |
| cors_smoke | PASS=13 FAIL=0 |
| logger_smoke | PASS=8 FAIL=0 |
| maintenance_smoke | PASS=7 FAIL=0 |
| spa_router_smoke | PASS=6 FAIL=0 |
| landing_smoke | PASS=17 FAIL=0 |
| openapi_parity | PASS |

## Operator
- `make check` / `make serve` / `make version` / `make domain`
- healthz/readyz include `version`
- Identity/order contract negatives gated in http_smoke

## Hardening (batch 0.3.5)
- staff_login_bad_password · credentials_required
- otp_request_mobile_required · otp_verify_bad_code
- order_accept_missing_quote_id (+ existing missing idempotency)
- http_smoke PASS 54 → **59**

## BLOCKED
Kimia Write · Pricing · Settlement · Payment · Delta blind port
