# Capability Ledger (Talamala greenfield)

Statuses: IMPLEMENTED (skeleton/fake) | PARTIAL | BLOCKED | NOT_STARTED

| ID | Domain | Capability | Status | Notes |
|----|--------|------------|--------|-------|
| CAP-001 | Tenant | Host resolution fail-closed | IMPLEMENTED | ADR-001 |
| CAP-002 | Audit | Audit events | IMPLEMENTED | SQLite Persistence-2 |
| CAP-003 | Idempotency | Tenant-scoped keys | IMPLEMENTED | SQLite Persistence-2 |
| CAP-004 | Identity | Customer OTP | IMPLEMENTED | Fake SMS |
| CAP-005 | Identity | Staff auth + rotation | IMPLEMENTED | |
| CAP-006 | Identity | Registration + Jibit gate | IMPLEMENTED | Fake Jibit |
| CAP-007 | Identity | Admin approval queue | IMPLEMENTED | |
| CAP-008 | Kimia | Read client | IMPLEMENTED | Http + Fake |
| CAP-009 | Kimia | Assets Toman mapping | IMPLEMENTED | |
| CAP-010 | Kimia | Write / create customer | BLOCKED | Ground truth |
| CAP-011 | Quote | Immutable quote model | IMPLEMENTED | |
| CAP-012 | Quote | Price provider | BLOCKED | Ground truth |
| CAP-013 | Order | Accept from quote | IMPLEMENTED | |
| CAP-014 | Order | Settlement write | BLOCKED | Ground truth |
| CAP-015 | Custody | Lifecycle | IMPLEMENTED | Talamala truth |
| CAP-016 | API | OpenAPI auth/customer/backoffice | IMPLEMENTED | Runtime parity + CI gate 2026-08-15 |
| CAP-017 | Frontend | Customer screens | PARTIAL | Structure only |
| CAP-018 | Frontend | Backoffice screens | PARTIAL | Structure only |
| CAP-019 | Payment | Gateways | BLOCKED | Ground truth |
| CAP-020 | Release | Exact-SHA CI | IMPLEMENTED | Fail-closed + smoke gates 2026-08-15 |

Do not copy GoldPlatform completion percentages.


## Persistence-1 note (2026-08-15)
Customer / Custody / Quote / Order repositories: SQLite PDO implemented.
Sessions, audit, idempotency, tenant resolver: still InMemory (Persistence-2).
