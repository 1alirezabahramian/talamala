# Full Release Scope — Authority boundary

**Authority:** Final Audit Agent (Release mode)  
**Pilot baseline:** Phase-1 `ACCEPTED_FOR_PILOT` remains valid for bounded pilot only.  
**Release verdict:** `ACCEPTED_FOR_RELEASE` — stricter; never inferred from pilot green.

## What Full Release means

A publishable product for the **declared** release scope where:

1. Core customer journey works end-to-end within grounded capabilities.
2. Backoffice operational journey works for in-scope ops.
3. No speculative finance (Kimia remains sole money/gold truth).
4. Tenant isolation, idempotency, DecimalString, auth fail-closed enforced.
5. OpenAPI parity + current-run smokes PASS on the **exact SHA**.
6. Exact-SHA CI attestation present.
7. Every `release_required` checklist item is GREEN (not BLOCKED/RED/ORANGE).
8. No Critical Veto (CV-*) and no Release Veto (RV-*).

## Registry

- Pilot items: `docs/audit/registry/CHECKLIST_REGISTRY.json` (`pilot_scope`)
- Release classification: `docs/audit/registry/RELEASE_SCOPE_REGISTRY.json`
  - `release_required` — must be closed for `ACCEPTED_FOR_RELEASE`
  - `release_deferred` — explicit Release-2+
  - The two lists must form a complete, unique, disjoint partition of every checklist ID; omission, overlap, duplicate, or unknown IDs trigger `RV-04`.

## Currently expected blockers for Full Release

| Area | Typical FA / GT |
|------|-----------------|
| Live pricing / quote provider | FA-048, FA-049 · GT-004 |
| Settlement / Kimia wire from Order | FA-060, FA-078 · GT-005 |
| Kimia Create live + registration wire | FA-045, FA-080, FA-081 · GT-002 |
| Payment gateway | FA-096–FA-098 · GT-006 |
| Production SMS / Jibit | FA-026, FA-039, FA-099 · GT-008/009 |
| Coin/Currency/Physical Kimia ops | FA-082 · GT-003 |

Pilot green **does not** clear these.

## Deferred by default

FA-020 durable multi-tenant · FA-054 full product catalog · FA-066 credit · FA-067 reconciliation · FA-095 advanced delivery proof · FA-101 Goftino · FA-102/103 outbox/bank recon · FA-112 outbox pattern · FA-123/124 PWA/native.

Owner may reclassify deferred → required only with an explicit registry change. Scope shrinkage must never be inferred from a failing capability.

## Run

```bash
make final-audit              # bounded Pilot authority
make final-audit-release      # stricter Full Release authority
```

With CI attestation:

```bash
TALAMALA_AUDIT_CI_SHA="$(git rev-parse HEAD)" \
TALAMALA_AUDIT_CI_STATUS=success \
make final-audit-release
```

## CI Authority

The GitHub Final Audit workflow executes Release Authority on the exact PR/main SHA after current-run backend, adversarial, Cycle 1/2, frontend typecheck/build, offline Kimia contract, and pilot-preflight evidence. A Release verdict produced without exact-SHA evidence is diagnostic only.

## No Human Green

Nobody may claim **release-ready / publishable / ACCEPTED_FOR_RELEASE** unless the Release Authority report for **that exact SHA** contains `release_verdict: ACCEPTED_FOR_RELEASE`.
