help:
	@echo 'Talamala targets:'
	@echo '  make check|domain|http|persist|cors|logger|maintenance|landing|spa|parity'
	@echo '  make frontend-typecheck|frontend-build|serve|version|php-syntax|kimia-write-contract|kimia-create-customer-contract|release-build'

# Talamala — operator shortcuts (no invent financial targets)

.PHONY: help info check smokes domain http persist cors logger maintenance landing spa parity php-syntax \
	frontend-typecheck frontend-build serve version kimia-write-contract kimia-create-customer-contract release-build verify-frontend

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
	@echo 'Kimia Write ACL: Batch V1 partial (no Order wire)'
	@echo 'Create Account ACL: PARTIAL (no Live Create, no registration wire)'
	@echo 'Blocked: Live Create evidence · Coin/Currency/Physical · Pricing · Settlement · Payment · SMS/Jibit · Delta'

release-build:
	bash scripts/release_build.sh

verify-frontend:
	bash scripts/verify_frontend.sh
