#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT/frontend/customer" && npm ci && npm run typecheck
cd "$ROOT/frontend/backoffice" && npm ci && npm run typecheck
echo "verify_frontend OK"
