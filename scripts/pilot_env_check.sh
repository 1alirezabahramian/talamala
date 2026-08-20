#!/usr/bin/env bash
# Phase-1 pilot env posture check — no secrets printed, no Live Kimia.
# Looks at .env if present, otherwise validates .env.example / .env.pilot.example templates.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

PASS=0
FAIL=0
WARN=0
ok()   { echo "OK   $1"; PASS=$((PASS + 1)); }
fail() { echo "FAIL $1"; FAIL=$((FAIL + 1)); }
warn() { echo "WARN $1"; WARN=$((WARN + 1)); }

echo "== pilot env check =="
echo "DATE=$(date -u +%Y-%m-%dT%H:%M:%SZ)"

ENV_FILE=""
if [[ -f .env ]]; then
  ENV_FILE=".env"
  ok "found .env (values not printed)"
elif [[ -f .env.pilot.example ]]; then
  ENV_FILE=".env.pilot.example"
  warn "no .env — checking .env.pilot.example template only"
else
  ENV_FILE=".env.example"
  warn "no .env — checking .env.example template only"
fi

get_val() {
  local key="$1"
  grep -E "^[[:space:]]*${key}=" "$ENV_FILE" 2>/dev/null | tail -1 | sed "s/^[^=]*=//" | tr -d '\r' || true
}

# --- write deny ---
WVE=$(get_val KIMIA_WRITE_VERIFY_ENABLE)
if [[ -z "$WVE" ]]; then
  ok "KIMIA_WRITE_VERIFY_ENABLE unset (default-deny OK for pilot)"
elif [[ "$WVE" == "0" ]]; then
  ok "KIMIA_WRITE_VERIFY_ENABLE=0"
else
  fail "KIMIA_WRITE_VERIFY_ENABLE must be 0 for Phase-1 pilot (got non-zero)"
fi

# --- create deny ---
KCE=$(get_val KIMIA_CREATE_ENABLE)
if [[ -z "$KCE" ]]; then
  ok "KIMIA_CREATE_ENABLE unset (default-deny OK for pilot)"
elif [[ "$KCE" == "0" ]]; then
  ok "KIMIA_CREATE_ENABLE=0"
else
  fail "KIMIA_CREATE_ENABLE must be 0 for Phase-1 pilot (got non-zero)"
fi

# --- env mode ---
TE=$(get_val TALAMALA_ENV)
if [[ "$ENV_FILE" == ".env" ]]; then
  if [[ "$TE" == "production" || "$TE" == "staging" || "$TE" == "local" ]]; then
    ok "TALAMALA_ENV=$TE"
  elif [[ -z "$TE" ]]; then
    warn "TALAMALA_ENV empty"
  else
    warn "TALAMALA_ENV unusual: $TE"
  fi
else
  ok "template documents TALAMALA_ENV"
fi

# --- CORS when production .env ---
CORS=$(get_val TALAMALA_CORS_ORIGINS)
if [[ "$ENV_FILE" == ".env" && "$TE" == "production" ]]; then
  if [[ -z "$CORS" ]]; then
    fail "production .env: TALAMALA_CORS_ORIGINS should be exact SPA origins (empty denies all)"
  else
    ok "TALAMALA_CORS_ORIGINS set (not printed)"
  fi
else
  ok "CORS check skipped or non-production template"
fi

# --- DB path hint ---
DB=$(get_val TALAMALA_DB_PATH)
if [[ "$ENV_FILE" == ".env" && "$TE" == "production" ]]; then
  if [[ -z "$DB" || "$DB" == ":memory:" ]]; then
    warn "production: prefer durable TALAMALA_DB_PATH (not :memory:)"
  else
    ok "TALAMALA_DB_PATH set (path not printed)"
  fi
else
  ok "DB path check skipped or non-production"
fi

# --- templates must document mutation deny ---
if grep -qE 'KIMIA_WRITE_VERIFY_ENABLE=0' .env.example 2>/dev/null; then
  ok ".env.example documents write-deny"
else
  fail ".env.example missing KIMIA_WRITE_VERIFY_ENABLE=0"
fi
if grep -qE 'KIMIA_CREATE_ENABLE=0' .env.example 2>/dev/null; then
  ok ".env.example documents create-deny"
else
  fail ".env.example missing KIMIA_CREATE_ENABLE=0"
fi
if [[ -f .env.pilot.example ]]; then
  if grep -qE 'KIMIA_WRITE_VERIFY_ENABLE=0' .env.pilot.example; then
    ok ".env.pilot.example documents write-deny"
  else
    fail ".env.pilot.example must set KIMIA_WRITE_VERIFY_ENABLE=0"
  fi
  if grep -qiE 'KIMIA_WRITE_VERIFY_ENABLE\s*=\s*(1|true)' .env.pilot.example; then
    fail ".env.pilot.example must not enable write"
  else
    ok ".env.pilot.example does not enable write"
  fi
  if grep -qE 'KIMIA_CREATE_ENABLE=0' .env.pilot.example; then
    ok ".env.pilot.example documents create-deny"
  else
    fail ".env.pilot.example must set KIMIA_CREATE_ENABLE=0"
  fi
  if grep -qiE 'KIMIA_CREATE_ENABLE\s*=\s*(1|true)' .env.pilot.example; then
    fail ".env.pilot.example must not enable create"
  else
    ok ".env.pilot.example does not enable create"
  fi
fi

# --- never commit real secrets heuristic ---
if [[ -f .env ]]; then
  if grep -qiE 'password\s*=\s*[^#[:space:]]+|secret\s*=\s*[^#[:space:]]+' .env 2>/dev/null; then
    warn ".env appears to contain credential-like keys — ensure it is gitignored"
  fi
  if git check-ignore -q .env 2>/dev/null; then
    ok ".env is gitignored"
  else
    if git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
      if git ls-files --error-unmatch .env >/dev/null 2>&1; then
        fail ".env is tracked by git — remove secrets from history"
      else
        warn ".env not listed in gitignore check (verify .gitignore)"
      fi
    fi
  fi
fi

echo ""
echo "---"
echo "PASS=$PASS FAIL=$FAIL WARN=$WARN"
if [[ $FAIL -gt 0 ]]; then
  echo "pilot_env_check FAILED"
  exit 1
fi
echo "pilot_env_check OK"
exit 0
