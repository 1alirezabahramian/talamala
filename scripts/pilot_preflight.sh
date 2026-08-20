#!/usr/bin/env bash
# Phase-1 pilot preflight — offline / fail-closed readiness (no Live Kimia, no GT invent).
# Safe for non-Iran sandboxes. Does not enable Write/Create.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

PASS=0
FAIL=0
SKIP=0

ok()  { echo "OK   $1"; PASS=$((PASS + 1)); }
fail(){ echo "FAIL $1"; FAIL=$((FAIL + 1)); }
skip(){ echo "SKIP $1"; SKIP=$((SKIP + 1)); }

echo "== Talamala pilot preflight =="
echo "ROOT=$ROOT"
echo "DATE=$(date -u +%Y-%m-%dT%H:%M:%SZ)"

# --- 1. VERSION pin ---
if [[ -f VERSION ]]; then
  V=$(tr -d '[:space:]' < VERSION)
  if [[ "$V" == "0.3.8-phase1" ]]; then
    ok "VERSION=$V (Phase-1 freeze pin)"
  else
    fail "VERSION expected 0.3.8-phase1 got '$V'"
  fi
else
  fail "VERSION file missing"
fi

# --- 2. Freeze docs present ---
for f in \
  docs/00-master/PHASE1_SAFE_CLOSURE.md \
  docs/00-master/RELEASE_SCOPE_PHASE1.md \
  docs/00-master/DEPLOY_PHASE1.md \
  docs/00-master/PILOT_CHECKLIST.md \
  docs/00-master/GROUND_TRUTH_BLOCKERS.md \
  docs/traceability/CAPABILITY_LEDGER.md
do
  if [[ -f "$f" ]]; then
    ok "doc:$f"
  else
    fail "missing:$f"
  fi
done

# --- 3. Write/Create remain deny-by-default in examples ---
if grep -qE 'KIMIA_WRITE_VERIFY_ENABLE=0' .env.example; then
  ok ".env.example KIMIA_WRITE_VERIFY_ENABLE=0"
else
  fail ".env.example must document KIMIA_WRITE_VERIFY_ENABLE=0"
fi
if grep -qiE 'KIMIA_WRITE_VERIFY_ENABLE\s*=\s*1' .env.example; then
  fail ".env.example must not enable write flag by default"
else
  ok ".env.example does not enable write flag"
fi
if grep -qiE 'Live Create|unattended.*[Cc]reate' docs/00-master/RELEASE_SCOPE_PHASE1.md 2>/dev/null; then
  ok "RELEASE_SCOPE documents Create restrictions"
else
  if grep -q 'Must NOT claim' docs/00-master/RELEASE_SCOPE_PHASE1.md; then
    ok "RELEASE_SCOPE has Must NOT claim section"
  else
    fail "RELEASE_SCOPE missing pilot restrictions"
  fi
fi

# --- 4. PHP syntax (no DB) ---
if command -v php >/dev/null 2>&1; then
  SYN_FAIL=0
  COUNT=0
  for f in $(find backend/app backend/bin -name '*.php' 2>/dev/null); do
    COUNT=$((COUNT + 1))
    if ! php -l "$f" >/dev/null 2>&1; then
      echo "  syntax error: $f"
      SYN_FAIL=1
    fi
  done
  if [[ $SYN_FAIL -eq 0 && $COUNT -gt 0 ]]; then
    ok "php-syntax backend/app + backend/bin ($COUNT files)"
  else
    fail "php-syntax"
  fi
else
  skip "php not available"
fi

# --- 5. Domain smoke (in-memory, no PDO required for pure domain) ---
if command -v php >/dev/null 2>&1; then
  if php backend/bin/smoke.php >/tmp/talamala_domain_smoke.out 2>&1; then
    if grep -q 'PASS=13 FAIL=0' /tmp/talamala_domain_smoke.out; then
      ok "domain_smoke PASS=13 FAIL=0"
    else
      fail "domain_smoke unexpected output"
      cat /tmp/talamala_domain_smoke.out | tail -20
    fi
  else
    fail "domain_smoke exited non-zero"
    cat /tmp/talamala_domain_smoke.out | tail -30
  fi
else
  skip "domain_smoke (no php)"
fi

# --- 5a. Decimal invariant smoke
if command -v php >/dev/null 2>&1; then
  if php backend/bin/decimal_invariant_smoke.php >/tmp/talamala_dec.out 2>&1; then
    if grep -q 'PASS=13 FAIL=0' /tmp/talamala_dec.out; then
      ok "decimal_invariant_smoke PASS=13 FAIL=0"
    else
      fail "decimal_invariant_smoke"
      tail -15 /tmp/talamala_dec.out || true
    fi
  else
    fail "decimal_invariant_smoke exited non-zero"
  fi
else
  skip "decimal_invariant_smoke"
fi

# --- 5b. HTTP negative smoke (no network)
if command -v php >/dev/null 2>&1; then
  if php backend/bin/http_negative_smoke.php >/tmp/talamala_neg.out 2>&1; then
    if grep -q 'PASS=17 FAIL=0' /tmp/talamala_neg.out; then
      ok "http_negative_smoke PASS=17 FAIL=0"
    else
      fail "http_negative_smoke"
      tail -20 /tmp/talamala_neg.out
    fi
  else
    fail "http_negative_smoke exited non-zero"
    tail -30 /tmp/talamala_neg.out || true
  fi
else
  skip "http_negative_smoke (no php)"
fi

# --- 6. OpenAPI parity (no DB) ---
if command -v php >/dev/null 2>&1; then
  if php backend/bin/openapi_parity_check.php >/tmp/talamala_parity.out 2>&1; then
    if grep -q 'PASS=22 FAIL=0\|parity OK' /tmp/talamala_parity.out; then
      ok "openapi_parity"
    else
      fail "openapi_parity"
      cat /tmp/talamala_parity.out | tail -15
    fi
  else
    fail "openapi_parity exited non-zero"
  fi
else
  skip "openapi_parity (no php)"
fi

# --- 7. Kimia write/create contract smokes (offline ACL guards only) ---
if command -v php >/dev/null 2>&1; then
  if php backend/bin/kimia_write_contract_smoke.php >/tmp/talamala_kw.out 2>&1; then
    ok "kimia-write-contract (offline)"
  else
    fail "kimia-write-contract"
    tail -20 /tmp/talamala_kw.out
  fi
  if php backend/bin/kimia_create_customer_contract_smoke.php >/tmp/talamala_kc.out 2>&1; then
    ok "kimia-create-customer-contract (offline)"
  else
    fail "kimia-create-customer-contract"
    tail -20 /tmp/talamala_kc.out
  fi
else
  skip "kimia contract smokes (no php)"
fi

# --- 8. Frontend typecheck (Node) — advisory if npm missing ---
if command -v npm >/dev/null 2>&1; then
  if (cd frontend/customer && npm ci --prefer-offline --no-audit --no-fund >/dev/null 2>&1 && npm run typecheck >/tmp/talamala_tc_c.out 2>&1); then
    ok "frontend/customer typecheck"
  else
    fail "frontend/customer typecheck"
    tail -30 /tmp/talamala_tc_c.out || true
  fi
  if (cd frontend/backoffice && npm ci --prefer-offline --no-audit --no-fund >/dev/null 2>&1 && npm run typecheck >/tmp/talamala_tc_b.out 2>&1); then
    ok "frontend/backoffice typecheck"
  else
    fail "frontend/backoffice typecheck"
    tail -30 /tmp/talamala_tc_b.out || true
  fi
else
  skip "frontend typecheck (npm not available)"
fi

# --- 9. Settlement remains blocked in domain (regression guard text) ---
if grep -R -n 'blocked_by_ground_truth\|settlement.*blocked' backend/app --include='*.php' >/dev/null 2>&1; then
  ok "settlement blocked marker present in backend"
else
  fail "settlement blocked marker missing — pilot must not auto-settle"
fi

# --- Summary ---
echo ""
echo "---"
echo "PASS=$PASS FAIL=$FAIL SKIP=$SKIP"
if [[ $FAIL -gt 0 ]]; then
  echo "pilot_preflight FAILED"
  exit 1
fi
echo "pilot_preflight OK — Phase-1 pilot path (no Live Kimia Write/Create)"
exit 0
