# Phase-1 pilot security baseline

| Control | Status |
|---------|--------|
| Host tenant fail-closed | Required |
| Session↔tenant isolation | Required |
| CORS allowlist | Required in production |
| CSP / Permissions-Policy / nosniff | Baseline on static + API |
| Correlation-Id | Required |
| Decimal money/weight strings | Required (no float) |
| Settlement blocked | Required until GT-005 |
| Kimia Write default-deny | Required |
| Live Create from registration | Forbidden without Owner auth |
| robots.txt blocks demos/dev | Required |
| Dev routes off in production | Required |

Evidence: http_smoke, http_negative_smoke, cors_smoke, domain_smoke, pilot_env_check.
