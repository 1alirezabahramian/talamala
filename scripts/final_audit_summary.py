#!/usr/bin/env python3
"""Print Final Audit color/score board from AUDIT_REPORT_latest.json (read-only)."""
from __future__ import annotations

import json
import sys
import subprocess
from collections import defaultdict
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
REPORT = ROOT / "docs/audit/reports/AUDIT_REPORT_latest.json"

EMOJI = {
    "GREEN": "🟢",
    "YELLOW": "🟡",
    "ORANGE": "🟠",
    "RED": "🔴",
    "BLOCKED": "⚫",
}


def main() -> int:
    if not REPORT.exists():
        print("FAIL: no report — run: make final-audit")
        return 2
    r = json.loads(REPORT.read_text(encoding="utf-8"))
    counts = r.get("counts") or {}
    score = r.get("final_audit_score")
    verdict = r.get("final_verdict")
    report_sha = str(r.get("git_sha") or "")
    try:
        head = subprocess.check_output(["git", "rev-parse", "HEAD"], cwd=ROOT, text=True, stderr=subprocess.DEVNULL).strip()
    except Exception:
        print("FAIL: cannot resolve current git HEAD")
        return 2
    if not report_sha or report_sha != head:
        print(f"FAIL: stale audit report — report SHA={report_sha or '<missing>'} current HEAD={head}")
        print("run: make final-audit")
        return 2
    sha = report_sha[:12]
    ver = r.get("version")
    vetos = r.get("critical_vetos") or []
    ci = r.get("exact_sha_ci") or r.get("ci") or {}

    print("======== FINAL AUDIT SUMMARY ========")
    print(f"SHA:     {sha}")
    print(f"VERSION: {ver}")
    print(f"Score:   {score} / 100")
    print(f"Verdict: {verdict}")
    if isinstance(ci, dict):
        print(f"CI:      ok={ci.get('ok')} reason={ci.get('reason')}")
    print("")
    print("Colors:")
    for k in ("GREEN", "YELLOW", "ORANGE", "RED", "BLOCKED"):
        print(f"  {EMOJI.get(k, '')} {k:8} {counts.get(k, 0)}")
    print("")
    if vetos:
        print("Critical Vetos:")
        for v in vetos:
            print(f"  - {v.get('id')}: {v.get('reason')}")
    else:
        print("Critical Vetos: none")
    print("")
    ds: dict[str, list[float]] = defaultdict(list)
    for it in r.get("items") or []:
        if it.get("score") is not None:
            ds[it.get("domain") or "?"].append(float(it["score"]))
    print("Domain averages (scored):")
    for d in sorted(ds.keys()):
        avg = sum(ds[d]) / len(ds[d])
        print(f"  {d:22} {avg:5.1f}  n={len(ds[d])}")
    print("")
    print("No Human Green: only ACCEPTED_FOR_PILOT on this SHA is green.")
    return 0 if verdict == "ACCEPTED_FOR_PILOT" else 1


if __name__ == "__main__":
    raise SystemExit(main())
