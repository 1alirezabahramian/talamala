# Pricing Policy — Owner Ratified (GT-004 policy subset)

**Ratification date:** 2026-08-21 (Asia/Baku / project conversation date)  
**Authority:** Project Owner  
**Source proposal:** `PRICING_POLICY_PROPOSED_FOR_OWNER.md`

## Owner ratification

The Owner explicitly ratified the Cycle 7 pricing policy proposal in the project conversation with the statement:

> تأیید سیاست قیمت Cycle7

This ratification grounds **only the business-policy subset** below. It does not ground or authorize a live external price provider.

## Ratified policy

- Asset policy slice: gold quote quantity in grams; buy and sell sides.
- Coefficients (decimal strings): `x="1"`, `y="0"`, `z="0"`.
- Application order: `adjusted_unit = (reference_unit * x) + y + z`.
- Rounding mode: half-up.
- Unit-price scale: `0` Rial.
- Total scale: `0` Rial.
- Quantity scale: up to `4` decimal grams.
- Rounding order: adjustment → unit rounding → total calculation → total rounding.
- Default quote TTL: `120` seconds.
- Maximum quote TTL: `300` seconds.
- `freeze_on_accept=true`.
- Accepted orders preserve the immutable accepted quote snapshot; no re-price.
- Production policy authority: Owner.
- Rial/Toman conversion remains backend-only with factor `10` at the presentation boundary.

## Explicitly NOT ratified / still blocked

The following remain unresolved GT-004 provider scope:

- official provider name and current contract/API document;
- authentication model and production/sandbox endpoints;
- authoritative observed-at field/clock semantics;
- freshness SLA for provider ticks;
- provider failover/stale behavior beyond the current fail-closed rule;
- controlled provider evidence;
- a real PriceProvider adapter.

Therefore:

- `live_pricing_authorized` MUST remain `false`;
- `BlockedPriceProvider` remains the default hard stop;
- non-fixture quote issuance must continue to fail through `PricingContract::assertLivePricingAllowed()`;
- `FA-048 Price provider integration` remains BLOCKED;
- this ratification is evidence only for `FA-047` and `FA-049`.

No Live price call was executed by this ratification.
