# Stage — Kimia Write contract hardening (post Batch V1)

**Base:** `f1151ad` · **VERSION:** `0.3.8-phase1`  
**No Order/Settlement/Create Customer wiring**

## Stages in this package

| ID | Change |
|----|--------|
| S1 | `KimiaWriteInput` — shared AccountId / decimal / UUID v4 / GoldUnit guards |
| S2 | `HttpKimiaWriteClient` delegates to `KimiaWriteInput` |
| S3 | `FakeKimiaWriteClient` same guards as HTTP |
| S4 | `backend/bin/kimia_write_contract_smoke.php` — 11 local assertions, no network |
| S5 | `KIMIA_WRITE_CONTRACT_BATCH_V1.json` — machine-readable proven contract |
| S6 | Makefile target `kimia-write-contract` |

## Explicit non-goals
Settlement wire · Order completion · Pricing · Create Customer · Coin/Currency/Physical

## Contract policy note
`KIMIA_WRITE_CONTRACT_BATCH_V1.json` separates live Swagger required/optional fields from stricter Talamala ACL requirements. `RequestId` is optional in Swagger but required as UUID v4 by the ACL; `GoldUnit` is optional in Swagger but explicitly sent by the ACL for `exchangegold` (default `1=gram`).
