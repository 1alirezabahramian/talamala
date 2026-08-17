#!/bin/sh
set -u

printf '%s\n' "TALAMALA_KIMIA_RUNNER boot"
printf '%s\n' "repo=1alirezabahramian/talamala"
printf '%s\n' "write_gate=${KIMIA_WRITE_VERIFY_ENABLE:-0}"

sh ops/chabokan-kimia-runner/preflight.sh
rc=$?

if [ "$rc" -eq 0 ]; then
  printf '%s\n' "TALAMALA_KIMIA_RUNNER PREFLIGHT_OK"
else
  printf '%s\n' "TALAMALA_KIMIA_RUNNER PREFLIGHT_FAIL exit=$rc"
fi

if [ -f var/kimia-verify/preflight_meta.json ]; then
  php -r '
    $p="var/kimia-verify/preflight_meta.json";
    $j=json_decode((string)@file_get_contents($p), true);
    if (is_array($j)) {
      echo "swagger_version=".($j["swagger_version"] ?? "unknown").PHP_EOL;
      echo "swagger_sha256=".($j["swagger_sha256"] ?? "unknown").PHP_EOL;
      echo "base_host=".($j["base_host"] ?? "unknown").PHP_EOL;
    }
  '
fi

if [ -f var/kimia-verify/preflight_result.json ]; then
  php -r '
    $p="var/kimia-verify/preflight_result.json";
    $j=json_decode((string)@file_get_contents($p), true);
    if (is_array($j)) {
      echo "preflight_ok=".(($j["ok"] ?? false) ? "true" : "false").PHP_EOL;
      foreach (($j["steps"] ?? []) as $name=>$step) {
        if (is_array($step) && (($step["ok"] ?? true) === false)) {
          echo "failed_step=".$name.PHP_EOL;
          if (isset($step["message"])) echo "failed_reason=".$step["message"].PHP_EOL;
          break;
        }
      }
    }
  '
fi

printf '%s\n' "Runner is now idle; no automatic retry and no Write."
printf '%s\n' "After env/network changes, restart this Chabokan service to run one new read-only preflight."

exec tail -f /dev/null
