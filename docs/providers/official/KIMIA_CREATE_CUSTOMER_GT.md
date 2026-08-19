# Kimia Create Customer — Ground Truth (GT-002)

**Status:** **PARTIAL — core live Swagger HTTP contract GROUNDED**  
**Live Swagger:** version `v1` · SHA-256 `be0fb0c6897015e238ef9dd58115b8502cf6f83feb868c91cff19377dfbb5cea`  
**Iran evidence run:** `32245857002` · runner source `e820915c097873323bc1dc2dead75d64eb032785`  
**Live Create:** **NOT EXECUTED / NOT AUTHORIZED**

## Grounded Create Account contract

| Item | Live Swagger Ground Truth |
|------|---------------------------|
| Method | `POST` |
| Path | `/api/account` |
| Summary | ایجاد حساب |
| Allowed account categories (operation description) | بنکداری، تکفروشی، امانات |
| Request schema | `AccountDto` |
| Swagger required properties | **none** (`[]`) |
| Success | HTTP `200` |
| Success body | primitive `integer/int32` = شناسه حساب ایجاد شده |
| Documented error | HTTP `400` = خطا در پردازش درخواست |

### AccountDto properties

All properties are optional/nullable in the current Swagger schema:

`AccountCode`, `AccountId`, `Address`, `Comment`, `DateBirthday`, `EconomicCode`, `IsVisible`, `Mobile`, `Name`, `NationalCode`, `PostalCode`, `ShopName`, `Tel`, `Type`.

Important constraints include: `Name/Mobile/ShopName/Tel/Address` max 255, `NationalCode/EconomicCode` max 20, `PostalCode` max 10, `Comment` max 500. `IsVisible` defaults to `true`.

`Type` description in live Swagger lists: `1=بنکداری`, `3=تکفروشی`, `5=سرمایه و برداشت`, `6=بانک`, `8=حساب داخلی`, `9=ذوب`, `10=امانات`, `11=هزینه`, `12=کارمندان`. The Create operation description narrows allowed Create categories to بنکداری / تکفروشی / امانات. Talamala must not infer additional Create types from the broader DTO description.

## Still NOT grounded

Swagger exposes only a generic HTTP `400`; it does **not** separately document:

- duplicate-customer detection/status/body semantics;
- validation error body/codes;
- authoritative post-create readback/reconciliation behavior.

Therefore GT-002 is **PARTIAL**, not fully closed. The HTTP method/path/request/success contract is grounded; duplicate/validation/readback semantics remain blocked pending separate evidence.

## Safety boundary

- No Live Create was performed while extracting this evidence.
- The runner forced `KIMIA_WRITE_VERIFY_ENABLE=0`; evidence ended with `Write not attempted (read_only)`.
- No registration/order/settlement wiring is authorized by this evidence.
- Any Live Create needs a **new, separate, explicit Owner authorization** plus exact test values.
- Settlement remains blocked by its own Ground Truth.

## Existing product binding — do not duplicate

- `CustomerRegistrationService` creates the Talamala customer; `kimiaAccountId: null` until bind.
- `bindKimiaAccount(...)` attaches an existing Kimia account id.
- Kimia Create remains a separate Integrations concern; this Ground Truth does not wire it into registration.
