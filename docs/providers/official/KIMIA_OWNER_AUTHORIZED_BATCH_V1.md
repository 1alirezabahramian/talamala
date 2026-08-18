# Kimia Owner-Authorized Batch V1 — bounded Write Verification

**Status:** **OWNER AUTHORIZED — EXECUTION BLOCKED BY CHABOKAN ROLLOUT — NO KIMIA MUTATION EVIDENCED**  
**Owner authorization:** `YES — Batch V1 با همین مقادیر مجاز است` — recorded 2026-08-19  
**Reviewed product runner:** `0088d74fd852d120087aec3612311d1cd1613ab6`  
**Latest read-only verification source:** `24a5b0e737178d859d8985dcff7a5570e475123e`  
**Target service:** `talamala-kimia-runner` (Chabokan / Iran)  
**Product Write capability:** **BLOCKED / default-deny**

> Owner authorization exists for exactly the bounded B1→B4 values recorded below. Two deployment-plumbing attempts did not produce the Write-enabled runtime markers and did not produce any observed Kimia financial side effect. No third Write-enabled deploy was attempted. Production Kimia Write remains blocked.

---

## 0) Verified Read-only checkpoint and post-attempt state

The Verification Runner is currently deployed to the dedicated Chabokan service in forced READ-ONLY mode.

| Item | Result |
|------|--------|
| Service | `talamala-kimia-runner` |
| Dedicated source ref | `ops/chabokan-kimia-runner` |
| Latest read-only verification source | `24a5b0e737178d859d8985dcff7a5570e475123e` |
| Swagger semantic deploy evidence | `32194446109` |
| Pre-execution Chabokan logs evidence | `32194486355` |
| Deployment-plumbing attempt 1 | `32195538845` — `timeout_waiting_for_batch_marker`, restore success |
| Deployment-plumbing attempt 2 | `32196021745` — `timeout_waiting_for_batch_marker`, restore success |
| Fresh post-attempt logs | `32196494967` |
| Fresh post-attempt preflight | `32196511607` |
| Fresh post-attempt boot | `2026-08-19 02:47:41` service-local log time |
| `PREFLIGHT_OK` | **PASS** |
| current `write_gate` | `0` |
| Write-enabled Batch marker observed | **NO** |
| Kimia mutation attempt evidenced | **0** |
| Live Swagger version | `v1` |
| Live Swagger SHA-256 | `be0fb0c6897015e238ef9dd58115b8502cf6f83feb868c91cff19377dfbb5cea` |
| Account `350` transaction rows after attempts | **14 — unchanged** |
| Account `350` transaction snapshot SHA-256 | `4a5354dd57db1c1477167641d2d6bcf5c0ebd7f3ed182e67d8cc3b879387f265` — unchanged |
| Latest runtime marker | `Write not attempted (read_only).` |

The current service boot forces `KIMIA_WRITE_VERIFY_ENABLE=0` and removes Owner-token, attempt-budget, allowlist, and mutation env values.

### Execution-blocker interpretation

Both deployment-plumbing attempts prepared the exact bounded payloads and called Chabokan deploy. Chabokan reported deploy acceptance, but during the complete polling windows the service continued exposing only the previous READ-ONLY runtime markers. Neither attempt ever exposed:

- `mode=verification-write-batch-v1`;
- `write_gate=1`;
- `BATCH_V1_STEP_START=...`;
- a mutation attempt reservation/state marker;
- a mutation HTTP response marker.

Attempt 1 also retained a sanitized evidence artifact:

```text
artifact_id=9345823293
artifact_sha256=ad90a3a9a952df04b304df1e269b304b245faa1b995328a395d69c2b736a8992
```

Fresh post-attempt Read-only evidence still returned the exact same 14-row Account 350 transaction snapshot and hash. Therefore the current classification is:

```text
Deployment-plumbing attempts: 2
Kimia mutation attempts evidenced: 0
Observed financial side effects: 0
Batch V1: OWNER AUTHORIZED BUT NOT EXECUTED
Current runtime: READ-ONLY
```

No third Write-enabled deployment attempt is permitted until the Chabokan rollout/source-build identity problem is fixed and exact runtime identity can be proven before opening the gate.

---

## 1) Preconditions — all must be true before any future POST

| # | Gate | Required state |
|---|------|----------------|
| G1 | Owner authorization | exact B1–B4 values are recorded below; **fresh re-authorization is required after rollout plumbing is fixed** |
| G2 | Runtime identity | exact intended runner source/image/build identity proven from the running container |
| G3 | Rollout proof | unique build/boot marker proves the newly deployed runtime actually rolled out while `write_gate=0` |
| G4 | Fresh preflight | `PREFLIGHT_OK` immediately before the mutation batch on the same proven runtime |
| G5 | Swagger identity | SHA-256 unchanged or any material delta re-reviewed |
| G6 | Write enable | `KIMIA_WRITE_VERIFY_ENABLE=1` only after G1–G5 pass |
| G7 | Owner token | random per-batch token pair; no default/predictable token |
| G8 | Allowlist | only account `350` |
| G9 | Attempt budget | `buy=1,sell=1,receive=1,pay=1,create=0` |
| G10 | Exact test inputs | only the numeric values in §4 |
| G11 | Request identity | fresh UUID v4 `RequestId` for every actual attempt |
| G12 | No unresolved attempt | no pending/unknown prior mutation |
| G13 | Scope | no create/coin/currency/physical/settlement/adjustment/transfer/void |

A timeout, transport ambiguity, HTTP mutation error, failed readback, unexpected side effect, schema delta, or runtime mismatch consumes that operation slot **only if a mutation attempt was actually reserved/sent**, and then halts the remaining batch. No automatic retry.

A Chabokan deployment timeout with no Write-runtime marker and no mutation reservation is classified separately as an **infrastructure rollout failure**, not a Kimia mutation attempt.

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

## 4) Swagger-grounded request semantics and Owner-approved exact inputs

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

**Batch V1 locks `GoldUnit=1` (گرم)** for B1/B2.

For Batch V1:

- `GoldPrice` = Kimia gold price in **ریال per gram**, because `GoldUnit=1`;
- `Value` on `/exchangegold` = the **gold quantity being exchanged/monetized in the selected GoldUnit**; with `GoldUnit=1`, `Value` is **grams**.

This interpretation is supported by the Swagger request model (`GoldPrice`, `GoldUnit`, `Value`) and real Account 350 evidence:

- `GoldPrice=181000000`, `Weight=0.2`, `GoldUnit=1`, `GoldUnitName=گرم`, `SumMoney=36200000` → `181000000 × 0.2 = 36200000`;
- `GoldPrice=180700000`, `Weight=10`, `GoldUnit=1`, `GoldUnitName=گرم`, `SumMoney=1807000000` in absolute value;
- the historical `GoldPrice=798004190` record explicitly has `GoldUnit=0`, `GoldUnitName=مثقال`, confirming why the unit cannot be implicit.

### 4.3 B1/B2 — ExchangeRequest

**POST:** `/api/voucher/exchangegold`  
**Schema:** `#/components/schemas/ExchangeRequest`

Swagger-required:

- `AccountId`
- `Action`
- `GoldPrice`
- `Value`

Batch-required for deterministic/safe verification:

- `RequestId` = fresh UUID v4 at actual execution
- `GoldUnit=1`
- `AddToExistingDateVoucher=false`
- `AccountId=350`

Batch omits `CurrencyId`, `Date`, and optional free-text fields unless separately needed.

| Operation | AccountId | Action | GoldUnit | GoldPrice (Rial/gram) | Value (gram) | Owner approval |
|-----------|-----------|--------|----------|------------------------|--------------|----------------|
| B1 buy | `350` | `32` | `1` | **`181000000`** | **`0.2`** | **YES — 2026-08-19** |
| B2 sell | `350` | `64` | `1` | **`181000000`** | **`0.2`** | **YES — 2026-08-19** |

### 4.4 B3/B4 — TradeCashRequest

**POST:** `/api/voucher/tradecash`  
**Schema:** `#/components/schemas/TradeCashRequest`

Swagger-required:

- `AccountId`
- `Action`
- `Value`

Batch-required:

- `RequestId` = fresh UUID v4 at actual execution
- `AddToExistingDateVoucher=false`
- `AccountId=350`

For Batch V1, `tradecash.Value` is a **Kimia monetary amount in Rial**.

| Operation | AccountId | Action | Value (Rial) | Owner approval |
|-----------|-----------|--------|--------------|----------------|
| B3 receive | `350` | `2` | **`36200000`** | **YES — 2026-08-19** |
| B4 pay | `350` | `4` | **`36200000`** | **YES — 2026-08-19** |

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
- body files remain runner-local and are never committed.

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

For every actual mutation attempt capture:

1. before balance + transaction snapshot;
2. request metadata/path + payload hash;
3. attempt reservation before network send;
4. HTTP result in gitignored evidence storage;
5. after balance + transaction readback;
6. final outcome: `success`, `http_error`, `outcome_unknown`, or `success_readback_failed`.

On `outcome_unknown`: **do not retry**. Read back, record evidence, and STOP.

---

## 7) Chabokan control and current blocker

Routine Issue #1 control remains Read-only/ops-safe:

```text
/chabokan status
/chabokan logs
/chabokan preflight TALAMALA
```

Routine Issue control exposes neither general deploy nor mutate.

Current blocker:

> `chabok deploy -s talamala-kimia-runner` can report a successful deploy/upload while subsequent service logs continue to expose the previous READ-ONLY runtime rather than a unique marker from the newly prepared one-shot source.

Before another Batch execution, deployment plumbing must prove exact running build/source identity while the gate is still closed. Preferred sequence:

```text
immutable/exact deployment
→ observe unique source/build/boot marker with write_gate=0
→ fresh PREFLIGHT_OK on that same runtime
→ verify approved Swagger SHA
→ only then open bounded one-shot gate
→ B1/readback → B2/readback → B3/readback → B4/readback
→ force gate closed
→ prove READ-ONLY runtime
```

No further Kimia Write attempt is authorized merely because Chabokan prints `Deployed`.

---

## 8) OWNER AUTHORIZATION RECORD

| Field | Recorded state |
|-------|----------------|
| I authorize exactly B1→B4, one attempt each | **YES — 2026-08-19** |
| Account `350` is the test target | **YES — confirmed 2026-08-19** |
| Account `350` has no Owner-imposed amount/trading limit | **YES — confirmed 2026-08-19** |
| Create account included | **NO** |
| B1 `GoldPrice=181000000`, `Value=0.2`, `GoldUnit=1` | **YES** |
| B2 `GoldPrice=181000000`, `Value=0.2`, `GoldUnit=1` | **YES** |
| B3 `Value=36200000 Rial` | **YES** |
| B4 `Value=36200000 Rial` | **YES** |
| Kimia money unit = Rial | **YES — Owner confirmed** |
| Platform display/input money unit = Toman | **YES — Owner confirmed** |
| Swagger SHA at authorization | `be0fb0c6897015e238ef9dd58115b8502cf6f83feb868c91cff19377dfbb5cea` |
| Read-only verification source reviewed | `24a5b0e737178d859d8985dcff7a5570e475123e` |
| Attempt budget | `buy=1,sell=1,receive=1,pay=1,create=0` — **YES** |
| Actual Kimia mutation attempts evidenced after authorization | **0** |
| Current execution status | **BLOCKED BY CHABOKAN ROLLOUT IDENTITY** |

Because two deployment-plumbing attempts occurred without reaching a proven Write-enabled runtime, **fresh explicit Owner re-authorization is required after the deployment plumbing is fixed**, immediately before any actual Kimia mutation.

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
