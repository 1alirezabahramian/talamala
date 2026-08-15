# Talamala — operator shortcuts (no invent financial targets)

.PHONY: check smokes http persist cors logger maintenance landing spa parity php-syntax

check:
	php backend/bin/check.php

smokes: check

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

serve:
	cd backend && php -S 127.0.0.1:8080 -t public public/router.php
