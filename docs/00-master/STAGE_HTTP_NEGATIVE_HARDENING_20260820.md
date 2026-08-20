# Stage — HTTP Negative Hardening

**Date:** 2026-08-20  
**Scope:** Phase-1 in-scope adversarial regression gate only.  
**No GT change. No Live Kimia Read/Write/Create. No Settlement wire. No VERSION bump.**

## Added gate

`backend/bin/http_negative_smoke.php` adds 8 fail-closed assertions around:

- missing tenant host → `tenant_unresolved`
- local fixture quote creation for the test only
- quote owner mismatch → rejected
- cross-tenant quote id → hidden as not found
- settlement HTTP route → absent
- wrong method on order accept → not found
- customer order list → identity required
- production → dev seed-quote hidden

`backend/bin/check.php` now executes `http_negative_smoke` as part of the aggregate local/release check.

`.github/workflows/http-negative-smoke.yml` also runs the smoke on `push` and `pull_request` and requires exact `PASS=8 FAIL=0`; any non-zero exit or count drift fails that workflow.

## Evidence discipline

The smoke and aggregate file passed PHP syntax validation before integration. Workflow presence is not PASS evidence: a current-run `PASS=8 FAIL=0` is **not** claimed by this stage record until the workflow/smoke executes on the exact claimed SHA.

No Human Green remains unchanged: only `make final-audit` on the exact claimed SHA with current-run evidence may produce `ACCEPTED_FOR_PILOT`.
