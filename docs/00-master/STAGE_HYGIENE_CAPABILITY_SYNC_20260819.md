# Stage — Capability / operator hygiene (no new GT)

**Base HEAD:** `71f2531`  
**Scope:** docs + Makefile accuracy only — no Live Kimia, no registration/order/settlement wire.

## Changes
1. CAP-017 / CAP-018 ledger text match shipped frontend flows.
2. CAP-036 — Create Account ACL PARTIAL (Swagger grounded, AccountDto guards present, Live Create blocked).
3. CAP-010 note clarified: bounded Batch V1 write scope remains separate from Create Account ACL.
4. `make info` now reports Create Account ACL as PARTIAL instead of claiming it is entirely missing.

## Traceability correction
`CAP-030` was already assigned to Frontend X-Correlation-Id on API client and is preserved unchanged. Create Account ACL therefore uses the next free identifier, `CAP-036`.

## Explicit non-goals
GT-002 live duplicate/validation/readback evidence · Live Create · registration/order/settlement wiring.
