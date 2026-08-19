# Kimia Create Customer — Ground Truth (GT-002)

**Status:** **NOT GROUNDED** for path/body/errors  
**Live Swagger (Iran preflight):** version `v1` · SHA-256 `be0fb0c6897015e238ef9dd58115b8502cf6f83feb868c91cff19377dfbb5cea`  
**Raw swagger.json in repo:** **absent**  
**Sandbox reachability to Kimia host:** **no** (must extract on `talamala-kimia-runner` / Owner)

## Why implementation is fail-closed

| Item | State |
|------|--------|
| Exact POST path for create customer/account | **UNKNOWN** |
| Request schema (required/optional) | **UNKNOWN** |
| Success response (id field name, shape) | **UNKNOWN** |
| Duplicate / validation error codes | **UNKNOWN** |
| Read-back rule after create | Domain says readback required; **not proven for Create** |

SOURCE_REGISTER: *Create Customer exact contract + duplicate semantics | UNKNOWN | GT-002*

Inventing `POST /api/account` body fields would violate Talamala non-negotiables.

## Confirmed related Read (not Create)

| Method | Path | Notes |
|--------|------|--------|
| GET | `/api/account` | query `Type` (not accountType) |
| GET | `/api/account/groups` | query `accountType` |

Observed **list** row shape in test double only (not create schema): `AccountId`, `Name`, `Type`, `Mobile`.

## Extraction checklist (Owner / Chabokan runner)

On runner with live Swagger:

```bash
php backend/bin/kimia_verify_runner.php catalog
# inspect var/kimia-verify/swagger_live.json + swagger_catalog.json
```

Fill only from live OpenAPI components/paths (no guess): HTTP method, path, operationId, request schema, required/optional properties, property types/enums, success HTTP status/body id, duplicate and validation errors.

Then archive Swagger excerpt/hash. Any live Create requires a **separate explicit Owner authorization**.

## Existing product binding — do not duplicate

- `CustomerRegistrationService` creates Talamala customer; `kimiaAccountId: null` until bind.
- `bindKimiaAccount(...)` attaches an existing Kimia account id.
- Kimia Create is a separate Integrations concern once GT-002 is closed.

## ACL until grounded

- `KimiaCreateCustomerContract::isGrounded() === false` by default.
- `HttpKimiaCreateCustomerClient` refuses POST while ungrounded.
- Grounded HTTP contract must be `POST` with a relative Kimia path only; external/full URLs are rejected.
- `FakeKimiaCreateCustomerClient` is test-only with explicit fixture contract.
- **No live Create in this delivery.**
