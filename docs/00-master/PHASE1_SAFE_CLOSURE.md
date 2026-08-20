# Phase-1 Safe Closure

**Status:** FROZEN  
**VERSION:** `0.3.8-phase1`  
**Baseline SHA (at Owner confirmation):** `f1e9eb2`  
**Date:** 2026-08-17  
**Authority:** Owner confirmation — freeze Phase-1; no speculative development

## Decision

Phase-1 (Foundation + Identity + Kimia Read + Custody + Order skeleton + CI/hardening) is **closed and frozen** at `0.3.8-phase1`.

From this point:

1. **Do not** invent Kimia Write, Pricing, Settlement, Payment, SMS/Jibit live, or durable Tenant/Delta behavior.
2. **Do not** ship demonstration-only features that imply production financial capability.
3. **Do not** invent OpenAPI or runtime contracts beyond what is already implemented and gated.
4. Further domain work requires **valid Ground Truth** archived under `docs/providers/official/` (or equivalent Owner-supplied official artifact) with date and integrity reference.

## What is in scope of this freeze (done)

- Host / `X-Talamala-Host` tenant resolve fail-closed
- OTP customer auth (fake SMS) + rate limit
- Staff login + password rotation
- Registration + Fake Jibit gate + staff approve
- Kimia **Read** client + assets Toman mapping
- Custody lifecycle (Talamala truth)
- Order accept from quote with `settlement: blocked_by_ground_truth`
- SQLite persistence: sessions, idempotency, audit, rate limits
- Exact-SHA CI gates + OpenAPI route parity
- Security headers, Correlation-Id, healthz/readyz version
- Contract negatives gated in `http_smoke` (PASS=78) and related smokes

## Explicitly out of scope until GT

| Area | Blocker IDs (see GROUND_TRUTH_BLOCKERS.md) |
|------|---------------------------------------------|
| Kimia Write / Create Customer | GT-001 … GT-003 |
| Pricing / live Quote | GT-004 |
| Settlement / hold / freeze | GT-005 |
| Payment (e.g. BehPardakht) | GT-006 |
| SMS.ir / Jibit live | GT-008, GT-009 |
| Durable multi-tenant / Delta port | Owner design + explicit authorization |

## Unfreeze rule

1. Owner supplies official current artifact (Swagger, policy, merchant contract, credentials process).
2. Archive under `docs/providers/official/` with date + hash.
3. Update Source Register + Capability Ledger status.
4. Only then implement the matching capability — still fail-closed, still decimal strings, still tenant-from-host.

## Operator note

`make check` on this freeze line must remain green.  
Regression of Phase-1 gates is a stop condition, not a soft warning.


## Closure authority (No Human Green)

Phase-1 pilot/release **acceptance** is not declared by humans.  
Only `make final-audit` on the release SHA with current-run Evidence and verdict `ACCEPTED_FOR_PILOT` may close.  
Policy: `docs/audit/CLOSURE_POLICY.md`.
