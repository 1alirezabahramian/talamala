# ADR-008: Quote Immutability & Lifecycle

## Status
Accepted (detail)

## Context
Accepted price must not change under the customer.

## Decision
- Quote numeric fields are immutable after issuance.
- Status only: open → accepted | expired | cancelled.
- Order copies quantity and prices from quote at accept time and stores `quote_id`.
- `PriceProvider` port exists; live feed and coefficients remain BLOCKED BY GROUND TRUTH.

## Consequences
- No client-side price authority.
- Settlement is a separate step and remains blocked with Kimia write gate.
