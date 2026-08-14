# Talamala — Current State (2026-08-14)

## HTTP smoke
`php backend/bin/http_smoke.php` → **PASS=32 FAIL=0**

Includes: tenant isolation (demo + other + unknown), OTP rate-limit + cross-tenant isolation,
full identity vertical, custody lifecycle, order accept+idempotency, production bearer hardening.

## OpenAPI
v1.3.0 aligned with working routes (429, register, rotate, custody ready/deliver).

## Next
C — Wire customer frontend OTP screens to local PHP server.
