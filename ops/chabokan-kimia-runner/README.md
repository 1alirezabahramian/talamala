# Talamala — Chabokan Kimia Read-Only Runner

Purpose: run the existing Kimia **read-only preflight** from an Iran-based Chabokan service without touching the GoldPlatform agent/runtime.

## Isolation

- Repository: `1alirezabahramian/talamala`
- Branch: `ops/chabokan-kimia-runner`
- Service name suggestion: `talamala-kimia-runner`
- Product/runtime feature work: none
- Kimia Write: blocked; this image requires `KIMIA_WRITE_VERIFY_ENABLE=0`
- Evidence: `var/kimia-verify/` only (gitignored)

## Owner setup in Chabokan — minimum clicks

1. Create a **new Docker service** in an **Iran** location.
2. Connect GitHub repository `1alirezabahramian/talamala`.
3. Select branch `ops/chabokan-kimia-runner`.
4. Build from the root `Dockerfile` on this branch.
5. Add only these service environment variables/secrets:

```text
KIMIA_BASE_URL=<current Kimia base URL>
KIMIA_USERNAME=<secret>
KIMIA_PASSWORD=<secret>
KIMIA_WRITE_VERIFY_ENABLE=0
```

`KIMIA_SWAGGER_URL` is optional; leave it unset unless the live Swagger URL is different from `${KIMIA_BASE_URL}/swagger/v1/swagger.json`.

6. Deploy/restart once. No console command is required: the container runs exactly one read-only preflight on boot and then stays idle to avoid retry loops.
7. Open service logs. Expected terminal marker is one of:

```text
TALAMALA_KIMIA_RUNNER PREFLIGHT_OK
```

or

```text
TALAMALA_KIMIA_RUNNER PREFLIGHT_FAIL exit=<code>
failed_step=<step>
failed_reason=<reason>
```

The log summary prints only host / Swagger version / SHA-256 / failed step. It never intentionally prints `KIMIA_USERNAME` or `KIMIA_PASSWORD`.

## Evidence produced after a successful live run

The existing preflight writes under `var/kimia-verify/`:

- `preflight_result.json`
- `preflight_meta.json`
- `swagger_live.json`
- `swagger_diff_write_related.md`
- `account_350_balance_before.json`
- `account_350_transactions_before.json`

Do not commit those files. Send only the required sanitized evidence/summary to the verification flow.

## Safety behavior

- Missing base URL/username/password => preflight refuses to run.
- `KIMIA_WRITE_VERIFY_ENABLE` anything other than `0` => runner refuses to run.
- The existing PHP preflight itself rejects any HTTP method other than GET.
- Failure does not trigger an automatic retry loop; the container stays idle. After fixing env/network, restart the service to run once again.

## GoldPlatform

This runner does not use the GoldPlatform repository, workdir, queue, env, service, token, or agent runtime. Keep the Chabokan service separate.
