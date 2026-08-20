# Talamala — Release Cycle 1

Base: `875467d3415c690aa8639253cf8dd0a9d737fa38`
VERSION: `0.3.8-phase1`

## Scope closed in this cycle
- `GET /v1/customer/me`: customer profile only; no balances and no Kimia account id exposure.
- `GET /v1/admin/orders`: read-only tenant-scoped order visibility; settlement remains `blocked_by_ground_truth`.
- `OrderRepository::listForTenant` implemented for SQLite and in-memory adapters.
- Customer Orders UI aligned to shared loading/error/empty/notice/status/form components.
- Backoffice Orders tab/client/screen added.
- OpenAPI parity updated for production routes.
- Release Cycle 1 route/isolation smoke added with exact `PASS=9 FAIL=0`.
- Frontend typecheck promoted from advisory to blocking CI gate.
- Frontend build added as blocking CI gate.

## Explicit non-scope / blockers
No Live Kimia Write/Create. No live Pricing. No Settlement wire. No Payment. No production SMS/Jibit. Durable multi-tenant remains open.

## Authority
This stage does not itself declare release readiness. No Human Green remains in force. Exact-SHA CI and authority verdict are required; Full Release will require a dedicated release-scope authority rather than re-labeling the bounded pilot verdict.
