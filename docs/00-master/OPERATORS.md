# OPERATORS

> **PHASE-1 SAFE CLOSURE** at `0.3.8-phase1` — no speculative financial/provider work. See `PHASE1_SAFE_CLOSURE.md`.

Talamala — Operator quick start

## One-liner checks

```bash
make check
# or: cd backend && php bin/check.php
```

Expect: http 78 · persist 9 · cors 13 · logger 8 · maintenance 7 · landing 18 · domain 13 · decimal 13 · http-negative 17 · openapi parity · ALL CHECKS PASSED

## Run API + static

```bash
export TALAMALA_ENV=local
# optional: TALAMALA_DB_PATH=var/talamala.sqlite
# optional: TALAMALA_LOG_PATH=var/talamala.log
# optional: TALAMALA_CORS_ORIGINS=http://127.0.0.1:5173
cd backend && php -S 127.0.0.1:8080 -t public public/router.php
```

Open http://127.0.0.1:8080/ (landing hub).  
Tenant demos: send `Host: demo.local` or `X-Talamala-Host: demo.local`.

## SPA / frontend (optional)

```bash
make frontend-typecheck   # tsc only
make frontend-build       # tsc + vite build → dist served under /app/customer /app/backoffice
# or manual:
#   cd frontend/customer && npm ci && npm run typecheck
#   cd frontend/backoffice && npm ci && npm run typecheck
```

Frontend typecheck is **advisory** in CI (`continue-on-error`); a Node/npm failure does not fail the green SHA.

## Production flags

- `TALAMALA_ENV=production` → Bearer only; `/v1/dev/*` off; no X-Customer-Id fallback
- Never commit secrets; use `.env.example` as template only

## Not enabled

Kimia Write · live price · settlement · payment — BLOCKED BY GROUND TRUTH

## Build identity

- `VERSION` file at repo root
- Optional `TALAMALA_BUILD_SHA` env (short SHA on landing)

## Chabokan control (Issue console)

**Preferred path for agents:** comment on GitHub Issue **#1** (Chabokan Control Console):

```text
/chabokan status
/chabokan logs
/chabokan preflight TALAMALA
/chabokan restart TALAMALA
/chabokan start TALAMALA
/chabokan stop TALAMALA
```

Rules:
- Service lock: `talamala-kimia-runner` only
- `preflight` / restart / start / stop require the confirmation token **TALAMALA**
- **deploy is not available** from Issue commands (by design)
- Never print `CHABOKAN_TOKEN`
- Successful preflight ≠ Kimia Write permission
- Do not touch GoldPlatform from this runner

Alternate: Actions → workflow **Talamala Chabokan Control**.

## Phase-1 pilot path

```bash
make pilot-env-check
make pilot-preflight
make pilot-record
make release-build
TALAMALA_BASE_URL=https://host make pilot-host-smoke
# chain: make pilot-all
```

Full flow: `docs/00-master/PILOT_RUNBOOK.md` · checklist: `PILOT_CHECKLIST.md`  
**Write remains off:** `KIMIA_WRITE_VERIFY_ENABLE=0`

## Gate matrix

`make pilot-gate-matrix` · `make decimal-invariant` · `make audit-domain-scorecard`
