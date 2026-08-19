#!/usr/bin/env python3
"""Talamala Final Audit Agent — conservative evidence-based closure gate.

Key rule: file existence is implementation evidence, not proof that a test or CI passed.
A project-level ACCEPTED_FOR_PILOT verdict requires current-run gate evidence plus an
exact-SHA CI attestation for the same git HEAD.
"""
from __future__ import annotations

import json
import os
import re
import subprocess
import sys
from collections import defaultdict
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
REG = ROOT / "docs/audit/registry/CHECKLIST_REGISTRY.json"
REPORT_DIR = ROOT / "docs/audit/reports"
LEDGER = ROOT / "docs/traceability/CAPABILITY_LEDGER.md"
GT = ROOT / "docs/00-master/GROUND_TRUTH_BLOCKERS.md"

SMOKE_MAP = {
    "domain_smoke": ROOT / "backend/bin/smoke.php",
    "http_smoke": ROOT / "backend/bin/http_smoke.php",
    "persist_smoke": ROOT / "backend/bin/persist_smoke.php",
    "cors_smoke": ROOT / "backend/bin/cors_smoke.php",
    "logger_smoke": ROOT / "backend/bin/logger_smoke.php",
    "maintenance_smoke": ROOT / "backend/bin/maintenance_smoke.php",
    "landing_smoke": ROOT / "backend/bin/landing_smoke.php",
    "spa_router_smoke": ROOT / "backend/bin/spa_router_smoke.php",
    "openapi_parity": ROOT / "backend/bin/openapi_parity_check.php",
    "kimia_write_contract": ROOT / "backend/bin/kimia_write_contract_smoke.php",
    "kimia_create_customer_contract": ROOT / "backend/bin/kimia_create_customer_contract_smoke.php",
}

GATE_COMMANDS = {
    "domain_smoke": ["php", "backend/bin/smoke.php"],
    "http_smoke": ["php", "backend/bin/http_smoke.php"],
    "persist_smoke": ["php", "backend/bin/persist_smoke.php"],
    "cors_smoke": ["php", "backend/bin/cors_smoke.php"],
    "logger_smoke": ["php", "backend/bin/logger_smoke.php"],
    "maintenance_smoke": ["php", "backend/bin/maintenance_smoke.php"],
    "landing_smoke": ["php", "backend/bin/landing_smoke.php"],
    "spa_router_smoke": ["php", "backend/bin/spa_router_smoke.php"],
    "openapi_parity": ["php", "backend/bin/openapi_parity_check.php"],
    "kimia_write_contract": ["php", "backend/bin/kimia_write_contract_smoke.php"],
    "kimia_create_customer_contract": ["php", "backend/bin/kimia_create_customer_contract_smoke.php"],
}

SCRIPT_MAP = {
    "pilot_preflight": ROOT / "scripts/pilot_preflight.sh",
    "pilot_env_check": ROOT / "scripts/pilot_env_check.sh",
    "pilot_host_smoke": ROOT / "scripts/pilot_host_smoke.sh",
    "pilot_record": ROOT / "scripts/pilot_record.sh",
    "release_build": ROOT / "scripts/release_build.sh",
}


def read_text(p: Path) -> str:
    try:
        return p.read_text(encoding="utf-8", errors="replace")
    except Exception:
        return ""


def git_head() -> str:
    if not (ROOT / ".git").exists():
        return "unknown"
    try:
        return subprocess.check_output(
            ["git", "rev-parse", "HEAD"], cwd=ROOT, text=True, stderr=subprocess.DEVNULL
        ).strip()
    except Exception:
        return "unknown"


def ledger_status(cap_id: str, ledger: str) -> str | None:
    for line in ledger.splitlines():
        if f"| {cap_id} |" in line or line.startswith(f"| {cap_id} |"):
            parts = [x.strip() for x in line.split("|")]
            parts = [x for x in parts if x]
            if len(parts) >= 4:
                return parts[3].upper()
    return None


def path_exists(rel: str) -> bool:
    return (ROOT / rel).exists()


def any_path(paths: list[str]) -> bool:
    return any(path_exists(p) for p in paths)


def openapi_has(files: list[str]) -> bool:
    for f in files:
        p = ROOT / f if f.startswith("openapi/") else ROOT / "openapi" / f
        if not p.exists():
            p = ROOT / "openapi" / Path(f).name
        if not p.exists():
            return False
    return True


def color_for(score: float | None, blocked: bool) -> str:
    if blocked or score is None:
        return "BLOCKED"
    if score >= 90:
        return "GREEN"
    if score >= 70:
        return "YELLOW"
    if score >= 40:
        return "ORANGE"
    return "RED"


def emoji(c: str) -> str:
    return {
        "GREEN": "🟢",
        "YELLOW": "🟡",
        "ORANGE": "🟠",
        "RED": "🔴",
        "BLOCKED": "⚫",
    }.get(c, "⚪")


def run_gate(name: str) -> dict:
    cmd = GATE_COMMANDS.get(name)
    path = SMOKE_MAP.get(name)
    if not cmd or not path or not path.exists():
        return {"status": "MISSING", "rc": None, "tail": "gate file missing"}
    try:
        cp = subprocess.run(
            cmd, cwd=ROOT, text=True, stdout=subprocess.PIPE, stderr=subprocess.STDOUT,
            timeout=120, check=False
        )
        out = cp.stdout or ""
        return {
            "status": "PASS" if cp.returncode == 0 else "FAIL",
            "rc": cp.returncode,
            "tail": "\n".join(out.splitlines()[-8:]),
        }
    except FileNotFoundError as e:
        return {"status": "MISSING", "rc": None, "tail": str(e)}
    except subprocess.TimeoutExpired:
        return {"status": "FAIL", "rc": 124, "tail": "timeout"}
    except Exception as e:
        return {"status": "FAIL", "rc": None, "tail": str(e)}


def collect_gate_results(reg: dict) -> dict:
    names = sorted({
        sm
        for it in reg.get("items", [])
        for sm in (it.get("evidence") or {}).get("smokes", [])
        if sm in GATE_COMMANDS
    })
    return {name: run_gate(name) for name in names}


def exact_sha_attestation(head: str) -> dict:
    """Require explicit CI attestation for this exact checkout.

    Supported environment:
      TALAMALA_AUDIT_CI_SHA=<40-char HEAD>
      TALAMALA_AUDIT_CI_STATUS=success
    """
    att_sha = os.environ.get("TALAMALA_AUDIT_CI_SHA", "").strip()
    att_status = os.environ.get("TALAMALA_AUDIT_CI_STATUS", "").strip().lower()
    ok = head != "unknown" and att_sha == head and att_status == "success"
    return {
        "ok": ok,
        "head": head,
        "attested_sha": att_sha or None,
        "attested_status": att_status or None,
        "reason": None if ok else "missing/mismatched exact-SHA CI success attestation",
    }


def score_item(it: dict, ledger: str, gt_text: str, ctx: dict) -> dict:
    dims_cfg = it.get("dimensions") or {}
    maxes = ctx["scoring_max"]
    evidence = it.get("evidence") or {}
    gts = it.get("gt_blockers") or []
    caps = it.get("capability_ids") or []
    pilot_scope = it.get("pilot_scope", "in")
    gates = ctx["gates"]
    ci_attested = ctx["ci_attestation"]["ok"]

    blocked = False
    block_reason: list[str] = []
    for g in gts:
        if g in gt_text:
            for c in caps:
                st = ledger_status(c, ledger)
                if st == "BLOCKED":
                    blocked = True
                    block_reason.append(f"{c}=BLOCKED via {g}")
            if pilot_scope == "out":
                blocked = True
                block_reason.append(f"pilot_scope=out + {g}")
    if pilot_scope == "out" and gts and not any(
        ledger_status(c, ledger) == "IMPLEMENTED" for c in caps
    ):
        blocked = True
        if not block_reason:
            block_reason.append("GT blocker + not implemented")

    if blocked and pilot_scope == "out":
        return {
            "id": it["id"], "title": it["title"], "domain": it["domain"],
            "critical": it.get("critical", False), "pilot_scope": pilot_scope,
            "score": None, "color": "BLOCKED", "verdict": "BLOCKED",
            "gaps": block_reason or ["Ground Truth required"], "dimensions": {},
        }

    dim_scores: dict[str, float] = {}
    dim_max_used: dict[str, float] = {}

    def enable(dim: str) -> bool:
        return bool(dims_cfg.get(dim, True))

    # Ground truth / scope evidence: documents and ledger mapping, not implementation proof.
    if enable("ground_truth"):
        mx = maxes["ground_truth"]
        s = 0
        if any_path(evidence.get("paths") or []) or evidence.get("smokes") or caps:
            s += 4
        if any(path_exists(p) for p in [
            "docs/00-master/PHASE1_SAFE_CLOSURE.md",
            "docs/00-master/RELEASE_SCOPE_PHASE1.md",
            "docs/00-master/CURRENT_STATE.md",
        ]):
            s += 3
        if caps and all(ledger_status(c, ledger) for c in caps):
            s += 2
        if not gts:
            s += 1
        dim_scores["ground_truth"] = min(mx, s)
        dim_max_used["ground_truth"] = mx

    # Runtime: code/path + implemented ledger + an actually passing relevant gate.
    if enable("runtime"):
        mx = maxes["runtime"]
        paths = evidence.get("paths") or []
        s = 0
        if paths and any_path(paths):
            s += 8
        elif not paths and (caps or evidence.get("smokes")):
            s += 2
        if any(ledger_status(c, ledger) == "IMPLEMENTED" for c in caps):
            s += 6
        elif any(ledger_status(c, ledger) == "PARTIAL" for c in caps):
            s += 3
        if any(gates.get(sm, {}).get("status") == "PASS" for sm in evidence.get("smokes") or []):
            s += 6
        dim_scores["runtime"] = min(mx, s)
        dim_max_used["runtime"] = mx

    if enable("tenant_security"):
        mx = maxes["tenant_security"]
        security_relevant = (
            it.get("domain") in ("2-multi-tenant", "3-auth", "4-registration")
            or it.get("critical", False)
        )
        if security_relevant:
            s = 0
            if gates.get("domain_smoke", {}).get("status") == "PASS":
                s += 4
            if gates.get("http_smoke", {}).get("status") == "PASS":
                s += 4
            if gates.get("cors_smoke", {}).get("status") == "PASS":
                s += 3
            if any(ledger_status(c, ledger) == "IMPLEMENTED" for c in ("CAP-001", "CAP-021")):
                s += 4
            dim_scores["tenant_security"] = min(mx, s)
            dim_max_used["tenant_security"] = mx

    # Automated tests: ONLY current-run PASS counts. File existence gets zero.
    if enable("automated_tests"):
        smokes = evidence.get("smokes") or []
        if smokes:
            mx = maxes["automated_tests"]
            passed = sum(1 for sm in smokes if gates.get(sm, {}).get("status") == "PASS")
            dim_scores["automated_tests"] = round(mx * passed / len(smokes), 2)
            dim_max_used["automated_tests"] = mx

    if enable("openapi_contract"):
        oas = evidence.get("openapi") or []
        if oas:
            mx = maxes["openapi_contract"]
            s = 6 if openapi_has(oas) else 0
            if gates.get("openapi_parity", {}).get("status") == "PASS":
                s += 4
            dim_scores["openapi_contract"] = min(mx, s)
            dim_max_used["openapi_contract"] = mx

    if enable("frontend_ux"):
        if evidence.get("frontend") or any("frontend/" in p for p in (evidence.get("paths") or [])):
            mx = maxes["frontend_ux"]
            s = 0
            if any_path(evidence.get("paths") or []):
                s += 6
            if path_exists("frontend/customer/package.json"):
                s += 2
            if path_exists("frontend/backoffice/package.json"):
                s += 2
            dim_scores["frontend_ux"] = min(mx, s)
            dim_max_used["frontend_ux"] = mx

    # Exact SHA CI: no credit from workflow-file existence. Require attestation.
    if enable("exact_sha_ci"):
        mx = maxes["exact_sha_ci"]
        dim_scores["exact_sha_ci"] = mx if ci_attested else 0
        dim_max_used["exact_sha_ci"] = mx

    if enable("ops_deploy"):
        mx = maxes["ops_deploy"]
        s = 0
        for p in evidence.get("paths") or []:
            if path_exists(p):
                s += 1
        for sc in evidence.get("scripts") or []:
            if SCRIPT_MAP.get(sc, Path("/x")).exists():
                s += 1
        for p in (
            "docs/00-master/DEPLOY_PHASE1.md",
            "docs/00-master/PILOT_CHECKLIST.md",
            "docs/00-master/PILOT_RUNBOOK.md",
            "scripts/release_build.sh",
            "scripts/pilot_preflight.sh",
        ):
            if path_exists(p):
                s += 1
        dim_scores["ops_deploy"] = min(mx, s)
        dim_max_used["ops_deploy"] = mx

    total = sum(dim_scores.values())
    denom = sum(dim_max_used.values()) or 1
    score = round(100.0 * total / denom, 1)

    for c in caps:
        st = ledger_status(c, ledger)
        if st == "PARTIAL":
            score = min(score, 88.0)
        elif st == "BLOCKED":
            blocked = True
            block_reason.append(f"{c}=BLOCKED")

    if pilot_scope == "partial" and gts:
        score = min(score, 88.0)

    gaps = list(block_reason)
    for sm in evidence.get("smokes") or []:
        st = gates.get(sm, {}).get("status")
        if st != "PASS":
            gaps.append(f"gate:{sm}={st or 'UNVERIFIED'}")
    if enable("exact_sha_ci") and not ci_attested:
        gaps.append("exact_sha_ci_unverified")
    if evidence.get("paths") and not any_path(evidence.get("paths") or []):
        gaps.append("missing_paths")

    if blocked and pilot_scope == "partial":
        c = color_for(score, False)
        verdict = "NOT_ACCEPTED"
        gaps.append("GT_partial")
    elif blocked:
        c = "BLOCKED"
        score = None
        verdict = "BLOCKED"
    else:
        c = color_for(score, False)
        verdict = "ACCEPTED" if c == "GREEN" else "NOT_ACCEPTED"

    return {
        "id": it["id"], "title": it["title"], "domain": it["domain"],
        "critical": it.get("critical", False), "pilot_scope": pilot_scope,
        "score": score, "color": c, "verdict": verdict,
        "gaps": gaps[:8], "dimensions": dim_scores,
    }


def evaluate_vetos(results: list[dict], ledger: str, gt_text: str, ctx: dict) -> list[dict]:
    vetos: list[dict] = []
    gates = ctx["gates"]

    # CV-01: tenant/security gate failures are hard stops.
    for cid in ("CAP-001", "CAP-021"):
        st = ledger_status(cid, ledger)
        if st and st not in ("IMPLEMENTED", "PARTIAL"):
            vetos.append({"id": "CV-01", "reason": f"{cid} status={st}"})
    for gate in ("http_smoke", "cors_smoke"):
        if gates.get(gate, {}).get("status") == "FAIL":
            vetos.append({"id": "CV-01", "reason": f"{gate} failed in current audit run"})

    # CV-02: unsafe financial wiring/default.
    env_ex = read_text(ROOT / ".env.example")
    if re.search(r"KIMIA_WRITE_VERIFY_ENABLE\s*=\s*1", env_ex):
        vetos.append({"id": "CV-02", "reason": "Write enabled in .env.example"})
    if ledger_status("CAP-014", ledger) == "IMPLEMENTED":
        vetos.append({"id": "CV-02", "reason": "CAP-014 Settlement marked IMPLEMENTED"})
    if ledger_status("CAP-012", ledger) == "IMPLEMENTED" and "GT-" in gt_text:
        vetos.append({"id": "CV-02", "reason": "CAP-012 Price provider claimed IMPLEMENTED while GT blockers remain"})

    # CV-03: exact-SHA CI must be independently attested for the same HEAD.
    if not ctx["ci_attestation"]["ok"]:
        vetos.append({"id": "CV-03", "reason": ctx["ci_attestation"]["reason"]})

    # CV-04: parity must actually pass in this audit run.
    if gates.get("openapi_parity", {}).get("status") != "PASS":
        vetos.append({
            "id": "CV-04",
            "reason": f"openapi_parity current-run status={gates.get('openapi_parity', {}).get('status', 'UNVERIFIED')}",
        })

    if ledger_status("CAP-019", ledger) == "IMPLEMENTED":
        vetos.append({"id": "CV-05", "reason": "CAP-019 Payment IMPLEMENTED without archived payment GT proof"})

    need = ["docs/00-master/DEPLOY_PHASE1.md", "scripts/release_build.sh", "scripts/pilot_preflight.sh"]
    if not all(path_exists(p) for p in need):
        vetos.append({"id": "CV-06", "reason": "required pilot deploy gate missing"})

    for r in results:
        if r.get("critical") and r.get("pilot_scope") == "in" and r.get("color") == "RED":
            vetos.append({"id": "CV-08", "reason": f"{r['id']} in-scope critical RED"})

    ver = read_text(ROOT / "VERSION").strip()
    if ver != ctx.get("version_pin"):
        vetos.append({"id": "CV-09", "reason": f"VERSION {ver or '<missing>'} != pin {ctx.get('version_pin')}"})

    seen = set()
    out = []
    for v in vetos:
        if v["id"] not in seen:
            seen.add(v["id"])
            out.append(v)
    return out


def main() -> int:
    if not REG.exists():
        print(f"FAIL: registry missing {REG}")
        return 2

    reg = json.loads(REG.read_text(encoding="utf-8"))
    # Compact registry form: item_schema + row arrays. Normalize before scoring.
    if reg.get("items") and isinstance(reg["items"][0], list):
        keys = reg.get("item_schema") or []
        reg["items"] = [dict(zip(keys, row)) for row in reg["items"]]
    ledger = read_text(LEDGER)
    gt_text = read_text(GT)
    head = git_head()
    gates = collect_gate_results(reg)
    ci = exact_sha_attestation(head)
    ctx = {
        "scoring_max": reg["scoring_max"],
        "version_pin": reg.get("version_pin"),
        "gates": gates,
        "ci_attestation": ci,
    }

    results = [score_item(it, ledger, gt_text, ctx) for it in reg["items"]]
    vetos = evaluate_vetos(results, ledger, gt_text, ctx)

    scored = [r for r in results if r["score"] is not None]
    mean = round(sum(r["score"] for r in scored) / len(scored), 1) if scored else 0.0
    counts = {k: 0 for k in ("GREEN", "YELLOW", "ORANGE", "RED", "BLOCKED")}
    for r in results:
        counts[r["color"]] = counts.get(r["color"], 0) + 1

    in_scope_critical = [r for r in results if r["pilot_scope"] == "in" and r["critical"]]
    all_critical_green = bool(in_scope_critical) and all(r["color"] == "GREEN" for r in in_scope_critical)
    in_scope_orange_red = [
        r for r in results
        if r["pilot_scope"] == "in" and r["color"] in ("ORANGE", "RED")
    ]

    if any(v["id"] == "CV-02" for v in vetos):
        final = "BLOCKED_FINANCIAL_GT"
    elif any(v["id"] in ("CV-01", "CV-08") for v in vetos):
        final = "REJECTED_SECURITY"
    elif vetos:
        final = "NOT_READY_FOR_PILOT"
    elif mean >= 90 and all_critical_green and not in_scope_orange_red:
        final = "ACCEPTED_FOR_PILOT"
    else:
        final = "NOT_READY_FOR_PILOT"

    short = head[:7] if head != "unknown" else "unknown"
    now = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
    report = {
        "authority": "Final Audit Agent = Authority for Closure",
        "generated_at": now,
        "git_sha": head,
        "version": read_text(ROOT / "VERSION").strip(),
        "final_audit_score": mean,
        "counts": counts,
        "critical_vetos": vetos,
        "final_verdict": final,
        "exact_sha_ci": ci,
        "gate_results": gates,
        "items": results,
    }

    REPORT_DIR.mkdir(parents=True, exist_ok=True)
    json_path = REPORT_DIR / "AUDIT_REPORT_latest.json"
    json_path.write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")

    md_path = REPORT_DIR / f"AUDIT_REPORT_{short}.md"
    lines = [
        f"# Final Audit Report — {short}", "",
        f"**Generated:** {now}  ",
        f"**Git SHA:** `{head}`  ",
        f"**VERSION:** `{report['version']}`  ",
        "**Authority:** Final Audit Agent", "",
        f"## Final Audit Score: **{mean} / 100**", "",
        "| Color | Count |", "|-------|-------|",
        f"| 🟢 Green | {counts['GREEN']} |",
        f"| 🟡 Yellow | {counts['YELLOW']} |",
        f"| 🟠 Orange | {counts['ORANGE']} |",
        f"| 🔴 Red | {counts['RED']} |",
        f"| ⚫ Blocked | {counts['BLOCKED']} |", "",
        "## Exact-SHA CI", "",
        f"- Attested: **{'YES' if ci['ok'] else 'NO'}**",
        f"- Reason: {ci['reason'] or 'exact HEAD success attestation matched'}", "",
        "## Current-run gates", "",
    ]
    for name, g in sorted(gates.items()):
        lines.append(f"- `{name}`: **{g['status']}** (rc={g['rc']})")
    lines += ["", "## Critical Vetos", ""]
    if not vetos:
        lines.append("_None active._")
    else:
        for v in vetos:
            lines.append(f"- **{v['id']}**: {v['reason']}")
    lines += ["", f"## Agent Verdict: **{final}**", "", "## Items", ""]
    lines += ["| ID | Title | Score | Color | Verdict | Gaps |", "|----|-------|-------|-------|---------|------|"]
    for r in results:
        sc = "—" if r["score"] is None else str(r["score"])
        gaps = ", ".join(r.get("gaps") or [])[:100]
        lines.append(
            f"| {r['id']} | {r['title'][:40]} | {sc} | {emoji(r['color'])} {r['color']} | {r['verdict']} | {gaps} |"
        )

    lines += ["", "## Domain averages (scored items only)", ""]
    ds = defaultdict(list)
    for r in scored:
        ds[r["domain"]].append(r["score"])
    for dom in sorted(ds):
        avg = round(sum(ds[dom]) / len(ds[dom]), 1)
        lines.append(f"- **{dom}**: {avg} / 100 (n={len(ds[dom])})")

    lines += [
        "", "## Closure rule", "",
        "High score alone does not accept release. Current-run gates and Critical Vetos override.",
        "Exact-SHA CI gets zero credit unless success is attested for this exact git HEAD.",
        "Out-of-pilot BLOCKED items remain blocked until Ground Truth is archived.", "",
    ]
    md_path.write_text("\n".join(lines) + "\n", encoding="utf-8")
    (REPORT_DIR / "AUDIT_REPORT_latest.md").write_text(md_path.read_text(encoding="utf-8"), encoding="utf-8")

    print("======== FINAL AUDIT AGENT ========")
    print(f"SHA={short} VERSION={report['version']}")
    print(f"Final Audit Score: {mean} / 100")
    print(f"Green={counts['GREEN']} Yellow={counts['YELLOW']} Orange={counts['ORANGE']} Red={counts['RED']} Blocked={counts['BLOCKED']}")
    print(f"Exact-SHA CI: {'ATTESTED' if ci['ok'] else 'UNVERIFIED'}")
    print("Critical Vetos:", "NONE" if not vetos else "")
    for v in vetos:
        print(f"  - {v['id']}: {v['reason']}")
    print(f"Agent Verdict: {final}")
    print(f"Report: {md_path.relative_to(ROOT)}")
    print(f"JSON:   {json_path.relative_to(ROOT)}")
    return 0 if final == "ACCEPTED_FOR_PILOT" else 1


if __name__ == "__main__":
    sys.exit(main())
