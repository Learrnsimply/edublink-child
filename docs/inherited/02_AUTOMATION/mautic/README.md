# Mautic Stack — Learn Simply

> **Status:** ✅ LIVE 2026-06-01 at https://mautic.learrnsimply.com
> **Version:** Mautic 7.1.1 (Docker image `mautic/mautic:latest`)
> **DB:** MySQL 8.4
> **Server-side hardening:** ✅ Tier 1+2+3 applied 2026-06-01
> **Pending UI work:** see [OMAR_UI_CHECKLIST.md](OMAR_UI_CHECKLIST.md)

## Quick reference

| | |
|---|---|
| **URL** | https://mautic.learrnsimply.com |
| **Admin user** | `omar` / `omarabdo385@gmail.com` |
| **Admin password** | See brand `.env` section 16 |
| **API URL** | https://mautic.learrnsimply.com/api |
| **API auth** | HTTP Basic Auth with admin creds |
| **VPS path** | `/docker/mautic-r4bx/` |
| **Repo path** | `02_AUTOMATION/mautic/docker-compose.yml` (version-controlled) |
| **Compose project name** | `mautic-r4bx` (from Hostinger one-click setup) |
| **Sub-containers** | `mautic-r4bx-mautic-1` (web), `mautic-r4bx-mautic-cron-1`, `mautic-r4bx-mautic-worker-1`, `mautic-r4bx-db-1` |

## Configuration state (server-side, already applied)

### local.php parameters configured

| Key | Value | Why |
|---|---|---|
| `default_timezone` | Africa/Cairo | All timestamps in Mautic UI + DB |
| `mailer_*` (legacy keys) | Hostinger SMTP via SSL 465 | Email sending (Mautic 5/7 prefers legacy keys over Symfony Mailer DSN — see `feedback_mautic5_legacy_smtp_keys.md`) |
| `mailer_from_name` | "اتعلم ببساطة" | Arabic from-name in every email |
| `trusted_proxies` | `['REMOTE_ADDR']` | **Critical:** Tells Symfony to trust X-Forwarded-Proto from Traefik. Without this, infinite redirect loop on UI access |
| `api_enabled` | true | Allows REST API |
| `api_enable_basic_auth` | true | Allows HTTP basic auth on API (instead of OAuth) |
| `api_batch_max_limit` | 200 | Allows batch operations up to 200 |
| `mailer_amount_per_minute` | 5 | **Week 1 IP warmup limit** |
| `mailer_amount_per_hour` | 50 | **Week 1 IP warmup limit** (Hostinger SMTP cap) |
| `mailer_amount_per_day` | 200 | **Week 1 IP warmup limit** (conservative) |
| `unsubscribe_text` | "لإلغاء الاشتراك، {unsubscribe_text}" | Arabic unsubscribe link text in all emails |
| `webmaster_email` | `contact@learrnsimply.com` | System notifications recipient |
| `mailer_memory_msg_limit` | 100 | Mailer memory-spool flush cap |

### Workers — bounded supervisord

Default Mautic Docker workers are UNBOUNDED → memory leak during 13K broadcast = OOM risk.

Custom `supervisord-bounded.conf` mounted at `/etc/supervisor/conf.d/supervisord.conf`:

| Queue | Memory limit | Time limit | Message limit |
|---|---|---|---|
| `messenger:consume email` | 128M | 160s | 60 messages |
| `messenger:consume hit` | 128M | 300s | 200 messages |
| `messenger:consume failed` | 64M | 600s | 50 messages |

Behavior: when ANY limit is reached, worker exits gracefully → supervisord restarts with fresh PHP process → no leak.

### Cron schedule (default, validated)

Mautic Docker image already implements the research-recommended cron stagger pattern:

```
0,15,30,45 * * * *  mautic:segments:update     # segments
5,20,35,50 * * * *  mautic:campaigns:update    # campaign membership
10,25,40,55 * * * * mautic:campaigns:trigger   # campaign events
```

No additional cron changes needed. Stagger prevents segment/campaign lock conflicts (Deep Research finding #4).

## Custom Fields (11 added — total 39 contact fields)

| Alias | Type | Purpose |
|---|---|---|
| `wc_customer_id` | number | WooCommerce customer ID for cross-reference |
| `course_interest` | text | Which courses they've shown interest in |
| `last_purchase_date` | datetime | Most recent WC purchase |
| `course_count` | number | Total courses purchased |
| `total_spent` | number | Cumulative spending (EGP) |
| `cart_value` | number | Current abandoned cart value (EGP) |
| `last_course_completed` | text | Most recent finished course |
| `source_channel` | select | Acquisition channel (website/whatsapp/telegram/facebook/instagram/youtube/tiktok/direct/other) |
| `telegram_chat_id` | text | For Telegram bot (W4) |
| `whatsapp_phone` | text | E.164 format for WhatsApp (W3) |
| `referrer` | text | UTM source or referral path |

## Segments (9 strategic)

| Alias | Logic | Purpose |
|---|---|---|
| `all_contacts` | email !empty | Baseline for broadcast scoping |
| `engaged_30d` | last_active ≥ -30 days | Primary broadcast target |
| `dormant_90d` | last_active < -90 days | Re-engagement targets (bulk of 13K) |
| `wc_buyers` | course_count > 0 | Anyone who bought |
| `high_value` | total_spent > 5000 EGP | VIPs |
| `non_buyers` | email !empty AND course_count empty | Top conversion targets |
| `active_cart` | cart_value > 0 | Abandoned cart recovery |
| `whatsapp_contacts` | whatsapp_phone !empty | For W3 agent targeting |
| `telegram_contacts` | telegram_chat_id !empty | For W4 agent targeting |

## Tags (24 starter taxonomy)

- **Lifecycle:** `lead`, `customer`, `paid-customer`, `refunded`, `churned`, `reactivated`
- **Behavior:** `engaged`, `dormant`, `high-intent`, `low-intent`, `cart-abandoned`, `review-given`
- **Tier:** `vip`
- **Source channel:** `whatsapp-lead`, `telegram-lead`, `instagram-lead`, `facebook-lead`, `youtube-lead`
- **Product interest:** `course-python`, `course-flutter`, `course-react`, `course-laravel`
- **Customer state:** `wc-customer`, `free-resource`

## Operations

### Check stack status
```bash
ssh learnsimply-vps "docker ps --filter name=mautic --format 'table {{.Names}}\t{{.Status}}'"
```

### View Mautic logs
```bash
ssh learnsimply-vps "docker logs mautic-r4bx-mautic-1 --tail=50 -f"
ssh learnsimply-vps "docker exec mautic-r4bx-mautic-1 tail -50 /var/www/html/var/logs/mautic_prod-\$(date +%F).php"
```

### Restart Mautic web (e.g., after local.php changes)
```bash
ssh learnsimply-vps "docker restart mautic-r4bx-mautic-1"
```

### Recreate worker (e.g., after supervisord conf changes)
```bash
ssh learnsimply-vps "cd /docker/mautic-r4bx && docker compose up -d --force-recreate mautic-worker"
```

### Clear Mautic cache (if config changes don't take effect)
```bash
ssh learnsimply-vps "docker exec mautic-r4bx-mautic-1 php /var/www/html/bin/console cache:clear --env=prod --no-debug && docker exec mautic-r4bx-mautic-1 chown -R www-data:www-data /var/www/html/var/cache"
```

### Verify workers running with bounded flags
```bash
ssh learnsimply-vps "docker exec mautic-r4bx-mautic-worker-1 ps aux | grep messenger"
```

### API test (returns empty list when no contacts)
```bash
curl -u 'omar:<password>' https://mautic.learrnsimply.com/api/contacts?limit=1
```

## Critical files in this repo

| File | Purpose |
|---|---|
| `docker-compose.yml` | Mautic stack compose (web+cron+worker+db) with bounded supervisord mount |
| `supervisord-bounded.conf` | Worker supervisord with `--memory-limit/--time-limit/--limit` flags |
| `add-trusted-proxies.php` | One-shot script: adds `trusted_proxies` to local.php |
| `enable-api.php` | One-shot script: enables Mautic API + basic auth |
| `configure-send-limits.php` | One-shot script: sets IP warmup throttle limits |
| `create-custom-fields.sh` | API-driven bulk field creation |
| `create-segments.sh` | API-driven bulk segment creation |
| `OMAR_UI_CHECKLIST.md` | UI tasks Omar must do (DKIM, DNS, bounce mailbox, theme) |

## Backups (all on VPS inside `mautic-r4bx-mautic-1` container)

`/var/www/html/config/`:
- `local.php.bak-20260601` — original (pre-trusted_proxies)
- `local.php.bak-redirect-fix-20260601` — pre-trusted_proxies snapshot
- `local.php.bak-api-enable-20260601` — pre-API
- `local.php.bak-send-limits-20260601` — pre-send-limits

`/docker/mautic-r4bx/`:
- `docker-compose.yml.bak-20260601` — pre-supervisord-mount

## Known limitations

| Limitation | Workaround / Plan |
|---|---|
| Mautic API errors cite "error #500" generically | Check `/var/www/html/var/logs/mautic_prod-YYYY-MM-DD.php` inside container for real cause |
| Cache directory permissions need www-data ownership after `cache:clear` | Run `chown -R www-data:www-data /var/www/html/var/cache` after every clear |
| Domain mapping warnings on `doctrine:schema:validate` | Known Mautic 5/7 cosmetic — Campaign + DynamicContent bidirectional mapping. Not fatal. |
| Hostinger SMTP daily cap ~3000 emails | Migrate to Amazon SES or Postmark for 13K broadcast (planned Week 4) |
| Default IP warmup limits very conservative (200/day) | After 7 days clean reputation + DNS verified, raise to 2000/day. After 14 days → 5000/day. After 21 days → 13K full |

## Related docs

- Brand `.env` section 16 — all VPS + Mautic credentials
- `_research/2026-06-01-final-recommendations.md` — original architectural decisions
- `_research/2026-06-01-deep-research-marketing.md` — IP warmup + deliverability research
- `02_AUTOMATION/n8n/README.md` — n8n stack for upcoming W1-W7 workflows
- `~/.claude/projects/.../memory/feedback_mautic5_legacy_smtp_keys.md` — Why we use legacy mailer_* keys, not DSN
