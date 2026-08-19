# Stage — Pilot host smoke (GET-only)

**Base HEAD:** `b0fdf4e`  
**Date:** 2026-08-20  
**Scope:** ops only — no Live Kimia, no new GT, no domain wire, no VERSION bump.

## Goal
Close the pilot checklist gap between offline/build gates and production Host traffic with a safe, credential-free smoke.

## Changes
1. `scripts/pilot_host_smoke.sh` — requires `TALAMALA_BASE_URL` (or arg1); GET only:
   `/healthz` · `/readyz` · `/` · `/robots.txt`
2. `make pilot-host-smoke` + help + `make info` pilot line
3. `PILOT_CHECKLIST.md` — host smoke first item
4. `CURRENT_STATE.md` — Operator section lists pilot path commands

## Explicit non-goals
- No OTP/staff/Kimia/custody automation here (remain manual checklist)
- No Write/Create, no Settlement wire
- No VERSION bump

## How to run
```bash
TALAMALA_BASE_URL=https://pilot.example make pilot-host-smoke
# or: bash scripts/pilot_host_smoke.sh https://pilot.example
```
