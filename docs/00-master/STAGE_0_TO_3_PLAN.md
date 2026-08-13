# Talamala — Stage 0 to 3 Plan & Exit Criteria
**Last updated:** 2026-08-12  

## Stage 0 — Truth Packet & Repository Governance
**Goal:** Every known rule has a source and classification. No product code.

### Work
- [x] Read entire truth packet
- [x] Create SOURCE_REGISTER
- [x] Create GROUND_TRUTH_BLOCKERS
- [x] Create ADR_INDEX
- [x] Create CAPABILITY_LEDGER
- [ ] Create branching / PR / exact-SHA CI policy (ADR-015)
- [ ] Archive any newly supplied official provider artifacts
- [ ] Owner confirmation of any remaining business decisions

### Exit Criteria
- All CONFIRMED / PARTIAL / UNKNOWN rules are registered
- Capability Ledger exists and is consistent with blockers
- ADR Index is published
- No application code committed
- Owner has been shown the registers and has no blocking objections

---

## Stage 1 — Architecture Skeleton
**Goal:** Runnable empty skeleton with tenant isolation, secrets boundary, audit/idempotency foundations and green CI.

### Work
- Repository skeleton (backend / frontend / openapi / infra / docs)
- Tenant resolution middleware (Host → tenant, fail-closed)
- Secrets & configuration boundary
- Base Audit + Idempotency registry tables and services
- Health / readiness endpoints
- OpenAPI skeleton files (auth / customer / backoffice)
- Exact-SHA CI pipeline (lint, unit, migration, OpenAPI validation, secret scan)
- Cross-tenant adversarial test skeleton

### Exit Criteria
- CI green on a known SHA
- Tenant isolation proven by tests
- No financial or provider logic yet
- ADR-001, 002, 003, 006, 010, 012, 015 accepted

---

## Stage 2 — Identity Vertical
**Goal:** Authenticated customer and staff sessions with correct tenant isolation.

### Work
- Customer OTP flow (SMS.ir adapter behind interface)
- Staff login + mandatory first-login password rotation
- Session / token model with tenant binding
- Onboarding state machine (registration → verification → access)
- Jibit adapter according to official v1.5.2 evidence (live status clearly separated)
- Permission foundation for staff roles

### Exit Criteria
- End-to-end authenticated flows with provider fakes / contract tests
- Live provider status is explicit and separate from code completeness
- Automatic onboarding modes remain disabled until GT-002 is resolved
- ADR-004, 005, 013, 014 accepted (auto modes still blocked)

---

## Stage 3 — Kimia Read Vertical
**Goal:** Customer financial view is produced only from Kimia-derived authoritative data.

### Work
- Immutable Customer ↔ Kimia binding
- KimiaReadClient + mappers (Anti-Corruption Layer)
- Accounts / catalog / balance / transactions read paths
- Rial → Toman normalization (backend only)
- Rebuildable snapshots with source timestamp
- Customer Assets screen fed exclusively from the read model

### Exit Criteria
- No local competing balance
- Disagreement with Kimia is impossible by design (Kimia wins)
- Contract tests against recorded Kimia responses
- ADR-007 accepted
- Create-customer and all write paths still BLOCKED

---

## After Stage 3
Stages 4–12 follow the roadmap in `05_ROADMAP.md`.  
Pricing, Quote, Order write, Settlement and Payments stay blocked until the corresponding Ground Truth Blockers are cleared.
