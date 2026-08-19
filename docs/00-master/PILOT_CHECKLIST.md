# Phase-1 pilot go-live checklist

## Before traffic
- [ ] Release SHA recorded
- [ ] `make release-build` or equivalent green
- [ ] `TALAMALA_ENV=production`
- [ ] CORS origins exact
- [ ] Durable DB path + backup plan
- [ ] Kimia Read credentials only; `KIMIA_WRITE_VERIFY_ENABLE=0`
- [ ] One staff operator account rotated off default password
- [ ] Owner signed RELEASE_SCOPE_PHASE1.md

## Smoke on production Host
- [ ] `/healthz` `/readyz`
- [ ] Customer OTP request
- [ ] Staff login + registration queue
- [ ] Custody receive on test customer
- [ ] Assets for **bound** account (Read)
- [ ] Order accept shows settlement blocked

## Explicit non-goals this pilot
- Live price provider
- Kimia settlement write from orders
- Unattended Create/Write to Kimia
