# Capability Ledger (Talamala greenfield)

Statuses: IMPLEMENTED (skeleton/fake) | PARTIAL | BLOCKED | NOT_STARTED

**Phase-1 Safe Closure:** FROZEN at VERSION `0.3.8-phase1` (SHA `f1e9eb2`). No new domain capability without archived Ground Truth. See `docs/00-master/PHASE1_SAFE_CLOSURE.md`.


| ID | Domain | Capability | Status | Notes |
|----|--------|------------|--------|-------|
| CAP-001 | Tenant | Host resolution fail-closed | IMPLEMENTED | ADR-001 · InMemory seed hosts |
| CAP-002 | Audit | Audit events | IMPLEMENTED | SQLite Persistence-2 |
| CAP-003 | Idempotency | Tenant-scoped keys | IMPLEMENTED | SQLite Persistence-2 |
| CAP-004 | Identity | Customer OTP | IMPLEMENTED | Fake SMS · rate limit 5/300s |
| CAP-005 | Identity | Staff auth + rotation | IMPLEMENTED | |
| CAP-006 | Identity | Registration + Jibit gate | IMPLEMENTED | Fake Jibit |
| CAP-007 | Identity | Admin approval queue | IMPLEMENTED | |
| CAP-008 | Kimia | Read client | IMPLEMENTED | Http + Fake |
| CAP-009 | Kimia | Assets Toman mapping | IMPLEMENTED | |
| CAP-010 | Kimia | Write buy/sell gold + receive/pay cash | PARTIAL | Live Batch V1 + shared input guards + contract smoke + Application read-before/read-after; Create Customer / Order/Settlement wiring still BLOCKED |
| CAP-011 | Quote | Immutable quote model | IMPLEMENTED | |
| CAP-012 | Quote | Price provider | BLOCKED | Ground truth |
| CAP-013 | Order | Accept from quote | IMPLEMENTED | |
| CAP-014 | Order | Settlement write | BLOCKED | Ground truth |
| CAP-015 | Custody | Lifecycle | IMPLEMENTED | Talamala truth |
| CAP-016 | API | OpenAPI auth/customer/backoffice | IMPLEMENTED | Runtime parity + CI gate |
| CAP-017 | Frontend | Customer screens | PARTIAL | Thin Vite · structure only |
| CAP-018 | Frontend | Backoffice screens | PARTIAL | Thin Vite · structure only |
| CAP-019 | Payment | Gateways | BLOCKED | Ground truth |
| CAP-020 | Release | Exact-SHA CI | IMPLEMENTED | Fail-closed smoke gates (http 78) |
| CAP-021 | Security | Session↔tenant isolation | IMPLEMENTED | Customer + staff · 403 mismatch |
| CAP-022 | Security | Minimal CSP on static HTML | IMPLEMENTED | unsafe-inline for zero-build demos |
| CAP-023 | Security | Permissions-Policy baseline | IMPLEMENTED | API + HTML · camera/mic/geo off |
| CAP-024 | Ops | robots.txt demo isolation | IMPLEMENTED | blocks demos /app /v1/dev |
| CAP-025 | Ops | X-Correlation-Id on API | IMPLEMENTED | index.php always emits |
| CAP-026 | Security | Cross-Domain-Policies none | IMPLEMENTED | API + HTML |
| CAP-027 | Ops | healthz/readyz version | IMPLEMENTED | from VERSION file |
| CAP-028 | API | Order accept idempotency required | IMPLEMENTED | 422 without key |
| CAP-029 | Identity | Auth contract negatives gated | IMPLEMENTED | staff/OTP http_smoke |
| CAP-030 | Frontend | X-Correlation-Id on API client | IMPLEMENTED | customer + backoffice |
| CAP-031 | Identity | Register validation/duplicate gated | IMPLEMENTED | http_smoke |
| CAP-032 | Identity | Staff password rotate negatives | IMPLEMENTED | weak/invalid current |
| CAP-033 | Custody | Receive validation/auth gated | IMPLEMENTED | 401/422 http_smoke |
| CAP-034 | Order | quote_not_found 409 gated | IMPLEMENTED | http_smoke |
| CAP-035 | Ops | healthz/readyz version | IMPLEMENTED | VERSION file |

Do not copy GoldPlatform completion percentages.

## Persistence notes (2026-08-16)
- Persistence-1: Customer / Custody / Quote / Order → SQLite PDO
- Persistence-2: Sessions / Idempotency / Audit / OTP rate limiter → SQLite PDO
- Tenant resolver: still InMemory (host seed only; durable multi-tenant resolver deferred)

## Release notes (2026-08-16)
- Frontend typecheck optional in CI (`continue-on-error`)
- http_smoke PASS=78 FAIL=0
- spa_router_smoke exact PASS=6 in CI
- VERSION 0.3.8-phase1 · cors 13 · landing 18 · Permissions-Policy
