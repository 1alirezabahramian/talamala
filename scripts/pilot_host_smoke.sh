#!/usr/bin/env bash
# Phase-1 pilot host smoke — GET-only, no auth secrets, no Kimia Write/Create.
# Usage: TALAMALA_BASE_URL=https://host.example make pilot-host-smoke
#    or: bash scripts/pilot_host_smoke.sh https://host.example
set -euo pipefail

BASE="${1:-${TALAMALA_BASE_URL:-}}"
if [[ -z "$BASE" ]]; then
  echo "FAIL TALAMALA_BASE_URL (or arg1) required — e.g. https://pilot.example"
  echo "This script only performs safe GETs: /healthz /readyz / /robots.txt"
  exit 1
fi
BASE="${BASE%/}"

PASS=0
FAIL=0
ok()  { echo "OK   $1"; PASS=$((PASS + 1)); }
fail(){ echo "FAIL $1 — $2"; FAIL=$((FAIL + 1)); }

echo "== pilot host smoke =="
echo "BASE=$BASE"
echo "DATE=$(date -u +%Y-%m-%dT%H:%M:%SZ)"

check_get() {
  local path="$1"
  local label="$2"
  local pattern="${3:-}"
  local tmp err code
  tmp="$(mktemp)"
  err="$(mktemp)"

  if ! code="$(curl -sS --max-time 15 -o "$tmp" -w '%{http_code}' "${BASE}${path}" 2>"$err")"; then
    fail "$path" "curl error: $(cat "$err" 2>/dev/null || true)"
    rm -f "$tmp" "$err"
    return 1
  fi

  if [[ "$code" != "200" ]]; then
    fail "$path" "HTTP $code"
    rm -f "$tmp" "$err"
    return 1
  fi

  if [[ -n "$pattern" ]] && grep -qiE "$pattern" "$tmp"; then
    ok "$label"
  else
    ok "$path (HTTP 200)"
  fi
  rm -f "$tmp" "$err"
  return 0
}

check_get /healthz "/healthz" 'ok|pass|healthy|version|0\.3\.8' || true
check_get /readyz "/readyz" '' || true
check_get / "/ (landing)" 'talamala|/app/customer|/app/backoffice' || true
check_get /robots.txt "/robots.txt (blocks demos/dev)" 'disallow|/v1/dev|/app' || true

echo ""
echo "---"
echo "PASS=$PASS FAIL=$FAIL"
echo "NOTE: OTP/staff/custody/assets/order need Host + credentials — manual per PILOT_CHECKLIST."
echo "NOTE: No Kimia Write/Create performed."
if [[ $FAIL -gt 0 ]]; then
  echo "pilot_host_smoke FAILED"
  exit 1
fi
echo "pilot_host_smoke OK"
exit 0
