#!/bin/sh
set -u

# Owner authorized this deployment for READ-ONLY sync/catalog only.
# Force the verification write gate closed for this service boot.
export KIMIA_WRITE_VERIFY_ENABLE=0
unset KIMIA_WRITE_OWNER_AUTH KIMIA_WRITE_OWNER_TOKEN KIMIA_WRITE_ATTEMPT_BUDGET KIMIA_WRITE_ACCOUNT_ALLOWLIST KIMIA_MUTATE_PATH KIMIA_MUTATE_BODY_FILE KIMIA_MUTATE_ACCOUNT_ID 2>/dev/null || true

printf '%s\n' "TALAMALA_KIMIA_RUNNER boot"
printf '%s\n' "repo=1alirezabahramian/talamala"
printf '%s\n' "mode=verification-read-only"
printf '%s\n' "write_gate=${KIMIA_WRITE_VERIFY_ENABLE}"

php backend/bin/kimia_verify_runner.php catalog
rc=$?

if [ "$rc" -eq 0 ]; then
  printf '%s\n' "TALAMALA_KIMIA_RUNNER PREFLIGHT_OK"
else
  printf '%s\n' "TALAMALA_KIMIA_RUNNER PREFLIGHT_FAIL exit=$rc"
fi

if [ -f var/kimia-verify/swagger_catalog.json ]; then
  php -r '
    $p="var/kimia-verify/swagger_catalog.json";
    $j=json_decode((string)@file_get_contents($p), true);
    if (is_array($j)) {
      echo "PREFLIGHT_CATALOG_READY=true".PHP_EOL;
      echo "PREFLIGHT_CATALOG_POST_COUNT=".count($j["post_paths"] ?? []).PHP_EOL;
      foreach (($j["post_paths"] ?? []) as $path) {
        if (is_string($path)) echo "PREFLIGHT_CATALOG_POST=".$path.PHP_EOL;
      }
      foreach (($j["action_enums"] ?? []) as $key=>$value) {
        echo "PREFLIGHT_CATALOG_ACTION=".$key." ".json_encode($value, JSON_UNESCAPED_UNICODE).PHP_EOL;
      }
    }
  '
fi

# Local-only parse of the live Swagger captured above. NO HTTP and NO mutation.
if [ "$rc" -eq 0 ]; then
  php backend/bin/kimia_contract_catalog_readonly.php || rc=$?
fi

printf '%s\n' "Write not attempted (read_only)."
printf '%s\n' "Runner is now idle; no automatic retry and no Write."
printf '%s\n' "A separate Owner authorization and separate deployment change are required before any mutate attempt."

exec tail -f /dev/null
