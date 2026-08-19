# Audit scoring model

Each checklist item scores **0–100** from evidence dimensions.  
**N/A dimensions are removed from the denominator**.

| Dimension | Max | What counts as evidence |
|-----------|-----|-------------------------|
| ground_truth | 10 | Requirement/ADR/official artifact or explicit Phase-1 scope statement |
| runtime | 20 | Code/path + ledger state + a relevant **current-run PASS** gate when one exists |
| tenant_security | 15 | Current-run tenant/auth/CORS gates + capability state |
| automated_tests | 15 | **Current audit run PASS only**; test-file existence earns no PASS credit |
| openapi_contract | 10 | Required OpenAPI files + **current-run parity PASS** |
| frontend_ux | 10 | SPA screen / FormField / a11y implementation evidence when applicable |
| exact_sha_ci | 15 | Explicit CI success attestation for the **same git HEAD** |
| ops_deploy | 5 | Runbook / pilot scripts / deploy docs |

## Exact-SHA CI attestation

`final-audit` does **not** infer CI success from the existence of `.github/workflows/ci.yml`.

For exact-SHA credit, run it with CI-supplied environment:

```bash
TALAMALA_AUDIT_CI_SHA="$(git rev-parse HEAD)" \
TALAMALA_AUDIT_CI_STATUS=success \
make final-audit
```

Those values must come from the CI execution that actually passed for that SHA. Manually inventing them is not accepted evidence.

## Colors

| Range | Color | Meaning |
|-------|-------|---------|
| 90–100 | 🟢 GREEN | Acceptable for item closure |
| 70–89 | 🟡 YELLOW | Near complete |
| 40–69 | 🟠 ORANGE | Incomplete |
| 0–39 | 🔴 RED | Serious / not implemented |
| — | ⚫ BLOCKED | Needs GT, external service, device, or Owner decision |

## Item verdict

- `ACCEPTED` — GREEN and no item-level blocker
- `NOT_ACCEPTED` — score gap or missing evidence
- `BLOCKED` — GT / out-of-evidence

**Score alone never grants project-level ACCEPTED.**  
There is no automatic score boost for “files exist”.
