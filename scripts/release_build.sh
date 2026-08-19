#!/usr/bin/env bash
# Phase-1 pilot release build — no Kimia Write/Create.
# Runs offline preflight → frontend build+typecheck → dist presence → backend gates.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "== VERSION $(tr -d '[:space:]' < VERSION) =="
echo "== pilot-preflight (offline) =="
bash scripts/pilot_preflight.sh

echo "== frontend customer (typecheck + build) =="
(cd frontend/customer && npm ci --no-audit --no-fund && npm run typecheck && npm run build)

echo "== frontend backoffice (typecheck + build) =="
(cd frontend/backoffice && npm ci --no-audit --no-fund && npm run typecheck && npm run build)

echo "== dist presence =="
for d in frontend/customer/dist frontend/backoffice/dist; do
  if [[ -f "$d/index.html" ]]; then
    echo "OK   $d/index.html"
  else
    echo "FAIL missing $d/index.html"
    exit 1
  fi
done

echo "== spa_router_smoke (post-build) =="
php backend/bin/spa_router_smoke.php

echo "== backend gates =="
HAS_SQLITE=0
if php -r 'exit(extension_loaded("pdo_sqlite") ? 0 : 1);' 2>/dev/null; then
  HAS_SQLITE=1
fi

if [[ "$HAS_SQLITE" -eq 1 ]]; then
  echo "(pdo_sqlite present → full check.php)"
  php backend/bin/check.php
else
  echo "(pdo_sqlite absent → subset without DB-dependent smokes)"
  php backend/bin/smoke.php
  php backend/bin/cors_smoke.php
  php backend/bin/logger_smoke.php
  php backend/bin/landing_smoke.php
  php backend/bin/spa_router_smoke.php
  php backend/bin/openapi_parity_check.php
  echo "NOTE: http_smoke / persist_smoke / maintenance_smoke need pdo_sqlite — run full check on host with driver."
fi

echo "== write-deny reminder =="
echo "KIMIA_WRITE_VERIFY_ENABLE must stay 0 for pilot unless new Owner-authorized Iran session."
echo "== release_build OK =="
