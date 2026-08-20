# AGENTS.md — Operating Rules for AI and Human Contributors

## Prime Directive
Build from business truth. Never invent Kimia, pricing, payment or settlement behavior.

## No Human Green (Closure)
Nobody — human or AI — may declare the project green, pilot-accepted, or release-closed unless `make final-audit` on **the same Git SHA** returns `ACCEPTED_FOR_PILOT` with current-run Evidence. Manual reports and chat claims are not authority. See `docs/audit/CLOSURE_POLICY.md`.

## Mandatory First Actions in Any New Session
1. Read `docs/00-master/SOURCE_REGISTER.md`
2. Read `docs/00-master/GROUND_TRUTH_BLOCKERS.md`
3. Read `docs/traceability/CAPABILITY_LEDGER.md`
4. Read `docs/adr/ADR_INDEX.md`
5. Check current Git branch / SHA / open PRs
6. Read `docs/00-master/CURRENT_STATE.md` (Phase-1 freeze + Kimia verification gate)
7. For live Iran Kimia **read/ops only**: use GitHub Issue **#1** (`/chabokan …`) — never Owner as Chabokan messenger; never call Kimia from non-Iran sandbox

## Stop Conditions
- **Phase-1 is FROZEN** at `0.3.8-phase1` (`PHASE1_SAFE_CLOSURE.md`). Do not invent Kimia Write, Pricing, Settlement, Payment, live SMS/Jibit, or Delta/tenant durability without archived Ground Truth.
- **Kimia Write is default-deny.** Preflight OK / account 350 readable does **not** authorize Write. Bounded Write only after a **new** explicit Owner authorization.
- Do not deploy arbitrary branches to `talamala-kimia-runner`; do not touch GoldPlatform from Talamala runner.
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

## Pilot ops shortcuts
`make pilot-status` · `make pilot-gate-matrix` · `make final-audit-summary` · `make decimal-invariant`
