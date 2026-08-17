# Kimia Write Verification — Resume Point (Live Preflight Recorded)

**Status:** LIVE PREFLIGHT PASSED — Write Verification gate remains **CLOSED / default-deny**  
**Date:** 2026-08-18  
**Runner:** `talamala-kimia-runner` (Chabokan / Iran)  
**Phase-1:** remains FROZEN at `0.3.8-phase1`  
**Goal of this track:** extract Ground Truth for Kimia Write — **not** ship a product feature

## Latest live evidence

Recorded in: `docs/providers/official/KIMIA_LIVE_PREFLIGHT_EVIDENCE_2026-08-18.md`

| Item | Result |
|---|---|
| Preflight | `PREFLIGHT_OK` ×2 |
| Live Swagger | HTTP `200` · version `v1` |
| SHA-256 | `be0fb0c6897015e238ef9dd58115b8502cf6f83feb868c91cff19377dfbb5cea` |
| Auth / account 350 / balance / tx baseline | PASS |
| `write_attempted` | `false` |
| Write Verification gate | **CLOSED** |

Interpretation: the 2026-08-18 evidence proves live reachability plus the read-only baseline on the same environment. It does **not** open a Kimia Write capability and does **not** change Phase-1 status. The existing analysis/archive remains the API-shape reference unless the Owner explicitly requests a specific live-vs-archive diff decision for the Write Verification track.

## Owner authorization (recorded)

| Item | Rule |
|------|------|
| Max experimental accounts created | ≤ 5, fields only from **live** Swagger |
| `account_id=350` | On **Verification whitelist** only |
| Allowed writes on 350 (after preflight + Owner gate) | Exactly **1 buy**, **1 sell**, **1 receive**, **1 pay** |
| After those four | **Mandatory stop** — no repeat / bulk / edit / delete / void / reverse |
| Other account writes | Forbidden unless newly created under ≤5 cap **and later separately whitelisted** |
| Secrets | Env only — never Git, logs, or commit reports |
| Historical archive alone | **Not** sufficient as Write authorization |

## Write is default-deny

HTTP Write must **not** be sent unless **all** applicable conditions are true at send time:

1. Live Swagger is reachable and its version + content hash are recorded.  
2. Credentials are present in secure env (not in Git).  
3. Connectivity + authenticated Read baseline succeeds on the same base URL / runner.  
4. **Owner explicitly authorizes the limited Write Verification run.**  
5. Explicit enable flag (e.g. `KIMIA_WRITE_VERIFY_ENABLE=1`) is set only for that authorized run.  
6. Target `account_id` ∈ allowlist file/env.  
7. Operation ∈ {create≤5, buy×1, sell×1, receive×1, pay×1} and not already consumed.  
8. If a specific live-vs-archive diff is opened and reveals a material change on Create/Trade/Cash paths, Owner must approve that delta before the affected Write.

Whitelist alone does **not** authorize Write. Successful preflight alone does **not** authorize Write.

## Completed Read-Only Preflight state

The live runner has now provided successful read-only evidence for:

```text
P0  Connectivity     → PASS
P1  Live Swagger     → HTTP 200, version v1, SHA-256 recorded
P3  Auth check       → PASS
P4  Account 350      → PASS / readable
P5  Balance baseline → PASS
P6  Tx baseline      → PASS
P7  Preflight result → PREFLIGHT_OK ×2; write_attempted=false
```

`P2 Archive diff` is **not opened by this evidence record**. Per the current Owner contract, the prior analysis/archive remains the API-shape reference unless the Owner explicitly requests a specific diff review before limited Write Verification.

## Env contract (secrets never committed)

```bash
# Required for preflight / verification Read
export KIMIA_BASE_URL="https://…"          # current host; do not assume historical IP
export KIMIA_USERNAME="…"
export KIMIA_PASSWORD="…"

# Optional path
export KIMIA_SWAGGER_URL="${KIMIA_BASE_URL}/swagger/v1/swagger.json"

# Write gate — MUST remain unset/0 until explicit Owner authorization
# export KIMIA_WRITE_VERIFY_ENABLE=0
# export KIMIA_WRITE_ACCOUNT_ALLOWLIST=350
```

## Evidence location

Committed summary evidence:

- `docs/providers/official/KIMIA_LIVE_PREFLIGHT_EVIDENCE_2026-08-18.md`

Runtime/raw evidence remains under `var/kimia-verify/` and must stay gitignored. Secrets must never be committed.

Suggested runtime evidence names:

- `preflight_meta.json`
- `swagger_live.json`
- `account_350_balance_before.json`
- `account_350_transactions_before.json`
- `preflight_result.json`

## Historical archive reference

- Note: `docs/providers/official/KIMIA_SWAGGER_ARCHIVE_NOTE.md`
- Historical action codes mentioned in archive material remain **reference-only until the limited Write Verification gate is explicitly opened**:
  - Exchange: 32 buy / 64 sell
  - Cash/transfer: 2 receive / 4 pay

## Exact resume point

Do **not** restart preflight from zero and do **not** broaden scope.

Next action is allowed only after explicit Owner authorization for the limited Write test. When authorized, resume on the same Iranian runner/environment, preserve the ≤5 experimental-account cap and the four-operation cap on account 350, and stop immediately after the authorized verification sequence.

Until that authorization exists:

- no Create / Buy / Sell / Receive / Pay Write;
- no production Kimia Write capability;
- no settlement/order completion wiring;
- no capability promotion;
- no Phase-1 change.

## Explicit non-goals

- No production Kimia Write capability in Talamala product code  
- No settlement/order completion wiring  
- No inventing request bodies  
- No Write from this document or preflight evidence alone
