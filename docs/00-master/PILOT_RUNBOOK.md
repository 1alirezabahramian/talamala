# Phase-1 Pilot Runbook

**VERSION:** `0.3.8-phase1`  
**Posture:** Safe Closure — Kimia Write/Create **off**; settlement **blocked**; no live pricing.

## 1. Offline (any machine)

```bash
make pilot-env-check      # .env / template posture, write-deny
make pilot-preflight      # VERSION · docs · domain · parity · typecheck · ACL contracts
# or chain:
make pilot-all            # env-check + preflight (+ host if TALAMALA_BASE_URL set)
```

## 2. Build artifacts

```bash
make release-build
# preflight → SPA typecheck+build → dist → backend gates
# full check.php when pdo_sqlite present
```

Record the release SHA:

```bash
git rev-parse HEAD
```

## 3. Host environment

```bash
cp .env.pilot.example .env
# edit: CORS origins, durable DB path, Kimia READ credentials only
# keep KIMIA_WRITE_VERIFY_ENABLE=0
make pilot-env-check
```

## 4. Deploy / serve

See `DEPLOY_PHASE1.md`. Minimal:

```bash
cd backend && php -S 0.0.0.0:8080 -t public public/router.php
```

- Landing `/`
- Customer `/app/customer/`
- Backoffice `/app/backoffice/`
- API `/v1/...`

## 5. Host smoke (safe GETs)

```bash
TALAMALA_BASE_URL=https://your-pilot-host make pilot-host-smoke
```

## 6. Manual pilot checklist (Host)

Follow `PILOT_CHECKLIST.md`:

1. healthz / readyz version  
2. Customer OTP request  
3. Staff login + registration queue  
4. Custody receive (test customer)  
5. Assets for **bound** account (Kimia **Read** only)  
6. Order accept → settlement **blocked**

## 7. Hard stops

| Action | Rule |
|--------|------|
| Kimia Write / Create | Forbidden without **new** explicit Owner authorization on Iran runner |
| Settlement from Order | Blocked until GT-005 + Owner decision |
| Live price provider | Blocked until GT-004 |
| Production SMS / Jibit | Blocked until GT-008 / GT-009 |
| Deploy via Chabokan Issue | Not allowed — status/logs/preflight/restart only |

## 8. Rollback

Redeploy previous exact SHA; restore DB backup taken before migrate.

## References

- Scope: `RELEASE_SCOPE_PHASE1.md`
- Blockers: `GROUND_TRUTH_BLOCKERS.md`
- State: `CURRENT_STATE.md`
- Chabokan: GitHub Issue **#1** (`/chabokan status|logs|preflight|restart …`)
