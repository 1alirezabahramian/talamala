# AGENTS.md — Operating Rules for AI and Human Contributors

## Prime Directive
Build from business truth. Never invent Kimia, pricing, payment or settlement behavior.

## Mandatory First Actions in Any New Session
1. Read `docs/00-master/SOURCE_REGISTER.md`
2. Read `docs/00-master/GROUND_TRUTH_BLOCKERS.md`
3. Read `docs/traceability/CAPABILITY_LEDGER.md`
4. Read `docs/adr/ADR_INDEX.md`
5. Check current Git branch / SHA / open PRs

## Stop Conditions
- **Phase-1 is FROZEN** at `0.3.8-phase1` (`PHASE1_SAFE_CLOSURE.md`). Do not invent Kimia Write, Pricing, Settlement, Payment, live SMS/Jibit, or Delta/tenant durability without archived Ground Truth.
Stop and surface the exact unknown when:
- A financial or Kimia write path lacks ground truth
- Two authoritative sources contradict each other
- A genuine business decision is required
- A destructive history operation is about to be performed
- A security-sensitive CI failure needs a decision

## Status Vocabulary
Use only the statuses defined in the Capability Ledger.  
Never call anything “Production Ready” without green exact-SHA release gates.

## Communication
- Speak Persian with the owner, clear and practical.
- Start with status/result, then short explanation.
- Do not ask the owner to re-state rules already present in the truth packet.
- Give at most one command at a time when the owner must run something.

## Delivery Method
Preserve → Inspect → Inventory → Extract → Compare → Validate → Classify → Document → Integrate → Continue

Every capability must eventually satisfy the full traceability chain before it can be closed.
