#!/usr/bin/env bash
# Print Phase-1 gate matrix (expected PASS counts). Fail-closed documentation only.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
echo "======== Phase-1 gate matrix ========"
echo "VERSION=$(tr -d '[:space:]' < VERSION)"
cat << 'MATRIX'
| Gate | Command | Expect |
|------|---------|--------|
| domain_smoke | php backend/bin/smoke.php | PASS=13 FAIL=0 |
| decimal_invariant | php backend/bin/decimal_invariant_smoke.php | PASS=13 FAIL=0 |
| http_smoke | php backend/bin/http_smoke.php | PASS=78 FAIL=0 |
| http_negative | php backend/bin/http_negative_smoke.php | PASS=17 FAIL=0 |
| persist_smoke | php backend/bin/persist_smoke.php | PASS=9 FAIL=0 |
| cors_smoke | php backend/bin/cors_smoke.php | PASS=13 FAIL=0 |
| logger_smoke | php backend/bin/logger_smoke.php | PASS=8 FAIL=0 |
| maintenance_smoke | php backend/bin/maintenance_smoke.php | PASS=7 FAIL=0 |
| spa_router_smoke | php backend/bin/spa_router_smoke.php | PASS=6 FAIL=0 |
| landing_smoke | php backend/bin/landing_smoke.php | PASS=18 FAIL=0 |
| openapi_parity | php backend/bin/openapi_parity_check.php | PASS=22 FAIL=0 |
| kimia_write_contract | make kimia-write-contract | offline ACL |
| kimia_create_contract | make kimia-create-customer-contract | offline ACL |
| pilot_preflight | make pilot-preflight | FAIL=0 |
| release_build | make release-build | OK |
| final_audit | make final-audit | see No Human Green |
MATRIX
echo "Write: KIMIA_WRITE_VERIFY_ENABLE must stay 0 for pilot."
