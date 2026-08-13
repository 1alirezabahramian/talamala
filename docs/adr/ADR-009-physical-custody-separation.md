# ADR-009: Physical Custody vs Financial Balance Separation

## Status
Accepted (detail)

## Context
Amanat is operational inventory, not a Kimia money line.

## Decision
- `CustodyItem` aggregate in Talamala DB with states held → ready_for_pickup → delivered.
- Staff actions audited (actor, tenant, target, correlation_id).
- Kimia AccountType 10 may operationally relate but does not replace Talamala custody records.
- Moving custody does not automatically mutate Kimia gold balance.

## Consequences
- CAP-015 domain implemented; UI/API staff ops can expand without Kimia write.
