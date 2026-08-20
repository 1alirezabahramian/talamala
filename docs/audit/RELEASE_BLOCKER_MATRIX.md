# Release Blocker Matrix — Cycle 5

**Source of truth:** exact Release Authority artifact for `0dec08b15445d6d43de31d0a01687f1026d53fdf`  
**Authority:** `RELEASE_SCOPE_REGISTRY.json` + machine `release_blockers` output.  
**Exact baseline:** `19` release-required blockers. No ranges, no approximate counts.  
**Rule:** do not move a row to GREEN without exact evidence. Live mutation remains forbidden without explicit Owner authorization.

## Classes

| Class | Meaning |
|-------|---------|
| **A — Offline / contractual** | DTO, adapter boundary, policy schema, fail-closed gates, smoke — no live external call |
| **B — Owner / official artifact** | Business policy, coefficients, TTL, merchant/provider contract |
| **C — Controlled evidence** | Narrow authorized live/sandbox test with archived evidence and restored deny posture |
| **D — Product integration** | Wire into registration/order/payment only after prerequisite A+B+C is grounded |

## Exact 19 release blockers

| ID | Title | GT | Class | Cycle status | Close condition |
|----|-------|----|-------|--------------|-----------------|
| FA-026 | Production SMS.ir | GT-008 | B→C | Open | Tenant credentials/templates + controlled OTP delivery proof |
| FA-039 | Live Jibit onboarding | GT-009 | B→C | Open | Current Jibit contract/credentials + controlled behavior evidence |
| FA-045 | Kimia Create Account live from registration | GT-002 | D | Open | After FA-080 evidence + explicit product wiring decision |
| FA-047 | Quote expiry / freeze duration authoritative source | GT-004 | B | Open | Owner-approved TTL/freeze policy |
| FA-048 | Price provider integration | GT-004 | B→C→D | Open | Official API contract + controlled provider evidence + adapter |
| FA-049 | Business coefficients x/y/z + rounding order | GT-004 | B | Open | Owner-approved coefficients, order and rounding |
| FA-060 | Settlement / hold / freeze semantics | GT-005 | B→C | Open | Owner rules + Kimia behavior evidence |
| FA-075 | Kimia Write ACL Batch V1 offline contract | GT-003 | A (depth) | Open | Offline Batch V1 is evidenced; registry remains blocked until required GT scope is reconciled |
| FA-076 | Kimia Write readback mandatory in ACL | GT-003 | A→C | Open | Offline readback contract + live evidence for remaining operation families |
| FA-078 | Kimia Write Order/Settlement wire | GT-003+005 | D | Open | Only after GT-005 and operation-specific write/readback evidence |
| FA-079 | Create Account HTTP contract grounded | GT-002 | A (depth) | Open | Swagger contract grounded; release row remains blocked while GT-002 scope is partial |
| FA-080 | Create Account duplicate/validation/readback live evidence | GT-002 | C | Open | Controlled Create evidence window + archived request/response/readback |
| FA-081 | Live Create executed in pilot | GT-002 | C | Open | Explicit Owner authorization + controlled evidence; never implied by FA-079 |
| FA-082 | Coin/Currency/Physical Kimia ops | GT-003 | C | Open | Controlled evidence per operation family |
| FA-084 | Official Kimia evidence archive present | GT-001/003 | A→C | Open | Archive current official raw evidence/hash for release-required Kimia scopes |
| FA-096 | Online payment gateway | GT-006 | B→C→D | Open | Merchant contract + sandbox evidence + integration |
| FA-097 | BehPardakht Mellat contract archived | GT-006 | B | Open | Current official merchant contract + credentials process |
| FA-098 | Payment capture in pilot scope | GT-006 | C→D | Open | Sandbox/live controlled capture evidence before product wire |
| FA-099 | SMS production provider | GT-008 | B→C | Open | Tenant production config + controlled delivery proof |

## Cycle 5 focus

1. **GT-004 depth (A/B scaffold):** machine contract remains `NOT_GROUNDED`; no coefficient, rounding, TTL, provider or freshness default is invented.
2. **GT-002 preparation (C):** controlled Create runbook defines the exact evidence window; no Live Create is executed.
3. **No score gaming:** this matrix is explanatory only. The Release Authority remains the machine source for blocker count and verdict.

## Explicit non-goals

- Live price fetch
- Live Create
- Settlement wire from Order
- Payment capture
- lowering `ACCEPTED_FOR_RELEASE` thresholds

## Cycle 6 progress

- **GT-005 / FA-060, FA-078:** offline Settlement contract + hard-stop wire guard added. Flags alone cannot enable wiring; complete Owner policy, evidence refs, semantics, Kimia side-effect model and zero unknowns are required. Release rows remain Open.
- **GT-006 / FA-096–098:** offline Payment contract added. Capture remains blocked unless official gateway contract, callback/signature/refund/reverse rules, Owner policy, evidence refs and zero unknowns are present. Release rows remain Open.
- **GT-008/009 / FA-026, FA-039, FA-099:** production integration stub only; Fake SMS/Jibit remain unchanged.
- **GT-004 / FA-047–049:** customer Quote-by-id exposes only an already-stored immutable snapshot. It does not fetch, calculate, refresh or authorize live pricing.
- **No score gaming:** machine Release Authority remains the only blocker/verdict source.

## Cycle 7 progress — proposal only

- **GT-004 / FA-047 + FA-049:** conservative business-policy proposal archived for Owner review; it is not Ground Truth yet.
- `PRICING_CONTRACT.json` remains `NOT_GROUNDED`, `live_pricing_authorized=false`, `proposal_status=AWAITING_OWNER_RATIFICATION`.
- `BlockedPriceProvider` is a hard-stop implementation and never emits a synthetic/live price.
- `QuoteIssuanceGuard` permits only explicit `dev-` / `fixture-` / `manual-` sources while the live contract is incomplete. Any other source must pass the full provider/freshness/asset/rounding/TTL/zero-unknown gate.
- Owner ratification in a later cycle may ground only the ratified policy subset. It does not close FA-048 or authorize Live Pricing.
- **Blocker count remains exactly 19** until machine Release Authority says otherwise.
