# Stage — Persistence-1 (SQLite)

**Status:** Closed for Identity + Custody + Quote + Order  
**Date:** 2026-08-15

## Goal
Replace InMemory stores for customer/custody/quote/order with durable SQL without inventing financial logic.

## Decision
- Engine: **SQLite via PDO** (no Composer; works offline)
- Default: `:memory:` (smoke isolation, one shared connection per process)
- Durable local: set `TALAMALA_DB_PATH` to a file path

## Implemented repositories
| Interface | Class |
|-----------|--------|
| CustomerRepository | `SqliteCustomerRepository` |
| CustodyRepository | `SqliteCustodyRepository` |
| QuoteRepository | `SqliteQuoteRepository` |
| OrderRepository | `SqliteOrderRepository` |

Connection/schema: `SqliteConnection` (migrate on connect)

## Still InMemory (Persistence-2)
- Audit logger
- Session store
- Idempotency registry
- OTP rate limiter
- Tenant resolver (seeded hosts)

## Not in scope
- PostgreSQL production driver wiring
- Laravel migrator
- Kimia Write / payment tables
- Balance local ledger (forbidden — Kimia sole truth)

## Verify
```bash
cd backend
php bin/http_smoke.php      # PASS=33
php bin/persist_smoke.php   # PASS=6 — file reboot survival
# durable server:
TALAMALA_DB_PATH=var/talamala.sqlite php -S 127.0.0.1:8080 -t public public/router.php
```
