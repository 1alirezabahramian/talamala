# Stage D — Registration after registration_required

**Status:** Frontend + local demo only (backend register already exists)

## Contract (existing OpenAPI auth-v1.3)

```
POST /v1/auth/customer/register
Body: { mobile, national_code, full_name }
201: { customer_id, access_status, kimia_bound }
400: jibit_mismatch | already_registered | validation
```

## Rules

- Jibit match = verification evidence only, NOT staff approval
- Default access_status remains Limited until `/v1/admin/registrations/{id}/approve`
- Kimia create still BLOCKED BY GROUND TRUTH
- No new endpoints, no invented prices

## Files

| Path | Role |
|------|------|
| `frontend/customer/src/api/auth.ts` | + `registerCustomer` |
| `frontend/customer/src/screens/auth/RegistrationScreen.tsx` | form UI |
| `frontend/customer/src/AppOtpFlow.tsx` | request → verify → register → done |
| `backend/public/otp-demo.html` | zero-build local vertical |

## Local verify

```bash
cd backend
php -S 127.0.0.1:8080 -t public public/router.php
# http://127.0.0.1:8080/otp-demo.html
# mobile 09121234567 + national_code 0012345678 (fake Jibit match)
```

Smoke: `php bin/http_smoke.php` still PASS=32 (no backend change required).
