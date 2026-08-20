# Final Audit Agent — Authority for Closure

> **No Human Green:** Nobody (Owner, developer, AI, manual report) may declare Talamala green/closed/pilot-accepted unless `make final-audit` on **that exact SHA** yields `ACCEPTED_FOR_PILOT` with current-run Evidence. See `CLOSURE_POLICY.md`.


**Role:** machine authority for Phase-1 pilot/release closure verdict.  
Humans supply Ground Truth and external evidence; the Agent must not manufacture PASS from file presence.

## Inputs

1. `docs/audit/registry/CHECKLIST_REGISTRY.json`
2. `docs/traceability/CAPABILITY_LEDGER.md`
3. `docs/00-master/GROUND_TRUTH_BLOCKERS.md`
4. Current repository filesystem / OpenAPI / pilot docs
5. **Current-run** smoke/contract results executed by the Agent
6. Exact-SHA CI success attestation matching `git rev-parse HEAD`

## Non-negotiable evidence rules

- Test file exists ≠ test passed.
- CI workflow exists ≠ exact-SHA CI passed.
- OpenAPI parity script exists ≠ parity passed.
- No score boost may turn weak evidence into GREEN.
- A stale report is historical evidence only; it must not be copied to `AUDIT_REPORT_latest.*`.

## Verdict gate

`ACCEPTED_FOR_PILOT` requires all of:

- no Critical Veto,
- mean score ≥ 90,
- all in-scope critical items GREEN,
- no in-scope ORANGE/RED item,
- exact-SHA CI attestation for the current HEAD.

Out-of-pilot GT-blocked capabilities may remain ⚫ when explicitly excluded by Phase-1 scope.

## Run

Local diagnostic run:

```bash
make final-audit
```

Without exact-SHA CI attestation, CV-03 is expected and the verdict cannot be accepted.

Authoritative CI-backed run:

```bash
TALAMALA_AUDIT_CI_SHA="$(git rev-parse HEAD)" \
TALAMALA_AUDIT_CI_STATUS=success \
make final-audit
```

The two environment values must come from the CI run for that exact SHA.

## Output

- `docs/audit/reports/AUDIT_REPORT_<sha>.md`
- `docs/audit/reports/AUDIT_REPORT_latest.md`
- `docs/audit/reports/AUDIT_REPORT_latest.json`

Generated reports are outputs, not hand-edited source truth.
