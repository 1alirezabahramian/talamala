# Stage — Expired data purge + shell CSS

**Status:** CLOSED  
**Date:** 2026-08-15

## Steps
1. `SessionStore::purgeExpired`
2. Sqlite session / idempotency / rate_limits purge
3. `SqliteMaintenance` helper
4. Soft purge on every `readyz` (ops.purged)
5. `maintenance_smoke.php` PASS=7
6. Customer `styles.css` + import in main
7. CI `maintenance-smoke` + check.php

## Expected
```text
php backend/bin/maintenance_smoke.php → PASS=7 FAIL=0
php backend/bin/http_smoke.php        → PASS=43 FAIL=0
```
