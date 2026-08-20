# Kimia Create Customer — Controlled evidence runbook (GT-002 remainder)

**Status:** PREPARED — not executed  
**Live Create:** forbidden until Owner signs section 7 and both mutation gates are explicitly enabled for the controlled window.

## Purpose

Close **FA-080** evidence gaps before considering FA-081 or registration wiring by capturing:

1. Duplicate mobile / national-code semantics (HTTP status + body)
2. Validation error body/codes beyond generic 400
3. Authoritative post-create readback (how to re-fetch account by id)

HTTP contract `POST /api/account` + `AccountDto` is already GROUNDED in `KIMIA_CREATE_CUSTOMER_CONTRACT.json`.

## Preconditions

- [ ] Iran runner healthy; Swagger SHA matches archived `be0fb0c6897015e238ef9dd58115b8502cf6f83feb868c91cff19377dfbb5cea` or a newer explicitly archived hash
- [ ] Test account credentials are provided out-of-band / vault only
- [ ] Owner completed section 7 authorization
- [ ] Exact test payload values are approved and contain no real customer personal data
- [ ] `KIMIA_CREATE_ENABLE=1` only on the runner for the controlled window
- [ ] Contract `live_create_authorized=true` only under Owner change control for that window
- [ ] `KIMIA_WRITE_VERIFY_ENABLE=0`; no settlement/order/payment mutation enabled
- [ ] Registration is not wired to live Create during evidence gathering

## Test matrix

| # | Scenario | Payload | Expected before run | Observed status | Evidence path/hash |
|---|----------|---------|---------------------|-----------------|--------------------|
| 1 | Minimal test create | Owner-approved test-only AccountDto | unknown beyond Swagger 200/int32 | | |
| 2 | Duplicate mobile | same test Mobile as #1 | UNKNOWN | | |
| 3 | Duplicate national code | same test NationalCode as #1, if applicable | UNKNOWN | | |
| 4 | Invalid allowed-domain Type | test value rejected by Talamala ACL before network where applicable | no network from app gate | | |
| 5 | Validation boundary | Owner-approved invalid field/value | UNKNOWN | | |
| 6 | Readback | documented/read endpoint using id from #1 | UNKNOWN until proven | | |

## Evidence pack

After the controlled window:

1. Archive redacted request/response pairs under `docs/providers/official/` with date/run id/hash.
2. Update `KIMIA_CREATE_CUSTOMER_CONTRACT.json` only for behavior actually observed.
3. Remove a `remaining_unknowns` entry only when its exact scenario is proven.
4. Restore `live_create_authorized=false`.
5. Restore/unset `KIMIA_CREATE_ENABLE`; verify `pilot-env-check` is green.
6. Record runner source SHA and Swagger hash.
7. Do **not** mark registration wiring or FA-081 green merely because FA-080 evidence improved.

## Abort conditions

Abort the window immediately on unexpected account scope, credential/auth anomaly, Swagger drift not yet archived, response ambiguity that could create further accounts, or any write flag other than the explicit Create gate becoming enabled.

## Non-goals

- Wiring Create into customer registration in the same change
- Claiming GT-002 fully closed without duplicate/validation/readback proof
- Running Create from a non-Iran sandbox
- Performing Kimia financial Write / Settlement

## Owner authorization (section 7)

I, ________________, authorize **one controlled Create evidence window** on environment/account ________ for GT-002 duplicate/validation/readback only.

Approved test-data reference (no secrets/real customer PII in Git): ________  
Date: ________  
Signature / recorded Issue comment: ________
