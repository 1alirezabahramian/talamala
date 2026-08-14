# Stage — Customer Custody Read UI

**Scope:** GET /v1/customer/custody only (Amanat list).

## Contract (existing Kernel)

```
GET /v1/customer/custody
→ { items: [ { id, description, weight_grams, status } ] }
```

weight_grams: decimal string. No client math.

## Out of scope

- POST receive / ready / deliver (staff — not this ZIP)
- price / quote / order
- Kimia write

## Commit files only

```
frontend/customer/src/api/custody.ts
frontend/customer/src/screens/custody/CustodyListScreen.tsx
backend/public/custody-demo.html
docs/00-master/STAGE_CUSTODY_READ_UI.md
```
