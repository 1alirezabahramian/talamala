# Stage — Pilot Operator Pack

**Base HEAD:** `18da4a7`  
**Date:** 2026-08-20  
**Scope:** ops/docs + offline gates only — **no** Live Kimia, **no** new GT, **no** VERSION bump.

## Goal
Give operators one coherent pack: env posture, runbook, SHA record template, and a single `pilot-all` chain.

## New / updated
| Path | Role |
|------|------|
| `scripts/pilot_env_check.sh` | write-deny + production posture hints |
| `scripts/pilot_all.sh` | env-check → preflight → optional host smoke |
| `.env.pilot.example` | pilot Host template (Write=0) |
| `docs/00-master/PILOT_RUNBOOK.md` | end-to-end operator flow |
| `docs/00-master/PILOT_SHA_RECORD.template.md` | deployment record (no secrets) |
| `Makefile` | `pilot-env-check` · `pilot-all` |
| `docs/00-master/OPERATORS.md` | pilot section |
| `README.md` | pilot path pointer |
| `docs/00-master/RELEASE_NOTES_0.3.8-phase1.md` | pilot tooling note |
| `docs/00-master/PILOT_CHECKLIST.md` | env-check + runbook links |
| `docs/00-master/CURRENT_STATE.md` | Operator pilot-all |
| `docs/00-master/DEPLOY_PHASE1.md` | runbook pointer |

## Explicit non-goals
Live Kimia Write/Create · GT invent · Order/Settlement wire · VERSION bump
