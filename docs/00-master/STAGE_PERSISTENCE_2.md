# Stage — Persistence-2 (SQLite Session + Idempotency + Audit)

**Status:** CLOSED  
**Date:** 2026-08-15  
**Builds on:** Persistence-1 (customers / custody / quotes / orders)

## Goal
Make sessions, idempotency registry, and audit events durable across process restarts without inventing financial logic or new APIs.

## Decision
- Same SQLite PDO path as Persistence-1 (`TALAMALA_DB_PATH` / default `:memory:`)
- Shared connection for `:memory:` so all repos see one DB per process
- Interfaces unchanged: `SessionStore`, `IdempotencyRegistry`, `AuditLogger`

## Implemented

| Interface | Class |
|-----------|--------|
| SessionStore | `SqliteSessionStore` |
| IdempotencyRegistry | `SqliteIdempotencyRegistry` |
| AuditLogger | `SqliteAuditLogger` |

### New tables
- `sessions` (token PK, tenant_id, subject_type/id, expires_at, meta_json)
- `idempotency_keys` (tenant_id + scope + key PK, result_json, expires_at)
- `audit_events` (append-only; tenant_id, actor, action, correlation_id, metadata_json)

## Still InMemory
- OTP rate limiter (`InMemoryRateLimiter`) — process-local fixed window
- Tenant resolver (`InMemoryTenantResolver`) — seeded demo hosts

## Not in scope
- PostgreSQL production driver
- JWT signing / session cookie policy
- OTP hash-at-rest redesign (Delta note — preserve existing; no invent)
- Kimia Write / payment / price tables
- Manual outbox replay UI

## Verify
```bash
php backend/bin/http_smoke.php      # PASS=33 FAIL=0
php backend/bin/persist_smoke.php   # PASS=9 FAIL=0
  # includes session_survives_reboot, idempotency_survives_reboot, audit_survives_reboot
php backend/bin/openapi_parity_check.php  # parity OK
```

## CI gates
- `http-smoke` → PASS=33 FAIL=0
- `persist-smoke` → PASS=9 FAIL=0
