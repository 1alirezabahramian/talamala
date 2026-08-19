# Talamala — Ground Truth Blocker Register
**Stage:** 0  
**Status:** Active  
**Last updated:** 2026-08-19  

Until a blocker is resolved with official/current evidence, the related capability remains:

> `BLOCKED BY GROUND TRUTH`

No “standard industry behavior”, no plausible guess, no invented payload is allowed.

---

## P0 — Must resolve before any related implementation

| ID | Blocker | Blocks | Required Evidence | Owner Action |
|----|---------|--------|-------------------|--------------|
| GT-001 | Current official Kimia Swagger / OpenAPI (raw file) | All Kimia write paths, exact Account/Balance semantics | Raw swagger.json or equivalent + version/date | Live Iran runner verifies current Swagger; raw artifact archival remains open |
| GT-002 | Kimia Create Customer duplicate/validation/readback semantics | Automatic onboarding Create + authoritative binding completion | **PARTIAL:** live Swagger now grounds `POST /api/account`, `AccountDto`, zero Swagger-required properties, HTTP 200 primitive int32 id and generic HTTP 400. Still required: duplicate/validation error behavior + post-create readback | Separate explicit Owner authorization + exact test values for any controlled live Create |
| GT-003 | Kimia write contracts | Order execution (partial), Settlement | **PARTIAL:** paper-gold buy/sell + cash receive/pay live-proven (Batch V1, account 350, ids 77193–77196). Still open: Coin, Currency, Physical, Settlement semantics, full balance-side-effect model | Continue GT for remaining ops |
| GT-004 | Price Provider official API + freshness/failover + business coefficients (x/y/z) + rounding order + Quote expiry | Pricing, Quote | Official provider contract + owner-approved pricing policy | Supply contract + policy |
| GT-005 | Settlement / reconciliation / hold / freeze / credit semantics | Settlement, Credit trading | Explicit business rules + Kimia behavior evidence | Owner decision + evidence |

## P1 — Required before production readiness of the feature

| ID | Blocker | Blocks | Required Evidence |
|----|---------|--------|-------------------|
| GT-006 | BehPardakht Mellat current merchant contract + sandbox process | Online payments | Official merchant docs + credentials process |
| GT-007 | Goftino official widget/API/privacy contract | Support chat integration | Current official docs |
| GT-008 | SMS.ir tenant-specific credentials, templates, live delivery proof | Production OTP | Tenant panel config + controlled live test results |
| GT-009 | Jibit sandbox/live credentials + current version + rate/error behavior | Live onboarding | Credentials + controlled test results |

## Resolution Rule

1. Obtain official/current artifact/evidence.
2. Record its version/hash/evidence run under `docs/providers/official/`.
3. Update Source Register and Capability Ledger/current state as applicable.
4. Only remove a blocker for the exact scope proven; unresolved semantics stay blocked.

---

**Current project policy:**  
Stage 0 and Stage 1 (Foundation) can proceed without resolving P0.  
Pricing, Order/Kimia Write, Settlement and Payments cannot advance beyond their grounded scope until the corresponding blockers are cleared.  
For Create Customer, the HTTP contract is grounded but **Live Create remains forbidden without a new explicit Owner authorization**; registration/order/settlement wiring remains off.
