help:
	@echo 'Talamala targets:'
	@echo '  make check|domain|http|persist|cors|logger|maintenance|landing|spa|parity'
	@echo '  make frontend-typecheck|frontend-build|serve|version|php-syntax|kimia-write-contract|kimia-create-customer-contract|release-build'
	@echo '  make pilot-preflight     # offline Phase-1 pilot readiness (no Live Kimia)'
	@echo '  make pilot-host-smoke    # TALAMALA_BASE_URL=https://host  GET healthz/readyz only'
	@echo '  make pilot-env-check     # .env / template write-deny posture'
	@echo '  make pilot-all           # env-check + preflight (+ host if BASE_URL)'
	@echo '  make pilot-record        # write PILOT_SHA_RECORD.last.md from git'

# Talamala — operator shortcuts (no invent financial targets)

.PHONY: help info check smokes domain http persist cors logger maintenance landing spa parity php-syntax \
	frontend-typecheck frontend-build serve version kimia-write-contract kimia-create-customer-contract release-build verify-frontend pilot-preflight pilot-host-smoke pilot-env-check pilot-all pilot-record

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

# Optional / advisory (Node required). Does not block CI green SHA.
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

# Offline Phase-1 pilot readiness: VERSION pin, freeze docs, write-deny defaults,
# php-syntax, domain_smoke, openapi parity, Kimia ACL contract smokes, frontend typecheck.
# Does not require pdo_sqlite or Iran runner. Never enables Live Write/Create.
pilot-preflight:
	bash scripts/pilot_preflight.sh

# Safe GET-only smoke against a deployed Host. Requires TALAMALA_BASE_URL.
# Does not call Kimia, does not send OTP/staff credentials.
pilot-host-smoke:
	bash scripts/pilot_host_smoke.sh

# Env posture for pilot (write-deny, no secret dump)
pilot-env-check:
	bash scripts/pilot_env_check.sh

# env-check → preflight → optional host smoke (TALAMALA_BASE_URL)
pilot-all:
	bash scripts/pilot_all.sh

# Fill docs/00-master/PILOT_SHA_RECORD.last.md from git (no secrets)
pilot-record:
	bash scripts/pilot_record.sh
