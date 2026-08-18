# Kimia Owner-Authorized Batch V1 — bounded Write Verification

**Status:** DRAFT for Owner signature — **NO Kimia Write authorized yet**  
**Main HEAD:** `0088d74fd852d120087aec3612311d1cd1613ab6`  
**Target service:** `talamala-kimia-runner` (Chabokan / Iran)  
**Product Write capability:** remains **BLOCKED / default-deny**

> This document defines a bounded verification batch. Committing this document, a successful preflight, an allowlist, or the presence of credentials does **not** authorize a mutation by itself.

---

## 0) Current verified Read-only checkpoint

Controlled Chabokan preflight executed through GitHub Issue #1:

| Item | Result |
|------|--------|
| GitHub Actions run | `32185718527` |
| Control workflow HEAD | `0088d74fd852d120087aec3612311d1cd1613ab6` |
| Service | `talamala-kimia-runner` |
| `PREFLIGHT_OK` | **PASS** |
| `write_gate` | `0` |
| Write attempted | **false** |
| Live Swagger version | `v1` |
| Live Swagger SHA-256 | `be0fb0c6897015e238ef9dd58115b8502cf6f83feb868c91cff19377dfbb5cea` |
| Base host | `94.101.184.26` |

This checkpoint proves current Iran-side reachability/read baseline only.

### Critical runtime distinction

The Chabokan service is built from the dedicated source ref:

`ops/chabokan-kimia-runner`

At this checkpoint that ref still contains `backend/bin/kimia_preflight_readonly.php` but **does not contain** the new:

`backend/bin/kimia_verify_runner.php`

Therefore:

> **B1–B4 MUST NOT RUN yet.**

A separate, reviewed ops synchronization/deploy of the Verification Runner to the dedicated Chabokan source is required before the mutation readiness gate can become true. Signing this batch does **not** authorize that deploy automatically.

---

## 1) Preconditions — all must be true before any POST

| # | Gate | Required state |
|---|------|----------------|
| G1 | Owner batch signature | completed in §8 |
| G2 | Dedicated Chabokan runner source | contains the reviewed Verification Runner code |
| G3 | Deployed runtime identity | exact deployed source SHA recorded and matches the reviewed runner |
| G4 | Fresh preflight | `PREFLIGHT_OK` on the same deployed runtime immediately before mutation |
| G5 | Live catalog | mutation path + schema extracted from the same fresh live Swagger |
| G6 | Live Swagger identity | version + SHA-256 recorded; material delta requires Owner review |
| G7 | `KIMIA_WRITE_VERIFY_ENABLE` | `1` on runner only for the authorized window |
| G8 | Owner token pair | both env vars present, equal, **random per-batch**, no predictable/default value |
| G9 | Account allowlist | only `350` for B1–B4 |
| G10 | Attempt budget | exactly `buy=1,sell=1,receive=1,pay=1,create=0` for this batch |
| G11 | Exact test inputs | Owner-approved values recorded after live schema extraction |
| G12 | Path/body | exact relative POST path + keys from live Swagger only |
| G13 | No unresolved prior reservation | batch state has no pending/unknown attempt |
| G14 | No scope expansion | coin/currency/physical/settlement/adjustment/transfer/void remain excluded |

Historical action values such as `32/64/2/4` are **reference-only**. They are not an authorization input.

---

## 2) Batch scope — exact

| Step | Operation | Account | Mutation attempts | Rule |
|------|-----------|---------|-------------------|------|
| B0 | preflight + live catalog | — | 0 | read-only |
| B1 | **buy** | `350` | **1 max** | then readback |
| B2 | **sell** | `350` | **1 max** | only if B1 completed cleanly |
| B3 | **receive** | `350` | **1 max** | only if previous step clean |
| B4 | **pay** | `350` | **1 max** | only if previous step clean |
| B5 | STOP | — | — | mandatory |

**Create account is NOT included in Batch V1.**  
If Create verification is needed later, it requires a separate Owner authorization and separate attempt budget.

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

## 4) Owner-approved exact inputs — fill only after live catalog

The operator/agent must not invent values or infer optional fields from historical material.

| Operation | Live path | Required live-schema fields | Exact Owner-approved test values | Unit |
|-----------|-----------|-----------------------------|----------------------------------|------|
| buy | `________` | `________` | `________` | `________` |
| sell | `________` | `________` | `________` | `________` |
| receive | `________` | `________` | `________` | `________` |
| pay | `________` | `________` | `________` | `________` |

Rules:

1. Values are filled **after** live catalog extraction from the deployed Verification Runner.
2. Use small controlled values only if allowed by the live contract and explicitly approved by Owner.
3. `AccountId` in every account-targeted payload must equal `350`.
4. `RequestId` or any other required field must follow the live schema.
5. Body files stay under `var/kimia-verify/payloads/` and are never committed.

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
- Never use `OWNER_WRITE_BATCH_V1` or another predictable literal as the secret.
- Never paste the secret into this document, GitHub Issue, Git commit, logs, or chat evidence.
- Record only a non-secret fingerprint if needed.

---

## 6) Per-operation execution card

For each of B1–B4:

```text
KIMIA_MUTATE_PATH=          # exact relative POST path in the fresh live catalog
KIMIA_MUTATE_ACCOUNT_ID=350
KIMIA_MUTATE_BODY_FILE=     # runner-local JSON matching the live schema
```

The reviewed CLI performs a fresh preflight in the same process before `mutate`.

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

Read-only:

```text
/chabokan status
/chabokan logs
/chabokan preflight TALAMALA
```

Current verification:

- Issue-comment permission is working.
- `/chabokan preflight TALAMALA` completed successfully in run `32185718527`.

Deployment is intentionally **not** exposed through issue comments. Any runner synchronization/deploy is a separate ops action and does not itself authorize Kimia Write.

---

## 8) OWNER SIGNATURE — required before B1

Without all required YES fields below, **no mutation**.

| Field | Owner fill |
|-------|------------|
| I authorize exactly Batch V1 B1–B4 | YES / NO |
| Account `350` confirmed as test target | YES / NO |
| Create account included | **NO** |
| Live catalog reviewed and §4 fully filled | YES / NO |
| Live Swagger SHA-256 approved | `________________________________` |
| Deployed Verification Runner SHA approved | `________________________________` |
| Attempt budget approved: `buy=1,sell=1,receive=1,pay=1,create=0` | YES / NO |
| Date (UTC) | `________________________________` |
| Name / role | `________________________________` |
| Random Owner-token provisioned in runner secrets | YES / NO |
| Non-secret Owner-token fingerprint (optional) | `________________________________` |

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
