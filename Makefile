help:
	@echo 'Talamala targets:'
	@echo '  make check|domain|http|persist|cors|logger|maintenance|landing|spa|parity'
	@echo '  make frontend-typecheck|frontend-build|serve|version|php-syntax|kimia-write-contract|kimia-create-customer-contract|release-build'
	@echo '  make pilot-preflight     # offline Phase-1 pilot readiness (no Live Kimia)'
	@echo '  make pilot-host-smoke    # TALAMALA_BASE_URL=https://host  GET healthz/readyz only'
	@echo '  make pilot-env-check     # .env / template write-deny posture'
	@echo '  make pilot-all           # env-check + preflight (+ host if BASE_URL)'
	@echo '  make pilot-record        # write PILOT_SHA_RECORD.last.md from git'
	@echo '  make final-audit         # Final Audit Agent v2 (closure authority)'
	@echo '  make final-audit-summary # read-only current-SHA audit board'
	@echo '  make pilot-status        # VERSION/SHA/write-deny/audit snapshot'
	@echo '  make ci-attest-hint      # explain exact-SHA CI attestation (no fake success)'
	@echo '  make decimal-invariant   # decimal-string invariant smoke (exact PASS=13 FAIL=0)'
	@echo '  make pilot-gate-matrix   # print exact Phase-1 gate matrix'
	@echo '  make audit-domain-scorecard # current-SHA domain scorecard after final-audit'

# Talamala — operator shortcuts (no invent financial targets)

.PHONY: help info check smokes domain http persist cors logger maintenance landing spa parity php-syntax \
	frontend-typecheck frontend-build serve version kimia-write-contract kimia-create-customer-contract release-build verify-frontend pilot-preflight pilot-host-smoke pilot-env-check pilot-all pilot-record final-audit final-audit-summary pilot-status ci-attest-hint decimal-invariant pilot-gate-matrix audit-domain-scorecard

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
	@echo 'Expected http_smoke PASS=78 (see CURRENT_STATE)'
	@echo 'Phase-1: SAFE CLOSURE (frozen at 0.3.8-phase1)'
	@echo 'Pilot: make pilot-all · pilot-env-check · pilot-preflight · release-build · pilot-host-smoke'
	@echo 'Runbook: docs/00-master/PILOT_RUNBOOK.md'
	@echo 'Kimia Write ACL: Batch V1 partial (no Order wire)'
	@echo 'Create Account ACL: PARTIAL (no Live Create, no registration wire)'
	@echo 'Blocked: Live Create evidence · Coin/Currency/Physical · Pricing · Settlement · Payment · SMS/Jibit · Delta'
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
final-audit:
	python3 scripts/final_audit_agent_v2.py
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
