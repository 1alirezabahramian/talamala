#!/usr/bin/env bash
# Phase-1 pilot host smoke — GET-only, no auth secrets, no Kimia Write/Create.
# Usage: TALAMALA_BASE_URL=https://host.example make pilot-host-smoke
#    or: bash scripts/pilot_host_smoke.sh https://host.example
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
EXPECTED_VERSION="$(tr -d '[:space:]' < "$ROOT/VERSION" 2>/dev/null || echo "")"

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
echo "EXPECTED_VERSION=${EXPECTED_VERSION:-unknown}"
echo "DATE=$(date -u +%Y-%m-%dT%H:%M:%SZ)"

json_field() {
  local file="$1" key="$2"
  if command -v python3 >/dev/null 2>&1; then
    python3 - "$file" "$key" <<'PY' 2>/dev/null || true
import json, sys
with open(sys.argv[1], encoding="utf-8") as f:
    d=json.load(f)
v=d.get(sys.argv[2], "")
print(v if isinstance(v, (str,int,float,bool)) else "")
PY
  else
    grep -oE "\"$key\"[[:space:]]*:[[:space:]]*\"[^\"]+\"" "$file" | head -1 | sed -E 's/.*:"([^"]*)".*/\1/' || true
  fi
}

curl_get() {
  # curl_get PATH BODY_FILE [extra curl args...]
  # Sets global CURL_CODE. Never runs in command substitution, so state is preserved.
  local path="$1" body="$2"
  shift 2
  local err
  err="$(mktemp)"
  CURL_CODE=""
  if ! CURL_CODE="$(curl -sS --max-time 15 "$@" -o "$body" -w '%{http_code}' "${BASE}${path}" 2>"$err")"; then
    fail "$path" "curl error: $(cat "$err" 2>/dev/null || true)"
    CURL_CODE="000"
    rm -f "$err"
    return 1
  fi
  rm -f "$err"
  return 0
}

# --- /healthz: must be 200, status=ok, and version must match repo VERSION ---
tmp="$(mktemp)"
if curl_get /healthz "$tmp"; then
  if [[ "$CURL_CODE" != "200" ]]; then
    fail "/healthz" "HTTP $CURL_CODE"
  else
    ok "/healthz HTTP 200"
    HS="$(json_field "$tmp" status)"
    HV="$(json_field "$tmp" version)"
    if [[ "$HS" == "ok" ]]; then
      ok "/healthz status=ok"
    else
      fail "/healthz status" "expected ok got '${HS:-}'"
    fi
    if [[ -n "$EXPECTED_VERSION" && "$HV" == "$EXPECTED_VERSION" ]]; then
      ok "/healthz version=$HV (matches VERSION file)"
    else
      fail "/healthz version" "host=${HV:-missing} file=${EXPECTED_VERSION:-missing}"
    fi
  fi
fi
rm -f "$tmp"

# --- /readyz: accept 200; strict tenant gate 400 is also acceptable ---
tmp="$(mktemp)"
if curl_get /readyz "$tmp"; then
  if [[ "$CURL_CODE" == "200" ]]; then
    ok "/readyz HTTP 200"
    RV="$(json_field "$tmp" version)"
    if [[ -n "$RV" && -n "$EXPECTED_VERSION" && "$RV" != "$EXPECTED_VERSION" ]]; then
      fail "/readyz version" "host=$RV file=$EXPECTED_VERSION"
    elif [[ -n "$RV" ]]; then
      ok "/readyz version=$RV"
    fi
  elif [[ "$CURL_CODE" == "400" ]]; then
    ok "/readyz tenant gate (HTTP 400 — fail-closed)"
  else
    fail "/readyz" "HTTP $CURL_CODE"
  fi
fi
rm -f "$tmp"

# --- landing ---
tmp="$(mktemp)"
if curl_get / "$tmp"; then
  if [[ "$CURL_CODE" == "200" ]]; then
    if grep -qiE 'talamala|/app/customer|/app/backoffice' "$tmp"; then
      ok "/ landing"
    else
      ok "/ HTTP 200"
    fi
  else
    fail "/" "HTTP $CURL_CODE"
  fi
fi
rm -f "$tmp"

# --- robots ---
tmp="$(mktemp)"
if curl_get /robots.txt "$tmp"; then
  if [[ "$CURL_CODE" == "200" ]]; then
    if grep -qiE 'disallow|/v1/dev|/app' "$tmp"; then
      ok "/robots.txt blocks demos/dev"
    else
      ok "/robots.txt HTTP 200"
    fi
  else
    fail "/robots.txt" "HTTP $CURL_CODE"
  fi
fi
rm -f "$tmp"

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
