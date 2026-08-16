# Stage — spa_router exact gate + Capability Ledger + VERSION 0.3.1

**Status:** CLOSED  
**Date:** 2026-08-16  
**Base HEAD:** 942be44

## Goal
Make spa_router_smoke fail-closed on exact PASS count (like other gates), refresh Capability Ledger, bump VERSION for Phase-1 hardening snapshot.

## Changes
1. `.github/workflows/ci.yml` — spa-router-smoke expects `PASS=6 FAIL=0`
2. `VERSION` → `0.3.1-phase1`
3. `docs/traceability/CAPABILITY_LEDGER.md` — CAP-021/022 + release notes
4. `docs/00-master/CURRENT_STATE.md` — aligned

## Non-goals
- No financial invent · no Kimia · no Delta · no tenant persistence

## Expected
```text
php backend/bin/spa_router_smoke.php → PASS=6 FAIL=0
php backend/bin/check.php            → ALL CHECKS PASSED
cat VERSION                          → 0.3.1-phase1
```
