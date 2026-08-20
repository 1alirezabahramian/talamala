# Stage — DecimalString domain hardening

**Base HEAD:** `25124ed`  
**Date:** 2026-08-20  
**Scope:** domain invariant + smoke + pilot alignment — **no** Live Kimia, **no** GT invent, **no** VERSION bump.

## Changes
1. `backend/app/Domain/Shared/DecimalString.php` — canonical decimal guard (reject scientific / empty / multi-dot / surrounding whitespace)
2. Enforce on `Quote`, `Order`, `CustodyItem` constructors
3. `domain_smoke` → PASS=13 (decimal_* checks)
4. `pilot_preflight` pins `domain_smoke` to exact `PASS=13 FAIL=0`
5. Order accept UI: settlement GT warning banner (reuses existing card styles)
6. CURRENT_STATE / CAPABILITY_LEDGER notes

## Explicit non-goals
Kimia Write wire · Pricing · Settlement unlock · VERSION bump

## Audit posture
No Human Green remains unchanged. This stage does not alter Final Audit Agent authority or claim pilot acceptance.
