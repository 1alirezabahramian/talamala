# Stage — Order Accept UI (settlement blocked)

POST /v1/customer/orders/accept { quote_id } + Idempotency-Key
→ settlement: blocked_by_ground_truth

Dev fixture: POST /v1/dev/seed-quote + X-Talamala-Dev: 1
Note on response: Fixture only — not a live price provider
