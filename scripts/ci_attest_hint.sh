#!/usr/bin/env bash
# Explain exact-SHA CI attestation for Final Audit (CV-03).
# Does NOT invent success.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
SHA="$(git rev-parse HEAD 2>/dev/null || true)"
echo "Current HEAD: ${SHA:-unknown}"
echo ""
echo "Authoritative path (GitHub Actions workflow final-audit.yml):"
echo "  After green 'Talamala Final Audit Authority' on this SHA, Agent runs with:"
echo "    TALAMALA_AUDIT_CI_SHA=<that SHA>"
echo "    TALAMALA_AUDIT_CI_STATUS=success"
echo ""
echo "Local diagnostic (NOT closure):"
echo "  make final-audit"
echo "  make final-audit-summary"
echo ""
if command -v gh >/dev/null 2>&1 && [[ -n "$SHA" ]]; then
  echo "Attempting gh run list for this SHA (read-only)…"
  gh run list --commit "$SHA" --workflow "Talamala Final Audit Authority" --limit 5 2>/dev/null || \
    gh run list --commit "$SHA" --limit 5 2>/dev/null || \
    echo "(gh could not list runs — login or network required)"
else
  echo "gh CLI not available — open Actions UI for SHA $SHA"
fi
echo ""
echo "Never set TALAMALA_AUDIT_CI_STATUS=success manually unless CI for THIS SHA succeeded."
