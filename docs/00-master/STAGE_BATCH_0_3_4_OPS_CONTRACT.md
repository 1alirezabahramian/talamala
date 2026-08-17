# Stage batch — 0.3.4 ops identity + contract negatives

**Status:** CLOSED  
**Date:** 2026-08-17  
**Base HEAD:** d51e538 (0.3.3)

## Stages

### A) Service version on health endpoints
- `HealthController` reads repo `VERSION`
- healthz + readyz body include `version`
- http_smoke: `healthz_has_version` · `readyz_has_version`

### B) Order accept contract negative
- Missing Idempotency-Key → 422 `quote_id_and_idempotency_key_required`
- http_smoke PASS 51 → **54**

### C) ADR_INDEX living status
- Mark implemented ADRs Accepted/Partial without inventing GT

### D) OpenAPI auth description
- Document X-Correlation-Id + health version (info version 1.4.0)

### E) Makefile
- `make domain` → `php backend/bin/smoke.php`

### F) VERSION
- **0.3.4-phase1**

## Non-goals
No Kimia write · no pricing · no payment · no Delta · no durable tenant resolver

## Expected
```text
php backend/bin/http_smoke.php → PASS=54 FAIL=0
php backend/bin/check.php      → ALL CHECKS PASSED
curl -s -H 'Host: demo.local' …/healthz | grep version
```
