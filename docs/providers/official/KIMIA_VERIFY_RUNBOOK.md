# Kimia Verification Runner — RUNBOOK

**Write is default-deny.** Delivering this runner does **not** authorize POST.

## Layout

| Path | Role |
|------|------|
| `backend/app/Integrations/Kimia/Verify/*` | Gate, HTTP helper, evidence, catalog, orchestrator |
| `backend/bin/kimia_verify_runner.php` | CLI |
| `backend/bin/kimia_preflight_readonly.php` | Legacy read-only preflight (unchanged) |
| `backend/app/Integrations/Kimia/HttpKimiaReadClient.php` | **Unchanged** product Read client |
| `var/kimia-verify/` | Evidence (gitignored) |

## Env (secrets never committed)

```bash
export KIMIA_BASE_URL="https://YOUR_HOST"
export KIMIA_USERNAME="…"
export KIMIA_PASSWORD="…"
# optional:
# export KIMIA_SWAGGER_URL="${KIMIA_BASE_URL}/swagger/v1/swagger.json"
# export KIMIA_BASELINE_ACCOUNT_ID=350
```

### Write gate (all required for mutate)

```bash
export KIMIA_WRITE_VERIFY_ENABLE=1
export KIMIA_WRITE_OWNER_TOKEN="<unique-owner-issued-batch-token>"   # REQUIRED; no default
export KIMIA_WRITE_OWNER_AUTH="$KIMIA_WRITE_OWNER_TOKEN"
export KIMIA_WRITE_ACCOUNT_ALLOWLIST="350"
# REQUIRED: exact attempt budget for this Owner-authorized batch; omitted ops remain 0
export KIMIA_WRITE_ATTEMPT_BUDGET="buy=1"
# hard maxima enforced by code: buy=1,sell=1,receive=1,pay=1,create=5
```

## Commands

```bash
# Read-only (safe)
php backend/bin/kimia_verify_runner.php preflight
php backend/bin/kimia_verify_runner.php catalog

# ONE mutation — only after Owner authorizes a batch AND body/path taken from live Swagger
export KIMIA_MUTATE_PATH="/api/…"          # exact POST path from fresh live OpenAPI; absolute URLs rejected
export KIMIA_MUTATE_BODY_FILE="./payload.json"
export KIMIA_MUTATE_ACCOUNT_ID=350             # must match AccountId/accountId in payload
php backend/bin/kimia_verify_runner.php mutate buy

# Create is separate: no pre-existing account id / allowlist target
unset KIMIA_MUTATE_ACCOUNT_ID
php backend/bin/kimia_verify_runner.php mutate create
```

## Safety rules enforced

1. No POST unless `KIMIA_WRITE_VERIFY_ENABLE=1` + explicit non-default Owner batch token + fresh preflight + budget; account-targeted ops also require allowlist.
2. Mutation path must be an **exact relative POST path present in the fresh live Swagger**; absolute URLs are rejected so Kimia credentials cannot leak to another host.
3. For buy/sell/receive/pay, payload `AccountId/accountId` must equal the gated allowlisted account id. Create is the only no-preexisting-account exception.
4. Every operation budget defaults to **0**. The exact Owner-authorized budget must be set explicitly; code clamps it to hard maxima.
5. Each mutation slot is **atomically reserved before network send**; concurrent writes and unresolved crash-after-reserve states fail closed.
6. HTTP error, `outcome_unknown`, or successful POST with failed readback → **persistently halt the current Owner batch**. Continuing requires a newly issued Owner batch token.
7. Action codes **not** hard-coded; catalog records live enums; historical 32/64/2/4 are reference only.
8. Coin/Currency/Physical/Settlement ops not in runner batch scope.
9. Raw request/response evidence is fixed under `var/kimia-verify/`, attempt-scoped, may contain financial/identity data, and must never be committed or pasted into public logs.

## Local syntax check

```bash
php -l backend/bin/kimia_verify_runner.php
find backend/app/Integrations/Kimia/Verify -name '*.php' -print0 | xargs -0 -n1 php -l
php backend/bin/kimia_verify_runner.php preflight   # fails closed without env (exit 1)
```
