#!/usr/bin/env bash
# Run offline Phase-1 gates that do not require network or Live Kimia.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
echo "NOTE: diagnostic offline bundle only; does not grant pilot closure."
FAIL=0
run() {
  local name="$1"; shift
  echo "== $name =="
  if "$@"; then
    echo "OK $name"
  else
    echo "FAIL $name"
    FAIL=$((FAIL + 1))
  fi
  echo
}
run domain_smoke php backend/bin/smoke.php
run decimal_invariant php backend/bin/decimal_invariant_smoke.php
run openapi_parity php backend/bin/openapi_parity_check.php
run kimia_write_contract php backend/bin/kimia_write_contract_smoke.php
run kimia_create_contract php backend/bin/kimia_create_customer_contract_smoke.php
run cors_smoke php backend/bin/cors_smoke.php
run logger_smoke php backend/bin/logger_smoke.php
run landing_smoke php backend/bin/landing_smoke.php
if php -r 'exit(extension_loaded("pdo_sqlite")?0:1);' 2>/dev/null; then
  run http_negative php backend/bin/http_negative_smoke.php
  run http_smoke php backend/bin/http_smoke.php
  run persist_smoke php backend/bin/persist_smoke.php
else
  echo "SKIP http/persist (no pdo_sqlite)"
fi
echo "---"
if [[ $FAIL -gt 0 ]]; then
  echo "pilot_offline_gates FAILED ($FAIL)"
  exit 1
fi
echo "pilot_offline_gates OK"
