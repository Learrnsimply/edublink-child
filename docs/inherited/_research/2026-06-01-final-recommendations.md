# Learn Simply — Tooling, Patterns, Marketing Stack Recommendations
**Date:** 2026-06-01
**For:** Omar (GTM Engineer, Learn Simply brand)
**Stack state:** Mautic LIVE, n8n PENDING

---

## 1. TL;DR — Top 7 immediate actions

1. **Add SPF + DKIM + DMARC for `learrnsimply.com` and `mautic.learrnsimply.com` BEFORE first Mautic broadcast** — Without these, the 13,140-subscriber activation campaign will land in Promotions tab or spam under Gmail/Yahoo Feb 2024 + Outlook May 2025 bulk-sender rules. Effort: **S**. Ref: Deep Research finding #2.

2. **Deploy n8n with bounded queue mode + Postgres + Redis on the VPS, not SQLite default** — Single-VPS Mautic + n8n + Traefik + Evolution API will OOM on first 13K broadcast if n8n uses SQLite and unbounded workers. Effort: **M**. Ref: Deep Research finding #1 + open question #2.

3. **Install n8n-MCP into Claude Code** (`czlonkowski/n8n-mcp`) — Unlocks 1,851 nodes + 2,352 templates + 13 workflow-management tools from Claude. Highest single-MCP leverage in this stack. Effort: **S**. Ref: Deep Research finding #5.

4. **Port `automation/CLAUDE.md` + `_data/schemas/supabase_schemas.md` from rs-aios** as the foundation for Learn Simply's AI agent context + lead schema. Skip the Supabase part for now (we're on MySQL/Mautic), but the agent-rule structure and the 5-stage funnel pattern transfer 1:1. Effort: **M**. Ref: RS-AIOS exploration items #2 + #4.

5. **Create project-local `brands/learn-simply/.claude/settings.json` + per-project skill `learn-simply-platform`** — Today every session re-learns the SSH alias, WP path, plugin list, deploy URL from scratch. A 200-line per-project skill eliminates that overhead and pins Bash allowlists. Effort: **S**. Ref: Claude Code Tooling Inventory Table C.

6. **Schedule Mautic cron jobs with the documented stagger pattern** (`0,15,30,45 / 5,20,35,50 / 10,25,40,55`) + bounded `messenger:consume email --time-limit=160 --memory-limit=128M --limit=60`. Skipping the stagger causes segment/campaign lock conflicts on the VPS. Effort: **S**. Ref: Deep Research finding #1, [docs.mautic.org/en/5.2/configuration/cron_jobs.html](https://docs.mautic.org/en/5.2/configuration/cron_jobs.html).

7. **Connect WordPress↔Mautic via the `n8n-nodes-base.mautic` node + WC trigger** as the very first n8n workflow, BEFORE any AI agent work. Without WC→Mautic sync, the 13K list will keep growing in WP while Mautic stays stale. Effort: **M**. Ref: Deep Research refuted item #6 (template #1456 exists but oversold — we build it ourselves using the documented Mautic node).

---

## 2. Patterns to PORT from RS brand

| Pattern | Source path | What it does | Adaptation needed for Learn Simply | Priority |
|---|---|---|---|---|
| **5-stage sales agent prompt** | `rs-aios/automation/agents/messenger_v6.json` + `rspaac/02_AUTOMATION/agents/messenger-sales-agent-LIVE.md` (17.8 KB) + `whatsapp-sales-agent-LIVE.md` (14.8 KB) | Gemini-powered customer funnel: need → format → pricing → payment → lead-insert. Tool-using agent with `pricing_agent`, `knowledge_base`, `leads_insert`. | Swap "ورشة" → "كورس". Swap RS instructors → Ahmed Adel. Swap workshop pricing (50% deposit) → course tuition (full / installments). Swap Supabase `leads_*` → Mautic contact upsert via n8n `mautic` node. Keep the Egyptian-Arabic tone rules, the binary-question rule, the zero-placeholder rule. | **P0** |
| **Agent CLAUDE.md (brand voice + business rules)** | `rs-aios/automation/CLAUDE.md` + `rspaac/02_AUTOMATION/agents/CLAUDE.md` | Defines tone (Egyptian street dialect, not formal MSA), STOP-and-lookup rules for pricing/dates, terminology fixes, max emoji count. Prevents hallucinated prices. | Replace RS workshop terminology with Ahmed's course catalog terms. Match Ahmed's actual voice from YouTube transcripts (use `brand-voice` skill to extract). Add Kashier-specific failure-recovery instructions. | **P0** |
| **Hybrid memory pattern (customer profile + session bucket)** | `rs-aios/automation/agents/messenger_v6.json` (memory nodes) | Keeps a long-lived customer profile in DB + short rolling chat-history window keyed by `customer_id` or phone. Stops stale data leaking into new conversations. | Replace Supabase `rs_chat_histories(_wa)` with **Postgres Chat Memory** in n8n (per Deep Research finding #4) keyed on `phone_number` for WhatsApp, `chat_id` for Telegram, `session_id` for on-site widget. Per-channel session-key prefix. | **P0** |
| **Tactics Coach 6-phase self-improving loop** | `rspaac/02_AUTOMATION/_tactics_coach/` (phases 1-6) | Daily Beta-Bernoulli scoring on tactic outcomes → auto-archives losers (score<0.25, n≥10) → weekly Claude routine generates ≤2 new tactics → Telegram alerts on anomalies. | Adapt for course pitch variants: `tactics_library` → `course_pitch_variants`. Outcome metric: enrollment_created vs cart_abandoned. Daily cron at 02:00 Cairo (already set up for rspaac, copy the structure). **Defer to D31-D60** — needs ≥2 weeks of data first. | **P1** |
| **Telegram bot reuse pattern** | `_agency/notify.sh` + `rspaac/02_AUTOMATION/_tactics_coach/phase6_telegram_notifier/SETUP.md` | Single workspace-wide bot (token + chat_id `6726176133`), reused across all brands. | **DO NOT create a new bot.** Reuse the existing one. Just add Learn-Simply-prefixed alert payloads. Critical per root `CLAUDE.md` rule. | **P0** |
| **Messenger daily QA review prompt** | `rspaac/02_AUTOMATION/scheduled-prompts/messenger-daily-qa-prompt.md` | Scheduled Claude routine reads last 24h of conversations, flags pricing errors / tone violations / skipped stages, suggests prompt fixes. | Point at Mautic activity log + n8n chat-history table instead of Supabase. Same error categories. Useful AFTER agent goes live. | **P2** |
| **Cron-driven safety nets** | `rs-aios/automation/workflows_registry.md` (Brief Backfill every 3h) | Documents how to add "safety net" workflows that catch missed events (e.g., lead created but no brief generated). | Build equivalent for Learn Simply: every 3h, scan Mautic contacts created in last 24h with no `course_interest` tag → re-trigger classification agent. | **P2** |

**Not worth porting:** The 28 Next.js dashboard API routes (`rs-aios/dashboard/custom/src/app/api/`) — wrong stack (Learn Simply is WP, not Next.js), and the Meta posting / design generation logic doesn't fit a course business. The 28 `rs-*` content-marketing skills target a digital agency selling marketing services, not an instructor selling programming courses.

---

## 3. Tools, Skills, Agents, Commands to INSTALL

| Name | Type | What it does | How to install | Priority |
|---|---|---|---|---|
| **`learn-simply-platform`** | per-project skill | Single source of truth: SSH alias `learnsimply`, WP path `/home/u700430280/domains/learrnsimply.com/public_html`, 61-plugin inventory, VPS IP 187.124.9.249, Mautic/n8n URLs, deploy procedure. Loaded automatically each session. | Create `brands/learn-simply/.claude/skills/learn-simply-platform/SKILL.md`. ~200 lines, copy from CLAUDE.md + setup-integrations.md. | **P0** |
| **`brands/learn-simply/.claude/settings.json`** | project config | Pins Bash allowlist (`wp` via ssh, `gh`, `mysqldump`, `docker compose` on VPS), denies destructive ops (`rm -rf`, `wp db drop`, `chmod 777`), sets `additionalDirectories` to include `_BRAIN/`. | Create file; mirror structure from `brands/rspaac/.claude/settings.json` if it exists, otherwise from `~/.claude/settings.json`. | **P0** |
| **SSH-guard PreToolUse hook** | hook | Blocks destructive commands on `ssh learnsimply` connection per CLAUDE.md "no `rm -rf` without confirmation" rule. Uses already-installed `hookify-rules` skill to author it. | `/hookify-configure` then add rule matching Bash with `ssh learnsimply.*rm -rf|wp db drop|crontab -e|chmod 777`. | **P0** |
| **`wordpress-patterns` + `woocommerce-ops` + `mautic-patterns` custom skills** | skills (3) | Stack-specific knowledge that no installed skill covers. Even thin stubs unblock Sprint 2/3 work. | Create under `~/.claude/skills/`. Seed each with today's lessons (autoload cleanup, .htaccess hardening, HPOS migration plan, Mautic cron pattern). | **P0** |
| **`/ls-status` slash command** | command | Pulls in one shot: WC monthly revenue, active cart count, Kashier 24h failure rate, GA4 pixel-fire status, Mautic queue depth, n8n active workflows count. Solves the recurring "where are we?" overhead at session start. | Create `~/.claude/commands/ls-status.md`. Use Bash with `wp wc order list --status=processing`, MySQL count queries, GA4 MCP, Mautic API. | **P0** |
| **`security-review` + `audit` + `verify` (existing)** | existing skills+command | Already installed and in active use this session. Keep using them on every WP-facing change. | No install — already in use (Procedure A this session was effectively `/security-review` + `/verify`). | **P0** |
| **`continuous-learning-v2` (project-scoped)** | existing skill | Auto-extracts reusable patterns from sessions as project-scoped instincts. v2.1 explicitly prevents cross-brand contamination. | Already installed. Enable project scope via `/configure-ecc` or by adding `project: learn-simply` in the skill's config. Critical to keep RS lessons out of Learn Simply context and vice versa. | **P1** |

**Honorable mentions (defer):** `claude-devfleet` + `/devfleet` for parallel Sprint 2 work (Kashier + WC + Mautic in parallel) — install once Sprint 2 actually starts. `eval-harness` + `/eval` for AI agent QA before going live — install when AI agent is built.

---

## 4. MCPs that would unlock new capabilities

| MCP | What it would enable | Install command | Priority |
|---|---|---|---|
| **n8n-MCP** (czlonkowski/n8n-mcp) | 1,851 nodes + 2,352 templates + 13 workflow-management tools (search_templates by `task`/`metadata`/`complexity`/`required services`, validate_workflow with AI Agent validation v2.17.0). Claude creates/updates/validates workflows directly on the VPS n8n instance. **The highest single-MCP leverage for this stack.** | Per docs at github.com/czlonkowski/n8n-mcp/blob/main/docs/CLAUDE_CODE_SETUP.md — stdio MCP, needs `N8N_API_URL=https://n8n.learrnsimply.com/api/v1` + `N8N_API_KEY`. Mature, actively maintained as of 2026. | **P0** |
| **MySQL MCP (READ-ONLY)** (`@benborla29/mcp-server-mysql`) | Read-only SQL against the Hostinger WP DB (and the Mautic DB on VPS) without spinning up SSH + mysqldump every time. Critical for autoload audits, order analytics, Mautic segment debugging, orphan-table scans. Mature. | Add to user `.claude.json` mcpServers: `npx -y @benborla29/mcp-server-mysql` with `MYSQL_HOST/USER/PASS/DB` from brand `.env`. **Must set `MYSQL_READONLY=true`** — accidental writes on production WP DB are a serious risk. | **P0** |
| **Mautic MCP via n8n template #5184** | All 20 Mautic operations (8 contact + 5 company + 2 segment + 2 campaign-contact + 2 company-contact + segment email send) exposed as an MCP webhook from n8n itself. Claude updates Mautic contacts, segments, campaigns without leaving the chat. | Import n8n.io/workflows/5184 into the VPS n8n; activate the MCP Trigger webhook; add the webhook URL as an HTTP MCP in `.claude.json`. Mature for what it does, but it's a community template — validate against Mautic 5.2 before production. | **P0** |
| **GA4 MCP** (`surendranb/google-analytics-mcp` or `uvx ga4-mcp`) | Pull funnel/conversion/event data inline. Today GA4 G-DT3Z0RSEBK fires PageView only (per analytics audit 2026-05-24). MCP makes it trivial to audit which events actually land + verify Phase 1 GTM rollout. | `uvx ga4-mcp` with OAuth client secrets + property ID (already documented). Mature as of 2026. | **P0** |
| **Telegram Bot MCP** (`chigwell/telegram-mcp`) | Direct rich-message posting from Claude → existing workspace Telegram bot. Replaces `_agency/notify.sh` bash for Mautic queue alerts, Kashier failure alerts, AI agent daily QA digests. Mature. | `pip install telegram-mcp`; reuse token + chat_id `6726176133` already in `_agency/notify.sh`. | **P1** |

**Honest 2026 maturity notes:**
- **Meta Ads MCP (April 29 2026 open beta)** — track release notes, **don't deploy yet**. Per Deep Research finding #8, access gating + feature stability still evolving. Revisit Sept 2026.
- **TikTok Ads MCP** — no mature public option as of 2026-05. We already have 13 TikTok creds in `.env`; if needed, a small custom n8n MCP wrapper is the pragmatic path. Defer until Sprint 4.
- **WordPress MCP via n8n template #5060** — exists, but $25 paid template covering only Posts/Pages/Users (12 ops). The wp-cli-over-SSH path we already use is sufficient for now. Skip.
- **YouTube Data API MCP** — useful for Ahmed's 369K-sub channel analytics → GTM roadmap. Defer to D31-D60 when content-engine work starts.

⚠️ **Security note for MCP install:** the GitHub PAT in `C:\Users\sw\.claude.json` line 1025 (`[REDACTED]`) is plaintext and user-global (touches all brands). Rotate before sharing `.claude.json` anywhere. New MCP creds belong in brand `.env`, not user `.claude.json`.

---

## 5. n8n workflow recommendations (build order)

Build in this order. Each unlocks the next.

### W1 — WC → Mautic contact sync (foundation, no AI)
- **Trigger:** WooCommerce webhook `customer.created` + `order.created` (set in WP admin).
- **Steps:** Webhook → Function node to map WC customer fields → Mautic node `contacts:create-or-update` with email/first_name/phone/tags `[wc-customer, course:{course_slug}]`.
- **AI role:** None.
- **Data flow:** WP/WC → n8n → Mautic. Adds new contacts, updates existing.
- **Outcome:** Mautic stops being stale. All 13K subs + every new buyer now syncable. Prerequisite for everything else.
- **Reference:** Mautic n8n node docs (`docs.n8n.io/integrations/builtin/app-nodes/n8n-nodes-base.mautic`). NOT template #1456 (refuted in Deep Research — exists but unreliable).

### W2 — Cart-abandonment recovery (Kashier-aware)
- **Trigger:** Cron every 30 min + WC order status webhook.
- **Steps:** MySQL node reads WC sessions with cart contents + `last_activity > 1h && < 24h` AND no completed order. Filter by Kashier failure events (909 failed CC = 195K EGP/year exposure). Branch: payment-failed vs distraction-abandoned (different copy). Push contact + tag to Mautic → triggers Mautic campaign.
- **AI role:** Optional Claude API call to personalize the recovery message based on cart contents + customer course history.
- **Data flow:** WC DB → n8n → Mautic campaign → SMTP send.
- **Outcome:** Per `bugs-report.md` Sprint 2 estimate, ~150K EGP/2 weeks recoverable from 1645 active sessions.
- **Reference:** Adapt n8n.io/workflows/6322 (Automated WooCommerce Abandoned Cart Recovery) — NOT a course-specific template (none exists per Deep Research finding #7); adapt the e-commerce pattern.

### W3 — WhatsApp AI sales agent via Evolution API
- **Trigger:** Webhook from Evolution API on incoming WhatsApp message.
- **Steps:** Webhook → MySQL lookup contact by phone → Postgres Chat Memory (keyed on phone) → Claude Sonnet 4.7 with tools `[pricing_agent, course_catalog, mautic_upsert]` → reply via Evolution API → upsert to Mautic with stage tag.
- **AI role:** Primary — 5-stage funnel agent ported from rspaac `whatsapp-sales-agent-LIVE.md`, Egyptian dialect, binary questions, zero-placeholder rule.
- **Data flow:** WhatsApp → Evolution API → n8n → Claude → Mautic.
- **Outcome:** First touch on Ahmed's WhatsApp inbound (today: manual). Expected lift on lead-to-enrollment after 2 weeks of tactic data.
- **Reference:** Per Deep Research caveat #3, Evolution API Baileys mode is reverse-engineered — start with Meta's official WhatsApp Cloud API for production. Baileys only acceptable for prototyping.

### W4 — Telegram AI agent (same brain, different channel)
- **Trigger:** Telegram Bot webhook (new bot for Learn Simply customer-facing — different from the `_agency/notify.sh` internal alerts bot).
- **Steps:** Same Claude tool-using agent as W3, different session-key prefix (`tg:{chat_id}` instead of `wa:{phone}`).
- **AI role:** Identical to W3.
- **Data flow:** Telegram → n8n → Claude → Mautic (Telegram chat_id stored in custom field).
- **Outcome:** Ahmed's 24.4K Telegram channel (60-86% engagement) now has a DM funnel.
- **Reference:** n8n.io/workflows/5311 (AI-Powered Telegram and WhatsApp Business Agent) as pattern. Per Deep Research finding #4, use distinct session-key prefixes per channel.

### W5 — Mautic broadcast safety net + queue monitor
- **Trigger:** Cron every 5 min.
- **Steps:** SSH/HTTP check Mautic `messenger:consume` worker status. If queue depth > 5000 OR worker not running → restart via VPS-side command + Telegram alert to existing bot.
- **AI role:** None (operational).
- **Data flow:** Mautic queue → n8n → Telegram.
- **Outcome:** Prevents silent failure during 13K-subscriber broadcast. Addresses Deep Research open question #2.

### W6 — Daily KPI digest (Telegram)
- **Trigger:** Cron 09:00 Cairo.
- **Steps:** Parallel: MySQL WC (orders, revenue, cart count) + GA4 MCP (sessions, conversions) + Mautic API (sent/opened/clicked) + Kashier failure count → format → Telegram.
- **AI role:** Optional Claude summary at end ("anomaly detected: cart abandons up 40% vs 7-day avg, possible Kashier issue").
- **Data flow:** All sources → n8n → Telegram bot chat `6726176133`.
- **Outcome:** Ahmed + Omar know the state of the business every morning without logging into 4 dashboards.

### W7 — Cohort enrollment Tactics Coach (D31+)
- **Trigger:** Daily cron 02:00 Cairo.
- **Steps:** Port of rspaac `_tactics_coach/phase4_daily_cron/daily_score_update.sql` adapted for `course_pitch_variants`. Update Beta-Bernoulli scores, auto-archive losers, weekly Claude routine generates ≤2 new variants.
- **AI role:** Weekly Claude analysis routine.
- **Data flow:** Mautic + WC → n8n → Mautic (custom field `winning_pitch_variant`).
- **Outcome:** After 2-3 weeks: data-ranked course pitches. Defer to Phase 2 of the 90-day roadmap.

---

## 6. Email deliverability — concrete DNS records to add

Add these in Hostinger DNS panel for **`learrnsimply.com`**. Mautic sends from `mautic.learrnsimply.com` subdomain so the DKIM selector lives on the subdomain.

```dns
; ============================================================
; SPF — Sender Policy Framework
; Authorizes Hostinger SMTP (smtp.hostinger.com) + Mautic VPS to send mail
; Place ONE SPF record on the root domain — multiple records = SPF fails
; ============================================================
learrnsimply.com.   IN  TXT  "v=spf1 include:_spf.hostinger.com ip4:187.124.9.249 ~all"

; If you ever add Amazon SES / Postmark for the 13K broadcast (recommended — see open question #3):
; learrnsimply.com.   IN  TXT  "v=spf1 include:_spf.hostinger.com include:amazonses.com ip4:187.124.9.249 ~all"

; ============================================================
; DKIM — DomainKeys Identified Mail (selector "mautic" is Mautic community convention)
; You must generate the RSA key inside Mautic (Settings > Configuration > Email > DKIM) first,
; then paste the public key here. The "p=" value is the actual key.
; ============================================================
mautic._domainkey.learrnsimply.com.   IN  TXT  "v=DKIM1; k=rsa; p=<PASTE_PUBLIC_KEY_FROM_MAUTIC_HERE>"

; ALSO add Hostinger's default DKIM if shared-hosting WP also sends mail (order confirmations from WP):
; <selector>._domainkey.learrnsimply.com   IN  TXT  "<Hostinger-provided value>"

; ============================================================
; DMARC — Domain-based Message Authentication, Reporting & Conformance
; Start with p=quarantine + rua reports for 2 weeks, then move to p=reject
; ============================================================
_dmarc.learrnsimply.com.   IN  TXT  "v=DMARC1; p=quarantine; rua=mailto:dmarc@learrnsimply.com; ruf=mailto:dmarc@learrnsimply.com; fo=1; adkim=s; aspf=s; pct=100"

; After 2 weeks of clean reports, harden to:
; _dmarc.learrnsimply.com.   IN  TXT  "v=DMARC1; p=reject; rua=mailto:dmarc@learrnsimply.com; pct=100"

; ============================================================
; OPTIONAL but recommended — MTA-STS + TLS-RPT for inbound encryption signal
; (Gmail / Outlook reward senders that publish these)
; ============================================================
_mta-sts.learrnsimply.com.   IN  TXT  "v=STSv1; id=20260601000000Z"
_smtp._tls.learrnsimply.com. IN  TXT  "v=TLSRPTv1; rua=mailto:tlsrpt@learrnsimply.com"
```

**Explanations (Egyptian Arabic):**
- **SPF:** بيقول لـ Gmail/Yahoo "الإيميلات اللي بتيجي من learrnsimply.com مفروض تيجي من السيرفرات دي بس". لو حد تاني بعت باسمك = حظر. `~all` (softfail) أأمن من `-all` في البداية لحد ما تتأكد إن كل مصادر الإيميل متدرجة.
- **DKIM:** توقيع رقمي على كل إيميل. Mautic بتولد المفتاح، إنت بتنشره في DNS. لازم تـ enable DKIM في Mautic UI (Settings → Email → DKIM) عشان الـ private key يبقى عند Mautic.
- **DMARC:** بيقول لـ Gmail/Yahoo "لو إيميل فشل في SPF أو DKIM، اعمل كذا". ابدأ بـ `quarantine` (يروح Spam) + reports عشان تشوف انت بتفقد إيه. بعد أسبوعين clean → `reject`.
- **Per Deep Research finding #2:** Gmail/Yahoo (Feb 2024) + Outlook (May 2025) bulk-sender rules require all three for 5K+ daily senders. 13K subs = ضمنها.

**Verify with:**
```bash
dig +short TXT learrnsimply.com
dig +short TXT mautic._domainkey.learrnsimply.com
dig +short TXT _dmarc.learrnsimply.com
# Or use MXToolbox: https://mxtoolbox.com/SuperTool.aspx
```

---

## 7. Marketing stack 90-day roadmap

### Phase 1 — D1-D30: Foundation + activate 13K dormant subs

| Action | Outcome |
|---|---|
| Deploy SPF + DKIM + DMARC (Section 6) + test with mail-tester.com → score ≥9/10 before any send | Unblocks legitimate use of the 13K list |
| Deploy n8n on VPS with Postgres + Redis queue mode + bounded workers | Unblocks W1-W6 |
| Fix Meta Pixel (currently ID=0) + verify TikTok MAPI still attributing (today: 1.04M EGP / 2398 orders) + add GA4 events beyond PageView | Recovers attribution signal; informs paid spend |
| Build W1 (WC↔Mautic sync) → segment 13K subs by `last_purchase`, `course_interest`, `engagement` (last open) | Mautic becomes truth source |
| Mautic activation broadcast to engaged segment (~3-5K from the 13K) with Ahmed's voice — "اشتقت لكم + كورس جديد" | **Conservative target: 400-600K EGP one-time** (assumes 4-6% conversion at average order value ~3K EGP based on the 67K/mo / ~22 orders baseline). Bigger if Ahmed's name pulls higher. |

### Phase 2 — D31-D60: Recover the leaky funnel

| Action | Outcome |
|---|---|
| Build W2 (Kashier-aware cart recovery) → 1645 active sessions targeted | Per existing Sprint 2 estimate: **~150K EGP/2 weeks** + closes the 909-Kashier-failure hole worth ~195K EGP/year |
| Sprint 2 Kashier gateway migration (existing item from CLAUDE.md) — pair with W2 | Reduces 30.2% cancellation rate toward industry norm (~10%) |
| Build W3 (WhatsApp AI agent) → start with Meta Cloud API, NOT Baileys | First-touch automation on Ahmed's WhatsApp inbound. Conservative: even 20% capture of inbound = significant lead gain |
| Build W6 (daily KPI Telegram digest) | Replaces dashboard logins; both Omar + Ahmed see truth daily |
| Eval harness on W3 agent before going live to >50 users/day | Prevents the "hallucinated price" disaster |

### Phase 3 — D61-D90: Self-improving + content multiplier

| Action | Outcome |
|---|---|
| Build W4 (Telegram AI agent) reusing W3 brain | Activates Ahmed's 24.4K Telegram channel (60-86% engagement) as a DM funnel |
| Build W7 (Tactics Coach for course pitch variants) | After 2-3 weeks of data: ML-ranked pitches. Per RS precedent: top-performing variants typically beat baseline by 25-40% |
| Content-engine + brand-voice skills on Ahmed's YouTube transcripts → repurpose to Telegram/FB/TikTok posts auto-scheduled via n8n | Multiplies Ahmed's existing content output without him recording more |
| GTM container fully wired (per 2026-05-24 decision in memory) → server-side events for Meta/TikTok/GA4 | Better attribution data → better paid-spend decisions |
| Review + harden VPS: enable Mautic queue mode if W3+W4 traffic > 200 conversations/day | Avoids OOM at scale per Deep Research open question #2 |

**Total 90-day revenue scenario (conservative):**
Baseline = 67K × 3 = 201K EGP. With activation broadcast (~500K EGP) + cart recovery (~300K EGP across 90d) + WhatsApp/Telegram funnel adding ~10-15% to baseline = **conservative 90-day total ~1.0-1.2M EGP vs ~200K baseline**. The single highest-leverage move is the D1-D14 deliverability fix + activation broadcast — without it everything downstream is throttled.

---

## 8. Risks and tradeoffs

1. **Single-VPS resource contention.** Traefik + Mautic + MySQL 8.4 + n8n + Redis + Evolution API + Postgres (for chat memory) on 16GB is workable but tight. The first 13K-subscriber broadcast is the inflection point.
**Mitigation this month:** Configure n8n queue mode with bounded workers from Day 1 (don't run SQLite default); set Mautic `messenger:consume email --memory-limit=128M --time-limit=160 --limit=60`; add a `htop`/Telegram alert when free RAM < 2GB. Per Deep Research open question #2, plan for Redis transport + systemd daemons if W3+W4 cross 200 conversations/day.

2. **Hostinger shared SMTP rate caps on 13K broadcast.** Hostinger shared plan likely throttles per-domain sends. The first big broadcast may queue for hours or get partial silent failures.
**Mitigation this month:** Test with a 500-contact segment first; if Hostinger throttles, set up Amazon SES sandbox-then-production for the bulk send (cheapest reliable option from EG; ~$0.10 per 1K). Per Deep Research open question #3.

3. **Evolution API Baileys mode ban risk.** Per Deep Research caveat #3, Meta actively bans Baileys-mode numbers, especially new ones with moderate volume. A ban mid-sprint would kill the WhatsApp channel and damage trust.
**Mitigation this month:** Use Meta's official WhatsApp Cloud API for W3 from Day 1. Reserve Baileys only for internal Omar↔Omar testing. Document the channel registration in `02_AUTOMATION/CLAUDE.md`.

4. **Mautic 5.2 cron lock conflicts on staggered jobs.** If the cron stagger is wrong (e.g., segments:update + campaigns:update at the same minute), segment membership locks can corrupt campaign-trigger state. Symptom: campaigns silently stop sending.
**Mitigation this month:** Implement the documented stagger exactly (`0,15,30,45 / 5,20,35,50 / 10,25,40,55`); add W5 monitoring workflow; log all `bin/console` exits to a file Claude can read via SSH.

5. **No-developer audit trail for AI agent decisions.** When W3/W4 agent quotes a wrong price or commits to a wrong date, Ahmed has no easy way to see what happened — and customers will screenshot it.
**Mitigation this month:** Build W6 (daily QA review) on Day 1 of W3 going live. Every conversation logged in Mautic activity log with full Claude tool-call trace. Run the rspaac `messenger-daily-qa-prompt.md` adaptation as a scheduled Claude routine from Day 1.

---

## 9. What I'd skip / avoid (and why)

1. **Don't deploy Meta Ads MCP yet (April 2026 open beta).** Per Deep Research finding #8 + verifier dissent on "no credentials needed", the gating is still moving. Track release notes; revisit Sept 2026. Use the GA4 MCP + n8n custom Meta Graph API calls in the meantime.

2. **Don't pay $25 for n8n WordPress MCP template #5060.** It covers only Posts/Pages/Users (12 ops), and wp-cli-over-SSH already handles 100% of what we need. The MySQL READ-ONLY MCP gives broader visibility for free.

3. **Don't try to build cross-channel shared memory (WhatsApp + Telegram + on-site widget) in Phase 1.** Per Deep Research open question #4, this is unsolved and adds significant complexity. Use per-channel session keys (correct architecture per finding #4) and handle continuity at the Mautic contact layer — much simpler, still good UX.

4. **Don't install the token-optimizer family (~80 MCP tools).** Heavyweight LLM-ops tooling that solves problems Learn Simply doesn't have. Adds context bloat to every session. Skip.

5. **Don't port the rs-aios Next.js dashboard (28 API routes).** It's beautifully built but wrong for Learn Simply — RS publishes Meta ads, Learn Simply sells courses. A WP-side "student dashboard" (Sprint 4+) is a different beast. The auditable pieces to lift are the webhook-validation + idempotency patterns, not the dashboard itself.

6. **Don't enable the Tactics Coach (W7) in Phase 1.** It needs ≥2 weeks of pitch-variant data to produce signal. Running it earlier just produces noise + Telegram spam. Defer to D61-D90.

7. **Don't add Stripe / Paymob MCPs "just in case".** Kashier is the current gateway; either fix it (Sprint 2) or migrate (Sprint 2/3 decision). Don't carry three payment integrations in parallel.

---

**Bottom line:** The three moves with highest 90-day ROI are (1) deliverability + activation broadcast on the 13K list, (2) Kashier-aware cart recovery (W1+W2), (3) WhatsApp AI agent on official Meta Cloud API (W3). Everything else amplifies these three. Total estimated 90-day revenue lift vs baseline: **~1.0-1.2M EGP conservative**, gated entirely on getting Section 6's DNS records right in Week 1.

Source files referenced inline. Key absolute paths: `c:\Users\sw\Documents\Claude\CLAUDE.md`, `c:\Users\sw\Documents\Claude\brands\learn-simply\CLAUDE.md`, `c:\Users\sw\Documents\Claude\rs-aios\automation\agents\messenger_v6.json`, `c:\Users\sw\Documents\Claude\brands\rspaac\02_AUTOMATION\agents\whatsapp-sales-agent-LIVE.md`, `c:\Users\sw\Documents\Claude\brands\rspaac\02_AUTOMATION\_tactics_coach\`, `c:\Users\sw\Documents\Claude\_agency\notify.sh`.