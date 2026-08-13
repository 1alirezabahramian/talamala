# ADR-003: Source of Truth Boundaries (Kimia vs Talamala)

## Status
Accepted (detail)

## Context
Need clear ownership so no competing ledger appears.

## Decision
1. **Kimia** is sole truth for Money, Gold (financial weight), Coin, Currency balances and related voucher/adjustment/transfer/exchange operations.
2. **Talamala** is sole truth for Physical Custody (Amanat) lifecycle, quotes issued by the platform, orders/idempotency registry, tenant config, OTP challenges, and audit log.
3. Talamala stores `kimia_account_id` references only — never balance columns that can diverge from Kimia.
4. After any Kimia write: mandatory readback (writes currently BLOCKED BY GROUND TRUTH).

## Consequences
- CAP-010 write path blocked until contracts + credentials.
- Custody CAP-015 progresses independently of Kimia write.
