# Kimia Owner-Authorized Batch V1 — bounded Write Verification

**Status:** DRAFT for Owner signature — **NO Kimia Write authorized yet**  
**Reviewed product runner:** `0088d74fd852d120087aec3612311d1cd1613ab6`  
**Deployed read-only verification source:** `247f4fc2b29c43e703a32ca881960723815b3ebe`  
**Target service:** `talamala-kimia-runner` (Chabokan / Iran)  
**Product Write capability:** remains **BLOCKED / default-deny**

> This document defines a bounded verification batch. Committing this document, a successful preflight, live Swagger evidence, an allowlist, or the presence of credentials does **not** authorize a mutation by itself.

---

## 0) Current verified Read-only checkpoint

The reviewed Verification Runner was synchronized to the dedicated Chabokan source and deployed in forced READ-ONLY mode. The final live contract extraction was observed from the Iran-side service after rollout.

| Item | Result |
|------|--------|
| Service | `talamala-kimia-runner` |
| Dedicated source ref | `ops/chabokan-kimia-runner` |
| Deployed verification source | `247f4fc2b29c43e703a32ca881960723815b3ebe` |
| Successful base Verification Runner deploy run | `32191365007` |
| Final post-rollout log/evidence run | `32192026653` |
| New boot observed | `2026-08-19 01:46:03` service-local log time |
| `PREFLIGHT_OK` | **PASS** |
| `write_gate` | `0` |
| Write attempted | **false** |
| Live Swagger version | `v1` |
| Live Swagger SHA-256 | `be0fb0c6897015e238ef9dd58115b8502cf6f83feb868c91cff19377dfbb5cea` |
| Live POST catalog | **13 paths** |
| Request-contract extractor | `PREFLIGHT_CONTRACT_EXTRACT_OK=true` |

The first contract-deploy workflow run (`32191758346`) reported failure only because its verification polling window ended before the new container had rolled. Its exact-source checkout, read-only source checks, service lock, and Chabokan deploy steps all passed. Run `32192026653` later proved the new boot and completed contract extraction. No mutation was attempted in either run.

### Runtime safety lock

The deployed boot forces:

```text
KIMIA_WRITE_VERIFY_ENABLE=0
```

and removes Owner token, attempt-budget, allowlist, and mutate-path/body environment variables before running preflight/catalog extraction.

Therefore the current deployed service is suitable for Read-only evidence gathering only.

---

## 1) Preconditions — all must be true before any POST

| # | Gate | Required state |
|---|------|----------------|
| G1 | Owner batch signature | completed in §8 |
| G2 | Dedicated Chabokan runner source | reviewed Verification Runner present |
| G3 | Deployed runtime identity | exact approved source SHA recorded |
| G4 | Fresh preflight | `PREFLIGHT_OK` on the same runtime immediately before mutation |
| G5 | Live contract | path + request schema from the same fresh live Swagger |
| G6 | Live Swagger identity | approved SHA-256; material delta requires Owner review |
| G7 | `KIMIA_WRITE_VERIFY_ENABLE` | `1` only for the separately authorized mutation window |
| G8 | Owner token pair | both env vars present, equal, **random per-batch**, no predictable/default value |
| G9 | Account allowlist | only `350` for B1–B4 |
| G10 | Attempt budget | exactly `buy=1,sell=1,receive=1,pay=1,create=0` |
| G11 | Exact financial test inputs | Owner-approved values recorded in §4 |
| G12 | Path/body | exact relative POST path + live-schema fields only |
| G13 | Request identity | fresh unique UUID v4 `RequestId` per attempt |
| G14 | No unresolved prior reservation | no pending/unknown mutation attempt |
| G15 | No scope expansion | coin/currency/physical/settlement/adjustment/transfer/void excluded |

Historical action values are not used as authority. The values below are now confirmed by **live Swagger for these specific request contexts only**; they must not be turned into a global Action-only mapper.

---

## 2) Batch scope — exact

| Step | Operation | Account | Mutation attempts | Live endpoint | Live Action |
|------|-----------|---------|-------------------|---------------|-------------|
| B0 | preflight + live catalog | — | 0 | — | — |
| B1 | **buy** | `350` | **1 max** | `/api/voucher/exchangegold` | `32` |
| B2 | **sell** | `350` | **1 max** | `/api/voucher/exchangegold` | `64` |
| B3 | **receive** | `350` | **1 max** | `/api/voucher/tradecash` | `2` |
| B4 | **pay** | `350` | **1 max** | `/api/voucher/tradecash` | `4` |
| B5 | STOP | — | — | — | — |

**Create account is NOT included in Batch V1.**  
Coin, currency, physical/barcode, settlement, adjustment, transfer, void/reverse and other Write families are also outside this batch.

### Batch halt rules

Any of the following halts the remaining batch:

- timeout / transport ambiguity;
- HTTP error from a mutation;
- mutation success with failed readback;
- unexpected response shape where side effect cannot be proven;
- unresolved reserved attempt;
- live Swagger/path/schema material delta;
- deployed runner SHA mismatch;
- Owner revocation.

No automatic retry. A failed/unknown mutation attempt still consumes its slot.

---

## 3) Allowlist

| account_id | Role |
|------------|------|
| **350** | sole financial mutation target for B1–B4 |

No other account may receive a mutation in this batch.

---

## 4) Live request contracts + Owner-approved exact inputs

### 4.1 Paper-gold exchange — B1/B2

**POST:** `/api/voucher/exchangegold`  
**Live request schema:** `#/components/schemas/ExchangeRequest`

Live-required fields:

| Field | Type | Live description / rule |
|-------|------|-------------------------|
| `AccountId` | integer/int32 | شناسه حساب — must be `350` |
| `Action` | integer/int32 | `32=خرید`, `64=فروش` |
| `GoldPrice` | number/decimal | قیمت طلا |
| `Value` | number/decimal | `چه مقدار پولی شود؟` |

Live-optional fields observed:

- `RequestId` string — Swagger states it is used to prevent duplicate document registration and recommends UUID v4.
- `AddToExistingDateVoucher` boolean
- `Comment` nullable string
- `CurrencyId` nullable int32
- `Date` nullable date-time
- `GoldUnit` nullable int32

**Batch V1 policy:** send the live-required fields plus a fresh unique UUID v4 `RequestId`. Do not send the other optional fields unless separately justified and approved. In particular, do not invent `GoldUnit`, `CurrencyId`, `Date`, or `AddToExistingDateVoucher` semantics.

Owner must approve the exact decimal inputs because the live schema does not fully specify the business unit for `GoldPrice` or `Value`:

| Operation | AccountId | Action | GoldPrice | Value | RequestId |
|-----------|-----------|--------|-----------|-------|-----------|
| B1 buy | `350` | `32` | `________________` | `________________` | fresh UUID v4 at execution |
| B2 sell | `350` | `64` | `________________` | `________________` | fresh UUID v4 at execution |

Owner-confirmed interpretation/unit for `GoldPrice`: `________________________________`  
Owner-confirmed interpretation/unit for exchange `Value`: `________________________________`

### 4.2 Cash receive/pay — B3/B4

**POST:** `/api/voucher/tradecash`  
**Live request schema:** `#/components/schemas/TradeCashRequest`

Live-required fields:

| Field | Type | Live description / rule |
|-------|------|-------------------------|
| `AccountId` | integer/int32 | شناسه حساب — must be `350` |
| `Action` | integer/int32 | `2=دریافت`, `4=پرداخت` |
| `Value` | number/decimal | مقدار |

Live-optional fields observed:

- `RequestId` string — duplicate-prevention identifier; UUID v4 recommended by Swagger.
- `AddToExistingDateVoucher` boolean
- `Comment` nullable string
- `Date` nullable date-time

**Batch V1 policy:** send the live-required fields plus a fresh unique UUID v4 `RequestId`. Do not send other optional fields in this verification batch without separate approval.

| Operation | AccountId | Action | Value | RequestId |
|-----------|-----------|--------|-------|-----------|
| B3 receive | `350` | `2` | `________________` | fresh UUID v4 at execution |
| B4 pay | `350` | `4` | `________________` | fresh UUID v4 at execution |

Owner-confirmed interpretation/unit for cash `Value`: `________________________________`

### 4.3 Evidence identity

All four contracts above were extracted from live Swagger:

```text
version=v1
sha256=be0fb0c6897015e238ef9dd58115b8502cf6f83feb868c91cff19377dfbb5cea
```

If the fresh preflight immediately before B1 produces a different Swagger SHA or materially different schema, **STOP** and re-review before any mutation.

---

## 5) Runner env contract — secrets never committed

Read:

```bash
KIMIA_BASE_URL=...
KIMIA_USERNAME=...
KIMIA_PASSWORD=...
```

Write verification window, only after §8 is signed and all gates pass:

```bash
KIMIA_WRITE_VERIFY_ENABLE=1
KIMIA_WRITE_ACCOUNT_ALLOWLIST=350
KIMIA_WRITE_ATTEMPT_BUDGET='buy=1,sell=1,receive=1,pay=1,create=0'
```

Owner authorization uses a **random per-batch secret**:

```bash
KIMIA_WRITE_OWNER_TOKEN=<random secret stored only in runner secret env>
KIMIA_WRITE_OWNER_AUTH=<same random secret stored only in runner secret env>
```

Hard rules:

- No default token.
- Never use a predictable literal as the secret.
- Never paste the secret into this document, GitHub Issue, Git commit, logs, or chat evidence.
- Record only a non-secret fingerprint if needed.

---

## 6) Per-operation execution card

B1/B2:

```text
KIMIA_MUTATE_PATH=/api/voucher/exchangegold
KIMIA_MUTATE_ACCOUNT_ID=350
KIMIA_MUTATE_BODY_FILE=<runner-local JSON built from §4.1 approved values>
```

B3/B4:

```text
KIMIA_MUTATE_PATH=/api/voucher/tradecash
KIMIA_MUTATE_ACCOUNT_ID=350
KIMIA_MUTATE_BODY_FILE=<runner-local JSON built from §4.2 approved values>
```

The reviewed CLI performs a fresh preflight in the same process before each `mutate` invocation.

Required evidence per attempt:

1. before balance + transaction snapshot;
2. request metadata/path/payload hash;
3. raw HTTP response in gitignored evidence storage;
4. after balance + transaction readback;
5. final outcome:
   - `success`
   - `http_error`
   - `outcome_unknown`
   - `success_readback_failed`

Raw evidence remains under `var/kimia-verify/` and is not committed.

---

## 7) Chabokan control

Preferred routine control path:

GitHub Issue #1 — `Chabokan Control Console`

Read-only commands:

```text
/chabokan status
/chabokan logs
/chabokan preflight TALAMALA
```

Current verification:

- Issue-comment permission is working.
- Verification Runner is deployed in forced Read-only mode.
- Live catalog and request contracts were extracted successfully from the Iran-side service.
- Routine Issue control still does **not** expose deploy or mutate.

A future Write-enabled deployment/configuration is a separate action and requires the complete Owner signature below. The current service boot itself forces Write disabled.

---

## 8) OWNER SIGNATURE — required before B1

Without all required YES fields below, **no mutation**.

| Field | Owner fill |
|-------|------------|
| I authorize exactly Batch V1 B1–B4 | YES / NO |
| Account `350` confirmed as test target | YES / NO |
| Create account included | **NO** |
| B1 buy `GoldPrice` + `Value` and units in §4 approved | YES / NO |
| B2 sell `GoldPrice` + `Value` and units in §4 approved | YES / NO |
| B3 receive `Value` + unit in §4 approved | YES / NO |
| B4 pay `Value` + unit in §4 approved | YES / NO |
| Live Swagger SHA-256 approved | `be0fb0c6897015e238ef9dd58115b8502cf6f83feb868c91cff19377dfbb5cea` / NO |
| Deployed Verification Runner source approved | `247f4fc2b29c43e703a32ca881960723815b3ebe` / NO |
| Attempt budget approved: `buy=1,sell=1,receive=1,pay=1,create=0` | YES / NO |
| Random Owner-token provisioned in runner secrets | YES / NO |
| Date (UTC) | `________________________________` |
| Name / role | `________________________________` |

**Owner instruction after signature:** execute only the signed steps, one at a time, never broaden scope, and stop on the first halt condition.

---

## 9) Explicitly out of scope

- Create account
- Coin write
- Currency write
- Physical/barcode write
- Settlement/order completion
- Adjustment
- Transfer
- Edit/delete/void/reverse
- Product capability promotion

This batch is Ground Truth verification only.
