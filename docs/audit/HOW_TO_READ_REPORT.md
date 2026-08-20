# How to read AUDIT_REPORT_latest

1. **final_verdict** — only `ACCEPTED_FOR_PILOT` is green for pilot closure.
2. **final_audit_score** — mean of scored items; high score ≠ accepted if Critical Veto active.
3. **counts** — GREEN/YELLOW/ORANGE/RED/BLOCKED item counts.
4. **critical_vetos** — CV-01…CV-09; any active blocks acceptance.
5. **exact_sha_ci** — must be ok for authoritative run.
6. **items[]** — per FA-xxx score, color, gaps.

See CLOSURE_POLICY.md (No Human Green).
