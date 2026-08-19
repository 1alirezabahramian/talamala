# Release notes — 0.3.8-phase1

## Included
- Multi-tenant foundation, OTP/staff/registration, custody
- Kimia Read assets path
- Order accept with settlement blocked
- Customer + Backoffice SPA (RTL)
- Kimia Write/Create **ACL grounded, writes not enabled** for pilot

## Not included
- Live pricing, automatic Kimia settlement, payments, unattended Kimia mutations

## Operator
See DEPLOY_PHASE1.md, PILOT_CHECKLIST.md, and PILOT_RUNBOOK.md.

Pilot tooling (ops only, Write still off):
- `make pilot-env-check` · `make pilot-preflight` · `make pilot-all`
- `make release-build` · `make pilot-host-smoke`
- `.env.pilot.example`
