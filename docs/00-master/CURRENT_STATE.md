# Talamala — Current State (2026-08-15)

## HTTP smoke
`php backend/bin/http_smoke.php` → **PASS=33 FAIL=0**

Includes: tenant isolation, OTP rate-limit, identity vertical, registration queue + approve,
dev Kimia bind, assets (Kimia read), custody lifecycle, order accept+idempotency,
production bearer hardening + production blocks on /v1/dev/*.

## OpenAPI
v1.3.0 aligned with working production routes. Dev routes stay out of OpenAPI (local only).

## Stage C — CLOSED 100%
OTP frontend → local server + registration form + assets link-in (`otp-demo.html`).

## Stage D Registration — CLOSED
POST /v1/auth/customer/register; Jibit = verification only; staff approve separate.

## Stage 3 Kimia Read — CLOSED
GET /v1/customer/assets; decimal strings; Rial→Toman on backend only.

## Backoffice Registration Queue — CLOSED
Staff login/rotate → list pending → approve.  
Local: optional `POST /v1/dev/bind-kimia` to attach existing Kimia account id + seed Fake balance.

## Quote / Order / Custody — backend vertical present
- Quote immutable fixture + accept (idempotent); settlement BLOCKED
- Custody: receive → ready → deliver
- Demos: order-demo, custody-demo, admin-custody-demo

## Non-negotiables
- Kimia = sole truth for Money/Gold/Coin/Currency
- Talamala = sole truth for Physical Custody (Amanat)
- Tenant from verified Host only (fail-closed)
- Decimal strings only
- No invented Action codes / price coefficients / payment contracts
- Kimia Write / live price / settlement remain **BLOCKED BY GROUND TRUTH**

## Next (talago)
1. Customer shell continuity demo (assets + custody + orders in one zero-build page)
2. Custody / order UI polish only — no settlement / no price invention
3. Do **not** enable Kimia Write without new ground-truth evidence
