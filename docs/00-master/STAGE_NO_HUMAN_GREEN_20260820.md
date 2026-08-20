# Stage — No Human Green (closure policy)

**Base HEAD:** `ab91d66`  
**Date:** 2026-08-20  
**Scope:** policy/docs + Agent banner only — no domain change, no VERSION bump.

## Rule
Nobody (Owner, developer, AI, manual report) may declare Talamala green/closed/pilot-accepted unless `make final-audit` on **that exact SHA** yields `ACCEPTED_FOR_PILOT` with current-run Evidence.

## Files
- `docs/audit/CLOSURE_POLICY.md` (canonical)
- `docs/audit/FINAL_AUDIT_AGENT.md` · `README.md`
- `AGENTS.md` · `CURRENT_STATE.md` · `PHASE1_SAFE_CLOSURE.md`
- `scripts/final_audit_agent.py` (console + report banner)
