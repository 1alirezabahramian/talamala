# Read-only Verification Runner deploy checkpoint

Owner authorized sync/deploy of the Verification Runner to `talamala-kimia-runner` in READ-ONLY mode only.

- Write authorization: **NO**
- Financial mutation budget at runtime boot: **closed**
- Deployment source includes `KIMIA_WRITE_VERIFY_ENABLE=0` in Dockerfile and boot.
- This marker is operational only and does not authorize any Kimia mutation.
