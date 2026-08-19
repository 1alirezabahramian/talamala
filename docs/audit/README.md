# Talamala Final Audit System

**Final Audit Agent = Authority for Closure — only with current evidence.**

## Layout

| Path | Role |
|------|------|
| `FINAL_AUDIT_AGENT.md` | authority, evidence rules, verdict gate |
| `AUDIT_SCORING.md` | dimensions 0–100 + colors |
| `CRITICAL_VETOS.md` | hard-stop rules |
| `registry/CHECKLIST_REGISTRY.json` | 138 concrete items / 12 domains |
| `reports/` | generated outputs from the SHA being audited |

## Run

```bash
make final-audit
```

Exit `0` only for `ACCEPTED_FOR_PILOT`; otherwise non-zero.

## Principle

**Run evidence → score → color → vetos → verdict.**

A stale `latest` report, a test file that merely exists, or a workflow YAML that merely exists cannot prove closure.
