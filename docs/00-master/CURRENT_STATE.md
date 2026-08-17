# Talamala — Current State (2026-08-17)

## VERSION
`0.3.8-phase1`

## Phase status
**PHASE-1 SAFE CLOSURE — FROZEN**  
See `docs/00-master/PHASE1_SAFE_CLOSURE.md`.

No speculative domain development until valid Ground Truth is archived.

## Smokes (freeze baseline)
| Check | Expect |
|-------|--------|
| domain_smoke | PASS=8 FAIL=0 |
| http_smoke | PASS=78 FAIL=0 |
| persist_smoke | PASS=9 FAIL=0 |
| cors_smoke | PASS=13 FAIL=0 |
| logger_smoke | PASS=8 FAIL=0 |
| maintenance_smoke | PASS=7 FAIL=0 |
| spa_router_smoke | PASS=6 FAIL=0 |
| landing_smoke | PASS=18 FAIL=0 |
| openapi_parity | PASS |

## Operator
- `make help` · `make info` · `make check` · `make serve`
- Freeze baseline SHA: `f1e9eb2` (Owner-confirmed)

## BLOCKED (requires official GT)
Kimia Write · Pricing · Settlement · Payment · SMS/Jibit live · durable Tenant/Delta

## Kimia Write Verification (separate track)
**STOPPED** at Read-Only Preflight gate. Resume: `docs/providers/official/KIMIA_WRITE_VERIFICATION_RESUME.md`
Runner (read-only): `php backend/bin/kimia_preflight_readonly.php`
Write default-deny until live swagger hash + env creds + baseline Read + Owner enable.
