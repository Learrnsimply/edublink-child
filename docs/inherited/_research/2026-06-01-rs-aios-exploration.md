Perfect! Now I have a comprehensive view. Let me create a final summary with the most relevant artifacts for Learn Simply's automation stack:

## **RS-AIOS Artifacts for Learn Simply's Automation Stack**

### Summary Table

| Path | Purpose | Learn Simply Value | Complexity |
|------|---------|-------------------|-----------|
| `automation/agents/messenger_v6.json` | n8n Messenger AI agent (hybrid memory, Supabase-driven) | Multi-platform AI customer support pattern | High |
| `automation/CLAUDE.md` | AI agent prompt context + brand voice rules | Defines agent behavior for customer interactions | Medium |
| `automation/workflows_registry.md` | n8n workflow registry (IDs, versions, routing rules) | Blueprint for workflow organization & migration | Medium |
| `_connections/supabase.md` | Supabase pricing + instructor schema | Database-driven workshop/product info structure | Low |
| `_data/schemas/supabase_schemas.md` | Chat history + leads tables (messenger/whatsapp) | Multi-channel lead capture schema | Medium |
| `_context/brand/voice.md` | Egyptian Arabic copywriting + tone guidelines | AI agent personality & customer communication | Low |
| `_context/workshops/_index.md` | 9 workshop definitions + pain hooks | Product knowledge base structure | Low |
| `dashboard/custom/src/app/api/` (28 routes) | Next.js API backend (Supabase, n8n, Claude SDK integration) | Webhook handlers + orchestration patterns | High |

---

### Key Artifacts in Detail

#### **1. n8n Messenger Workflow (`automation/agents/messenger_v6.json`)**
**Full path:** `C:\Users\sw\Documents\Claude\rs-aios\automation\agents\messenger_v6.json`

**What it does:**  
Multi-stage Facebook Messenger AI agent that ingests customer messages via webhook, fetches live pricing from Supabase, maintains session-bucketed chat memory, and routes customers through 5 phases (collect phone → brief → upsell → booking/sales/transfer). Uses Gemini 3.0 Flash as LLM brain.

**Why Learn Simply would use it:**  
Complete reference for building WhatsApp/Messenger chatbots with Supabase-backed pricing + customer routing logic. The hybrid memory pattern (customer profile + session buckets) prevents stale data from breaking sales funnels.

**Complexity to port:** **High** (n8n-specific; requires full workflow recreation + API credential setup)

---

#### **2. Automation CLAUDE.md (`automation/CLAUDE.md`)**
**Full path:** `C:\Users\sw\Documents\Claude\rs-aios\automation\CLAUDE.md`

**What it does:**  
Team context + brand voice rules for AI agents. Covers messaging tone (Egyptian Arabic street dialect, not formal), workshop terminology fixes ("ورشة" not "كورس"), routing delays (2-hour sales delay), and STOP-and-lookup rules for dynamic data (prices, instructor names, dates).

**Why Learn Simply would use it:**  
Establishes discipline for AI agent prompts—prevents hallucinated pricing, enforces consistent terminology, and ensures agent responses match business rules. Easily adaptable to Learn Simply's products/tone.

**Complexity to port:** **Low** (copy structure, replace products/terminology)

---

#### **3. Workflows Registry (`automation/workflows_registry.md`)**
**Full path:** `C:\Users\sw\Documents\Claude\rs-aios\automation\workflows_registry.md`

**What it does:**  
Central registry of all n8n workflows with IDs, versions, active status, and migration history. Marks production workflows as "DO NOT TOUCH" and documents 6 new draft workflows (Messenger v6, WhatsApp, Conversation Analysis, Data Transformation, Sync Extended, Brief Backfill).

**Why Learn Simply would use it:**  
Shows how to organize n8n across 4 production agents + 5 data pipelines without conflicts. Documents trigger frequencies (5/15/30 min) and safety nets (Brief Backfill for missing briefs every 3 hours).

**Complexity to port:** **Medium** (adapt for Learn Simply's workflow count)

---

#### **4. Supabase Schema & Connections (`_connections/supabase.md` + `_data/schemas/supabase_schemas.md`)**
**Full paths:**  
- `C:\Users\sw\Documents\Claude\rs-aios\_connections\supabase.md`
- `C:\Users\sw\Documents\Claude\rs-aios\_data\schemas\supabase_schemas.md`

**What it does:**  
Defines Supabase project (`zjyyappiptysrbfcjytf`) with:
- `public.pricing` table (workshops, dates, prices, instructors) — source of truth
- `leads_messenger`, `leads_whatsapp` — multi-channel lead capture (14 cols: customer_id, name, phone, workshop, brief, routing, last_message_at, etc.)
- `rs_chat_histories(_wa)` — conversation memory
- Triggers auto-updating `last_message_at` on new messages

**Why Learn Simply would use it:**  
Battle-tested schema for separating live pricing data from leads/chat history. The trigger pattern prevents AI agents from using stale pricing. Functional indexes (`COALESCE(last_message_at, created_at)`) critical for sales routing performance.

**Complexity to port:** **Medium** (SQL straightforward; requires Supabase setup)

---

#### **5. Dashboard API Routes (`dashboard/custom/src/app/api/`)**
**Full path:** `C:\Users\sw\Documents\Claude\rs-aios\dashboard\custom\src\app\api\`

**What it does:**  
28 Next.js API routes handling:
- **Webhook receivers** (`/posts/[id]/status`, `/calendar`, etc.) — webhook signature validation + idempotency
- **Supabase integrations** (`/errors/log`, `/ai-runs`, `/schedule`) — realtime error tracking & cron state
- **n8n orchestration** (`/brief`, `/generate-design`, `/publish`) — triggering workflows + polling status
- **Claude API calls** (design generation, content scripting) — prompt building + streaming
- **Meta/Facebook APIs** (Graph API + Meta Business APIs) — scheduling + insights

**Why Learn Simply would use it:**  
Shows patterns for:
- Webhook signature verification (critical for security)
- Database transaction safety (error isolation, retry logic)
- Long-running operations (status polling for design generation ~10 min)
- Error event logging + Telegram alerting

**Complexity to port:** **High** (business-logic heavy; requires full API surface recreation)

---

#### **6. Brand Voice & Context (`_context/brand/voice.md`, `_context/workshops/_index.md`)**
**Full paths:**
- `C:\Users\sw\Documents\Claude\rs-aios\_context\brand\voice.md`
- `C:\Users\sw\Documents\Claude\rs-aios\_context\workshops\_index.md`

**What it does:**  
Defines RS brand voice (Egyptian Arabic, street-dialect, expert-but-friendly) + 9 workshop ICPs (pain hooks, segments, messaging angles). Enforces terminology: "ورشة" (workshop) not "كورس" (course), "تطبيق عملي" (hands-on) not "محتوى نظري" (theory).

**Why Learn Simply would use it:**  
Template for building AI agent personality + customer segment messaging. The pain-hook structure works for any training/service business.

**Complexity to port:** **Low** (straightforward copy + adapt terminology)

---

#### **7. n8n WhatsApp & Messenger Prompts**
**Full path:** `C:\Users\sw\Documents\Claude\rs-aios\automation\_history/`

**What it does:**  
Backup prompts for WhatsApp Sales Agent (26K chars) + Messenger Brief Agent (11K chars) containing system instructions for:
- Multi-phase customer journey (lead collection → brief generation → routing)
- Tool calling (fetch pricing, check availability, generate briefs)
- Error recovery (fallback to human team if stalled)
- Arabic glyph handling + phone number normalization

**Why Learn Simply would use it:**  
Complete prompt examples for multi-turn chatbot flows. Shows how to structure constraints (stage gates, data validation) and recovery paths.

**Complexity to port:** **Medium** (copy structure, retrain for Learn Simply products)

---

### What's NOT Useful (Marketing-Specific)

- `.claude/skills/rs-*` (28 skills) — pure marketing content generation (Instagram hooks, ROAS calcs, competitor analysis). Learn Simply sells education/support, not digital marketing.
- `dashboard/custom/src/app/*` (Next.js dashboard) — post planning + Meta publishing. Learn Simply would need student support dashboard instead.
- `_context/market/`, `_context/audience/` — RS financial services segmentation. Learn Simply has different segments.

---

### Getting Started: 3 Artifacts to Fork

1. **`automation/CLAUDE.md`** — Read first. Adapt agent rules for Learn Simply's tone + products (no rewrite needed, just copy).
2. **`_data/schemas/supabase_schemas.md`** — Create equivalent for student leads + support tickets (rename `leads_messenger` → `student_inquiries`, add `course_id` field).
3. **`automation/workflows_registry.md`** — Plan Learn Simply's 3-5 core workflows (WhatsApp support agent, email notification, course enrollment trigger) using this checklist.

Then use messenger_v6.json as a live reference (not direct copy) for n8n workflow structure.