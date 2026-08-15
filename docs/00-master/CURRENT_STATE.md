# Talamala — Current State (2026-08-15)

## Smokes
| Check | Expect |
|-------|--------|
| http_smoke | PASS=41 FAIL=0 |
| persist_smoke | PASS=9 FAIL=0 |
| cors_smoke | PASS=10 FAIL=0 |
| logger_smoke | PASS=7 FAIL=0 |
| openapi_parity | PASS |
| frontend typecheck | PASS |

## Runtime env (see `.env.example`)
- `TALAMALA_ENV` local|staging|production
- `TALAMALA_DB_PATH` SQLite file or `:memory:`
- `TALAMALA_LOG_PATH` JSON-line log file
- `TALAMALA_CORS_ORIGINS` allow-list

## SPA
`/app/customer` · `/app/backoffice` after `npm run build`

## BLOCKED
Kimia Write · Pricing/Catalog · Settlement · Payment · Delta blind port
