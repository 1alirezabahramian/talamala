#!/usr/bin/env python3
"""Talamala Final Audit Agent v2 adapter.

Purpose:
- preserve the conservative base engine and veto model;
- enforce documented N/A denominator semantics;
- exclude pilot_scope != 'in' from ACCEPTED_FOR_PILOT scoring;
- bind explicit current-run evidence via PILOT_EVIDENCE_OVERLAY.json;
- never infer CI success from workflow-file existence.

This adapter does not change Ground Truth and cannot enable Kimia Write/Create,
Pricing, Settlement, Payment, or any other out-of-scope capability.
"""
from __future__ import annotations

import copy
import importlib.util
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
BASE_PATH = ROOT / "scripts/final_audit_agent.py"
OVERLAY = ROOT / "docs/audit/registry/PILOT_EVIDENCE_OVERLAY.json"

spec = importlib.util.spec_from_file_location("talamala_final_audit_base", BASE_PATH)
if spec is None or spec.loader is None:
    raise RuntimeError("cannot load base final audit agent")
base = importlib.util.module_from_spec(spec)
spec.loader.exec_module(base)

# Additional current-run gates. These remain fail-closed through base.run_gate().
base.SMOKE_MAP.update({
    "http_negative_smoke": ROOT / "backend/bin/http_negative_smoke.php",
    "pilot_env_check": ROOT / "scripts/pilot_env_check.sh",
    "pilot_preflight": ROOT / "scripts/pilot_preflight.sh",
})
base.GATE_COMMANDS.update({
    "http_negative_smoke": ["php", "backend/bin/http_negative_smoke.php"],
    "pilot_env_check": ["bash", "scripts/pilot_env_check.sh"],
    "pilot_preflight": ["bash", "scripts/pilot_preflight.sh"],
})


def _overlay_registry() -> Path:
    reg = json.loads(base.REG.read_text(encoding="utf-8"))
    keys = reg.get("item_schema") or []
    rows = reg.get("items") or []
    if rows and isinstance(rows[0], list):
        items = [dict(zip(keys, row)) for row in rows]
    else:
        items = rows

    ov = json.loads(OVERLAY.read_text(encoding="utf-8")) if OVERLAY.exists() else {}
    patches = ov.get("item_patches") or {}
    for it in items:
        patch = patches.get(it.get("id"))
        if not patch:
            continue
        evidence = copy.deepcopy(it.get("evidence") or {})
        smokes = list(evidence.get("smokes") or [])
        for smoke in patch.get("add_smokes") or []:
            if smoke not in smokes:
                smokes.append(smoke)
        if smokes:
            evidence["smokes"] = smokes
        it["evidence"] = evidence
        if "replace_capability_ids" in patch:
            it["capability_ids"] = list(patch["replace_capability_ids"])

    # Keep compactness irrelevant in the temporary runtime registry.
    reg["items"] = items
    tmp = ROOT / ".audit_registry_runtime.json"
    tmp.write_text(json.dumps(reg, ensure_ascii=False), encoding="utf-8")
    return tmp


_original_score_item = base.score_item


def _normalized_score_item(it: dict, ledger: str, gt_text: str, ctx: dict) -> dict:
    """Apply documented N/A semantics without granting missing evidence credit."""
    # ACCEPTED_FOR_PILOT is a bounded pilot verdict. Out/partial scope remains
    # visible but is N/A to the pilot score; unresolved work is not claimed done.
    if it.get("pilot_scope") != "in":
        return {
            "id": it["id"],
            "title": it["title"],
            "domain": it["domain"],
            "critical": it.get("critical", False),
            "pilot_scope": it.get("pilot_scope", "out"),
            "score": None,
            "color": "BLOCKED",
            "verdict": "BLOCKED",
            "gaps": ["excluded_from_bounded_phase1_pilot_score"],
            "dimensions": {},
        }

    r = _original_score_item(it, ledger, gt_text, ctx)
    if r.get("score") is None:
        return r

    evidence = it.get("evidence") or {}
    caps = it.get("capability_ids") or []
    gts = it.get("gt_blockers") or []
    dims = dict(r.get("dimensions") or {})
    maxes = ctx["scoring_max"]
    gates = ctx["gates"]

    # Ground-truth dimension: capability-ledger mapping is N/A when no
    # capability_ids are declared. Do not leave an undeclared subcomponent as 0.
    if "ground_truth" in dims:
        evidence_anchor = bool(evidence.get("paths") or evidence.get("smokes") or caps)
        earned = 0.0
        available = 0.0
        if evidence_anchor:
            available += 4
            if base.any_path(evidence.get("paths") or []) or evidence.get("smokes") or caps:
                earned += 4
        available += 3
        if any(base.path_exists(p) for p in [
            "docs/00-master/PHASE1_SAFE_CLOSURE.md",
            "docs/00-master/RELEASE_SCOPE_PHASE1.md",
            "docs/00-master/CURRENT_STATE.md",
        ]):
            earned += 3
        if caps:
            available += 2
            if all(base.ledger_status(c, ledger) for c in caps):
                earned += 2
        if not gts:
            available += 1
            earned += 1
        if available > 0 and evidence_anchor:
            dims["ground_truth"] = round(maxes["ground_truth"] * earned / available, 2)

    # Runtime dimension is composed only from evidence actually declared for
    # that checklist row. Missing path/cap/smoke subcomponents are N/A, not zero.
    declared_paths = list(evidence.get("paths") or [])
    declared_smokes = list(evidence.get("smokes") or [])
    available = 0.0
    earned = 0.0
    if declared_paths:
        available += 8
        if base.any_path(declared_paths):
            earned += 8
    if caps:
        available += 6
        statuses = [base.ledger_status(c, ledger) for c in caps]
        if any(st == "IMPLEMENTED" for st in statuses):
            earned += 6
        elif any(st == "PARTIAL" for st in statuses):
            earned += 3
    if declared_smokes:
        available += 6
        if any(gates.get(sm, {}).get("status") == "PASS" for sm in declared_smokes):
            earned += 6
    if "runtime" in dims and available > 0:
        dims["runtime"] = round(maxes["runtime"] * earned / available, 2)

    # Recompute item score from the dimensions that are actually applicable.
    denom = sum(maxes[k] for k in dims if k in maxes) or 1
    score = round(100.0 * sum(dims.values()) / denom, 1)

    # Preserve real capability-state caps for positive capabilities.
    for c in caps:
        st = base.ledger_status(c, ledger)
        if st == "PARTIAL":
            score = min(score, 88.0)
        elif st == "BLOCKED":
            return {
                **r,
                "score": None,
                "color": "BLOCKED",
                "verdict": "BLOCKED",
                "gaps": list(dict.fromkeys((r.get("gaps") or []) + [f"{c}=BLOCKED"])),
                "dimensions": dims,
            }

    color = base.color_for(score, False)
    gaps = [g for g in (r.get("gaps") or []) if g not in {"missing_paths"}]
    if declared_paths and not base.any_path(declared_paths):
        gaps.append("missing_paths")

    return {
        **r,
        "score": score,
        "color": color,
        "verdict": "ACCEPTED" if color == "GREEN" else "NOT_ACCEPTED",
        "gaps": list(dict.fromkeys(gaps))[:8],
        "dimensions": dims,
    }


base.score_item = _normalized_score_item
runtime_registry = _overlay_registry()
base.REG = runtime_registry

try:
    raise SystemExit(base.main())
finally:
    try:
        runtime_registry.unlink()
    except FileNotFoundError:
        pass
