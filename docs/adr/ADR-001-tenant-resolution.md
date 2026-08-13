# ADR-001 — Tenant Resolution (Host-based, fail-closed)

**Status:** Accepted  
**Date:** 2026-08-12  

## Context

Multi-tenant white-label requires strict isolation. Client-supplied `tenant_id` is an attack vector.

## Decision

1. Resolve tenant exclusively from verified HTTP Host (or equivalent TLS SNI / domain binding).
2. Maintain `tenant_domains` with primary + allowed hosts, `is_active`, `is_verified`.
3. Unknown, inactive, or unverified hosts **fail closed** (403/404).
4. Bind resolved `Tenant` into request context for all downstream services.
5. All tenant-owned queries and idempotency keys are scoped by `tenant_id`.
6. Never accept client body/query `tenant_id` as authority on customer or backoffice routes.

## Consequences

- Positive: prevents cross-tenant leakage by design.
- Negative: local development requires Host header or hosts file mapping.

## Alternatives

- Header `X-Tenant-Id` — rejected (spoofable).
- Subdomain-only without verification flag — rejected (fail-open risk).
