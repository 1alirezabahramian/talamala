# Talamala — Current State (2026-08-19)

## VERSION
`0.3.8-phase1`

## Phase status
**PHASE-1 SAFE CLOSURE — FROZEN**  
See `docs/00-master/PHASE1_SAFE_CLOSURE.md`.

No speculative domain development until valid Ground Truth is archived.
Do not bump VERSION merely for ops/docs.

## Smokes (Phase-1 freeze baseline)
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
- App: `make help` · `make info` · `make check` · `make serve`
- Pilot path: `make pilot-all` · `pilot-env-check` · `pilot-preflight` · `release-build` · `pilot-host-smoke`
- Runbook: `docs/00-master/PILOT_RUNBOOK.md` · Checklist: `PILOT_CHECKLIST.md` · Deploy: `DEPLOY_PHASE1.md`
- Phase-1 code freeze baseline: `f1e9eb2`
- **Chabokan control (preferred):** GitHub Issue **#1** — Chabokan Control Console  
  Commands: `/chabokan status|logs|preflight TALAMALA|restart TALAMALA|start TALAMALA|stop TALAMALA`  
  (deploy via Issue intentionally **not** allowed)
- Fallback UI: Actions → **Talamala Chabokan Control** (service locked: `talamala-kimia-runner`)
- Do not route Owner through Chabokan console for routine ops
- Do not call Kimia from non-Iran sandbox

## Kimia Write (Batch V1 verified 2026-08-19)
Live: buy/sell `exchangegold` (32/64) · receive/pay `tradecash` (2/4) · ids 77193–77196 · runner restored read-only.
Evidence: `docs/providers/official/KIMIA_WRITE_VERIFICATION_EVIDENCE_2026-08-19.md`
ACL: `KimiaWriteClient` / `HttpKimiaWriteClient` + `KimiaWriteApplicationService` with mandatory balance read-before/read-after. No Kernel/Order/Settlement wiring.
Contract hardening: shared `KimiaWriteInput` guards + offline `make kimia-write-contract` smoke + machine-readable Batch V1 contract.

## Kimia Create Customer (GT-002)
**PARTIAL — core Swagger HTTP contract GROUNDED.** Live Iran evidence proves `POST /api/account`, request schema `AccountDto`, no Swagger-required properties, HTTP `200` primitive int32 AccountId, and generic HTTP `400`.  
Evidence run: `32245857002`; Swagger v1 SHA-256 `be0fb0c6897015e238ef9dd58115b8502cf6f83feb868c91cff19377dfbb5cea`.  
Still open: duplicate semantics, validation error body/codes, and authoritative post-create readback.  
**No Live Create executed; no registration/order/settlement wiring.** Separate explicit Owner authorization remains mandatory for any live Create.

## BLOCKED (requires further GT)
Create Customer duplicate/validation/readback · Coin/Currency/Physical · Pricing · Settlement · Payment · SMS/Jibit live · durable Tenant/Delta

## Kimia Write Verification (separate track; Batch V1 completed)
| Item | State |
|------|--------|
| Preflight (Iran runner) | **PREFLIGHT_OK** ×2 (live evidence) |
| Live Swagger | v1 · SHA-256 `be0fb0c6897015e238ef9dd58115b8502cf6f83feb868c91cff19377dfbb5cea` |
| Account 350 Read / balance / tx | PASS |
| `write_attempted` | **true — exactly Batch V1: buy/sell/receive/pay once each** |
| Write gate | **CLOSED again after Batch V1 restore** (default-deny) |

Evidence: `docs/providers/official/KIMIA_LIVE_PREFLIGHT_EVIDENCE_2026-08-18.md`  
Resume: `docs/providers/official/KIMIA_WRITE_VERIFICATION_RESUME.md`

**No Kimia Write** without a **new** explicit Owner authorization.

## Phase-1 pilot release posture
See `docs/00-master/RELEASE_SCOPE_PHASE1.md` and `docs/00-master/DEPLOY_PHASE1.md`.
Pilot release may expose only the bounded Phase-1 capabilities documented there; live pricing, automated settlement, online payment, unattended Kimia write, and unproven production SMS/Jibit remain out of scope.

- Pilot checklist: `docs/00-master/PILOT_CHECKLIST.md`
- Build: `make release-build`
