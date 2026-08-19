#!/usr/bin/env bash
# Phase-1 pilot release build — no Kimia Write.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
echo "== VERSION $(cat VERSION) =="
echo "== frontend customer =="
(cd frontend/customer && npm ci && npm run typecheck && npm run build)
echo "== frontend backoffice =="
(cd frontend/backoffice && npm ci && npm run typecheck && npm run build)
echo "== backend check =="
php backend/bin/check.php
echo "== release_build OK =="
