#!/usr/bin/env bash
# Phase-1 pilot gate chain (offline → optional host).
# Never enables Kimia Write/Create.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "======== pilot_all: env check ========"
bash scripts/pilot_env_check.sh

echo ""
echo "======== pilot_all: offline preflight ========"
bash scripts/pilot_preflight.sh

if [[ -n "${TALAMALA_BASE_URL:-}" ]]; then
  echo ""
  echo "======== pilot_all: host smoke ========"
  bash scripts/pilot_host_smoke.sh
else
  echo ""
  echo "SKIP host smoke (set TALAMALA_BASE_URL to enable)"
fi

echo ""
echo "pilot_all OK — continue PILOT_CHECKLIST manual Host items with Write disabled"
