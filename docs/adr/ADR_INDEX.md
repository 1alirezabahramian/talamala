# Talamala — ADR Index
**Stage:** 0–1  
**Status:** Living  
**Last updated:** 2026-08-17  

Architecture Decision Records. Status reflects skeleton implementation, not production readiness.

| ID | Title | Priority | Status | Notes |
|----|-------|----------|--------|-------|
| ADR-001 | Tenant Resolution (Host-based, fail-closed) | P0 | Accepted | Implemented (InMemory seed hosts) |
| ADR-002 | Financial Precision & Units (Decimal, Rial↔Toman) | P0 | Accepted | Decimal strings enforced in domain |
| ADR-003 | Source of Truth Boundaries (Kimia vs Talamala) | P0 | Accepted | Read path only; writes BLOCKED |
| ADR-004 | Customer Authentication Model (OTP-only) | P0 | Accepted | Fake SMS · rate limit |
| ADR-005 | Staff Authentication & First-login Password Rotation | P0 | Accepted | |
| ADR-006 | Idempotency, Audit & Correlation Model | P0 | Accepted | SQLite + X-Correlation-Id |
| ADR-007 | Kimia Anti-Corruption Layer (Read vs Write Clients) | P0 | Partial | Read client only |
| ADR-008 | Quote Immutability & Lifecycle | P1 | Accepted | |
| ADR-009 | Physical Custody vs Financial Balance Separation | P0 | Accepted | |
| ADR-010 | OpenAPI Contract Strategy | P0 | Accepted | Runtime parity CI gate |
| ADR-011 | Capability Status Vocabulary & Traceability | P0 | Accepted | Capability Ledger |
| ADR-012 | Secrets, Credentials & Encryption at Rest | P0 | Partial | Redaction + no secrets in repo |
| ADR-013 | Customer Access Status vs Customer Level | P1 | Partial | access_status on approve path |
| ADR-014 | Onboarding Policy Modes | P1 | Proposed | Auto modes BLOCKED until GT-002 |
| ADR-015 | Branching, PR & Exact-SHA CI Policy | P0 | Accepted | Fail-closed smoke gates |

## Writing Rule
- One decision per ADR.
- Must state Context, Decision, Consequences, Alternatives considered, and Status.
- No ADR may invent financial or provider truth.
- Accepted ADRs become part of the normative source set.
