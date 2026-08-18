# Kimia Write Verification Evidence — 2026-08-19

Status: VERIFIED LIVE WRITE BATCH V1

Repository: `1alirezabahramian/talamala`
Phase: `0.3.8-phase1` (unchanged)
Execution source SHA: `24a5b0e737178d859d8985dcff7a5570e475123e`
GitHub Actions run: `32197791006`
Chabokan service: `talamala-kimia-runner`
Account target: `350`
Swagger version: `v1`
Swagger SHA-256: `be0fb0c6897015e238ef9dd58115b8502cf6f83feb868c91cff19377dfbb5cea`

## Owner-authorized bounded batch

Exactly one attempt was allowed for each operation and no Create attempt was permitted:

- buy = 1
- sell = 1
- receive = 1
- pay = 1
- create = 0

The batch ran in one Chabokan release after a fresh preflight. Any operation failure would have halted the remaining batch. The runtime returned the write gate to `0` after the fourth operation, and a separate read-only release was then deployed and positively identified by a unique restore marker.

## Live results

| Step | Operation | Endpoint | Action | HTTP | Kimia returned id | Runner outcome |
|---|---|---|---:|---:|---:|---|
| B1 | buy | `/api/voucher/exchangegold` | 32 | 200 | 77193 | success |
| B2 | sell | `/api/voucher/exchangegold` | 64 | 200 | 77194 | success |
| B3 | receive | `/api/voucher/tradecash` | 2 | 200 | 77195 | success |
| B4 | pay | `/api/voucher/tradecash` | 4 | 200 | 77196 | success |

Response body hashes:

- buy: `fe3be1d9368ae7b19ce4bac28e7ba49098308c55df6360de368ef1c47d6373d3`
- sell: `1b8e01af25b675f9db61372ec2d524745f2dbcf1146d5f43a28edf375189c1bc`
- receive: `ba5c57cd9e6430fd9c2e52b37327c923cabce93ac1337e3b15d3f57eb060a6f0`
- pay: `dff808e4b762927abc2e10fa5c5b79f07c8defb8b218337081d8d45159c288a6`

Runner state after B4 recorded:

- consumed buy = 1
- consumed sell = 1
- consumed receive = 1
- consumed pay = 1
- halted = false
- all four outcomes = `success`
- all four HTTP statuses = `200`
- all four immediate readbacks = `readback_ok=true`

## Important readback interpretation

The immediate transaction endpoint snapshots used by the verifier returned three rows before and after each individual operation, so the per-operation `TX_NEW` diff was empty. This does **not** negate the successful writes: Kimia returned distinct voucher/record ids for all four POSTs, each request completed with HTTP 200, and the verifier's balance/transaction readbacks succeeded.

After the batch was completed and the dedicated service had been restored to the read-only release, a fresh preflight showed:

- `PREFLIGHT_OK`
- `write_gate=0`
- transaction snapshot record count for account 350 = `18`
- transaction snapshot SHA-256 = `bdfe7bab4a8a2d07a0be0ddb8f3dc587644c61b9788289a21a9cdb5a2186cb34`
- `Write not attempted (read_only)` during the restore verification

This later snapshot provides additional evidence that the account transaction history had advanced beyond the earlier short page observed during the immediate mutation readbacks.

## Restore proof

Unique restore marker:

`BATCH_V1_RESTORE_MARKER=32197791006-1-r4`

The restored runtime reported:

- `mode=verification-read-only`
- `write_gate=0`
- `PREFLIGHT_OK`
- same live Swagger SHA-256 as above
- `Write not attempted (read_only)`

Therefore the verification Write gate is CLOSED again after Batch V1.

## Contract conclusions proven by this batch

For account-targeted verification against account 350, the following live Kimia POST contracts are now directly evidenced:

- gold buy: POST `/api/voucher/exchangegold`, `Action=32`
- gold sell: POST `/api/voucher/exchangegold`, `Action=64`
- cash receive: POST `/api/voucher/tradecash`, `Action=2`
- cash pay: POST `/api/voucher/tradecash`, `Action=4`

The tested request shapes were accepted by the live server and returned distinct numeric ids.

This evidence does **not** authorize production financial writes, Create Customer, Coin, Currency, Physical/barcode, settlement, adjustment, transfer, edit/delete/void/reverse, or any capability outside the exact bounded verification batch. Production integration still requires normal application-level safeguards and separate product decisions.

## Source references

- GitHub Actions run: `32197791006`
- Batch marker: `32197791006-1`
- Issue #1 bot result: `KIMIA_BATCH_V1_R4 outcome=complete restore=1`

Raw sensitive evidence remains runtime-only and must not be committed.
