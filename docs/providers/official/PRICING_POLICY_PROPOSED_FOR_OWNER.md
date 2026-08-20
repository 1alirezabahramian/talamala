# Proposed Pricing Policy (GT-004) — awaiting Owner ratification

**Status:** PROPOSAL ONLY. This document does not ground `PRICING_CONTRACT.json` and does not authorize a live feed.

## 1. First policy slice

| Item | Conservative proposal |
|---|---|
| Asset scope | Gold quote quantity in grams only for the first grounded slice |
| Sides | buy + sell |
| Live external feed | none in this slice |
| Quote source until provider GT is complete | explicit fixture / dev / manual snapshot only |

## 2. Coefficients — proposal, not fact

| Symbol | Proposed role | Proposed value |
|---|---|---:|
| x | multiplier on reference unit price | `"1"` |
| y | additive Rial per gram | `"0"` |
| z | reserved second adjustment | `"0"` |

Proposed order:

`adjusted_unit = (reference_unit * x) + y + z`

Then apply the approved rounding rule to unit price and total. Arithmetic must remain backend-only decimal-string arithmetic; no float.

These values are deliberately neutral placeholders. They must not be treated as store policy until explicit Owner ratification.

## 3. Rounding proposal

| Field | Proposal |
|---|---|
| unit price scale | `0` Rial |
| total scale | `0` Rial |
| mode | half-up |
| quantity scale | up to 4 decimal grams |
| order | adjustment → unit rounding → total calculation → total rounding |

## 4. Quote TTL / freeze proposal (FA-047)

| Field | Proposal |
|---|---|
| default_ttl_seconds | `120` |
| max_ttl_seconds | `300` |
| freeze_on_accept | `true` |
| accepted order behavior | preserve the immutable accepted quote snapshot; do not re-price |
| production policy authority | Owner |

## 5. Missing/stale feed behavior

Until an official live provider contract exists:

- no live feed is configured;
- no last-known price fallback is allowed;
- failure to obtain an authoritative price means **refuse quote issuance**;
- failover remains `none`.

A future provider slice must separately define official API documentation, authentication, observed-at semantics, freshness SLA and failover policy before Live Pricing can open.

## 6. Rial / Toman

Existing invariant remains unchanged: conversion is backend-only with factor `10` where a presentation boundary requires Toman. Contract/domain price snapshots remain explicit about their unit.

## 7. Ratification

Ratification of this document may ground only the business-policy subset represented here. It **must not** set `live_pricing_authorized=true` or mark the provider integration complete.

Owner ratification: ____________________

Date: ____________________
