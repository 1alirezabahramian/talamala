# Closure Policy — No Human Green

**Effective:** 2026-08-20  
**Authority:** Owner rule; enforced by Final Audit Agent

## Absolute rule

No person — Owner, developer, AI agent, or manual report — may declare Talamala **green**, **ready**, **closed**, **pilot-accepted**, or **release-accepted** unless:

1. `make final-audit` was run on **the exact same Git SHA** being claimed, and
2. the Agent produced `final_verdict: ACCEPTED_FOR_PILOT` (or a future stricter production verdict), and
3. that verdict is backed by **current-run Evidence** (executed smokes/contracts, not file presence alone), and
4. for CI-backed acceptance, exact-SHA attestation matches HEAD:
   `TALAMALA_AUDIT_CI_SHA=$(git rev-parse HEAD)` and `TALAMALA_AUDIT_CI_STATUS=success` from the CI run for that SHA.

## Forbidden substitutes

| Claim | Status |
|-------|--------|
| “CI was green last week” | Invalid for a different SHA |
| “Most checklist items look done” | Invalid without Agent report |
| Manual spreadsheet / slide / chat “ما سبزیم” | Invalid |
| High score with active Critical Veto | Invalid |
| Stale `AUDIT_REPORT_*` copied forward | Invalid as current evidence |
| Agent run without executing required gates | Invalid |

## Allowed language without ACCEPTED_FOR_PILOT

- “Agent score is X; verdict is NOT_READY_FOR_PILOT”
- “Phase-1 scope excludes GT-00N; those items remain ⚫”
- “Pilot ops path exists; closure not granted”

## Binding references

- `docs/audit/FINAL_AUDIT_AGENT.md`
- `docs/audit/CRITICAL_VETOS.md`
- `docs/audit/AUDIT_SCORING.md`
- `make final-audit` → `docs/audit/reports/AUDIT_REPORT_latest.json`

**Final Audit Agent = sole Authority for Closure.**
