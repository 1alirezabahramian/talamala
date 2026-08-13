# Talamala — Ground Truth Blocker Register
**Stage:** 0  
**Status:** Active  
**Last updated:** 2026-08-12  

Until a blocker is resolved with official/current evidence, the related capability remains:

> `BLOCKED BY GROUND TRUTH`

No “standard industry behavior”, no plausible guess, no invented payload is allowed.

---

## P0 — Must resolve before any related implementation

| ID | Blocker | Blocks | Required Evidence | Owner Action |
|----|---------|--------|-------------------|--------------|
| GT-001 | Current official Kimia Swagger / OpenAPI (raw file) | All Kimia write paths, Create Customer, exact Account/Balance semantics | Raw swagger.json or equivalent + version/date | Supply file or provide authenticated access |
| GT-002 | Kimia Create Customer exact request/response/error/duplicate/readback | Onboarding automatic modes, Kimia binding creation | Official contract + real sandbox response | Supply or authorize live probe |
| GT-003 | Kimia write contracts for each financial operation (paper gold, coin, currency, physical, receive/pay) | Order execution, Settlement | Exact request bodies + success/error responses + side effects on balance | Supply or authorize controlled tests |
| GT-004 | Price Provider official API + freshness/failover + business coefficients (x/y/z) + rounding order + Quote expiry | Pricing, Quote | Official provider contract + owner-approved pricing policy | Supply contract + policy |
| GT-005 | Settlement / reconciliation / hold / freeze / credit semantics | Settlement, Credit trading | Explicit business rules + Kimia behavior evidence | Owner decision + evidence |

## P1 — Required before production readiness of the feature

| ID | Blocker | Blocks | Required Evidence |
|----|---------|--------|-------------------|
| GT-006 | BehPardakht Mellat current merchant contract + sandbox process | Online payments | Official merchant docs + credentials process |
| GT-007 | Goftino official widget/API/privacy contract | Support chat integration | Current official docs |
| GT-008 | SMS.ir tenant-specific credentials, templates, live delivery proof | Production OTP | Tenant panel config + controlled live test |
| GT-009 | Jibit sandbox/live credentials + current version + rate/error behavior | Live onboarding | Credentials + controlled test results |

## Resolution Rule

1. Obtain official/current artifact.
2. Archive it under `docs/providers/official/` with date + SHA-256.
3. Update Source Register and Capability Ledger.
4. Only then remove the `BLOCKED BY GROUND TRUTH` status for the affected capability.

---

**Current project policy:**  
Stage 0 and Stage 1 (Foundation) can proceed without resolving P0.  
Stage 5 (Pricing/Quote), Stage 6 (Order/Kimia Write), Stage 7 (Settlement), Stage 9 (Payments) cannot start until the corresponding P0/P1 blockers are cleared.
