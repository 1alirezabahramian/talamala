# Stage — Backoffice CSS + log rotate + SPA HTML errors

**Status:** CLOSED  
**Date:** 2026-08-15

## Steps
1. `frontend/backoffice/src/styles.css` + import in main
2. StructuredLogger soft rotate (`TALAMALA_LOG_MAX_BYTES`, default 5MB → `.1`)
3. logger_smoke **PASS=8** (includes rotate)
4. router.php RTL HTML for 503 (missing build) / 404
5. spa_router_smoke soft dist + HTML string checks
6. CI logger PASS=8 + spa-router-smoke job
7. `.env.example` LOG_MAX_BYTES

## Expected
```text
php backend/bin/logger_smoke.php     → PASS=8 FAIL=0
php backend/bin/spa_router_smoke.php → FAIL=0
php backend/bin/http_smoke.php       → PASS=43 FAIL=0
php backend/bin/maintenance_smoke.php → PASS=7 FAIL=0
```
