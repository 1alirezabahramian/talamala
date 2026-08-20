#!/usr/bin/env bash
# One-screen pilot status (no secrets, no Live Kimia).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "======== Talamala pilot status ========"
echo "DATE=$(date -u +%Y-%m-%dT%H:%M:%SZ)"
echo "VERSION=$(tr -d '[:space:]' < VERSION 2>/dev/null || echo unknown)"
if git rev-parse HEAD >/dev/null 2>&1; then
  echo "HEAD=$(git rev-parse HEAD)"
  echo "SHORT=$(git rev-parse --short HEAD)"
  echo "BRANCH=$(git rev-parse --abbrev-ref HEAD)"
else
  echo "HEAD=unknown"
fi

if grep -qE 'KIMIA_WRITE_VERIFY_ENABLE=0' .env.example 2>/dev/null; then
  echo "WRITE_DENY_TEMPLATE=ok (.env.example =0)"
else
  echo "WRITE_DENY_TEMPLATE=missing"
fi

if [[ -f docs/audit/reports/AUDIT_REPORT_latest.json ]]; then
  python3 - <<'PY'
import json
import subprocess
from pathlib import Path
r=json.loads(Path("docs/audit/reports/AUDIT_REPORT_latest.json").read_text())
head=subprocess.check_output(["git","rev-parse","HEAD"], text=True).strip()
report_sha=str(r.get("git_sha") or "")
print(f"AUDIT_REPORT_SHA={report_sha or 'missing'}")
if report_sha != head:
    print("AUDIT_REPORT_MATCH=stale")
    print("AUDIT_VERDICT=UNVERIFIED_FOR_CURRENT_HEAD")
    print("AUDIT_HINT=run make final-audit on this HEAD")
else:
    print("AUDIT_REPORT_MATCH=current")
    print(f"AUDIT_SCORE={r.get('final_audit_score')}")
    print(f"AUDIT_VERDICT={r.get('final_verdict')}")
    c=r.get('counts') or {}
    print(f"AUDIT_COLORS=G{c.get('GREEN',0)}/Y{c.get('YELLOW',0)}/O{c.get('ORANGE',0)}/R{c.get('RED',0)}/B{c.get('BLOCKED',0)}")
    vetos=r.get('critical_vetos') or []
    print(f"AUDIT_VETOS={len(vetos)}")
    for v in vetos:
        print(f"  VETO {v.get('id')}: {v.get('reason')}")
PY
else
  echo "AUDIT_REPORT=missing (run make final-audit)"
fi

echo "HINT: make pilot-preflight · make release-build · make final-audit"
echo "HINT: CI attestation for closure: TALAMALA_AUDIT_CI_SHA + TALAMALA_AUDIT_CI_STATUS=success"
