# Stage — Pilot ops pack (≥20 files)

**Base HEAD:** `bfca513`  
**Date:** 2026-08-20  
**Scope:** ops + decimal smoke + SPA NoticeBanner — no Live Kimia, no new GT, no VERSION bump.

## Highlights
- `decimal_invariant_smoke` PASS=13 wired into check + preflight
- Gate matrix doc + script
- Troubleshooting / security baseline / command card
- Audit how-to + phase1 in-scope map + domain scorecard script
- Shared `NoticeBanner` customer + backoffice

## Integration hardening
- audit domain scorecard rejects stale/mismatched audit SHA
- decimal invariant preflight is pinned to exact `PASS=13 FAIL=0`
- gate matrix uses exact current smoke counts
- `DELIVERY.md` is transport-only and excluded from product commit
