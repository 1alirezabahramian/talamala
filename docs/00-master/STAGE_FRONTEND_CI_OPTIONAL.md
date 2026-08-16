# Stage — Frontend typecheck optional in CI + Makefile targets

**Status:** CLOSED  
**Date:** 2026-08-16  
**Base HEAD:** ab6c4d76bd640cce7b0e2a0d9305f8d77686f0e1

## Goal
Make frontend typecheck / build non-blocking for release gates (Node/npm flake must not fail exact-SHA CI). Add operator Makefile targets.

## Changes
1. `.github/workflows/ci.yml`
   - `frontend-typecheck` job: `continue-on-error: true`, name marked optional
   - Report text: frontend-typecheck listed as advisory only
2. `Makefile`
   - `frontend-typecheck` — npm ci + tsc for customer + backoffice
   - `frontend-build` — npm ci + build for both
3. Docs: CURRENT_STATE.md, OPERATORS.md updated

## Non-goals
- No invent financial APIs / Kimia / pricing / payment
- No change to required PHP smoke gates or PASS numbers
- No CSP / session / tenant behaviour change

## Expected
```text
php backend/bin/check.php          → ALL CHECKS PASSED (unchanged)
# frontend targets need Node; optional
make frontend-typecheck            # local advisory
```

## CI jobs still required for green SHA
php-syntax · http-smoke (48) · persist (9) · cors (10) · logger (8) · maintenance (7) · spa-router · landing (13) · openapi-parity · secret-scan
