# Talamala — Source Register
**Stage:** 0  
**Status:** Active  
**Last updated:** 2026-08-12  

This register classifies every known business and technical rule according to the strength of its evidence.

## Classification Legend
- **CONFIRMED** — Explicitly stated in normative specs or verified historical evidence with clear owner acceptance.
- **PARTIAL** — Evidence exists but incomplete, needs validation against current official contracts.
- **UNKNOWN** — No usable ground truth. Capability must remain `BLOCKED BY GROUND TRUTH`.
- **HISTORICAL ONLY** — Useful for learning, not normative for greenfield Talamala.

---

## 1. Financial Truth Boundaries

| Rule | Status | Source |
|------|--------|--------|
| Kimia is the final source of truth for Money, Gold, Coin, Currency | CONFIRMED | 01_ARCHITECTURE_BLUEPRINT, 02_DOMAIN_RULES, 00_PROJECT_MEMORY, Master Prompt |
| Talamala is the final source of truth for Physical Custody / Amanat | CONFIRMED | Same |
| No local competing balance table for Money/Gold/Coin/Currency | CONFIRMED | 11_DATA_MODEL_BLUEPRINT, Master Prompt |
| Local cache/snapshot only if derived, timestamped, rebuildable, reconcilable; Kimia wins conflicts | CONFIRMED | Master Prompt §1 |
| Balances may be positive, zero or negative | CONFIRMED | 02_DOMAIN_RULES, Project Memory |

## 2. Units & Precision

| Rule | Status | Source |
|------|--------|--------|
| Never use binary float for money, weights, prices, quantities, commissions | CONFIRMED | Master Prompt §3, Domain Rules |
| Use Decimal / fixed precision | CONFIRMED | Same |
| Kimia uses Rial; customer display uses Toman | CONFIRMED | Domain Workshop, Project Memory |
| Rial ↔ Toman conversion only in backend (Adapter) | CONFIRMED | Same |
| Frontend never performs financial conversion or calculation | CONFIRMED | UI/UX Spec, Frontend IA |
| 18k equivalence: `(weight × fineness) / 750` | CONFIRMED | Domain Rules, Workshop |

## 3. Multi-tenancy & White-label

| Rule | Status | Source |
|------|--------|--------|
| Tenant resolved server-side from verified Host/domain | CONFIRMED | Architecture Blueprint, Security Checklist, Master Prompt |
| Client-supplied tenant_id is never authoritative | CONFIRMED | Same |
| Unknown / inactive / unverified host fails closed | CONFIRMED | Security Checklist |
| All tenant-owned data must be tenant-scoped | CONFIRMED | Same |
| Branding (logo, palette, legal copy, domains) is runtime tenant config | CONFIRMED | Master Prompt §4 |

## 4. Identity & Access

| Rule | Status | Source |
|------|--------|--------|
| Customer auth = mobile + OTP only | CONFIRMED | Domain Rules, Master Prompt |
| Staff auth = username + password + mandatory first-login rotation | CONFIRMED | Same |
| Customer Access Status is separate from Customer Level and from onboarding | CONFIRMED | Domain Rules |
| Access states include at least: active, limited, suspended, blocked | CONFIRMED | Master Prompt |
| Referral must store stable `referrer_user_id`, not raw mobile | CONFIRMED | Master Prompt §5 |

## 5. Quote → Order → Settlement

| Rule | Status | Source |
|------|--------|--------|
| Quote is immutable; edit/reprice creates new Quote | CONFIRMED | Domain Rules, Backend Algorithm |
| Client must never submit authoritative price, commission, balance or final total | CONFIRMED | Master Prompt §8 |
| After successful Kimia write → mandatory readback + reconciliation | CONFIRMED | Domain Rules, Backend Algorithm |
| Order reaches `completed` only after authoritative Kimia success | CONFIRMED | Domain Workshop |
| Exact freeze duration, x/y/z coefficients, rounding order, commission/tax, hold/settlement semantics | UNKNOWN | Ground Truth Blockers |

## 6. Kimia Integration

| Rule | Status | Source |
|------|--------|--------|
| Controllers must never call Kimia HTTP client directly | CONFIRMED | Architecture Blueprint, Domain Workshop |
| Anti-Corruption Layer required (ReadClient / WriteClient separate) | CONFIRMED | Master Prompt §9 |
| customer_intent and kimia_action are separate fields | CONFIRMED | Domain Workshop |
| Action codes 1/2/3/4/7/8 are known with context | PARTIAL | Project Memory + Domain Workshop (need current Swagger confirmation) |
| Exact write request/response bodies for financial operations | UNKNOWN | Ground Truth Blockers |
| Create Customer exact contract + duplicate semantics | UNKNOWN | Ground Truth Blockers |
| Official current Kimia Swagger/OpenAPI file | UNKNOWN | Ground Truth Blockers |

## 7. External Providers

| Provider | Purpose | Status | Notes |
|----------|---------|--------|-------|
| Kimia | Financial truth | PARTIAL (Read) / UNKNOWN (Write) | Need raw current Swagger |
| Jibit | Identity matching | PARTIAL | Official v1.5.2 PDF referenced; live credentials & current version needed |
| SMS.ir | OTP | PARTIAL | Contract extract exists; tenant credentials & live proof needed |
| BehPardakht Mellat | Payment | UNKNOWN | BLOCKED |
| Goftino | Support chat | UNKNOWN | BLOCKED |
| Price Provider | Market prices | UNKNOWN | BLOCKED |

## 8. Frontend / UX

| Rule | Status | Source |
|------|--------|--------|
| Persian, RTL, mobile-first | CONFIRMED | UI/UX Spec, Frontend IA |
| No financial calculation in browser | CONFIRMED | Same |
| Loading / empty / error / forbidden / offline / retry states required | CONFIRMED | Same |
| One real high-fidelity page must be approved before broad UI production | CONFIRMED | Same |
| PWA-safe on iOS/Android/Windows/macOS | CONFIRMED | Same |

## 9. Security & Operations

| Rule | Status | Source |
|------|--------|--------|
| Secrets encrypted at rest, never logged, never returned to client | CONFIRMED | Security Checklist, Master Prompt |
| Every sensitive mutation: permission + tenant + idempotency + audit + correlation | CONFIRMED | Same |
| Audit must capture actor + tenant + target + reason + correlation | CONFIRMED | Same |
| Cross-tenant adversarial tests required | CONFIRMED | Security Checklist |
| Backup/restore drill before production | CONFIRMED | Same |

---

**Next action for this register:**  
Any new evidence (official Swagger, live API response, owner decision) must update the corresponding row and the Capability Ledger.  
