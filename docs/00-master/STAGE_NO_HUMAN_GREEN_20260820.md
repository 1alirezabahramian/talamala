# Stage — No Human Green (closure policy)

**Base HEAD:** `ab91d66`  
**Date:** 2026-08-20  
**Scope:** policy/docs only — no domain change, no VERSION bump.

## Rule
Nobody (Owner, developer, AI, manual report) may declare Talamala green/closed/pilot-accepted unless `make final-audit` on **that exact SHA** yields `ACCEPTED_FOR_PILOT` with current-run Evidence.

## Integrated files
- `docs/audit/CLOSURE_POLICY.md` (canonical)
- `docs/audit/FINAL_AUDIT_AGENT.md`
- `AGENTS.md`
- `docs/00-master/CURRENT_STATE.md`
- `docs/00-master/PHASE1_SAFE_CLOSURE.md`
- this stage record

## Integration note
The package also proposed a console banner change in `scripts/final_audit_agent.py`, but that delivered file had a Python `IndentationError` and was not integrated. The existing fail-closed Agent implementation remains unchanged and executable.

`docs/audit/README.md` from the package matched current main and required no change.
