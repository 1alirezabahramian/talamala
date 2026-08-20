# Pricing Policy — Owner template (GT-004)

Fill this (or attach official provider PDF/URL) and archive under `docs/providers/official/` with date + hash.  
Until archived and `PRICING_CONTRACT.json` flipped to `GROUNDED` by an authorized update, **no live pricing**.

## 1. Provider

- Legal/commercial name:
- Official API documentation (URL or file path + version/date):
- Auth (API key / mutual TLS / other):
- Base URL(s) production / sandbox:
- Rate limits:

## 2. Freshness & failover

- Max age of a usable tick (seconds):
- Behavior when stale (reject quote / block accept / other):
- Failover source (if any):
- Clock source / timezone for `observed_at`:

## 3. Coefficients (x / y / z)

Define **exactly** what each means and the **application order**.

| Symbol | Meaning | Value | Applies to |
|--------|---------|-------|------------|
| x | | | buy / sell / both |
| y | | | |
| z | | | |

Application order (example only — replace with truth): `…`

## 4. Rounding

- Order of operations (price → qty → total or other):
- Rounding mode (floor / ceil / half-even / other):
- Scale for Rial unit price:
- Scale for quantity:
- Scale for total Rial:

## 5. Quote TTL / freeze (FA-047)

- Default `expires_at` offset from issue:
- Maximum allowed TTL:
- Does accept freeze the quoted price? (yes/no + rule):
- Authority who may change TTL in production:

## 6. Assets / products in scope for Release-1

- List:

## 7. Authorization

- Owner name / date:
- Statement: “I authorize Talamala to implement PriceProvider against the above policy only.”

**Do not** put secrets in Git. Credentials process belongs in operator vault, not this template.
