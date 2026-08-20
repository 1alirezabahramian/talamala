# Phase-1 pilot command card

```bash
make pilot-status
make pilot-env-check
make pilot-preflight
make pilot-gate-matrix
make release-build
TALAMALA_BASE_URL=https://host make pilot-host-smoke
make final-audit
make final-audit-summary
make audit-domain-scorecard
make ci-attest-hint
make pilot-record
```

Write stays off. Closure only via Final Audit Agent on exact SHA.
