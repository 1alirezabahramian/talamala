# Stage — Logout UI + Logger stream + env example

**Status:** CLOSED  
**Date:** 2026-08-15

## Steps (commit-friendly)
1. `StructuredLogger::fromEnv()` + `TALAMALA_LOG_PATH`
2. `backend/bin/logger_smoke.php` → PASS=7
3. Customer + Backoffice logout buttons (uses existing `/v1/auth/logout`)
4. `.env.example` (no secrets)
5. `backend/bin/check.php` aggregates http/persist/cors/logger/parity
6. CI job `logger-smoke`

## Expected
```text
php backend/bin/logger_smoke.php  → PASS=7 FAIL=0
php backend/bin/http_smoke.php    → PASS=41 FAIL=0
php backend/bin/cors_smoke.php    → PASS=10 FAIL=0
php backend/bin/check.php         → ALL CHECKS PASSED
```
