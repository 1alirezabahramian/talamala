# Talamala — ADR Index
**Stage:** 0  
**Status:** Proposed  
**Last updated:** 2026-08-12  

Architecture Decision Records to be written and accepted before the related code is implemented.

| ID | Title | Priority | Status | Blocks / Enables |
|----|-------|----------|--------|------------------|
| ADR-001 | Tenant Resolution (Host-based, fail-closed) | P0 | Proposed | Stage 1 |
| ADR-002 | Financial Precision & Units (Decimal, Rial↔Toman) | P0 | Proposed | All financial code |
| ADR-003 | Source of Truth Boundaries (Kimia vs Talamala) | P0 | Proposed | Data model, all financial & custody |
| ADR-004 | Customer Authentication Model (OTP-only) | P0 | Proposed | Stage 2 |
| ADR-005 | Staff Authentication & First-login Password Rotation | P0 | Proposed | Stage 2 |
| ADR-006 | Idempotency, Audit & Correlation Model | P0 | Proposed | Stage 1–2 |
| ADR-007 | Kimia Anti-Corruption Layer (Read vs Write Clients) | P0 | Proposed | Stage 3+ |
| ADR-008 | Quote Immutability & Lifecycle | P1 | Proposed | Stage 5 |
| ADR-009 | Physical Custody vs Financial Balance Separation | P0 | Proposed | Stage 8 |
| ADR-010 | OpenAPI Contract Strategy (auth / customer / backoffice) | P0 | Proposed | Stage 1 |
| ADR-011 | Capability Status Vocabulary & Traceability | P0 | Proposed | All stages |
| ADR-012 | Secrets, Credentials & Encryption at Rest | P0 | Proposed | Stage 1 |
| ADR-013 | Customer Access Status vs Customer Level | P1 | Proposed | Stage 2–4 |
| ADR-014 | Onboarding Policy Modes (Manual / Assisted / Full Auto) | P1 | Proposed | Stage 2 (auto modes blocked until GT-002) |
| ADR-015 | Branching, PR & Exact-SHA CI Policy | P0 | Proposed | Stage 0 exit |

## Writing Rule
- One decision per ADR.
- Must state Context, Decision, Consequences, Alternatives considered, and Status.
- No ADR may invent financial or provider truth.
- Accepted ADRs become part of the normative source set.
