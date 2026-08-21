#!/usr/bin/env python3
"""Talamala Full Release Authority.

Strict wrapper around the existing Pilot Final Audit Agent.
It never weakens or replaces Pilot semantics. It first executes the Pilot Agent
on the same checkout, then applies a complete release-scope registry and RV-*.

Release-only evidence overrides are deliberately narrow and fail-closed. They
may resolve an exact Owner-grounded business-policy row without changing the
Pilot checklist or authorizing a broader live capability.
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
RELEASE_OVERLAY = ROOT / "docs/audit/registry/RELEASE_EVIDENCE_OVERLAY.json"
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


def _repo_path(value: object) -> Path | None:
    if not isinstance(value, str) or not value.strip():
        return None
    p = (ROOT / value).resolve()
    try:
        p.relative_to(ROOT.resolve())
    except ValueError:
        return None
    return p


def _validate_pricing_release_overrides() -> tuple[set[str], list[dict], list[dict]]:
    """Validate only the Cycle8 Owner-ratified GT-004 policy subset.

    No generic human-green mechanism exists here. Only FA-047/FA-049 are
    eligible, and FA-048 must remain explicitly excluded and live pricing false.
    """
    resolved: set[str] = set()
    evidence: list[dict] = []
    errors: list[dict] = []
    allowed = {"FA-047", "FA-049"}

    if not RELEASE_OVERLAY.is_file():
        return resolved, evidence, errors
    try:
        overlay = json.loads(RELEASE_OVERLAY.read_text(encoding="utf-8"))
    except Exception as exc:
        return resolved, evidence, [{"id": "RV-04", "reason": f"invalid release evidence overlay: {exc}"}]

    overrides = overlay.get("item_overrides")
    if not isinstance(overrides, dict):
        return resolved, evidence, [{"id": "RV-04", "reason": "release evidence item_overrides must be an object"}]
    unknown = sorted(set(overrides) - allowed)
    if unknown:
        errors.append({"id": "RV-04", "reason": "unauthorized release evidence override IDs: " + ",".join(unknown)})
    if "FA-048" not in (overlay.get("explicitly_not_overridden") or []):
        errors.append({"id": "RV-04", "reason": "FA-048 must be explicitly excluded from pricing policy override"})

    contract_path = ROOT / "docs/providers/official/PRICING_CONTRACT.json"
    if not contract_path.is_file():
        errors.append({"id": "RV-04", "reason": "pricing contract missing for release evidence overlay"})
        return resolved, evidence, errors
    try:
        contract = json.loads(contract_path.read_text(encoding="utf-8"))
    except Exception as exc:
        errors.append({"id": "RV-04", "reason": f"invalid pricing contract for release override: {exc}"})
        return resolved, evidence, errors

    common_ok = True
    common_checks = [
        (contract.get("status") == "PARTIALLY_GROUNDED", "pricing status must be PARTIALLY_GROUNDED"),
        (contract.get("live_pricing_authorized") is False, "live_pricing_authorized must remain false"),
        (contract.get("proposal_status") == "OWNER_RATIFIED_POLICY_SUBSET", "Owner ratification marker missing"),
        (isinstance(contract.get("remaining_unknowns"), list) and len(contract.get("remaining_unknowns")) > 0, "provider unknowns must remain visible"),
        ((contract.get("blocked_scope") or []) == ["FA-048 live price provider integration"], "FA-048 blocked scope must remain explicit"),
    ]
    provider = contract.get("provider") or {}
    common_checks.append((isinstance(provider, dict) and all(provider.get(k) is None for k in ["name", "official_api_doc_url_or_path", "auth_model", "freshness_sla_seconds", "failover_policy", "observed_at_field"]), "provider fields must remain unresolved"))
    for ok, reason in common_checks:
        if not ok:
            common_ok = False
            errors.append({"id": "RV-04", "reason": reason})

    for rid in sorted(allowed):
        row = overrides.get(rid)
        if not isinstance(row, dict):
            continue
        row_ok = common_ok
        if row.get("status") != "OWNER_RATIFIED":
            row_ok = False
            errors.append({"id": "RV-04", "reason": f"{rid} override status must be OWNER_RATIFIED"})
        if row.get("required_smoke") != "pricing_contract":
            row_ok = False
            errors.append({"id": "RV-04", "reason": f"{rid} must require pricing_contract smoke"})
        if row.get("live_pricing_authorized_must_be") is not False:
            row_ok = False
            errors.append({"id": "RV-04", "reason": f"{rid} override must require live pricing false"})
        ep = _repo_path(row.get("evidence_path"))
        cp = _repo_path(row.get("contract_path"))
        if ep is None or not ep.is_file():
            row_ok = False
            errors.append({"id": "RV-04", "reason": f"{rid} ratification evidence missing"})
        if cp != contract_path.resolve():
            row_ok = False
            errors.append({"id": "RV-04", "reason": f"{rid} contract_path mismatch"})

        if rid == "FA-047":
            q = contract.get("quote_policy") or {}
            exact = q.get("default_ttl_seconds") == 120 and q.get("max_ttl_seconds") == 300 and q.get("freeze_on_accept") is True and q.get("accepted_order_behavior") == "preserve immutable accepted quote snapshot; do not re-price"
            if not exact:
                row_ok = False
                errors.append({"id": "RV-04", "reason": "FA-047 ratified TTL/freeze values mismatch"})
        if rid == "FA-049":
            c = contract.get("coefficients") or {}
            r = contract.get("rounding") or {}
            exact = c.get("x") == "1" and c.get("y") == "0" and c.get("z") == "0" and c.get("application_order") == "adjusted_unit = (reference_unit * x) + y + z" and r.get("mode") == "half_up" and r.get("scale_rial") == 0 and r.get("scale_total_rial") == 0 and r.get("scale_quantity") == 4
            if not exact:
                row_ok = False
                errors.append({"id": "RV-04", "reason": "FA-049 ratified coefficient/rounding values mismatch"})

        if row_ok:
            resolved.add(rid)
            evidence.append({"id": rid, "authority": "OWNER_RATIFIED", "scope": row.get("scope"), "evidence_path": row.get("evidence_path")})

    return resolved, evidence, errors


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

    resolved_overrides, override_evidence, override_errors = _validate_pricing_release_overrides()
    vetos.extend(override_errors)

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
        if rid in resolved_overrides:
            green += 1
            continue
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
        "release_evidence_overrides": override_evidence,
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
        f"**release_deferred:** {len(deferred)}  ",
        f"**release_evidence_overrides:** {len(override_evidence)}  ", "",
        "### Release Vetos", "",
    ]
    lines.extend(["NONE"] if not vetos else [f"- `{v.get('id')}`: {v.get('reason')}" for v in vetos])
    if override_evidence:
        lines += ["", "### Release Evidence Overrides", ""]
        lines.extend([f"- `{x.get('id')}` OWNER_RATIFIED — {x.get('scope')}" for x in override_evidence])
    lines += ["", "See `docs/audit/RELEASE_SCOPE_FULL.md` and `CRITICAL_VETOS.md`.", ""]
    board = "\n".join(lines)
    for target in dict.fromkeys([REPORT_MD_LATEST, exact_md]):
        if target.is_file():
            target.write_text(target.read_text(encoding="utf-8") + board, encoding="utf-8")

    print("======== RELEASE AUTHORITY ========")
    print(f"SHA={sha[:7]} release_verdict={verdict}")
    print(f"required_green={green}/{len(required)} blocked_rows={blocked_count} mean={mean}")
    print(f"release_vetos={len(vetos)} deferred={len(deferred)} overrides={len(override_evidence)}")
    return 0 if verdict == "ACCEPTED_FOR_RELEASE" else 1


if __name__ == "__main__":
    raise SystemExit(main())
