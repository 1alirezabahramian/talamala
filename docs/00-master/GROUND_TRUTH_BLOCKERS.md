# Talamala — Ground Truth Blocker Register
**Stage:** 0  
**Status:** Active  
**Last updated:** 2026-08-21  

Until a blocker is resolved with official/current evidence, the related capability remains:

> `BLOCKED BY GROUND TRUTH`

No “standard industry behavior”, no plausible guess, no invented payload is allowed.

---

## P0 — Must resolve before any related implementation

| ID | Blocker | Blocks | Required Evidence | Owner Action |
|----|---------|--------|-------------------|--------------|
| GT-001 | Current official Kimia Swagger / OpenAPI (raw file) | All Kimia write paths, exact Account/Balance semantics | Raw swagger.json or equivalent + version/date | Live Iran runner verifies current Swagger; raw artifact archival remains open |
| GT-002 | Kimia Create Customer duplicate/validation/readback semantics | Automatic onboarding Create + authoritative binding completion | **PARTIAL:** HTTP contract GROUNDED + default-deny live gate. Still required: duplicate/validation body + post-create readback. **Prep:** `KIMIA_CREATE_CONTROLLED_RUNBOOK.md` | Owner signs controlled window; approve test-only data; archive evidence; restore deny |
| GT-003 | Kimia write contracts | Order execution (partial), Settlement | **PARTIAL:** paper-gold buy/sell + cash receive/pay live-proven (Batch V1, account 350, ids 77193–77196). Still open: Coin, Currency, Physical, Settlement semantics, full balance-side-effect model | Continue GT for remaining ops |
| GT-004 | Price Provider official API + freshness/failover + business coefficients (x/y/z) + rounding order + Quote expiry | Pricing, Quote | **PARTIALLY GROUNDED:** Owner ratified the Cycle7 business-policy subset on 2026-08-21. `FA-047` TTL/freeze and `FA-049` x/y/z+rounding are evidenced by `PRICING_POLICY_OWNER_RATIFIED_20260821.md`. Still required for `FA-048`: official provider/API/auth/observed-at/freshness/failover + controlled provider evidence + real adapter. `live_pricing_authorized=false`. | Archive official provider contract and controlled provider evidence; Live Pricing stays closed until then |
| GT-005 | Settlement / reconciliation / hold / freeze / credit semantics | Settlement, Credit trading | Explicit business rules + Kimia behavior evidence. **Scaffold:** `SETTLEMENT_CONTRACT.json` + Owner template + hard-stop `SettlementWireGuard`; still NOT_GROUNDED. | Fill Owner template + controlled evidence before any wire |

## P1 — Required before production readiness of the feature

| ID | Blocker | Blocks | Required Evidence |
|----|---------|--------|-------------------|
| GT-006 | BehPardakht Mellat current merchant contract + sandbox process | Online payments | Official merchant docs + credentials process. **Scaffold:** `PAYMENT_CONTRACT.json` + Owner template; capture remains blocked. |
| GT-007 | Goftino official widget/API/privacy contract | Support chat integration | Current official docs |
| GT-008 | SMS.ir tenant-specific credentials, templates, live delivery proof | Production OTP | Tenant panel config + controlled live test results |
| GT-009 | Jibit sandbox/live credentials + current version + rate/error behavior | Live onboarding | Credentials + controlled test results. **Scaffold:** `SMS_JIBIT_CONTRACT.json`; Fake path unchanged. |

## Resolution Rule

1. Obtain official/current artifact/evidence.
2. Record its version/hash/evidence run under `docs/providers/official/`.
3. Update Source Register and Capability Ledger/current state as applicable.
4. Only remove a blocker for the exact scope proven; unresolved semantics stay blocked.

---

**Current project policy:**  
Stage 0 and Stage 1 (Foundation) can proceed without resolving P0.  
Pricing, Order/Kimia Write, Settlement and Payments cannot advance beyond their grounded scope until corresponding blockers are cleared.  
For Create Customer, the HTTP contract is grounded but **Live Create remains forbidden without a new explicit Owner authorization**; registration/order/settlement wiring remains off.  
For Pricing, the Owner-ratified business policy may be used as release evidence for TTL/freeze and coefficient/rounding policy, but **no external provider call or live quote issuance is authorized until the provider-specific remainder of GT-004 is grounded**.
