# Stage — Landing hub + Operators doc

**Status:** CLOSED  
**Date:** 2026-08-15

## Steps
1. `backend/public/landing.html` — FA RTL hub links
2. `router.php` serves `/` → landing
3. `docs/00-master/OPERATORS.md` — quick start
4. README pointer
5. `landing_smoke.php` PASS=8
6. CI + check.php
7. `robots.txt` soft disallow on demos

## Expected
```text
php backend/bin/landing_smoke.php → PASS=8 FAIL=0
php backend/bin/http_smoke.php    → PASS=43 FAIL=0
```
