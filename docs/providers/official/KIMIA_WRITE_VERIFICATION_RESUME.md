# Kimia Write Verification — Resume Point (Read-Only Preflight)

**Status:** STOPPED — awaiting live Kimia environment  
**Date:** 2026-08-17  
**Phase-1:** remains FROZEN at `0.3.8-phase1`  
**Goal of this track:** extract Ground Truth for Kimia Write — **not** ship a product feature

## Owner authorization (recorded)

| Item | Rule |
|------|------|
| Max experimental accounts created | ≤ 5, fields only from **live** Swagger |
| `account_id=350` | On **Verification whitelist** only |
| Allowed writes on 350 (after preflight + Owner gate) | Exactly **1 buy**, **1 sell**, **1 receive**, **1 pay** |
| After those four | **Mandatory stop** — no repeat / bulk / edit / delete / void / reverse |
| Other account writes | Forbidden unless newly created under ≤5 cap **and later separately whitelisted** |
| Secrets | Env only — never Git, logs, or commit reports |
| Historical archive alone | **Not** sufficient as Write reference |

## Write is default-deny

HTTP Write must **not** be sent unless **all** of the following are true at send time:

1. **Live Swagger** fetched successfully; `version` + content **hash** recorded  
2. **Credentials** present in secure env (not in Git)  
3. **Connectivity + Read baseline** succeeded on the same base URL  
4. Explicit enable flag (e.g. `KIMIA_WRITE_VERIFY_ENABLE=1`)  
5. Target `account_id` ∈ allowlist file/env  
6. Operation ∈ {create≤5, buy×1, sell×1, receive×1, pay×1} and not already consumed  
7. If live Swagger **differs** from archive on Create/Trade/Cash paths: **Owner must approve the diff** before any Write  

Whitelist alone does **not** authorize Write.

## Preflight sequence (Read-Only) — resume here

When Kimia is reachable, run **only** this order (do not restart inventing scope):

```text
P0  Connectivity     → TCP/HTTP to KIMIA_BASE_URL
P1  Live Swagger     → fetch; record URL, HTTP status, version, sha256
P2  Archive diff     → compare Write-related paths/schemas/actions vs
                        docs/providers/official/KIMIA_SWAGGER_ARCHIVE_NOTE.md
                        (+ raw archive if present)
P3  Auth check       → authenticated Read (e.g. GET /api/account)
P4  Account 350      → confirm account visible / readable
P5  Balance baseline → GET balance for 350; store raw JSON
P6  Tx baseline      → GET transactions for 350; store raw JSON
P7  Evidence pack    → write under var/kimia-verify/ (gitignored)
```

**No Write in preflight.**  
If P1 fails → STOP (current state).  
If P2 shows material diff on Create/Trade/Cash → report diff to Owner; **no Write until Owner confirms**.  
If P3–P6 fail → STOP; fix env/network/account before Write gate.

## Env contract (secrets never committed)

```bash
# Required for preflight Read
export KIMIA_BASE_URL="https://…"          # current host; do not assume historical IP
export KIMIA_USERNAME="…"
export KIMIA_PASSWORD="…"

# Optional paths
export KIMIA_SWAGGER_URL="${KIMIA_BASE_URL}/swagger/v1/swagger.json"  # override if needed

# Write gate (must stay unset/0 until Owner opens Write after preflight)
# export KIMIA_WRITE_VERIFY_ENABLE=0
# export KIMIA_WRITE_ACCOUNT_ALLOWLIST=350
```

## Evidence location

- Directory: `var/kimia-verify/` (**gitignored** via `var/`)
- Suggested files after preflight:
  - `preflight_meta.json` — timestamps, base URL host only, swagger version, sha256
  - `swagger_live.json` — live OpenAPI body
  - `swagger_diff_write_related.md` — endpoint/schema/action diff for Create/Trade/Cash
  - `account_350_balance_before.json`
  - `account_350_transactions_before.json`
  - `preflight_result.json` — pass/fail per P0–P6

Redact username/password from all evidence files.

## Historical archive reference (Read path; Write incomplete alone)

- Note: `docs/providers/official/KIMIA_SWAGGER_ARCHIVE_NOTE.md`
- git blob SHA cited: `ea3de1aa56c6f2a940eba24a6c4f57eb9fc904ed`
- Action codes **mentioned in archive note only** (must be re-confirmed from **live** Swagger before Write):
  - Exchange: 32 buy / 64 sell
  - Cash/transfer: 2 receive / 4 pay

## After successful Preflight

1. Deliver Evidence pack summary to Owner (no secrets).  
2. If Swagger diff exists → wait for Owner decision on each delta.  
3. Only then consider Write gate (`KIMIA_WRITE_VERIFY_ENABLE=1`) under the four-operation cap.  
4. After Create (≤5) + four financial ops on 350 → STOP and hand GT for Capability decision.

## Explicit non-goals (this resume point)

- No production Kimia Write capability in Talamala product code  
- No settlement/order completion wiring  
- No inventing request bodies  
- No Write from this document alone
