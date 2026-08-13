# Talago Snapshot — 2026-08-12

## Counts
- ~91 files in monorepo skeleton
- PHP: all syntax-clean
- Smoke: OTP · Staff · Registration · Assets · Custody lifecycle · Price blocked

## Domains live (code)
- Tenant (host fail-closed)
- Audit + Idempotency
- Identity (OTP, Staff, Customer, Registration, Jibit port)
- Kimia Read (Http + Fake + FinancialReadService)
- Quote (immutable model + PriceProvider port BLOCKED)
- Custody / Amanat (receive → ready → delivered)

## Frontend
- Customer screen stubs: OTP request/verify, Assets
- API client (opaque money strings)
- IA notes

## Explicit non-goals until ground truth
- Quote issuance with real prices
- Kimia financial writes
- Payment gateways
- Goftino

## GitHub
Create-repo still 403. Owner should create empty `talamala` repo for push.
