# Talamala — Capability Ledger (Bootstrap)
**Stage:** 0  
**Status:** Active  
**Last updated:** 2026-08-12  

Status vocabulary (mandatory):
- `NOT IMPLEMENTED`
- `BLOCKED BY GROUND TRUTH`
- `IMPLEMENTED — NOT TESTED`
- `TESTED — NOT MERGED`
- `MERGED — CLOSURE PENDING`
- `REUSE AS-IS` / `REUSE AFTER FIX` / `REFACTOR` / `REBUILD`
- `HISTORICAL ONLY`
- `SUPERSEDED` / `DUPLICATE CANDIDATE`

| Capability | Domain | Current Status | Ground Truth | Target Stage | Notes |
|------------|--------|----------------|--------------|--------------|-------|
| Tenant resolution (Host → tenant) | Tenant & White-label | NOT IMPLEMENTED | CONFIRMED | 1 | Fail-closed required |
| Tenant branding & domains | Tenant & White-label | NOT IMPLEMENTED | CONFIRMED | 10 | Runtime config |
| Customer OTP login | Identity | NOT IMPLEMENTED | CONFIRMED | 2 | |
| Staff username/password + first-login rotation | Identity | NOT IMPLEMENTED | CONFIRMED | 2 | |
| Customer registration + Jibit match | Onboarding | NOT IMPLEMENTED | PARTIAL (Jibit) | 2 | Auto modes blocked by GT-002 |
| Customer Access Status management | Access | NOT IMPLEMENTED | CONFIRMED | 2 | Separate from Level |
| Customer Level / trading policy | Policy | NOT IMPLEMENTED | PARTIAL | 4+ | Numeric values unknown |
| Kimia customer binding (immutable) | Kimia Integration | NOT IMPLEMENTED | PARTIAL | 3 | Create blocked by GT-002 |
| Kimia Read — accounts / catalog | Kimia Integration | NOT IMPLEMENTED | PARTIAL | 3 | |
| Kimia Read — balance / transactions | Kimia Integration | NOT IMPLEMENTED | PARTIAL | 3 | CurrencyId semantics PARTIAL |
| Rial→Toman normalization | Financial Read | NOT IMPLEMENTED | CONFIRMED | 3 | Backend only |
| Price observation & formula | Pricing | BLOCKED BY GROUND TRUTH | UNKNOWN | 5 | GT-004 |
| Immutable Quote | Quote | NOT IMPLEMENTED | CONFIRMED rules / UNKNOWN numbers | 5 | Depends on GT-004 |
| Order lifecycle | Order | NOT IMPLEMENTED | CONFIRMED high-level | 6 | Write blocked by GT-003 |
| Kimia Write (financial) | Kimia Integration | BLOCKED BY GROUND TRUTH | UNKNOWN | 6 | GT-003 |
| Post-write readback + reconciliation | Settlement | BLOCKED BY GROUND TRUTH | UNKNOWN | 6–7 | GT-003 + GT-005 |
| Settlement / hold / credit | Settlement | BLOCKED BY GROUND TRUTH | UNKNOWN | 7 | GT-005 |
| Physical Custody ledger | Custody | NOT IMPLEMENTED | CONFIRMED separation | 8 | |
| Delivery workflow | Delivery | NOT IMPLEMENTED | PARTIAL | 8 | |
| Payment gateway (BehPardakht) | Payments | BLOCKED BY GROUND TRUTH | UNKNOWN | 9 | GT-006 |
| SMS.ir OTP adapter | Notifications | NOT IMPLEMENTED | PARTIAL | 2 | Live proof P1 |
| Goftino support | Support | BLOCKED BY GROUND TRUTH | UNKNOWN | 10 | GT-007 |
| Audit log | Audit | NOT IMPLEMENTED | CONFIRMED requirements | 1 | |
| Idempotency registry | Audit | NOT IMPLEMENTED | CONFIRMED requirements | 1 | |
| Outbox / reliable messaging | Operations | NOT IMPLEMENTED | CONFIRMED direction | 1–10 | |
| OpenAPI contracts (3 files) | API | NOT IMPLEMENTED | CONFIRMED policy | 1 | |
| Customer high-fidelity page | UX | NOT IMPLEMENTED | CONFIRMED gate | 4 | Must be approved first |
| Backoffice high-fidelity patterns | UX | NOT IMPLEMENTED | CONFIRMED gate | 4 | |
| Exact-SHA CI + security gates | CI/CD | NOT IMPLEMENTED | CONFIRMED | 1 | |
| Cross-tenant adversarial tests | Security | NOT IMPLEMENTED | CONFIRMED | 1 | |
| Backup / restore drill | Operations | NOT IMPLEMENTED | CONFIRMED | 11 | Before production |

---

**Traceability chain required for any capability to be called complete:**
Requirement → Source → Ground Truth → ADR → DB → Backend → API → OpenAPI → Frontend → Permission → Audit → Idempotency → Tests → exact-SHA CI → PR → Merge → Visual/Runtime Verification → Docs
