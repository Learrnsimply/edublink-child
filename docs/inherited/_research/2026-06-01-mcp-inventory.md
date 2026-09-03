Confirmed: zero project-level MCP config for Learn Simply. All active MCPs come from user-level `C:\Users\sw\.claude.json` (lines 969-1039) plus claude.ai web-app connectors (visible in the deferred tool list).

---

# MCP Inventory — Learn Simply Brand

## Table A — ACTIVE MCPs

Two layers feed this session: **(1) stdio servers** in `C:\Users\sw\.claude.json` lines 969-1039, and **(2) claude.ai web connectors** exposed as deferred `mcp__claude_ai_*` tools in this harness. No `.mcp.json` exists at workspace root, brand root, or any of the 3 team folders (verified via Glob + grep).

### Layer 1 — Local stdio MCPs (user-level `.claude.json`)

| # | Name | Command / Source | What it does | Useful for Learn Simply? |
|---|---|---|---|---|
| 1 | **context7** | `npx @upstash/context7-mcp@latest` | Fetches up-to-date library/framework/API docs (React, WP, WooCommerce SDKs, etc.) | **Y** — needed when touching WooCommerce REST, GA4 GTAG, Meta CAPI, Kashier SDK, FluentCRM hooks. Avoids stale training data. |
| 2 | **memory** | `npx @modelcontextprotocol/server-memory` | Knowledge-graph entity/relation store across sessions | **Y** — already storing Ahmed's profile, TikTok creds, plugin inventory, audit findings. Critical for the brand's accumulated context. |
| 3 | **token-optimizer** | `npx token-optimizer-mcp` | Token counting, compression, smart caching, smart_read/grep/edit wrappers | **Y (low priority)** — useful for big file reads in `01_WEB/website/` submodule, but not load-bearing. |
| 4 | **sequential-thinking** | `npx @modelcontextprotocol/server-sequential-thinking` | Structured multi-step reasoning tool | **Y** — for multi-stage triage like Phase 2 security work, Kashier migration plan, GTM rollout. |
| 5 | **linkedin-scraper** | `uvx linkedin-scraper-mcp@latest` | LinkedIn profile/company/jobs/messaging scraper | **N** — Ahmed's audience lives on YouTube/Telegram/FB, not LinkedIn. Zero relevance to Learn Simply ICP. |
| 6 | **github** | `npx @modelcontextprotocol/server-github` (PAT embedded) | Repo, PR, issues, code search via GitHub API | **Y — critical** — `Learrnsimply/edublink-child` theme repo, `omarabdo516/learn-simply-backups` weekly snapshots, Sprint 1 PR #1, all live here. ⚠️ **PAT is plaintext in `.claude.json` line 1025 — rotate.** |
| 7 | **playwright** | `npx @playwright/mcp` (pinned Chromium) | Headless browser automation, screenshots, network capture, form filling | **Y — critical** — used to verify Meta Pixel firing, `wp-json/wp/v2/users` 404, `.sql` template 403s, checkout flow. Already in active use (`.playwright-mcp/` cache exists in project root). |

### Layer 2 — claude.ai web connectors (visible as `mcp__claude_ai_*` deferred tools)

| # | Name | What it does | Useful for Learn Simply? |
|---|---|---|---|
| 8 | **Notion** | Search/create/update Notion pages & DBs | **Y** — `/notion-pr-sync` updates the GrowthMora Project Status DB; Learn Simply will join it once added as a tracked project. |
| 9 | **Gmail** | Read/search/label/draft emails | **Y** — Ahmed comms thread + Hostinger/Kashier/Meta support replies. Useful for `email-ops` skill workflows. |
| 10 | **Google Calendar** | Create/list events, suggest times | **Y (low)** — Ahmed sync calls, audit review meetings. |
| 11 | **Google Drive** | Search/read/create/copy files | **Y (low)** — only if Ahmed shares assets via Drive. |
| 12 | **Supabase** | Schema/SQL/edge functions/migrations | **N (today)** — Learn Simply is WordPress + MySQL, no Supabase yet. Reserve for AIOS data layer later. |
| 13 | **Vercel** | Deploy/logs/projects | **N** — site is on Hostinger LiteSpeed shared hosting, not Vercel. |
| 14 | **Cloudflare Developer Platform** | Workers/Pages/DNS | **N (today)** — DNS is at Hostinger. Only relevant if migrating DNS to Cloudflare. |
| 15 | **GoDaddy** | Domain availability/suggestions | **N** — Hostinger-registered domain. |
| 16 | **Higgsfield** | AI image/video generation, virality prediction | **Y (campaign work only)** — useful for short-form YouTube/TikTok creative ideation, NOT for the WP audit/security work that's currently in scope. |

---

## Table B — RECOMMENDED MCPs not installed

Filtered to Learn Simply's actual stack (WordPress + WooCommerce + Hostinger + GA4 + Meta Pixel + TikTok + Telegram + YouTube + FluentCRM/Mautic).

| Priority | MCP | What it adds | Install (user-level `.claude.json` → `mcpServers`) |
|---|---|---|---|
| **🔴 P0** | **WordPress MCP** (`@modelcontextprotocol/server-wordpress` or `automattic/wordpress-mcp`) | Direct WP REST/wp-cli over MCP — read posts/products/orders, update options, manage users, run wp-cli from chat without SSH session. Replaces 80% of the manual `ssh learnsimply && wp ...` loop. | Two options: **(a)** install the official Automattic plugin on the WP site (exposes a `/wp-json/wp/v2/mcp` endpoint, auth via App Password) and add an HTTP MCP entry. **(b)** Use the community `npx @instawp/mcp-wp` with `WP_URL` + App Password env. Recommended (b) for speed. |
| **🔴 P0** | **MySQL MCP** (`@modelcontextprotocol/server-mysql` or `benborla29/mcp-server-mysql`) | Read-only SQL over the Hostinger DB without spinning up SSH+mysqldump. Critical for autoload audits, order analytics, orphan-table scans. | `npx -y @benborla29/mcp-server-mysql` with `MYSQL_HOST/USER/PASS/DB` from Hostinger (already in brand `.env`). **Set `MYSQL_READONLY=true` to avoid accidental writes.** |
| **🔴 P0** | **Google Analytics (GA4) MCP** (`surendranb/google-analytics-mcp` or `googleanalytics/ga4-mcp`) | Pull funnel/conversion/event data directly. Today we have GA4 G-DT3Z0RSEBK firing PageView only — an MCP makes it trivial to audit which events actually land. | `pip install ga4-mcp` or `uvx ga4-mcp`. Needs OAuth client secrets + property ID (already known). |
| **🟠 P1** | **Meta / Facebook Ads MCP** (community `meta-ads-mcp`) | Read campaign/adset performance + CAPI server-events. Directly addresses the "Meta Pixel ID=0, access_token empty" finding from 2026-05-24 analytics audit. | `uvx meta-ads-mcp` with `META_ACCESS_TOKEN` + `META_AD_ACCOUNT_ID`. |
| **🟠 P1** | **TikTok Ads / Events MCP** (community `tiktok-ads-mcp` or hand-rolled MAPI wrapper) | TikTok already attributes **1.04M EGP / 2398 orders** — read attribution + creative perf in-session. | No mature public MCP yet; recommend small custom MCP wrapping the 13 TikTok creds already in `.env`. Defer until P0 done. |
| **🟠 P1** | **Telegram MCP** (`chigwell/telegram-mcp` or `modelcontextprotocol/server-telegram`) | Push audit summaries / Ahmed alerts directly. Currently uses `_agency/notify.sh` bash script — an MCP version means Claude can format + send rich messages inline. | `pip install telegram-mcp` with `TELEGRAM_BOT_TOKEN` + `TELEGRAM_CHAT_ID` (already in `_agency/notify.sh`). |
| **🟠 P1** | **YouTube Data API MCP** (`coyamo/youtube-mcp` or `googleworkspace/youtube-mcp`) | Read 369K-sub channel analytics, top videos, subs delta — feeds GTM roadmap decisions. | OAuth via Google Cloud project; reuse the same client as GA4 MCP. |
| **🟡 P2** | **Sentry / Bugsnag MCP** (`getsentry/sentry-mcp`) | If a Sentry project ever lands, this gives in-session error triage for the theme + plugins. | Defer until Sentry exists. |
| **🟡 P2** | **Stripe / Payment MCP** | Not applicable yet (Kashier today), but if Sprint 2 migrates to Paymob/Stripe, install then. | Defer. |
| **🟢 P3** | **Exa / Firecrawl Search MCP** | Already partly covered by global `WebSearch` + skills, but Exa gives neural search for research. | `npx -y exa-mcp` with `EXA_API_KEY`. Nice-to-have. |
| **🟢 P3** | **Filesystem MCP** (`@modelcontextprotocol/server-filesystem`) | Sandboxed file ops outside the project root (e.g., reading `_BRAIN/`, `Documents/GrowthMora/`). Mostly redundant with built-in Read/Write. | Skip unless cross-root reads become frequent. |

### Install pattern (paste into `C:\Users\sw\.claude.json` under `mcpServers`)

```jsonc
"mysql-learn-simply": {
  "type": "stdio",
  "command": "npx",
  "args": ["-y", "@benborla29/mcp-server-mysql"],
  "env": {
    "MYSQL_HOST": "<from brand .env>",
    "MYSQL_USER": "<readonly user>",
    "MYSQL_PASS": "<...>",
    "MYSQL_DB":   "u700430280_...",
    "MYSQL_READONLY": "true"
  }
}
```

Restart Claude Code after editing. Verify with `/mcp` (or check the deferred-tool list reappears with new names).

---

## ⚠️ Security note (file `C:\Users\sw\.claude.json` line 1025)

GitHub PAT `[REDACTED]` is stored in plaintext. Consistent with workspace "Private repo, secrets-OK" policy from root `CLAUDE.md`, but it's user-global (touches all brands). Rotate if this file has ever been shared / synced via GitHub. The brand `.env` files (already gitignored per `.gitignore`) are a safer home for new MCP creds.

## Sources

- `C:\Users\sw\.claude.json` lines 969-1039 — all 7 active stdio MCPs
- `C:\Users\sw\.claude\settings.json` — confirmed no `mcpServers` block here
- Glob over `Documents\Claude\**\.mcp.json` — only `_tools/after-effects-mcp/`, `brands/arc/`, `brands/rspaac/`, `brands/kitc/`, `rs-aios/` have project-level MCP files. **Not learn-simply.**
- Deferred-tool inventory in this harness — confirms 9 claude.ai web connectors (Notion, Gmail, Calendar, Drive, Supabase, Vercel, Cloudflare, GoDaddy, Higgsfield) plus the 7 stdio mirrors.
- Brand `.env` (21 KB) — confirmed presence of GA4, TikTok, Meta, Hostinger MySQL credentials needed for P0/P1 MCPs.