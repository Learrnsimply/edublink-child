# n8n Stack — Learn Simply

> **Status:** ✅ LIVE 2026-06-01 at https://n8n.learrnsimply.com
> **Architecture:** queue mode, Postgres 16 + Redis 7, 1 main + 1 worker (bounded concurrency 10)

## Quick reference

| | |
|---|---|
| **URL** | https://n8n.learrnsimply.com |
| **API base** | https://n8n.learrnsimply.com/api/v1 |
| **VPS path** | `/docker/n8n/` |
| **Repo path** | `02_AUTOMATION/n8n/docker-compose.yml` (version-controlled) |
| **Secrets** | `.env` on VPS (chmod 600) + brand `.env` section 16 (cross-device sync) |
| **Owner account** | PENDING — Omar creates via first-run wizard in browser |
| **API key** | PENDING — Omar generates in Settings → API after owner account exists |

## Why this architecture

| Choice | Reason |
|---|---|
| **Queue mode (not single-instance)** | Per `_research/2026-06-01-final-recommendations.md` finding #1: SQLite default + unbounded workers will OOM during 13K-subscriber broadcast on a single 16GB VPS |
| **Postgres 16, not MySQL 8.4** | n8n's preferred DB. Avoids sharing Mautic's MySQL (isolation: Mautic outage ≠ n8n outage) |
| **Redis with password + maxmemory 256mb LRU** | Bounded — prevents Redis from eating RAM if queue depth grows |
| **1 worker (not 2+)** | 16GB is "tight" per research; start with 1, scale when W3+W4 traffic crosses 200 conversations/day |
| **`N8N_CONCURRENCY_PRODUCTION_LIMIT=10`** | Hard cap on parallel executions per worker. Mitigation for finding #1 |
| **`EXECUTIONS_DATA_PRUNE` + 14d retention** | Postgres doesn't accumulate dead execution data forever |
| **Memory limits per service** | postgres 1G, redis 384M, n8n main 2G, worker 2G = 5.4G total cap (out of 13G available) |

## Operations

### Deploy / redeploy
```bash
ssh learnsimply-vps "cd /docker/n8n && docker compose pull && docker compose up -d"
```

### Check status
```bash
ssh learnsimply-vps "cd /docker/n8n && docker compose ps"
```

### View logs
```bash
ssh learnsimply-vps "cd /docker/n8n && docker compose logs n8n --tail=100 -f"
ssh learnsimply-vps "cd /docker/n8n && docker compose logs n8n-worker --tail=100 -f"
```

### Restart
```bash
ssh learnsimply-vps "cd /docker/n8n && docker compose restart"
```

### Backup volumes (Postgres + n8n config)
```bash
ssh learnsimply-vps "docker run --rm -v n8n_postgres-data:/data -v /backups:/backup alpine tar czf /backup/n8n-postgres-\$(date +%F).tar.gz -C /data ."
```

## Next steps (in order)

1. **Omar:** Open https://n8n.learrnsimply.com in browser → first-run wizard → create owner account (email + strong password)
2. **Omar:** Settings → API → Create API Key → save to brand `.env` `N8N_API_KEY=...`
3. **Claude:** Install n8n-MCP in `~/.claude.json` with `N8N_API_URL` + `N8N_API_KEY`
4. **Claude:** Build W1 workflow (WC → Mautic contact sync) per `_research/2026-06-01-final-recommendations.md` Section 5
5. **Omar + Claude:** Import Mautic MCP template #5184 into n8n → activate MCP Trigger webhook

## Known minor warnings (non-blocking)

| Warning | Impact | Action |
|---|---|---|
| `N8N_RUNNERS_ENABLED -> Remove this environment variable; it is no longer needed.` | None — flag is now default-on | Clean up in next compose iteration |
| `Python 3 is missing — Python task runner unavailable in internal mode` | Only affects users running Python code in Code nodes. JavaScript fine. | Defer — add `python3` install layer if needed later |
| `OFFLOAD_MANUAL_EXECUTIONS_TO_WORKERS recommended` | Manual test runs use main instance RAM | Add `OFFLOAD_MANUAL_EXECUTIONS_TO_WORKERS=true` in next compose iteration |

## Rollback plan

If anything goes wrong:
```bash
ssh learnsimply-vps "cd /docker/n8n && docker compose down"
# Volumes persist — data safe. To wipe entirely:
ssh learnsimply-vps "cd /docker/n8n && docker compose down -v"  # ⚠️ DESTROYS Postgres data
```

Mautic stack is independent — `docker compose down` on n8n stack does NOT affect Mautic.
