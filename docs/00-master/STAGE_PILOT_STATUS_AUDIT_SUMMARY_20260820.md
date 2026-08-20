# Stage — Pilot status + audit summary board

**Base HEAD:** `8bf5848`  
**Date:** 2026-08-20  
**Scope:** ops visibility only — no Live Kimia, no GT, no VERSION bump.

## Changes
1. `scripts/final_audit_summary.py` + `make final-audit-summary` — score/colors/domains/vetos from latest report, but only when report SHA exactly matches current HEAD.
2. `scripts/pilot_status.sh` + `make pilot-status` — VERSION/SHA/write-deny/audit snapshot; stale audit reports are labeled `UNVERIFIED_FOR_CURRENT_HEAD`.
3. `scripts/ci_attest_hint.sh` + `make ci-attest-hint` — how CV-03 attestation works (no fake success).
4. CURRENT_STATE operator pointer.

## Explicit non-goals
Inventing CI success · accepting stale reports · Live Kimia · Human green without Agent.

## Closure rule
These commands are visibility helpers only. Closure remains exclusively `make final-audit` on the exact SHA with current evidence and exact-SHA CI attestation.
