# Talamala — Source Register
**Stage:** 0  
**Status:** Active  
**Last updated:** 2026-08-19  

This register classifies every known business and technical rule according to the strength of its evidence.

## Classification Legend
- **CONFIRMED** — Explicitly stated in normative specs or verified historical evidence with clear owner acceptance.
- **PARTIAL** — Evidence exists but incomplete, needs validation against current official contracts or runtime side effects.
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
| Kimia uses Rial; customer/platform display uses Toman | CONFIRMED | Owner confirmation 2026-08-19 + prior Domain Workshop/Project Memory |
| Rial ↔ Toman conversion only in backend (Adapter) | CONFIRMED | Domain Workshop / Project Memory |
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
| Live `/api/voucher/exchangegold` uses `ExchangeRequest.Action`: `32=خرید`, `64=فروش` | CONFIRMED (context-scoped) | Iran-side live Swagger v1, SHA-256 `be0fb0c...77dfbb5cea` |
| Live `/api/voucher/tradecash` uses `TradeCashRequest.Action`: `2=دریافت`, `4=پرداخت` | CONFIRMED (context-scoped) | Same live Swagger evidence |
| A numeric Kimia Action code may be treated as a global domain enum across endpoints | **NOT CONFIRMED / FORBIDDEN ASSUMPTION** | Live schemas show endpoint-specific contexts; map endpoint/context + Action + ActionName |
| `exchangegold` request structure: required `AccountId`, `Action`, `GoldPrice`, `Value`; optional `GoldUnit`, duplicate-prevention `RequestId`, and other optional fields | CONFIRMED (request schema) | Live `ExchangeRequest`; evidence runs `32194446109`, `32194486355` |
| `tradecash` request structure: required `AccountId`, `Action`, `Value`; optional duplicate-prevention `RequestId` and other optional fields | CONFIRMED (request schema) | Live `TradeCashRequest`; same evidence |
| `RequestId` is duplicate-prevention identifier; UUID v4 recommended | CONFIRMED (schema description) | Official/live Swagger |
| Kimia `GoldUnit`: `0=مثقال`, `1=گرم`, `2=اونس`, `3=کیلوگرم` | CONFIRMED (Swagger contract) | Official Kimia Swagger artifact; live `ExchangeRequest` confirms `GoldUnit` field |
| Batch V1 paper-gold verification uses `GoldUnit=1` so `GoldPrice` is interpreted as Rial/gram and `Value` as gold quantity in grams | CONFIRMED FOR BATCH V1 | Swagger GoldUnit contract + Owner money-unit rule + account `350` transaction evidence |
| Account `350` real paper-gold history demonstrates gram relation (`181000000 × 0.2 = 36200000`; `180700000 × 10 = 1807000000`) | CONFIRMED (Read-only evidence) | Chabokan account-350 evidence run `32193878935`; raw snapshot SHA-256 `4a5354dd...387f265` |
| Live success for `exchangegold`/`tradecash` writes (HTTP 200 + numeric ids 77193–77196) on account 350 | CONFIRMED | KIMIA_WRITE_VERIFICATION_EVIDENCE_2026-08-19 · run 32197791006 |
| Full balance/tx side-effect model and error catalog for writes | PARTIAL | Immediate tx list may not show TX_NEW; balance readback_ok; deeper recon still open |
| Exact write contracts for coin/currency/physical/transfer/adjustment/settlement families | UNKNOWN | GT-003 / GT-005 |
| Create Customer exact contract + duplicate semantics | UNKNOWN | GT-002 |
| Current official Kimia Swagger is reachable live from Iran runner: version `v1`, SHA-256 `be0fb0c6897015e238ef9dd58115b8502cf6f83feb868c91cff19377dfbb5cea` | PARTIAL — live verified, raw official artifact not yet archived in repo | Chabokan live preflight + contract evidence |

## 7. External Providers

| Provider | Purpose | Status | Notes |
|----------|---------|--------|-------|
| Kimia | Financial truth | PARTIAL (Read + bounded live Write for exchangegold/tradecash) | Batch V1 completed on account 350; remaining families/error catalog/deeper reconciliation still open |
| Jibit | Identity matching | PARTIAL | Official v1.5.2 PDF referenced; live credentials & current version needed |
| SMS.ir | OTP | PARTIAL | Contract extract exists; tenant credentials & live proof needed |
| BehPardakht Mellat | Payment | UNKNOWN | BLOCKED |
| Goftino | Support chat integration | UNKNOWN | BLOCKED |
| Price Provider | Market prices | UNKNOWN | BLOCKED |

## 8. Frontend / UX

| Rule | Status | Source |
|------|--------|--------|
| Persian, RTL, mobile-first | CONFIRMED | UI/UX Spec, Frontend IA |
| No financial calculation in browser | CONFIRMED | Same |
| Loading / empty / error / forbidden / offline / retry states required | CONFIRMED | UI/UX Spec |
| One real high-fidelity page must be approved before broad UI production | CONFIRMED | UI/UX Spec |
| PWA-safe on iOS/Android/Windows/macOS | CONFIRMED | UI/UX Spec |

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
Batch V1 proves live success responses for paper-gold buy/sell and cash receive/pay. Keep CAP-010 PARTIAL until error behavior and deeper authoritative balance/transaction reconciliation are proven; remaining Write families stay blocked.  
