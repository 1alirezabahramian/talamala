# Stage — Host smoke VERSION pin + SHA record + backup note

**Base HEAD:** `557b09f`  
**Date:** 2026-08-20  
**Scope:** ops only — no Live Kimia, no new GT, no VERSION bump.

## Changes
1. `scripts/pilot_host_smoke.sh` — parse `/healthz` JSON; require `status=ok`; compare `version` to `VERSION` file; `/readyz` handles tenant fail-closed 400
2. `scripts/pilot_record.sh` — auto-fill `PILOT_SHA_RECORD.last.md` from git (no secrets)
3. `docs/00-master/PILOT_BACKUP.md` — SQLite backup/rollback for pilot Host
4. Makefile: `pilot-record`
5. Runbook / checklist / CURRENT_STATE / `.gitignore` for `PILOT_SHA_RECORD.last.md`

## Explicit non-goals
Live Kimia · GT · Settlement wire · VERSION bump
