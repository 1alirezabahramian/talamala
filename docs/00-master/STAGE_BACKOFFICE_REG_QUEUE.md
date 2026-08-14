# Stage — Backoffice Registration Queue + Approve

Closes customer loop: register → Limited → staff approve → Active.

## Contracts (existing)

```
POST /v1/auth/staff/login
POST /v1/auth/staff/password/rotate   (first login)
GET  /v1/admin/registrations          → { items: [...] }
POST /v1/admin/registrations/{id}/approve → { customer_id, access_status }
```

Item fields: customer_id, mobile, full_name, national_code, access_status, kimia_bound, created_at

## Out of scope

- price / quote / order
- Kimia Write / bind UI (bind remains separate / BLOCKED auto-create)
- CustodyOps (next)

## Local demo staff

- username: operator
- password: ChangeMe-Now-1 (must rotate on first login)
