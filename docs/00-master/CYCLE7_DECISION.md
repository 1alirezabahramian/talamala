# Cycle 7 Decision — GT-004 Pricing policy lock

**Base:** exact current `main` at Cycle start (`0a9554a2ae2ab5ee51b0265c8411ec29fec86f3b`).

## Selected path

GT-004 Pricing first because it allows meaningful offline progress without any Live mutation, customer creation, settlement side effect, or payment capture.

## Cycle 7 boundary

This cycle creates a **proposal and hard locks only**. It does not assert Ground Truth.

- `PRICING_CONTRACT.json` remains `NOT_GROUNDED`.
- `live_pricing_authorized` remains `false`.
- Proposed TTL / coefficients / rounding are not production truth until explicit Owner ratification.
- Owner ratification of the business policy does **not** by itself authorize a live provider.
- Live pricing additionally requires the complete provider/freshness/failover/asset-scope contract and zero remaining unknowns enforced by `PricingContract::assertLivePricingAllowed()`.
- `BlockedPriceProvider` remains a hard-stop adapter and never returns a synthetic price.
- `QuoteIssuanceGuard` permits only explicit non-live fixture/manual/dev sources while GT-004 is incomplete.

## No score gaming

Release blocker count and verdict remain machine-owned. No FA row moves to GREEN in this cycle merely because a proposal exists.

## Next boundary

An explicit Owner ratification may ground only the ratified policy slice (for example TTL/freeze and coefficients/rounding). Provider integration remains independently blocked until official API evidence is archived and implemented.
