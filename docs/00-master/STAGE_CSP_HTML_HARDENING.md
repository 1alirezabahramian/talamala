# Stage — Minimal CSP for static HTML

**Status:** CLOSED  
**Date:** 2026-08-15  
**SHA base:** 07108b6 (HEAD before this stage)

## Goal
Harden static HTML responses (landing + zero-build demos + SPA error pages) with a minimal Content-Security-Policy without breaking demos that rely on inline `<style>` / `<script>`.

## Changes
1. `backend/public/router.php`
   - `$htmlSecurityHeaders` now emits CSP:
     `default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'`
   - SPA/error HTML pages get the same baseline (nosniff, DENY, Referrer-Policy, CSP)
2. `backend/bin/landing_smoke.php` — new check `router_html_csp` → PASS=13
3. CI gate + CURRENT_STATE + OPERATORS expect numbers updated to landing PASS=13
4. CAPABILITY_LEDGER Persistence note corrected (sessions/audit/idem on SQLite)

## Non-goals
- No invent financial APIs
- No CSP on JSON API responses (SecurityHeaders remains as-is)
- No nonce/hash migration of demo scripts (unsafe-inline kept for zero-build demos)

## Expected
```text
php backend/bin/landing_smoke.php → PASS=13 FAIL=0
make check / php backend/bin/check.php → ALL CHECKS PASSED
```
