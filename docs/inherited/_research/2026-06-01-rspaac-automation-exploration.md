Perfect! Now I have enough information. Let me create the comprehensive report:

## RSPAAC Automation Artifacts for Learn Simply — Comprehensive Audit

I've completed a thorough exploration of the rspaac brand's 02_AUTOMATION folder. Below is a curated inventory of reusable artifacts for Learn Simply's Mautic + n8n + AI agent stack.

---

### High-Value Artifacts Summary

| **Artifact** | **Path** | **Purpose** | **Learn Simply Application** | **Adaptation** | **Priority** |
|---|---|---|---|---|---|
| **WhatsApp Sales Agent Prompt** | `agents/whatsapp-sales-agent-LIVE.md` (14.8 KB) | Gemini-powered sales consultant (5-stage funnel: need → format → online/offline → pricing → payment) | Direct reuse for sales automation on WhatsApp | Rebrand "عمر عبدالرحمن / RS Financial" → Learn Simply consultant; adapt workshops to course catalog (Mautic courses); change Egyptian workshop tone to general e-learning; swap pricing structure (50% deposit logic → tuition schedule); update knowledge base tool endpoints | **P0** |
| **Messenger Sales Agent Prompt** | `agents/messenger-sales-agent-LIVE.md` (17.8 KB) | Same 5-stage agent for Facebook Messenger + soft phone-capture gate (Stage 2.5) | Proven multi-channel pattern; includes phone validation (Arabic-Indic → English normalization) | Same rebranding + course swap + add phone validation to Mautic lead creation (leads_insert → upsert in Mautic) | **P0** |
| **Brief Analysis Agent** | `agents/brief-agent-LIVE.md` | Extracts workshop intent + engagement level from 24h conversation history | Adopt for course-interest classification from student interactions | Map RS "workshops" → Learn Simply "courses"; extract intent keywords (cohort vs self-paced); update Supabase schema to Learn Simply's course table | **P1** |
| **Tactics Coach System (6-Phase)** | `_tactics_coach/` | Self-improving sales tactic library: daily scoring (Beta-Bernoulli), weekly Claude routine, Telegram alerts | Transform for AI-guided course recommendations + objection handling | Retrain on Learn Simply customer objections (price, time, prerequisites); use Supabase tactics_library for course-pitch variants; daily routine unchanged (Telegram setup provided) | **P1** |
| **Follow-up Plan (WhatsApp + Messenger)** | `_archive/followup_plans_old/n8n-followup-plan.md` | 2-workflow re-engagement system: post-purchase drip (1h→1d→3d→7d) + inactive loop | Cart/enrollment recovery: verify access, prompt course start, upsell next level | Convert "course_purchased" → enrollment trigger; stages: (1) welcome, (2) module-1 guide, (3) progress check, (4) completion upsell; swap Google Sheets → Mautic contact logs | **P1** |
| **n8n Workflow Templates** | `_archive/migration_20260423/` `AI Agent - WhatsApp Supabase.MODIFIED.json` | Production WhatsApp workflow: webhook → Gemini reply → stage classification → Supabase (leads + chat history) | Foundational n8n pattern: webhook triggering → LLM inference → database upsert | Adapt webhook path (WhatsApp → Mautic); replace Supabase tables (leads_whatsapp → mautic_contacts; rs_chat_histories_wa → mautic_activity_log) | **P0** |
| **Pricing API Agent** | Embedded in prompts (`pricing_agent` tool) | Calls n8n edge function to fetch live workshop schedules + prices | Create Learn Simply pricing function: return course tiers, enrollment capacity, discount rules | Replace workshop IDs with course SKUs; add seat limits + open/closed enrollment states; return JSON: `{course_id, title, price, capacity_left, discount}` | **P1** |
| **Knowledge Base Tool** | Embedded in prompts (`knowledge_base` tool) | Vector search (Supabase pgvector) for workshop curriculum details + instructor info | Adopt for course content QA: syllabus, prerequisites, learning outcomes, instructor bio | Index Learn Simply courses into pgvector with embeddings; structure: course_id, module_list, prerequisites, outcomes, instructor_id | **P1** |
| **Stage Classifier (Gemini Flash Lite)** | `_tactics_coach/phase3_stage-classifier/index.ts` | Edge function: detects conversation stage (intro → need → online/offline → pricing → payment) | Classify student journey stage (awareness → exploration → decision → enrollment → post-course) | Map 5 RS stages → 5 Learn Simply stages; retrain Gemini prompt on e-learning context; store stage snapshots in Mautic custom fields | **P2** |
| **Daily Cron + Scoring** | `_tactics_coach/phase4_daily_cron/daily_score_update.sql` | Matches tactic calls to outcomes (success/fail); updates Beta-Bernoulli scores; auto-archives poor tactics (score<0.25, n≥10) | Automate course recommendation scoring: which recommendations lead to enrollment? | Run daily: count enrollment_created events per pitch_variant_id; update score; deprecate <40% conversion tactics | **P2** |
| **Telegram Bot Setup** | `_tactics_coach/phase6_telegram_notifier/SETUP.md` | BotFather bot creation + n8n webhook → formatted Telegram alerts | Notifications for enrollment anomalies (spike, drop, cohort filling) | Reuse bot token (or create Learn Simply bot); alert payloads: cohort_name, enrolled_count, alert_type (filled/low_engagement) | **P2** |
| **Claude Daily Routine (Tactics Coach)** | `_tactics_coach/HANDOFF.md` step 5 + `phase5_daily_routine/ROUTINE_PROMPT_FINAL.md` | Scheduled Claude routine (daily 02:00 Cairo): deep analysis of tactic outcomes, gap detection, new tactic generation, Telegram POST | Adapt for Learn Simply: daily review of course performance, objection patterns, recommendation tweaks | Supabase MCP integration (tactics_library → course_recommendation_library); same Telegram alert flow; daily routine 02:00 UTC (adjust for Learn Simply timezone) | **P1** |
| **Database Schema (Leads + Chat History)** | Implicit in CLAUDE.md + migration docs | Supabase tables: `leads_messenger` / `leads_whatsapp` (14 cols) + `rs_chat_histories` with GENERATED customer_id + indexes | Foundation for Mautic integration: customer journey storage + fast lookups | Migrate: leads_* → mautic_contacts; chat_histories → mautic_activity_log; add indexes on (customer_id, created_at), (session_id, created_at); use Postgres triggers for auto-updated_at | **P0** |
| **Messenger QA Review Prompt** | `scheduled-prompts/messenger-daily-qa-prompt.md` | Structured Claude prompt: analyzes 24h Messenger chats, identifies errors (pricing, tone, stage flow), suggests prompt fixes | Manual quality gate: review n8n Messenger logs, validate agent behavior against Learn Simply brand voice | Extract chat data from Mautic activity log; same error categories (pricing from wrong source, tone violations, skipped stages); output to Learn Simply brand wiki | **P2** |

---

### Special Artifacts (Detailed Description)

#### **1. Tactics Coach — Full Self-Improving System (P1: High-Value)**

**What it does:** Autonomous feedback loop:
- **Phase 1 (Schema):** Supabase tables for tactic tracking (`tactics_library`, `stage_snapshots`, `tactics_outcomes`)
- **Phase 2 (Thompson Sampling):** Edge function using Beta-Bernoulli priors for exploration/exploitation
- **Phase 3 (Stage Classifier):** Fire-and-forget Gemini Flash Lite classifier; stores stage transitions
- **Phase 4 (Daily Cron):** pg_cron job at 00:00 Cairo; matches tactic → outcome, updates Beta scores, archives losers
- **Phase 5 (Weekly Claude Routine):** Human-in-loop deep analysis; detects anomalies, generates ≤2 new tactics/week
- **Phase 6 (Telegram):** Alerts for anomalies (new champions, drops, etc.)

**Why for Learn Simply:** Continuously improve course recommendations + enrollment messaging. After 1-2 weeks of student data, you'll have ML-ranked pitch variants (e.g., "emphasize ROI" beats "emphasize community" → 78% vs 52% enrollment).

**Adaptation needed:**
- Swap `tactics_library` situation/tactic/outcome to course/pitch_variant/enrolled_bool
- Reindex Supabase vectors for e-learning objections (time, cost, prerequisites instead of financial service hesitations)
- Daily routine: review pitch performance, not workshop sales
- Telegram: alert on cohort-fill or engagement anomalies, not revenue

**Files to port:**
- `_tactics_coach/phase1/` — Supabase schema (alter for Learn Simply)
- `_tactics_coach/phase3/index.ts` — Gemini classifier (retrain on course context)
- `_tactics_coach/phase4/daily_score_update.sql` — Daily cron (adapt table/column names)
- `_tactics_coach/phase5/ROUTINE_PROMPT_FINAL.md` — Claude routine (substitute course/tactics tables)

---

#### **2. Sales Agent Prompts (P0: Immediate Use)**

Both WhatsApp + Messenger agents share identical 5-stage flow + identical logic, differing only in channel constraints:

**Stage 1:** Understand student need → recommend course (diagnostic: year/level → mapping to curriculum)
**Stage 2 (Messenger only):** Soft phone capture (3 attempts, reassurance → soft escape)
**Stage 3:** Format choice (online Zoom vs offline + recorded) — calls pricing_agent
**Stage 4:** Payment methods + deposit info (from `<data>` section, NOT hardcoded)
**Stage 5:** Verify receipt → lead insert to Supabase/Mautic

**Critical rules embedded:**
- Binary questions only (A or B)
- No markdown, max 2-3 emojis, short replies (80–300 chars)
- Prices ONLY from pricing_agent, NEVER from memory
- 50% deposit logic conditional (mention only when asked or "can't afford full")
- Zero-placeholder rule: all {names} {prices} {dates} must substitute before sending

**Adaptation:** 
- Swap Arabic instructor names → Learn Simply instructors
- Swap workshops → courses + credential tracks
- Swap deposit (50% workshop price) → tuition payment plan (e.g., 25% due before start)
- Update tools: pricing_agent → return course pricing + seat availability; knowledge_base → course curriculum; leads_insert → Mautic contact upsert

---

#### **3. n8n Workflow Backups (P0: Reference Architecture)**

**Files:**
- `_backups/Ai-Agent-Messenger-v6-HybridMemory.json` (55 KB)
- `_backups/_backup_Messenger_v6_2026-05-21_pre-codenode.json` (92 KB)
- `_archive/migration_20260423/AI Agent - WhatsApp Supabase.MODIFIED.json`

**Structure:** 
Webhook → extract customer + message → LLM (Gemini) with tools → reply + fire-and-forget classifier → database

**Key nodes:**
1. Webhook (receive message)
2. IF phone already in DB? (lookup Supabase)
3. Build context (chat history last 24h + customer info + stage)
4. Gemini LLM call with tools (pricing_agent, knowledge_base, tactics_agent, leads_insert)
5. Send reply
6. HTTP POST → stage-classifier edge fn (async, never blocks reply)
7. Upsert to Supabase (leads + chat history)

**Porting:** Import into n8n, replace Supabase credentials + table names with Mautic API calls.

---

#### **4. Telegram + Daily Claude Routine (P1)**

**Telegram setup:** 
Create bot via BotFather (@BotFather in Telegram) → save token + chat ID → n8n Telegram credential

**Claude routine:**
- Runs daily, 02:00 UTC (configurable)
- Requires Supabase MCP (enabled on routine)
- Queries tactics_weekly_reports + tactics_library
- Detects anomalies: new champions (score jumped >0.3), archived tactics (< 0.25), avg score delta
- Posts formatted message to Telegram

**For Learn Simply:** Same template, swap tables + metrics. Telegram alerts: cohort enrollment spike/drop, top-performing pitch variant, recommendation gaps.

---

### Missing Artifacts (Not Found in rspaac)

- **Email campaign templates** — n8n workflow for post-enrollment nurture; not in this folder
- **Evolution API setup** (WhatsApp self-hosted) — RS uses Meta Cloud API; if Learn Simply needs self-hosted, will require separate setup
- **Cart recovery flows** — archived follow-up plans exist but are pre-Supabase; need adaptation
- **Knowledge base RAG structure** — embedded in Supabase pgvector but no seed data exported
- **Marketing automation templates** — no Mautic-specific workflows found

---

### Deployment Sequence (Recommended for Learn Simply)

1. **P0 Phase 1:** Copy database schema (leads + chat history) → adapt to Mautic
2. **P0 Phase 2:** Port n8n workflows (Messenger + WhatsApp) → test with Mautic
3. **P0 Phase 3:** Update sales agent prompts → test tone, stage flow, tool integration
4. **P1 Phase 4:** Deploy Tactics Coach Phase 3–5 (stage classifier + daily cron + Claude routine)
5. **P1 Phase 5:** Set up Telegram alerts
6. **P2 Phase 6:** Implement daily QA review prompt (Claude session)

---

### Key Files to Download Now

```
/agents/whatsapp-sales-agent-LIVE.md — paste into Supabase prompts table
/agents/messenger-sales-agent-LIVE.md — paste into Supabase prompts table
/_tactics_coach/ — entire folder (phases 1–6)
/_archive/followup_plans_old/n8n-followup-plan.md — reference for re-engagement flows
/_backups/*Messenger*v6*.json — import as n8n workflow template
/CLAUDE.md — database schema reference (leads + chat history)
/scheduled-prompts/messenger-daily-qa-prompt.md — QA review routine
```

---

**Total artifact value:** ~180 KB of prod-tested prompts + workflow templates + automation logic. Estimated adaptation effort: 2–3 weeks for full Learn Simply integration (rebranding + Mautic API swaps + testing).