# ADR-015 — Branching, PR & Exact-SHA CI Policy

**Status:** Proposed  
**Date:** 2026-08-12  
**Deciders:** Project Owner + Principal Architect  

## Context
Talamala requires strict traceability. Old SHA results must never be used as proof for a new SHA. Every merge must be auditable.

## Decision
1. **Default branch:** `main` (protected).
2. **Feature work:** short-lived branches named `stage-N/short-description` or `feat/short-description`.
3. **Pull Requests are mandatory** for any change that touches code, OpenAPI, migrations or security-sensitive docs.
4. **CI must run on the exact candidate SHA** and report:
   - Backend unit + feature tests
   - Fresh database migration
   - Frontend typecheck / build
   - OpenAPI validation + contract tests (when present)
   - Lint / static analysis
   - Dependency vulnerability audit
   - Secret scan
   - Tenant isolation / adversarial tests (when present)
5. **Merge is allowed only when CI is green on that SHA.**
6. **Release evidence** must record the exact SHA, CI run ID and test summary.
7. No force-push to `main`. No direct commits to `main` except emergency hotfixes that still go through a PR afterwards.

## Consequences
- Positive: full audit trail, no “it worked on my machine / previous SHA”.
- Negative: slightly slower early velocity; acceptable for a financial system.

## Alternatives Considered
- Direct commits to main — rejected (no review, no SHA evidence).
- Long-lived develop branch — rejected (adds merge complexity without benefit at current scale).
