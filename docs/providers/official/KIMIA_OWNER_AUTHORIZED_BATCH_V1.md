# Kimia Owner-Authorized Batch V1 — bounded Write Verification

**Status:** DRAFT for Owner signature — **NO Kimia Write authorized yet**  
**Reviewed product runner:** `0088d74fd852d120087aec3612311d1cd1613ab6`  
**Latest read-only verification source:** `24a5b0e737178d859d8985dcff7a5570e475123e`  
**Target service:** `talamala-kimia-runner` (Chabokan / Iran)  
**Product Write capability:** **BLOCKED / default-deny**

> This document defines one bounded verification batch. Committing it, successful Read-only evidence, an allowlist, credentials, or a green preflight does **not** authorize a mutation. B1–B4 require the explicit Owner signature in §8.

---

## 0) Verified Read-only checkpoint

The Verification Runner is deployed to the dedicated Chabokan service in forced READ-ONLY mode.

| Item | Result |
|------|--------|
| Service | `talamala-kimia-runner` |
| Dedicated source ref | `ops/chabokan-kimia-runner` |
| Latest read-only verification source | `24a5b0e737178d859d8985dcff7a5570e475123e` |
| Swagger semantic deploy evidence | `32194446109` |
| Chabokan logs evidence | `32194486355` |
| `PREFLIGHT_OK` | **PASS** |
| `write_gate` | `0` |
| Write attempted | **false** |
| Live Swagger version | `v1` |
| Live Swagger SHA-256 | `be0fb0c6897015e238ef9dd58115b8502cf6f83feb868c91cff19377dfbb5cea` |
| Live POST catalog | **13 paths** |
| Account `350` transaction evidence | **14 rows**, Read-only |

The service boot forces `KIMIA_WRITE_VERIFY_ENABLE=0` and removes Owner-token, attempt-budget, allowlist, and mutation env values. Any future Write-enabled verification window is a separate Owner-authorized operation.

---

## 1) Preconditions — all must be true before any POST

| # | Gate | Required state |
|---|------|----------------|
| G1 | Owner signature | §8 explicitly authorizes B1–B4 |
| G2 | Runtime identity | exact approved runner source recorded |
| G3 | Fresh preflight | `PREFLIGHT_OK` immediately before the mutation batch |
| G4 | Swagger identity | SHA-256 unchanged or any material delta re-reviewed |
| G5 | Write enable | `KIMIA_WRITE_VERIFY_ENABLE=1` only for authorized window |
| G6 | Owner token | random per-batch token pair; no default/predictable token |
| G7 | Allowlist | only account `350` |
| G8 | Attempt budget | `buy=1,sell=1,receive=1,pay=1,create=0` |
| G9 | Exact test inputs | all numeric values Owner-approved in §4/§8 |
| G10 | Request identity | fresh UUID v4 `RequestId` for every attempt |
| G11 | No unresolved attempt | no pending/unknown prior mutation |
| G12 | Scope | no create/coin/currency/physical/settlement/adjustment/transfer/void |

A timeout, transport ambiguity, HTTP mutation error, failed readback, unexpected side effect, schema delta, or runtime mismatch consumes that operation slot and **halts the remaining batch**. No automatic retry.

---

## 2) Exact Batch V1

| Step | Operation | Account | Attempts | Endpoint | Action |
|------|-----------|---------|----------|----------|--------|
| B0 | fresh preflight + contract check | — | 0 | Read-only | — |
| B1 | **buy gold** | `350` | **1 max** | `/api/voucher/exchangegold` | `32` |
| B2 | **sell gold** | `350` | **1 max** | `/api/voucher/exchangegold` | `64` |
| B3 | **receive cash** | `350` | **1 max** | `/api/voucher/tradecash` | `2` |
| B4 | **pay cash** | `350` | **1 max** | `/api/voucher/tradecash` | `4` |
| B5 | **STOP** | — | — | — | — |

These Action values are **endpoint/context-scoped**. They are not a global Kimia Action mapping.

---

## 3) Account 350 Ground Truth

Owner confirmation on **2026-08-19**:

- account `350` is the dedicated test account;
- it has no Owner-imposed amount/trading restriction for this verification work.

That does **not** remove the Verification Runner safety budget. Batch V1 remains one mutation attempt for each of B1–B4.

---

## 4) Swagger-grounded request semantics and exact inputs

### 4.1 Money-unit boundary

Owner-confirmed platform rule:

- **Talamala/platform money unit:** تومان
- **Kimia money unit:** ریال

Therefore all monetary values sent to Kimia in this verification are **ریال**. Production conversion belongs at the backend integration boundary; the browser must not perform authoritative financial conversion.

### 4.2 Kimia GoldUnit

Official Swagger documents Kimia gold units as:

| `GoldUnit` | Meaning |
|------------|---------|
| `0` | مثقال |
| `1` | گرم |
| `2` | اونس |
| `3` | کیلوگرم |

**Batch V1 locks `GoldUnit=1` (گرم)** for B1/B2. This removes ambiguity between gram-price and mithqal-price transactions.

For Batch V1:

- `GoldPrice` = Kimia gold price in **ریال per gram**, because `GoldUnit=1`;
- `Value` on `/exchangegold` = the **gold quantity being exchanged/monetized in the selected GoldUnit**; with `GoldUnit=1`, Batch V1 treats `Value` as **grams**.

This interpretation is supported by the Swagger request model (`GoldPrice`, `GoldUnit`, `Value`) and by real account `350` transaction evidence, including:

- `GoldPrice=181000000`, `Weight=0.2`, `SumMoney=36200000` → `181000000 × 0.2 = 36200000`;
- `GoldPrice=180700000`, `Weight=10`, `SumMoney=1807000000` in absolute value.

A historical account-350 row with `GoldPrice=798004190` and one gram producing about `184219914` Rial also demonstrates why `GoldUnit` cannot be left implicit: its ratio is consistent with a mithqal-based price converted to gram basis.

### 4.3 B1/B2 — ExchangeRequest

**POST:** `/api/voucher/exchangegold`  
**Schema:** `#/components/schemas/ExchangeRequest`

Swagger-required:

- `AccountId`
- `Action`
- `GoldPrice`
- `Value`

Batch-required for deterministic/safe verification:

- `RequestId` = fresh UUID v4
- `GoldUnit=1`
- `AddToExistingDateVoucher=false`
- `AccountId=350`

Batch omits `CurrencyId`, `Date`, and optional free-text fields unless separately needed.

| Operation | AccountId | Action | GoldUnit | GoldPrice (Rial/gram) | Value (gram) | RequestId |
|-----------|-----------|--------|----------|------------------------|--------------|-----------|
| B1 buy | `350` | `32` | `1` | `________________` | `________________` | fresh UUID v4 |
| B2 sell | `350` | `64` | `1` | `________________` | `________________` | fresh UUID v4 |

### 4.4 B3/B4 — TradeCashRequest

**POST:** `/api/voucher/tradecash`  
**Schema:** `#/components/schemas/TradeCashRequest`

Swagger-required:

- `AccountId`
- `Action`
- `Value`

Batch-required:

- `RequestId` = fresh UUID v4
- `AddToExistingDateVoucher=false`
- `AccountId=350`

For Batch V1, `tradecash.Value` is a **Kimia monetary amount in Rial**.

| Operation | AccountId | Action | Value (Rial) | RequestId |
|-----------|-----------|--------|--------------|-----------|
| B3 receive | `350` | `2` | `________________` | fresh UUID v4 |
| B4 pay | `350` | `4` | `________________` | fresh UUID v4 |

### 4.5 Swagger identity

Current live identity:

```text
version=v1
sha256=be0fb0c6897015e238ef9dd58115b8502cf6f83feb868c91cff19377dfbb5cea
```

If the fresh preflight before B1 yields a different hash or a material contract change, **STOP before any mutation**.

---

## 5) Verification env contract — secrets never committed

Read credentials remain existing runner secrets.

The separately authorized Write-verification window requires:

```bash
KIMIA_WRITE_VERIFY_ENABLE=1
KIMIA_WRITE_ACCOUNT_ALLOWLIST=350
KIMIA_WRITE_ATTEMPT_BUDGET='buy=1,sell=1,receive=1,pay=1,create=0'
KIMIA_WRITE_OWNER_TOKEN=<random per-batch secret>
KIMIA_WRITE_OWNER_AUTH=<same random per-batch secret>
```

Rules:

- no default token;
- no predictable token literal;
- never commit/paste/log the token;
- body files remain runner-local under `var/kimia-verify/` and are never committed.

---

## 6) Per-operation execution and evidence

B1/B2 path:

```text
KIMIA_MUTATE_PATH=/api/voucher/exchangegold
KIMIA_MUTATE_ACCOUNT_ID=350
```

B3/B4 path:

```text
KIMIA_MUTATE_PATH=/api/voucher/tradecash
KIMIA_MUTATE_ACCOUNT_ID=350
```

For every mutation attempt capture:

1. before balance + transaction snapshot;
2. request metadata/path + payload hash;
3. HTTP result in gitignored evidence storage;
4. after balance + transaction readback;
5. final outcome: `success`, `http_error`, `outcome_unknown`, or `success_readback_failed`.

On `outcome_unknown`: **do not retry**. Read back, record evidence, and STOP.

---

## 7) Chabokan control

Routine Issue #1 control remains Read-only/ops-safe:

```text
/chabokan status
/chabokan logs
/chabokan preflight TALAMALA
```

Routine Issue control exposes neither general deploy nor mutate. A future Write-enabled runner configuration is a separate bounded operation after §8 is signed.

---

## 8) OWNER SIGNATURE — required before B1

Without all required YES fields, **no mutation**.

| Field | Owner fill |
|-------|------------|
| I authorize exactly B1→B4, one attempt each | YES / NO |
| Account `350` is the test target | **YES — confirmed 2026-08-19** |
| Account `350` has no Owner-imposed amount/trading limit | **YES — confirmed 2026-08-19** |
| Create account included | **NO** |
| B1 values in §4.3 approved | YES / NO |
| B2 values in §4.3 approved | YES / NO |
| B3 value in §4.4 approved | YES / NO |
| B4 value in §4.4 approved | YES / NO |
| `GoldUnit=1` (gram) approved for B1/B2 | YES / NO |
| Kimia money unit = Rial approved | **YES — Owner confirmed** |
| Platform display/input money unit = Toman approved | **YES — Owner confirmed** |
| Swagger SHA approved | `be0fb0c6897015e238ef9dd58115b8502cf6f83feb868c91cff19377dfbb5cea` / NO |
| Read-only verification source reviewed | `24a5b0e737178d859d8985dcff7a5570e475123e` / NO |
| Attempt budget approved | `buy=1,sell=1,receive=1,pay=1,create=0` — YES / NO |
| Random Owner token provisioned only in runner secrets | YES / NO |
| Date (UTC) | `________________________________` |
| Name / role | `________________________________` |

After signature: execute only the signed steps, sequentially, and stop on the first halt condition.

---

## 9) Explicitly out of scope

- Create account
- Coin Write
- Currency Write
- Physical/barcode Write
- Settlement/order completion
- Adjustment
- Transfer
- Edit/delete/void/reverse
- Product capability promotion

Batch V1 is Ground Truth verification only; successful verification does not automatically enable production Kimia Write.
