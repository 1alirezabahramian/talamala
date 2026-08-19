# Phase-1 — What can be published vs what cannot

**VERSION:** `0.3.8-phase1`  
**Accounting rule:** Kimia remains sole money/gold ledger. Talamala must not invent balances or post uncontrolled writes.

## Can ship (limited production / pilot)

| Capability | Notes |
|------------|--------|
| Multi-tenant Host resolution | Fail-closed |
| Customer OTP login/register | Fake or real SMS when GT-008 closed |
| Staff login + registration approval queue | |
| Kimia **Read** assets (bound account) | Requires Kimia credentials + binding |
| Physical custody lifecycle | Talamala truth |
| Quote accept → order | Settlement stays **blocked** until GT-005 |
| Customer + Backoffice SPA | Build to `frontend/*/dist`, serve `/app/*` |
| OpenAPI + smoke gates | CI exact SHA |

## Must NOT claim in marketing / pilot contract

| Item | Why |
|------|-----|
| Live market pricing | GT-004 |
| Automatic settlement into Kimia | GT-005 + Order wire policy |
| Unattended Kimia Create/Write | Owner auth + evidence only |
| Online payment capture | GT-006 |
| Production SMS/Jibit without tenant proof | GT-008 / GT-009 |

## Pilot posture (recommended)

1. One tenant Host + CORS allowlist  
2. Kimia Read credentials; **Write enable = 0**  
3. Staff approve registrations; bind Kimia account id manually when needed  
4. Orders accepted for workflow only — settlement message remains blocked  
5. Custody for physical Amanat only  

## Definition of “releasable” for Phase-1

- `make check` green on release SHA  
- Customer + Backoffice `npm run build` artifacts present  
- `.env` production values set without secrets in Git  
- This scope document acknowledged by Owner  
