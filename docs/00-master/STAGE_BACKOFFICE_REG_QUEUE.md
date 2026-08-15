# Stage — Backoffice Registration Queue + Approve

**Status:** Closed (vertical + local demo)  
**Date:** 2026-08-15

Closes customer loop: register → Limited → staff approve → Active.

## Contracts (existing)

```
POST /v1/auth/staff/login
POST /v1/auth/staff/password/rotate   (first login)
GET  /v1/admin/registrations          → { items: [...] }
POST /v1/admin/registrations/{id}/approve → { customer_id, access_status }
```

Item fields: customer_id, mobile, full_name, national_code, access_status, kimia_bound, created_at

## Local-only helper (not production)

```
POST /v1/dev/bind-kimia
Headers: X-Talamala-Host + X-Talamala-Dev: 1
Body: { customer_id, kimia_account_id, seed_money_rial?, seed_gold_weight_g? }
```

- Binds an **existing** Kimia account id (no Kimia Write / create)
- Optional FakeKimia balance seed for local assets demo
- Blocked when `TALAMALA_ENV=production` (same as other /v1/dev/*)

## Delivered

| Item | Path |
|------|------|
| Queue controller | `backend/app/Http/Controllers/Admin/RegistrationQueueController.php` |
| React API | `frontend/backoffice/src/api/registrations.ts` |
| React screen | `frontend/backoffice/src/screens/RegistrationQueueScreen.tsx` |
| Zero-build demo | `backend/public/admin-reg-demo.html` |
| Dev bind route | `POST /v1/dev/bind-kimia` in Kernel |

## Out of scope (still held)

- price / quote / order from this screen
- Kimia Write / auto-create customer in Kimia
- Production bind HTTP (manual/ops later when ground truth exists)

## Local demo staff

- username: `operator`
- password: `ChangeMe-Now-1` (must rotate on first login)

## Verify

```bash
cd backend
php bin/http_smoke.php   # PASS=33 (includes dev_bind_kimia)
php -S 127.0.0.1:8080 -t public public/router.php
# http://127.0.0.1:8080/admin-reg-demo.html
```
