# Phase-1 pilot go-live checklist

**Runbook:** `docs/00-master/PILOT_RUNBOOK.md`  
**SHA record template:** `docs/00-master/PILOT_SHA_RECORD.template.md`

## Offline preflight (any sandbox)
- [ ] `make pilot-env-check` green (write-deny · production posture hints)
- [ ] `make pilot-preflight` green (VERSION · docs · domain · parity · typecheck · ACL contracts)
- [ ] or `make pilot-all` (env + preflight; host if `TALAMALA_BASE_URL` set)
- [ ] No Live Kimia Write/Create; settlement remains blocked

## Before traffic
- [ ] Release SHA recorded (use `PILOT_SHA_RECORD.template.md`)
- [ ] `make release-build` green (preflight + SPA build + dist + backend gates)
- [ ] On host with `pdo_sqlite`: full `php backend/bin/check.php` → ALL CHECKS PASSED
- [ ] `.env` from `.env.pilot.example`; `TALAMALA_ENV=production`
- [ ] CORS origins exact
- [ ] Durable DB path + backup plan
- [ ] Kimia Read credentials only; `KIMIA_WRITE_VERIFY_ENABLE=0`
- [ ] One staff operator account rotated off default password
- [ ] Owner signed RELEASE_SCOPE_PHASE1.md

## Smoke on production Host
- [ ] `TALAMALA_BASE_URL=https://… make pilot-host-smoke`
- [ ] `/healthz` `/readyz` (manual confirm version if needed)
- [ ] Customer OTP request
- [ ] Staff login + registration queue
- [ ] Custody receive on test customer
- [ ] Assets for **bound** account (Read)
- [ ] Order accept shows settlement blocked

## Explicit non-goals this pilot
- Live price provider
- Kimia settlement write from orders
- Unattended Create/Write to Kimia
