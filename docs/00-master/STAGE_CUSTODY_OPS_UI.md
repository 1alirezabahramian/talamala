# Stage — Staff CustodyOps UI

receive → ready_for_pickup → delivered

## Contracts (Kernel existing)

```
POST /v1/admin/custody/receive
  body: { customer_id, description, weight_grams, fineness? }
  201: { id, status, weight_grams }

POST /v1/admin/custody/{id}/ready
  200: { id, status }

POST /v1/admin/custody/{id}/deliver
  200: { id, status }
```

weight_grams: decimal string only.
