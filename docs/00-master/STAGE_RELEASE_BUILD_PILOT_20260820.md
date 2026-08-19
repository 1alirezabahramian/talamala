# Stage — Release-build pilot hardening

**Base HEAD:** `69f59a1`  
**Date:** 2026-08-20  
**Scope:** ops only — no Live Kimia, no new GT, no domain wire, no VERSION bump.

## Goal
Make `make release-build` the reliable Phase-1 pilot build path:
preflight → SPA typecheck+build → dist gate → backend smokes (full when pdo_sqlite exists).

## Changes
1. `scripts/release_build.sh` rewritten:
   - always runs `pilot_preflight.sh` first
   - customer + backoffice `npm ci` + typecheck + build
   - requires `frontend/*/dist/index.html`
   - runs `spa_router_smoke` post-build
   - if `pdo_sqlite` loaded → full `check.php`
   - else → domain/cors/logger/landing/spa/parity subset + explicit note
   - write-deny reminder printed
2. `DEPLOY_PHASE1.md` — preferred path is `make release-build`
3. `PILOT_CHECKLIST.md` — before-traffic clarifies full check on host with driver

## Explicit non-goals
Live Kimia · GT-002 remainder · Order/Settlement wire · VERSION bump

## How to run
```bash
make release-build
```
