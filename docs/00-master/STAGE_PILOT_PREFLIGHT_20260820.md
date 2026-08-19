# Stage — Pilot preflight (offline readiness)

**Base HEAD:** `a9fefb4`  
**Date:** 2026-08-20  
**Scope:** ops/docs + offline gate only — **no** Live Kimia, **no** new GT, **no** registration/order/settlement wire, **no** VERSION bump.

## Goal
One independent step on the Phase-1 pilot/release path: a machine-checkable offline preflight that operators can run in any sandbox (including non-Iran) before production traffic.

## Changes
1. `scripts/pilot_preflight.sh` — fail-closed checks:
   - VERSION pin `0.3.8-phase1`
   - Freeze / release / pilot docs present
   - `.env.example` documents `KIMIA_WRITE_VERIFY_ENABLE=0` and does not enable write
   - PHP syntax (backend/app + bin)
   - `domain_smoke` PASS=8
   - OpenAPI parity
   - Offline Kimia Write + Create Customer contract smokes
   - Frontend customer + backoffice typecheck
   - Settlement blocked marker still present in backend
2. `make pilot-preflight` target + help line
3. `PILOT_CHECKLIST.md` — offline preflight section

## Explicit non-goals
- Live Kimia Read/Write/Create
- GT-002 remaining evidence (duplicate/validation/readback)
- Pricing, Settlement, Payment, SMS/Jibit
- Full `make check` (requires pdo_sqlite) — preflight deliberately avoids DB driver dependency

## How to run
```bash
make pilot-preflight
# or: bash scripts/pilot_preflight.sh
```

## Traceability
Does not change Capability Ledger statuses. Strengthens pilot operator path only.
