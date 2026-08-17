#!/bin/sh
set -eu

say() { printf '%s\n' "$*"; }

say "TALAMALA_KIMIA_RUNNER mode=read-only"

if [ "${KIMIA_WRITE_VERIFY_ENABLE:-0}" != "0" ]; then
  say "REFUSE: KIMIA_WRITE_VERIFY_ENABLE must be 0 on the Chabokan read-only runner"
  exit 64
fi

missing=""
for name in KIMIA_BASE_URL KIMIA_USERNAME KIMIA_PASSWORD; do
  eval "value=\${$name:-}"
  if [ -z "$value" ]; then
    missing="$missing $name"
  fi
done

if [ -n "$missing" ]; then
  say "FAIL: missing required secret/env:$missing"
  exit 78
fi

if ! php -r 'exit(extension_loaded("curl") ? 0 : 1);'; then
  say "FAIL: PHP curl extension is unavailable"
  exit 69
fi

if [ ! -f backend/bin/kimia_preflight_readonly.php ]; then
  say "FAIL: backend/bin/kimia_preflight_readonly.php not found"
  exit 66
fi

mkdir -p var/kimia-verify
chmod 700 var/kimia-verify 2>/dev/null || true

exec php backend/bin/kimia_preflight_readonly.php
