help:
	@echo 'Talamala targets:'
	@echo '  make check|domain|http|persist|cors|logger|maintenance|landing|spa|parity'
	@echo '  make frontend-typecheck|frontend-build|serve|version|php-syntax|kimia-write-contract|kimia-create-customer-contract|pricing-contract|release-build'
	@echo '  make pilot-preflight     # offline Phase-1 pilot readiness (no Live Kimia)'
	@echo '  make pilot-host-smoke    # TALAMALA_BASE_URL=https://host GET healthz/readyz only'
	@echo '  make pilot-env-check     # .env / template write-deny posture'
	@echo '  make pilot-all           # env-check + preflight (+ host if BASE_URL)'
	@echo '  make pilot-record        # write PILOT_SHA_RECORD.last.md from git'
	@echo '  make final-audit         # bounded Pilot closure authority'
	@echo '  make final-audit-release # stricter Full Release authority'
	@echo '  make final-audit-summary # read-only current-SHA audit board'
	@echo '  make pilot-status        # VERSION/SHA/write-deny/audit snapshot'
	@echo '  make ci-attest-hint      # explain exact-SHA CI attestation'
	@echo '  make decimal-invariant   # exact PASS=13 FAIL=0'
	@echo '  make pilot-gate-matrix   # print exact Phase-1 gate matrix'
	@echo '  make audit-domain-scorecard # current-SHA domain scorecard'
	@echo '  make pilot-offline-gates # diagnostic only; not closure authority'
	@echo '  make release-cycle1-http # exact PASS=9 FAIL=0'
	@echo '  make release-cycle2-http # exact PASS=6 FAIL=0'

.PHONY: help info check smokes domain http persist cors logger maintenance landing spa parity php-syntax \
	frontend-typecheck frontend-build serve version kimia-write-contract kimia-create-customer-contract pricing-contract release-build verify-frontend \
	pilot-preflight pilot-host-smoke pilot-env-check pilot-all pilot-record final-audit final-audit-release final-audit-summary \
	pilot-status ci-attest-hint decimal-invariant pilot-gate-matrix audit-domain-scorecard pilot-offline-gates \
	release-cycle1-http release-cycle2-http

check:
	php backend/bin/check.php
smokes: check
domain:
	php backend/bin/smoke.php
http:
	php backend/bin/http_smoke.php
persist:
	php backend/bin/persist_smoke.php
cors:
	php backend/bin/cors_smoke.php
logger:
	php backend/bin/logger_smoke.php
maintenance:
	php backend/bin/maintenance_smoke.php
landing:
	php backend/bin/landing_smoke.php
spa:
	php backend/bin/spa_router_smoke.php
parity:
	php backend/bin/openapi_parity_check.php
php-syntax:
	find backend/app backend/bin -name '*.php' -print0 | xargs -0 -n1 php -l
kimia-write-contract:
	php backend/bin/kimia_write_contract_smoke.php
kimia-create-customer-contract:
	php backend/bin/kimia_create_customer_contract_smoke.php
pricing-contract:
	php backend/bin/pricing_contract_smoke.php

# These local commands are blocking in CI.
frontend-typecheck:
	cd frontend/customer && npm ci && npm run typecheck
	cd frontend/backoffice && npm ci && npm run typecheck
frontend-build:
	cd frontend/customer && npm ci && npm run build
	cd frontend/backoffice && npm ci && npm run build

serve:
	cd backend && php -S 127.0.0.1:8080 -t public public/router.php
version:
	@cat VERSION
info:
	@echo VERSION=$$(cat VERSION 2>/dev/null || echo unknown)
	@echo 'Expected http_smoke PASS=78'
	@echo 'Phase-1: SAFE CLOSURE (frozen at 0.3.8-phase1)'
	@echo 'Release path: Cycle1 + Cycle2 exact HTTP evidence + blocking frontend gates + Release Authority'
	@echo 'Pilot: make pilot-all · pilot-env-check · pilot-preflight · release-build · pilot-host-smoke'
	@echo 'Blocked Full Release: GT-002/003/004/005/006/008/009 until grounded/implemented or explicitly deferred'

release-build:
	bash scripts/release_build.sh
verify-frontend:
	bash scripts/verify_frontend.sh
pilot-preflight:
	bash scripts/pilot_preflight.sh
pilot-host-smoke:
	bash scripts/pilot_host_smoke.sh
pilot-env-check:
	bash scripts/pilot_env_check.sh
pilot-all:
	bash scripts/pilot_all.sh
pilot-record:
	bash scripts/pilot_record.sh

# Pilot Agent semantics remain unchanged.
final-audit:
	python3 scripts/final_audit_agent_v2.py
# Strict wrapper: first Pilot authority, then release_required + RV-*.
final-audit-release:
	python3 scripts/release_audit_agent.py
final-audit-summary:
	python3 scripts/final_audit_summary.py
pilot-status:
	bash scripts/pilot_status.sh
ci-attest-hint:
	bash scripts/ci_attest_hint.sh
pilot-gate-matrix:
	bash scripts/pilot_gate_matrix.sh
audit-domain-scorecard:
	python3 scripts/audit_domain_scorecard.py
decimal-invariant:
	php backend/bin/decimal_invariant_smoke.php
pilot-offline-gates:
	bash scripts/pilot_offline_gates.sh
release-cycle1-http:
	php backend/bin/release_cycle1_http_smoke.php
release-cycle2-http:
	php backend/bin/release_cycle2_http_smoke.php
