# Phase-1 pilot — gate matrix

Exact expected PASS counts for freeze baseline.  
Do not lower a gate to force green.

| Gate | Expect |
|------|--------|
| domain_smoke | PASS=13 FAIL=0 |
| decimal_invariant_smoke | PASS=13 FAIL=0 |
| http_smoke | PASS=78 FAIL=0 |
| http_negative_smoke | PASS=17 FAIL=0 |
| persist_smoke | PASS=9 FAIL=0 |
| cors_smoke | PASS=13 FAIL=0 |
| logger_smoke | PASS=8 FAIL=0 |
| maintenance_smoke | PASS=7 FAIL=0 |
| landing_smoke | PASS=18 FAIL=0 |
| openapi_parity | PASS=22 FAIL=0 |

Commands: `make pilot-gate-matrix` · `make check` · `make pilot-preflight`.

**No Human Green:** gates green ≠ project ACCEPTED_FOR_PILOT without Final Audit Agent + CI attestation.
