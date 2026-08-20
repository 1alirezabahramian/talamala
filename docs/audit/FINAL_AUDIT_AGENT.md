# Final Audit Agent — Authority for Closure

> **No Human Green:** nobody may declare Talamala pilot-accepted unless `make final-audit` on the exact SHA yields `ACCEPTED_FOR_PILOT`; nobody may declare the product publishable unless `make final-audit-release` on that exact SHA yields `release_verdict: ACCEPTED_FOR_RELEASE`.

## Roles

- `scripts/final_audit_agent_v2.py` — unchanged bounded Pilot authority.
- `scripts/release_audit_agent.py` — strictly stronger Full Release wrapper; it first runs the Pilot authority, then applies Release scope + RV-*.

Humans supply Ground Truth and external evidence. Neither Agent may manufacture PASS from file presence.

## Inputs

1. `docs/audit/registry/CHECKLIST_REGISTRY.json`
2. `docs/audit/registry/RELEASE_SCOPE_REGISTRY.json` (Release mode)
3. Capability ledger + GT blockers
4. Current repository / OpenAPI / frontend / deploy files
5. Current-run smoke and contract results
6. Exact-SHA CI success attestation matching `git rev-parse HEAD`

## Pilot gate

`ACCEPTED_FOR_PILOT` still requires the existing rules unchanged: no CV-*, mean ≥90, all Pilot in-scope critical GREEN, no Pilot ORANGE/RED, exact-SHA CI attestation.

## Full Release gate

`ACCEPTED_FOR_RELEASE` requires all of:

- Pilot authority on the same SHA remains `ACCEPTED_FOR_PILOT`;
- no CV-* and no RV-*;
- every `release_required` checklist item GREEN;
- all required critical items GREEN;
- release mean ≥90;
- release registry is a complete, unique, disjoint classification of every checklist row (RV-04 fail-closed);
- exact-SHA current-run evidence/CI attestation.

Until GT-002/003/004/005/006/008/009 and their required rows are grounded/implemented (or explicitly Owner-deferred where permitted), Release mode is expected to report `NOT_READY_FOR_RELEASE` or another blocking verdict. Do not lower thresholds to force green.

## Run

```bash
make final-audit
make final-audit-release
```

Authoritative exact-SHA environment:

```bash
TALAMALA_AUDIT_CI_SHA="$(git rev-parse HEAD)" \
TALAMALA_AUDIT_CI_STATUS=success \
make final-audit-release
```

## CI Release Authority

The GitHub Final Audit workflow runs Release Authority after exact-SHA evidence and Pilot authority. `NOT_READY_FOR_RELEASE` is a valid development-time Authority result and its artifact is still published. Workflow greenness alone never means Release green; **only** `release_verdict: ACCEPTED_FOR_RELEASE` does.

## Output

- `docs/audit/reports/AUDIT_REPORT_<sha>.md`
- `docs/audit/reports/AUDIT_REPORT_latest.md`
- `docs/audit/reports/AUDIT_REPORT_latest.json`

Generated reports are outputs, not hand-edited truth.
