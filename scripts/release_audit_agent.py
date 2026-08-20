#!/usr/bin/env python3
"""Talamala Full Release Authority.

Strict wrapper around the existing Pilot Final Audit Agent.
It never weakens or replaces Pilot semantics. It first executes the Pilot Agent
on the same checkout, then applies a complete release-scope registry and RV-*.
"""
from __future__ import annotations

import json
import os
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
PILOT_AGENT = ROOT / "scripts/final_audit_agent_v2.py"
CHECKLIST = ROOT / "docs/audit/registry/CHECKLIST_REGISTRY.json"
RELEASE_REG = ROOT / "docs/audit/registry/RELEASE_SCOPE_REGISTRY.json"
REPORT_JSON = ROOT / "docs/audit/reports/AUDIT_REPORT_latest.json"
REPORT_MD_LATEST = ROOT / "docs/audit/reports/AUDIT_REPORT_latest.md"


def _items_from_registry(path: Path) -> list[dict]:
    obj = json.loads(path.read_text(encoding="utf-8"))
    rows = obj.get("items") or []
    keys = obj.get("item_schema") or []
    if rows and isinstance(rows[0], list):
        return [dict(zip(keys, row)) for row in rows]
    return [row for row in rows if isinstance(row, dict)]


def _validate_release_registry(rel: dict) -> tuple[list[str], list[str], list[dict]]:
    errors: list[dict] = []
    required = rel.get("release_required")
    deferred = rel.get("release_deferred")
    if not isinstance(required, list) or not all(isinstance(x, str) for x in required):
        return [], [], [{"id": "RV-04", "reason": "release_required must be a list of IDs"}]
    if not isinstance(deferred, list) or not all(isinstance(x, str) for x in deferred):
        return [], [], [{"id": "RV-04", "reason": "release_deferred must be a list of IDs"}]
    if not required:
        errors.append({"id": "RV-04", "reason": "release_required is empty"})
    if len(required) != len(set(required)):
        errors.append({"id": "RV-04", "reason": "duplicate IDs in release_required"})
    if len(deferred) != len(set(deferred)):
        errors.append({"id": "RV-04", "reason": "duplicate IDs in release_deferred"})
    overlap = sorted(set(required) & set(deferred))
    if overlap:
        errors.append({"id": "RV-04", "reason": "required/deferred overlap: " + ",".join(overlap)})

    checklist_ids = {str(x.get("id")) for x in _items_from_registry(CHECKLIST) if x.get("id")}
    classified = set(required) | set(deferred)
    unknown = sorted(classified - checklist_ids)
    missing = sorted(checklist_ids - classified)
    if unknown:
        errors.append({"id": "RV-04", "reason": "unknown release IDs: " + ",".join(unknown)})
    if missing:
        errors.append({"id": "RV-04", "reason": "unclassified checklist IDs: " + ",".join(missing)})
    if len(classified) != len(checklist_ids):
        errors.append({
            "id": "RV-04",
            "reason": f"partition mismatch classified={len(classified)} checklist={len(checklist_ids)}",
        })
    return list(required), list(deferred), errors


def _dedupe(vetos: list[dict]) -> list[dict]:
    seen: set[str] = set()
    out: list[dict] = []
    for v in vetos:
        key = f"{v.get('id')}:{v.get('reason')}"
        if key not in seen:
            seen.add(key)
            out.append(v)
    return out


def main() -> int:
    env = os.environ.copy()
    env.pop("TALAMALA_AUDIT_MODE", None)
    pilot = subprocess.run([sys.executable, str(PILOT_AGENT)], cwd=ROOT, env=env, check=False)
    if not REPORT_JSON.is_file():
        print("RELEASE AUTHORITY: pilot report missing")
        return 2

    report = json.loads(REPORT_JSON.read_text(encoding="utf-8"))
    vetos: list[dict] = []
    blockers: list[dict] = []
    required: list[str] = []
    deferred: list[str] = []

    if not RELEASE_REG.is_file() or not CHECKLIST.is_file():
        vetos.append({"id": "RV-04", "reason": "release/checklist registry missing"})
    else:
        try:
            rel = json.loads(RELEASE_REG.read_text(encoding="utf-8"))
            required, deferred, errors = _validate_release_registry(rel)
            vetos.extend(errors)
        except Exception as exc:  # fail closed
            vetos.append({"id": "RV-04", "reason": f"invalid release registry: {exc}"})

    by_id = {row.get("id"): row for row in report.get("items") or [] if row.get("id")}
    green = 0
    blocked_count = 0
    scores: list[float] = []
    for rid in required:
        row = by_id.get(rid)
        if row is None:
            blockers.append({"id": rid, "color": "MISSING", "reason": "not in audit results"})
            vetos.append({"id": "RV-01", "reason": f"{rid} missing from results"})
            continue
        color = str(row.get("color") or "RED")
        critical = bool(row.get("critical"))
        if color == "GREEN":
            green += 1
            if row.get("score") is not None:
                scores.append(float(row["score"]))
        else:
            if color == "BLOCKED":
                blocked_count += 1
            blockers.append({
                "id": rid,
                "color": color,
                "critical": critical,
                "title": row.get("title"),
            })
            vetos.append({
                "id": "RV-02" if critical else "RV-01",
                "reason": f"{rid} color={color}" + (" critical" if critical else ""),
            })

    # Release is strictly stronger: every Pilot CV-* remains active.
    for v in report.get("critical_vetos") or []:
        vetos.append({"id": v.get("id", "CV"), "reason": f"pilot_veto:{v.get('reason')}"})
    if any(v.get("id") == "CV-02" for v in report.get("critical_vetos") or []):
        vetos.append({"id": "RV-03", "reason": "ungrounded financial claim (CV-02 active)"})

    vetos = _dedupe(vetos)
    mean = round(sum(scores) / len(scores), 1) if scores else 0.0
    pilot_ok = report.get("final_verdict") == "ACCEPTED_FOR_PILOT" and pilot.returncode == 0

    if any(v.get("id") in {"CV-02", "RV-03"} for v in vetos):
        verdict = "BLOCKED_FINANCIAL_GT"
    elif any(v.get("id") in {"CV-01", "CV-08"} for v in vetos):
        verdict = "REJECTED_SECURITY"
    elif vetos or not pilot_ok or mean < 90 or green != len(required):
        verdict = "NOT_READY_FOR_RELEASE"
    else:
        verdict = "ACCEPTED_FOR_RELEASE"

    release = {
        "audit_mode": "release",
        "release_verdict": verdict,
        "release_vetos": vetos,
        "release_blockers": blockers,
        "release_required_count": len(required),
        "release_required_green": green,
        "release_required_blocked": blocked_count,
        "release_deferred_count": len(deferred),
        "release_mean_score": mean,
        "final_verdict_authority": "release",
        "authoritative_verdict": verdict,
    }
    report.update(release)
    REPORT_JSON.write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")

    sha = str(report.get("git_sha") or "unknown")
    exact_md = ROOT / "docs/audit/reports" / f"AUDIT_REPORT_{sha[:7]}.md"
    lines = [
        "", "## Release Authority", "",
        f"**release_verdict:** `{verdict}`  ",
        f"**release_required:** {green}/{len(required)} GREEN  ",
        f"**release_blocked_rows:** {blocked_count}  ",
        f"**release_mean_score:** {mean}  ",
        f"**release_deferred:** {len(deferred)}  ", "",
        "### Release Vetos", "",
    ]
    lines.extend(["NONE"] if not vetos else [f"- `{v.get('id')}`: {v.get('reason')}" for v in vetos])
    lines += ["", "See `docs/audit/RELEASE_SCOPE_FULL.md` and `CRITICAL_VETOS.md`.", ""]
    board = "\n".join(lines)
    for target in dict.fromkeys([REPORT_MD_LATEST, exact_md]):
        if target.is_file():
            target.write_text(target.read_text(encoding="utf-8") + board, encoding="utf-8")

    print("======== RELEASE AUTHORITY ========")
    print(f"SHA={sha[:7]} release_verdict={verdict}")
    print(f"required_green={green}/{len(required)} blocked_rows={blocked_count} mean={mean}")
    print(f"release_vetos={len(vetos)} deferred={len(deferred)}")
    return 0 if verdict == "ACCEPTED_FOR_RELEASE" else 1


if __name__ == "__main__":
    raise SystemExit(main())
