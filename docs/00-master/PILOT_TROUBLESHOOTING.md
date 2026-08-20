# Pilot troubleshooting

## `could not find driver` (PDO SQLite)
Install `php-sqlite3` / `php8.3-sqlite3`. Without it http/persist/maintenance smokes fail and Final Audit raises CV-01.

## `CV-03` exact-SHA CI attestation
Local `make final-audit` is diagnostic. Closure requires GitHub workflow `final-audit.yml` success on **this** SHA with `TALAMALA_AUDIT_CI_*`.  
See `make ci-attest-hint`. Never invent `STATUS=success`.

## CORS all denied
`TALAMALA_CORS_ORIGINS` empty = deny. Set exact SPA origins in production `.env`.

## Settlement blocked on order accept
Expected for Phase-1. Do not wire Kimia Write. See RELEASE_SCOPE_PHASE1.md.

## OTP not received
Pilot uses Fake SMS unless GT-008 closed. Check `/v1/dev/last-otp` only in non-production with dev header.

## Write flag
`KIMIA_WRITE_VERIFY_ENABLE` must be `0` for pilot. `make pilot-env-check`.
