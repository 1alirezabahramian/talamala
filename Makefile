help:
	@echo 'Talamala targets:'
	@echo '  make check|domain|http|persist|cors|logger|maintenance|landing|spa|parity'
	@echo '  make frontend-typecheck|frontend-build|serve|version|php-syntax'

# Talamala — operator shortcuts (no invent financial targets)

.PHONY: help check smokes domain http persist cors logger maintenance landing spa parity php-syntax \
	frontend-typecheck frontend-build serve version

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
