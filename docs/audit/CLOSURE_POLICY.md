# Closure Policy — No Human Green

**Effective:** 2026-08-20  
**Authority:** Owner rule; enforced by machine Authority

## Absolute rule

No person — Owner, developer, AI agent, or manual report — may declare Talamala **green**, **ready**, **closed**, **pilot-accepted**, or **release-accepted** unless the matching Authority ran on the **exact same Git SHA**:

1. Pilot claim → `make final-audit` → `final_verdict: ACCEPTED_FOR_PILOT`
2. Release claim → `make final-audit-release` → `release_verdict: ACCEPTED_FOR_RELEASE`
3. The verdict is backed by current-run evidence, not file presence or stale reports.
4. CI-backed acceptance requires exact-SHA attestation matching `git rev-parse HEAD`.

**Pilot green ≠ Release green.** `ACCEPTED_FOR_PILOT` never authorizes publishable/full-release language.

## Forbidden substitutes

| Claim | Status |
|-------|--------|
| “CI was green last week” | Invalid for a different SHA |
| “Most checklist items look done” | Invalid without matching Agent report |
| Manual spreadsheet / slide / chat “ما سبزیم” | Invalid |
| High score with active CV-* or RV-* | Invalid |
| Stale report copied forward | Invalid as current evidence |
| Agent run without executing required gates | Invalid |
| “Pilot accepted ⇒ release ready” | Invalid |

## Release registry integrity

`RELEASE_SCOPE_REGISTRY.json` must classify **every checklist ID exactly once** as `release_required` or `release_deferred`. Missing, duplicate, unknown, or overlapping IDs activate RV-04. This prevents scope shrinkage from manufacturing a release green.

## Allowed language without the matching ACCEPTED_* verdict

- “Agent score is X; verdict is NOT_READY_FOR_PILOT / NOT_READY_FOR_RELEASE.”
- “Release Authority executed; blockers remain.”
- “Phase-1 pilot is accepted; Full Release is not.”
- “GT-00N remains required for Full Release.”

## Binding references

- `docs/audit/FINAL_AUDIT_AGENT.md`
- `docs/audit/RELEASE_SCOPE_FULL.md`
- `docs/audit/CRITICAL_VETOS.md`
- `docs/audit/AUDIT_SCORING.md`
- `docs/audit/registry/RELEASE_SCOPE_REGISTRY.json`
- `make final-audit` / `make final-audit-release`

**Machine Authority is the sole Authority for Closure claims.**
