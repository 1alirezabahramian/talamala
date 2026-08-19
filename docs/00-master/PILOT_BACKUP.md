# Phase-1 pilot — SQLite backup & rollback

**Scope:** durable `TALAMALA_DB_PATH` on the pilot Host only.  
No Kimia Write/Create. No cloud invent.

## Before deploy / migrate

```bash
# example — adjust path to your TALAMALA_DB_PATH
DB=/var/lib/talamala/talamala.sqlite
TS=$(date -u +%Y%m%dT%H%M%SZ)
cp -a "$DB" "${DB}.bak.${TS}"
ls -la "${DB}.bak.${TS}"
```

Keep at least the last 3 backups until the pilot is stable.

## After successful smoke

Optional second copy off-box (scp/rsync) — still no secrets in git.

## Rollback

1. Stop traffic / stop PHP server  
2. Restore file:

```bash
cp -a /var/lib/talamala/talamala.sqlite.bak.<TS> /var/lib/talamala/talamala.sqlite
```

3. Redeploy **previous exact Git SHA**  
4. `TALAMALA_BASE_URL=… make pilot-host-smoke`  
5. Confirm `KIMIA_WRITE_VERIFY_ENABLE=0`

## Non-goals

- Do not "fix" balances from backups into Kimia  
- Do not run Live Create to recreate customers without Owner auth  
- Do not commit `.sqlite` or `.env` to git
