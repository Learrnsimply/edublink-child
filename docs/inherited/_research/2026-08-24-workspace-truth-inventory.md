# Workspace source-of-truth inventory

> **Ticket:** [Inventory the workspace's sources of truth](https://github.com/omarabdo516/learn-simply/issues/8) · **Map:** [Wayfinder — Learn Simply: double sales as an AI-native growth system](https://github.com/omarabdo516/learn-simply/issues/3)
> **measured_at:** 2026-08-24 · **Scope:** every Markdown file tracked by git in `omarabdo516/learn-simply` at commit `98789ac`
> **Nothing was moved, edited or deleted under this ticket.** Every recommendation below is a recommendation.

---

## 0. Method

| Step | Command |
|---|---|
| File set | `git ls-files '*.md'` — **121 files**, 0 untracked Markdown |
| Size / lines / last change | `wc -c`, `wc -l`, `git log -1 --format=%ad --date=short -- <file>` (Appendix A, machine-generated) |
| Staleness test | Every numeric or status claim grepped against the four 2026-08-24 research reports in `_research/`, which measured the live systems |
| Link test | Every relative Markdown link resolved against the working tree (§6) |

The four research reports are the yardstick because they are the only artifacts in the repo that were measured against live systems this month:
[revenue-order-forensics](2026-08-24-revenue-order-forensics.md) ·
[traffic-funnel-tracking](2026-08-24-traffic-funnel-tracking.md) ·
[crm-email-automation-state](2026-08-24-crm-email-automation-state.md) ·
[audience-market-competitors](2026-08-24-audience-market-competitors.md).

**Recommendation vocabulary** — exactly five values:

- **keep** — accurate, or timeless, and owned by nothing else.
- **update** — the file should survive, but named claims inside it are wrong.
- **merge** — its content belongs inside another file that already claims the same authority.
- **archive** — true when written, superseded now. Move to `_archive/`, do not correct. It is history.
- **propose deletion** — no residual value, or the file says so itself. Nothing is removed under this ticket.

---

## 1. Verdict

**The repo is not confused about facts. It is confused about time.**

Almost nothing here was wrong when it was written. The failure mode is a writing *shape*: roughly two thirds of these files are written as **status** — "🟢 LIVE", "🔄 ACTIVE", "CURRENT STATE", "أحدث سياق" — and status decays silently. A green badge from June reads exactly the same whether the thing is alive or 68 days cold. That is precisely what happened: the WhatsApp agent's session died on 2026-06-17 and four separate documents still announce it as live.

Three structural findings:

1. **Four documents claim to be "what is true now"** — `CLAUDE.md`, `HANDOFF.md`, `_session-log.md`, `ROADMAP.md` — and each freezes on a different date (2026-06-25, 2026-06-25, 2026-06-24, 2026-06-14). A session reading all four gets three different presents. The cost is not just wrong numbers: it is that no reader can tell which one loses when they disagree.
2. **The trustworthy set is small and recent.** 8 files are current as of 2026-08-24. Roughly 40 are accurate-when-written history that no longer describes anything live. The remainder is design and reference material that ages slowly.
3. **The repo has no archive convention.** There is no `_archive/` directory. `01_WEB/specs/002-website-audit-v2/README.md` already *decided*, on 2026-06-11, that the May audit paperwork went stale after the host move and should be archived to `_superseded/` — and it was never executed. So staleness is not an unnoticed problem here; it is a noticed problem with no mechanism.

**The rewrite trap, stated explicitly:** the state of this workspace does not justify a rewrite. §4 lists **28 wrong claims** in the **12 files** marked `update` — every one of them a line edit or a table row. The bulk of the rest is history that needs *moving*, not editing. Total: one `_archive/` directory, 28 corrections, and two files whose ownership needs deciding (`ROADMAP.md`, `HANDOFF.md`).

**Recommendation tally** (counted from the tables in §5, command in §9):

| Recommendation | Files | Share |
|---|---|---|
| **keep** | 51 | 42.1% |
| **update** | 12 | 9.9% |
| **merge** | 5 | 4.1% |
| **archive** | 49 | 40.5% |
| **propose deletion** | 4 | 3.3% |
| **total** | **121** | 100% |

*Counted from the §5 tables by the command in §9; the total reconciles to the 121 tracked Markdown files.*

---

## 2. What can still be trusted, by tier

### Tier 1 — current (measured 2026-08-24)

`CONTEXT.md` · `docs/agents/issue-tracker.md` · `docs/agents/triage-labels.md` · `docs/agents/domain.md` · the four `_research/2026-08-24-*.md` reports.

Eight files. Everything a session needs to *orient* correctly today is in this tier, plus GitHub issues #3–#23.

### Tier 2 — trustworthy but ages by nature

`lessons.md` (rules with stated causes — they do not decay) · `02_AUTOMATION/n8n/workflows/W4-cart-recovery.md` and `_REGISTRY.md` (accurate but describe a system whose live state is in n8n, not here) · the `mu-plugins/*/README.md` set (they document code that is still deployed) · `.claude/skills/*/SKILL.md` (operating instructions; two carry stale numbers).

### Tier 3 — accurate when written, superseded now

The May audit corpus (`01_WEB/bugs-*.md`, `01_WEB/audit-*.md`, `03_KNOWLEDGE/audit-*.md`, `comprehensive-audit.md`, `phase-0-audit.md`, `competitor-analysis.md`), the June planning corpus (`ROADMAP.md`, `specs/001-launch-unblock/`, `gtm-roadmap-90days.md`, `MIGRATION-DEPLOY-RUNBOOK.md`), and the session-narrative corpus (`HANDOFF.md`, `_session-log.md`, `01_WEB/HANDOFF-web-lane2-2026-06-23.md`).

### Tier 4 — actively misleading if read as current

Four files, listed here because they are the ones that will cost a session real time or real money:

| File | Why it is dangerous, not merely stale |
|---|---|
| `CLAUDE.md` | The file every session loads first. Its "CURRENT STATE" announces the WhatsApp agent as live (dead 68 days) and 5 of its 7 headline numbers are wrong. |
| `03_KNOWLEDGE/knowledge-base.md` | The RAG source the customer-facing WhatsApp agent answers prices from. **6 of 11 prices are wrong** and two SKUs are missing. Harmless only while the agent is dead; wrong to customers the minute it is relinked. |
| `ROADMAP.md` | Declares itself "Single source of truth" for phase order, with Phase 001 🔄 ACTIVE against a deadline 70 days past, and no mention of spec 004, which shipped. |
| `02_AUTOMATION/n8n/workflows/_REGISTRY.md` | Six workflows carry a green 🟢 ACTIVE badge and have executed nothing since 2026-06-17. The badge is n8n's own flag and it is true — and it means nothing. |

---

## 3. Duplicate authorities

Ten pairs (or clusters) where more than one file claims the same authority. This is the list the workspace decision ([Decide the workspace shape and how Matt's method lands here](https://github.com/omarabdo516/learn-simply/issues/14)) has to resolve.

| # | Authority claimed | Files that claim it | Which should win |
|---|---|---|---|
| 1 | **"What is true right now"** | `CLAUDE.md` §CURRENT STATE · `HANDOFF.md` · `_session-log.md` §CURRENT STATE · `ROADMAP.md` §Current work · Claude memory files | None of them. Live state belongs in the live system; the repo should carry a *pointer* plus a measured date. |
| 2 | **Route and priority order** | `ROADMAP.md` · `03_KNOWLEDGE/gtm-roadmap-90days.md` · `specs/002–005/README.md` (4 placeholders) · wayfinder map #3 | The map (#3) — it is the only one with a stated destination and live tickets. |
| 3 | **Headline business numbers** | `CLAUDE.md:104-110` · `03_KNOWLEDGE/client-context.md:194-201` · `03_KNOWLEDGE/comprehensive-audit.md` · `_session-log.md:268-276` (a verbatim copy of the `CLAUDE.md` table) | One measured baseline block, sourced and dated. Map #3's "Measured starting position" is currently that block. |
| 4 | **Bug status** | `01_WEB/bugs-report.md` + 8 drill-downs · `01_WEB/specs/001-.../tasks.md` · `01_WEB/_triage-2026-06-23.json` (81KB, 133 bugs) · specs 003 + 004 | The specs, for anything still open; the rest is a May snapshot of a site that has since changed host and theme. |
| 5 | **Catalogue and prices** | `03_KNOWLEDGE/knowledge-base.md` · `03_KNOWLEDGE/client-context.md` · `03_KNOWLEDGE/data/website-extract/_generated-curriculum.md` · live WooCommerce Store API | WooCommerce. The KB should be *generated* from it, not typed. |
| 6 | **The WhatsApp agent's prompt** | `02_AUTOMATION/agents/omar-system-prompt.md` (v0.2 in file, 2026-06-17) · the n8n DB row (live prompt **v10**) | The DB. `inject_prompt.mjs` already syncs file → DB, so the file is the *editable* source but not the *authoritative* one — and it drifted anyway. |
| 7 | **Workflow inventory and state** | `02_AUTOMATION/n8n/workflows/_REGISTRY.md` · `.claude/skills/ls-whatsapp-agent/SKILL.md` · n8n itself | n8n. The registry says so in its own header, then prints stale state anyway — it needs a "last execution" column, not a rewrite. |
| 8 | **Payment-leak diagnosis** | `03_KNOWLEDGE/payment-leak-deep-dive-2026-06-24.md` (≈14.3K EGP/month net card leak) · `_research/2026-08-24-revenue-order-forensics.md` (recoverable pool ≈16–47K EGP/**year**) | The research report — larger sample, and it accounts for the 30.8% that self-recovers by rebuy. **The two disagree by roughly an order of magnitude and the conflict is currently unrecorded anywhere.** |
| 9 | **Channel sizes** | `03_KNOWLEDGE/audit-channels.md` · `03_KNOWLEDGE/competitor-analysis.md` · `CLAUDE.md` · `_research/2026-08-24-audience-market-competitors.md` | The research report (measured against the public YouTube / Telegram / Instagram / TikTok endpoints). |
| 10 | **Analytics stack state** | `03_KNOWLEDGE/analytics-audit-2026-05-24.md` · `03_KNOWLEDGE/audit-tracking-funnel.md` · `_research/2026-08-24-traffic-funnel-tracking.md` | The research report. Note `analytics-audit-2026-05-24.md` already carries a self-written superseded banner — the only file in the repo that does this correctly. |

---

## 4. The stale claims, listed for mechanical correction

Every row is one line edit. `→` reads "should be". Sources are the 2026-08-24 research reports unless stated.

### 4.1 `CLAUDE.md` — the file every session loads first

| Line | Claim | Correction | Source |
|---|---|---|---|
| 88, 118 | مساعد واتساب "عمر" **LIVE** بيرد على كل العملاء | **Dead since 2026-06-17T20:55:13Z** (`device_removed`); 0 messages in 68 days; **50 escalations unanswered** | crm §5 |
| 91, 110 | نزيف الدفع الصافي ≈ **14.3K ج/شهر** | Superseded: recoverable pool **≈16–47K EGP/year** — 30.8% of cancelled gross is already self-recovered by rebuy | revenue-forensics |
| 104 | إيراد شهري ~67K EGP · source "مارس 2024 – مايو 2026" | Value survives (measured run-rate **64,138 EGP/month**, 2026 YTD) but the provenance is 3 months stale — replace with the map's measured baseline block | `wp_wc_order_stats` |
| 105 | إيراد إجمالي **1,131,000 EGP في 27 شهر** | Not re-measured this session — mark `[historical, 2026-05]` or re-run the query | — |
| 106 | Email subscribers **13,140** (Mautic ~13.7K) | **14,630** contacts | crm §1 |
| 108 | YouTube **369K** مشترك · **18.5M** مشاهدة | **403,000** subscribers · **20,454,898** lifetime views · 364 videos | audience §1.1 |
| 109 | Telegram 24.4K · تفاعل **60-86%** | **25,096** subscribers · real engagement **16.5%** · **silent since 2026-07-24** | audience §1.1–1.2 |
| 122 | ⚠️ متفنّد: Kashier / 909 / 195K | **Still correct — keep this warning.** Independently re-confirmed 2026-08-24 | revenue-forensics |

### 4.2 `_session-log.md:268-276` — a verbatim copy of the table above

Same eight corrections, plus `Orders ملغية 30.2%` → **43.2%** of completed+cancelled (2026 YTD) / **48%** over the last 60 days.

### 4.3 `ROADMAP.md`

| Line | Claim | Correction |
|---|---|---|
| 11 | North Star assets: 350K+ YouTube · 13.7K email · 24.4K Telegram · ~67K/month | 403K · 14,630 · 25,096 · 64,138/month |
| 19 | Phase 001 **🔄 ACTIVE** — "deadline 15 يونيو!" | Deadline passed **70 days** ago; Dart launched and is selling |
| 40 | 13,711 contacts in Mautic | 14,630 |
| 54 | Blocker: منتج Dart في WooCommerce — مستني أحمد | **Resolved.** Dart is published (Tutor LMS, 2026-08-13), listed at 600 EGP, 103 students |
| Track 2 table | Lists specs 001 / 002 / 003 | **Spec 004 (premium-course-experience) is missing entirely** — it shipped (PR #20) |

### 4.4 `03_KNOWLEDGE/knowledge-base.md` — the customer-facing one

Live prices from the WooCommerce Store API, 2026-08-24 (audience §3.1):

| Line(s) | Product | KB says | Live |
|---|---|---|---|
| 27, 57 | كورس جافا للمبتدئين + كتاب | 450 | **550** (+22%) |
| 28, 82 | البرمجة الكائنية (OOP) Java | 550 | **700** (+27%) |
| 29, 108 | هياكل البيانات م١ | 550 | **650** (+18%) |
| 30, 132 | هياكل البيانات م٢ | 499 | 499 (unchanged) |
| 173 | هياكل البيانات الكاملة (L1+L2) | 900 | **999** (+11%) |
| 174 | Java الكاملة (Basics + OOP) | 849 | **999** (+18%) |
| 186–187 | كتاب جافا / كتاب C++ | 199 / 200 | unchanged |
| 346 | كورس Dart **350 ج** "لفترة محدودة" | **600 EGP**, permanent listing |
| — | **جميع الدورات (bundle, id 40754) — 2,500 EGP** | **absent from the KB entirely** |
| §7 policies | "no refund guarantee" | **A public 7-day refund guarantee now exists** on the homepage and terms page |

This file is the reason the correction matters more than it looks: it is what the WhatsApp agent quotes. Six wrong prices × a relinked agent = wrong answers to paying customers.

### 4.5 Routing files that point at the wrong things

| File:line | Claim | Correction |
|---|---|---|
| `03_KNOWLEDGE/CLAUDE.md:40` | audit-channels.md — YouTube (369K)، Telegram (24.4K) | 403K / 25,096 — and the file itself is superseded |
| `03_KNOWLEDGE/CLAUDE.md:41` | "GA4، Meta Pixel (**متوقّف!**)" | Meta Pixel `699717432496147` **fires**; last event 2026-08-23 |
| `02_AUTOMATION/CLAUDE.md:49` | `n8n-workflows/` — "لو قرّرنا نعمل n8n integration (**مفيش instance حالياً**)" | n8n has been live since 2026-06-01 with **10 workflows**, and `02_AUTOMATION/n8n/` exists in this very folder |
| `02_AUTOMATION/CLAUDE.md` §المحتوى الحالي | Lists `backups/` only | `agents/`, `mautic/`, `n8n/` all exist and are documented |
| `01_WEB/CLAUDE.md:30` | "137 bug total — verified" + spec table lists only 001 | Specs 003 and 004 are the live ones; 001 froze at 20/113 |
| `.claude/skills/ls-whatsapp-agent/SKILL.md:41` | W3 — "27+ نود · ACTIVE" | **38 nodes**; flag ACTIVE, executions since 2026-06-17: **0** |
| `02_AUTOMATION/n8n/workflows/_REGISTRY.md` | W3, W3b, W3c, W3t, W3t-order, W3t-mautic 🟢 ACTIVE | Active **and starved** — add a `last execution` column and regenerate |
| `03_KNOWLEDGE/client-context.md:195-201` | 1,131,000 · ~67,000/شهر · 13,140 · 350K YouTube | See §4.1 |

---

## 5. Full inventory

Size, line count and last-changed date for all 121 files are in **Appendix A** (machine-generated). This section carries the judgement: what each file is the source of truth for, and what to do with it.

### 5.1 Root (8 files)

| File | Source of truth for | Verdict | Recommendation |
|---|---|---|---|
| `CLAUDE.md` | Brand routing · CURRENT STATE · headline numbers | Routing sound; state 60 days stale; 5 of 7 numbers wrong | **update** (§4.1) |
| `CONTEXT.md` | Domain glossary | Current, written 2026-08-24 | **keep** |
| `lessons.md` | Durable lessons (rule + why + how) | Ages well — the rules are causal, not status | **keep** |
| `ROADMAP.md` | Phase order — self-declared "single source of truth" | Frozen 2026-06-14; ACTIVE phase 70 days past deadline; misses spec 004 | **archive** — the map + specs now own the route (feeds #14) |
| `HANDOFF.md` | Nothing exclusive — a stack of dated session snapshots, newest first | 77KB, every section already superseded by the one above it | **archive** |
| `_session-log.md` | Session history | Good as history; the "CURRENT STATE" header is a status claim that decayed | **update** header → dated entry, keep as history |
| `MIGRATION-DEPLOY-RUNBOOK.md` | Post-migration deploy steps | The migration happened 2026-06-04. Runbook spent. | **archive** |
| `ahmed-asks-2026-05-24.md` | A draft message to Ahmed | Its own header says "delete this file after Ahmed answers". He answered 2026-06-04. | **propose deletion** |

### 5.2 `docs/agents/` (3 files) — all 2026-08-24

`issue-tracker.md` · `triage-labels.md` · `domain.md` — the contract the agent skills read. Current. → **keep** ×3

### 5.3 `_research/` (10 files)

| File | Verdict | Recommendation |
|---|---|---|
| `2026-08-24-revenue-order-forensics.md` | Current, method stated, reproducible | **keep** |
| `2026-08-24-traffic-funnel-tracking.md` | Current | **keep** |
| `2026-08-24-crm-email-automation-state.md` | Current — one factual error, §8 below | **keep** + one correction |
| `2026-08-24-audience-market-competitors.md` | Current | **keep** |
| `2026-06-01-claude-tooling-inventory.md` | Tooling snapshot, superseded | **archive** |
| `2026-06-01-mcp-inventory.md` | MCP snapshot, superseded | **archive** |
| `2026-06-01-deep-research-marketing.md` | Market read superseded by the 2026-08-24 audience report | **archive** |
| `2026-06-01-final-recommendations.md` | Recommendations from a superseded read | **archive** |
| `2026-06-01-rs-aios-exploration.md` | Cross-brand exploration (rspaac) | **archive** — and note map #3 rules cross-brand work out of scope |
| `2026-06-01-rspaac-automation-exploration.md` | Same | **archive** |

### 5.4 `03_KNOWLEDGE/` (17 files)

| File | Source of truth for | Verdict | Recommendation |
|---|---|---|---|
| `CLAUDE.md` | Folder routing | Two wrong claims (§4.5) | **update** |
| `knowledge-base.md` | What the WhatsApp agent tells customers | 6 of 11 prices wrong · 2 SKUs missing · refund policy changed | **update** — highest operational stake in the repo |
| `client-context.md` | Ahmed's profile · ICP · catalogue · numbers | Profile survives; the numbers block is stale (§4.5) | **update** — strip numbers, point at the measured baseline |
| `payments-international-options.md` | International payment rails | Still the only write-up of this; not re-verified | **keep**, re-verify before acting |
| `gap-analysis-2026-06-11.md` | The June correction record — this ticket's direct predecessor | Accurate for its date; its own corrections are now themselves history | **archive** |
| `comprehensive-audit.md` | 10-dimension master scorecard | Superseded by four research reports | **archive** |
| `audit-channels.md` | Channel sizes and engagement | Every headline number wrong | **archive** |
| `audit-tracking-funnel.md` | GA4 / Pixel state | Superseded | **archive** |
| `analytics-audit-2026-05-24.md` | Analytics stack, May | Already self-labelled superseded — the one file that did this right | **archive** |
| `competitor-analysis.md` | Competitive set | Superseded by audience report §4–5 | **archive** |
| `gtm-roadmap-90days.md` | 90-day GTM plan | Superseded by `ROADMAP.md`, then by the map | **archive** |
| `phase-0-audit.md` | Initial access audit | Historical | **archive** |
| `setup-integrations.md` | Channel-connection roadmap | Partly executed, partly obsolete | **archive** |
| `payment-leak-deep-dive-2026-06-24.md` | Payment-leak diagnosis | Superseded, and **conflicts by ~10× with the current read** (§3 pair 8) | **archive** — record the conflict when you do |
| `ahmed-questions-kb-gaps.md` | Open questions for Ahmed | Answered 2026-06-04; answers already in the KB | **merge** into `knowledge-base.md`, then delete |
| `data/website-extract/05-pages-text.md` | Scraped site text, 2026-06-03 | Regenerable; prices inside it are now wrong | **archive** |
| `data/website-extract/_generated-curriculum.md` | Generated curriculum dump | Regenerable | **archive** |

### 5.5 `01_WEB/` (45 files)

| File(s) | Source of truth for | Verdict | Recommendation |
|---|---|---|---|
| `CLAUDE.md` | Web-team routing | Points only at spec 001; 003/004 are the live ones | **update** |
| `bugs-report.md` + `bugs-code/functional/data/runtime/integrity/plugins/perf/security-deep.md` (9) | The May 2026 bug corpus (137 bugs) | Accurate then. Site has since changed host, theme and plugin set | **archive** ×9 — `01_WEB/specs/002-website-audit-v2/README.md` already decided this on 2026-06-11 |
| `audit-code-findings.md` · `audit-commerce-deep.md` · `audit-technical.md` | Wave-1 technical drill-downs | Same | **archive** ×3 |
| `HANDOFF-web-lane2-2026-06-23.md` | One session's resume note | Spent | **archive** |
| `specs/001-bug-remediation-90day/` (5 files) | The frozen 90-day bug plan | Froze at 20/113; explicitly superseded by 002 | **archive** ×5 |
| `specs/002-website-audit-v2/README.md` | Scope note for a re-audit never started | Still the correct statement of that intent | **keep** |
| `specs/003-theme-defect-remediation/` (7 files) | Theme defect closeout | Shipped (26/29 pre-done, the rest deployed + verified) | **keep** ×7 (delivery record) |
| `specs/004-premium-course-experience/` (8 files) | Premium course UX | Shipped (PR #20, deployed + md5-verified) | **keep** ×8 (delivery record) |
| `mu-plugins/dart-landing/README.md` · `dart-popup/README.md` · `skip-cart/README.md` | Three deployed mu-plugins | Document live code | **keep** ×3 |
| `_tools/ui-audit/README.md` | The Playwright UI-audit runner | Tool still present and runnable | **keep** |
| `_evidence/task-3.11-autoload-cleanup-manifest.md` · `task-3.16-index-mysql-manifest.md` | Change manifests for two executed DB tasks | Dated evidence — worth keeping precisely because they are evidence | **keep** ×2 |
| `_evidence/2026-05-23-blog-verification/README.md` | Verification evidence | Same | **keep** |
| `_evidence/notion-export-2026-05-24/` (3 files) | A Notion export | All embedded images are missing from the repo (§6); content duplicated in `ahmed-asks-2026-05-24.md` | **propose deletion** ×3 |

### 5.6 `02_AUTOMATION/` (26 files)

| File(s) | Source of truth for | Verdict | Recommendation |
|---|---|---|---|
| `CLAUDE.md` | Automation-team routing | Says n8n does not exist; lists only `backups/` | **update** |
| `n8n/workflows/_REGISTRY.md` | Workflow inventory | Auto-generated and accurate on IDs; the ACTIVE column is true and meaningless | **update** — regenerate with a `last execution` column |
| `n8n/workflows/W4-cart-recovery.md` | W4, the only automation currently earning | Accurate and current | **keep** |
| `n8n/workflows/W4-W5-cart-recovery-PLAN.md` | W4 design + the unbuilt W5 | W4 built; W5 deferred by Omar's decision | **keep** |
| `n8n/workflows/W1-wc-mautic-sync.md` · `W2-dart-waitlist-popup.md` | W1, W2 | Both still active | **keep** ×2 |
| `n8n/README.md` | n8n instance overview | Accurate | **keep** |
| `agents/omar-system-prompt.md` | The agent prompt (file side) | File is v0.2; live DB row is **v10** — drifted (§3 pair 6) | **update** — resync or mark the DB authoritative |
| `agents/tools/escalate.md` · `order_lookup.md` · `mautic_upsert.md` | Tool contracts | Match the deployed wrappers | **keep** ×3 |
| `agents/rag/README.md` | The RAG corpus + eval | Accurate | **keep** |
| `agents/deploy/README.md` | Deploy checklist for the agent | Accurate | **keep** |
| `agents/whatsapp-agent-design.md` | Customer-journey design | Design intent — ages slowly | **keep** |
| `agents/omar-n8n-workflow-spec.md` | The build spec | Built; spec is now a record | **keep** as record |
| `agents/omar-build-plan.md` · `whatsapp-media-pipeline-plan-2026-06-10.md` · `workflow-quality-study-2026-06-10.md` | Build plans, executed | Spent | **archive** ×3 |
| `mautic/README.md` | Mautic instance | Contact count stale (13.7K → 14,630) | **update** |
| `mautic/OMAR_UI_CHECKLIST.md` | A one-time UI setup checklist | Executed | **archive** |
| `mautic/ses-setup-runbook.md` | SES provisioning | SES was denied and is not the sender; Brevo is | **archive** |
| `mautic/campaigns/01-reengagement-13k.md` | The 13K re-engagement campaign | Never sent; list is now 14,630 and 97.1% never emailed | **update** — it is still the live plan for a live opportunity |
| `mautic/campaigns/dart-deliverability-plan.md` | Dart launch deliverability | Launch is over | **archive** |
| `mautic/campaigns/email-copy-drafts.md` | Email copy | Reusable | **keep** |
| `mautic/campaigns/utm-convention.md` | UTM naming convention | The only place this is written down | **keep** |
| `mautic/themes/learn-simply-arabic/README.md` | The Arabic RTL email theme | Documents deployed code | **keep** |

### 5.7 `specs/` — root GTM track (7 files)

| File(s) | Verdict | Recommendation |
|---|---|---|
| `001-launch-unblock/spec.md` · `plan.md` · `tasks.md` | Dart launched (16 sales, June); the "sending channel blocker" was corrected to non-existent in the file itself | **archive** ×3 |
| `002-email-engine/README.md` · `003-youtube-funnel/README.md` · `004-cro-tracking/README.md` · `005-content-outbound/README.md` | Scope notes, never spec'd. They now overlap map #3's **Not yet specified** section — duplicate authority pair 2 | **merge** ×4 into the map's fog, then archive |

### 5.8 `.claude/skills/` (4 files)

| File | Verdict | Recommendation |
|---|---|---|
| `ls-notion/SKILL.md` | Notion IDs and rules; not re-verified against the workspace this session (`UNVERIFIED`) | **keep** |
| `ls-wrap/SKILL.md` | Session-close procedure; still correct | **keep** |
| `ls-wp-tutor/SKILL.md` | WP/Tutor operating rules; "~67K ج/شهر" is still within measurement | **keep** |
| `ls-whatsapp-agent/SKILL.md` | Node count and ACTIVE state wrong (§4.5); correctly tells the reader to verify live first | **update** |

### 5.9 `_client-reports/` (1 file)

`bugs-report-en.md` — the English audit report prepared for Ahmed, dated May 23 2026. It is a *delivered client artifact*: it should not be corrected after the fact. → **archive**

---

## 6. Broken links

Nine real breaks (excluding two Arabic-word false positives and one `[link]` placeholder inside a prompt template):

| File | Broken target | Cause |
|---|---|---|
| `03_KNOWLEDGE/audit-tracking-funnel.md` (×2) | `audit-code-findings.md` | File lives in `01_WEB/`, link is folder-relative |
| `03_KNOWLEDGE/phase-0-audit.md` | `audit-code-findings.md` | Same |
| `03_KNOWLEDGE/comprehensive-audit.md` (×3) | `audit-technical.md`, `audit-commerce-deep.md`, `audit-code-findings.md` | Same |
| `01_WEB/mu-plugins/dart-popup/README.md` (×2) | `../../MIGRATION-DEPLOY-RUNBOOK.md` | One `../` short — the file is at the repo root |
| `01_WEB/_evidence/notion-export-2026-05-24/` (3 files) | `image%201.png` … `image%206.png`, and two sibling exports | Export images were never committed |

The `03_KNOWLEDGE` breaks all point into `01_WEB/`, which is itself an artefact of the duplicate-authority problem: the knowledge folder cites the web folder's audits as if it owned them.

---

## 7. True, and written nowhere

The inverse of staleness — things measured on 2026-08-24 that no repo document records:

1. **The WhatsApp agent is dead and 50 customer escalations are unanswered.** Now ticketed ([#17](https://github.com/omarabdo516/learn-simply/issues/17), [#18](https://github.com/omarabdo516/learn-simply/issues/18)) but recorded in no repo doc.
2. **Prices rose 11–27% across the catalogue between June and August**, and two SKUs appeared (Dart 600, all-courses bundle 2,500). No doc knows.
3. **A public 7-day refund guarantee now exists.** The May audit listed its absence as a CRO defect.
4. **Mautic bounce processing is off** (0 config rows) — so list health is unmeasurable.
5. **SPF omits Brevo**, the only live sender, under `p=quarantine` ([#19](https://github.com/omarabdo516/learn-simply/issues/19)).
6. **`hold_stock_minutes=1440` is still live** on a digital-product store ([#11](https://github.com/omarabdo516/learn-simply/issues/11)).
7. **Guest checkout is off with a hard login redirect** ([#20](https://github.com/omarabdo516/learn-simply/issues/20)).
8. **The sitewide countdown is a per-visitor fake**, admitted in the theme source ([#23](https://github.com/omarabdo516/learn-simply/issues/23)).
9. **WordPress is on 7.0.4, not 6.9.4; 54 plugins active, not 61.**

Items 1–3 are the ones that change what a session would *do*, not just what it would say.

---

## 8. One correction to a Decisions-so-far artifact

`_research/2026-08-24-crm-email-automation-state.md:576` states that W4 *"appears in **no** repo doc"*.

That is wrong. W4 is documented in four places, all committed before the report was written:

| Path | Bytes | Last change |
|---|---|---|
| `02_AUTOMATION/n8n/workflows/W4-cart-recovery.md` | 10,117 | 2026-06-24 |
| `02_AUTOMATION/n8n/workflows/W4-W5-cart-recovery-PLAN.md` | 12,796 | 2026-06-24 |
| `02_AUTOMATION/n8n/workflows/_REGISTRY.md` | 3,144 | 2026-06-24 |
| `CLAUDE.md` and `HANDOFF.md` | — | one mention each |

Verified: `grep -c '\bW4\b'` returns 1 (`CLAUDE.md`), 0 (`ROADMAP.md`), 1 (`HANDOFF.md`), 3 (`_session-log.md`).

The accurate version of the finding: **W4 is well documented in `02_AUTOMATION/`, and absent from `ROADMAP.md`.** The report's underlying point — that the orientation layer stopped tracking reality after 2026-06-17 — survives intact; only the "no repo doc" phrasing is false. Recorded here rather than silently fixed, per the map's evidence rules.

---

## 9. Reproducibility

```bash
cd /home/omar/Documents/Claude/brands/learn-simply

# file set (121)
git ls-files '*.md' | wc -l
git status --porcelain --untracked-files=all | grep -E '\.md$'   # → empty

# size / lines / last change, per file (Appendix A)
git ls-files '*.md' | sort | while read f; do
  printf '| `%s` | %s | %s | %s |\n' "$f" "$(wc -c <"$f")" "$(wc -l <"$f")" \
    "$(git log -1 --format=%ad --date=short -- "$f")"
done

# stale-claim sweep
git ls-files '*.md' | grep -v '^_research/' | grep -v '_evidence/' \
  | xargs grep -nEi '195K|909|Kashier|13,140|369K|18\.5M|60-86|67K|14\.3K'

# broken relative links
git ls-files '*.md' | while read f; do d=$(dirname "$f");
  grep -oE '\]\(\.{0,2}[^):]*\)' "$f" | sed 's/^](//;s/)$//;s/#.*$//' | while read l; do
    case "$l" in http*|mailto*|"") continue;; esac; [ -e "$d/$l" ] || echo "$f -> $l"; done; done

# recommendation tally (§1)
grep -oE '\*\*(keep|update|merge|archive|propose deletion)\*\*( ×[0-9]+)?' \
  _research/2026-08-24-workspace-truth-inventory.md
```

---

## Appendix A — every tracked Markdown file

Machine-generated. Columns: path · bytes · lines · last commit date.

| Path | Bytes | Lines | Last change |
|---|---|---|---|
| `.claude/skills/ls-notion/SKILL.md` | 11750 | 123 | 2026-06-24 |
| `.claude/skills/ls-whatsapp-agent/SKILL.md` | 8796 | 105 | 2026-06-24 |
| `.claude/skills/ls-wp-tutor/SKILL.md` | 6860 | 74 | 2026-06-24 |
| `.claude/skills/ls-wrap/SKILL.md` | 9493 | 101 | 2026-06-24 |
| `01_WEB/CLAUDE.md` | 4052 | 88 | 2026-05-23 |
| `01_WEB/HANDOFF-web-lane2-2026-06-23.md` | 7018 | 59 | 2026-06-23 |
| `01_WEB/_evidence/2026-05-23-blog-verification/README.md` | 943 | 21 | 2026-05-23 |
| `01_WEB/_evidence/notion-export-2026-05-24/Ahmed & Omar 36223402016580c7b6c2d1f2728bffd2.md` | 631 | 23 | 2026-05-24 |
| `01_WEB/_evidence/notion-export-2026-05-24/Everything 36623402016580209658c02d378c6b3c.md` | 1225 | 77 | 2026-05-24 |
| `01_WEB/_evidence/notion-export-2026-05-24/Script from Learn simply 36623402016580e28c2bc9a172920878.md` | 339 | 16 | 2026-05-24 |
| `01_WEB/_evidence/task-3.11-autoload-cleanup-manifest.md` | 5901 | 164 | 2026-05-24 |
| `01_WEB/_evidence/task-3.16-index-mysql-manifest.md` | 6191 | 180 | 2026-05-24 |
| `01_WEB/_tools/ui-audit/README.md` | 6216 | 140 | 2026-05-23 |
| `01_WEB/audit-code-findings.md` | 8015 | 94 | 2026-05-23 |
| `01_WEB/audit-commerce-deep.md` | 18636 | 331 | 2026-05-23 |
| `01_WEB/audit-technical.md` | 22271 | 447 | 2026-05-23 |
| `01_WEB/bugs-code.md` | 22043 | 434 | 2026-05-23 |
| `01_WEB/bugs-data.md` | 19665 | 379 | 2026-05-23 |
| `01_WEB/bugs-functional.md` | 18573 | 288 | 2026-05-23 |
| `01_WEB/bugs-integrity.md` | 30975 | 625 | 2026-05-23 |
| `01_WEB/bugs-perf.md` | 12777 | 328 | 2026-05-23 |
| `01_WEB/bugs-plugins.md` | 21520 | 454 | 2026-05-24 |
| `01_WEB/bugs-report.md` | 15321 | 252 | 2026-05-23 |
| `01_WEB/bugs-runtime.md` | 16730 | 347 | 2026-05-23 |
| `01_WEB/bugs-security-deep.md` | 14453 | 363 | 2026-05-23 |
| `01_WEB/mu-plugins/dart-landing/README.md` | 4755 | 48 | 2026-06-04 |
| `01_WEB/mu-plugins/dart-popup/README.md` | 8122 | 109 | 2026-06-04 |
| `01_WEB/mu-plugins/skip-cart/README.md` | 2837 | 32 | 2026-06-24 |
| `01_WEB/specs/001-bug-remediation-90day/README.md` | 2730 | 45 | 2026-05-23 |
| `01_WEB/specs/001-bug-remediation-90day/checklists/security.md` | 7909 | 105 | 2026-05-24 |
| `01_WEB/specs/001-bug-remediation-90day/plan.md` | 23086 | 357 | 2026-05-23 |
| `01_WEB/specs/001-bug-remediation-90day/spec.md` | 14418 | 238 | 2026-05-24 |
| `01_WEB/specs/001-bug-remediation-90day/tasks.md` | 28738 | 449 | 2026-05-24 |
| `01_WEB/specs/002-website-audit-v2/README.md` | 1946 | 22 | 2026-06-11 |
| `01_WEB/specs/003-theme-defect-remediation/README.md` | 2449 | 35 | 2026-06-14 |
| `01_WEB/specs/003-theme-defect-remediation/checklists/requirements.md` | 2566 | 36 | 2026-06-14 |
| `01_WEB/specs/003-theme-defect-remediation/plan.md` | 12766 | 127 | 2026-06-14 |
| `01_WEB/specs/003-theme-defect-remediation/quickstart.md` | 3794 | 92 | 2026-06-14 |
| `01_WEB/specs/003-theme-defect-remediation/research.md` | 8039 | 89 | 2026-06-14 |
| `01_WEB/specs/003-theme-defect-remediation/spec.md` | 17248 | 166 | 2026-06-14 |
| `01_WEB/specs/003-theme-defect-remediation/tasks.md` | 10009 | 124 | 2026-06-14 |
| `01_WEB/specs/004-premium-course-experience/checklists/requirements.md` | 1618 | 35 | 2026-06-25 |
| `01_WEB/specs/004-premium-course-experience/contracts/template-data-contract.md` | 2118 | 38 | 2026-06-25 |
| `01_WEB/specs/004-premium-course-experience/data-model.md` | 3003 | 50 | 2026-06-25 |
| `01_WEB/specs/004-premium-course-experience/plan.md` | 5577 | 65 | 2026-06-25 |
| `01_WEB/specs/004-premium-course-experience/quickstart.md` | 2380 | 31 | 2026-06-25 |
| `01_WEB/specs/004-premium-course-experience/research.md` | 5411 | 51 | 2026-06-25 |
| `01_WEB/specs/004-premium-course-experience/spec.md` | 15993 | 132 | 2026-06-25 |
| `01_WEB/specs/004-premium-course-experience/tasks.md` | 9738 | 112 | 2026-06-25 |
| `02_AUTOMATION/CLAUDE.md` | 2675 | 58 | 2026-05-23 |
| `02_AUTOMATION/agents/deploy/README.md` | 8009 | 107 | 2026-06-10 |
| `02_AUTOMATION/agents/omar-build-plan.md` | 9383 | 126 | 2026-06-10 |
| `02_AUTOMATION/agents/omar-n8n-workflow-spec.md` | 38641 | 418 | 2026-06-10 |
| `02_AUTOMATION/agents/omar-system-prompt.md` | 33606 | 274 | 2026-06-17 |
| `02_AUTOMATION/agents/rag/README.md` | 15165 | 197 | 2026-06-03 |
| `02_AUTOMATION/agents/tools/escalate.md` | 22255 | 306 | 2026-06-03 |
| `02_AUTOMATION/agents/tools/mautic_upsert.md` | 18704 | 228 | 2026-06-03 |
| `02_AUTOMATION/agents/tools/order_lookup.md` | 21666 | 270 | 2026-06-03 |
| `02_AUTOMATION/agents/whatsapp-agent-design.md` | 13238 | 120 | 2026-06-04 |
| `02_AUTOMATION/agents/whatsapp-media-pipeline-plan-2026-06-10.md` | 7279 | 93 | 2026-06-10 |
| `02_AUTOMATION/agents/workflow-quality-study-2026-06-10.md` | 11031 | 81 | 2026-06-10 |
| `02_AUTOMATION/mautic/OMAR_UI_CHECKLIST.md` | 10653 | 254 | 2026-06-01 |
| `02_AUTOMATION/mautic/README.md` | 8942 | 186 | 2026-06-01 |
| `02_AUTOMATION/mautic/campaigns/01-reengagement-13k.md` | 6811 | 103 | 2026-06-03 |
| `02_AUTOMATION/mautic/campaigns/dart-deliverability-plan.md` | 3936 | 47 | 2026-06-10 |
| `02_AUTOMATION/mautic/campaigns/email-copy-drafts.md` | 9946 | 149 | 2026-06-04 |
| `02_AUTOMATION/mautic/campaigns/utm-convention.md` | 2328 | 39 | 2026-06-24 |
| `02_AUTOMATION/mautic/ses-setup-runbook.md` | 12971 | 156 | 2026-06-04 |
| `02_AUTOMATION/mautic/themes/learn-simply-arabic/README.md` | 2489 | 47 | 2026-06-03 |
| `02_AUTOMATION/n8n/README.md` | 3927 | 83 | 2026-06-01 |
| `02_AUTOMATION/n8n/workflows/W1-wc-mautic-sync.md` | 5001 | 86 | 2026-06-02 |
| `02_AUTOMATION/n8n/workflows/W2-dart-waitlist-popup.md` | 6473 | 110 | 2026-06-04 |
| `02_AUTOMATION/n8n/workflows/W4-W5-cart-recovery-PLAN.md` | 12796 | 170 | 2026-06-24 |
| `02_AUTOMATION/n8n/workflows/W4-cart-recovery.md` | 10117 | 127 | 2026-06-24 |
| `02_AUTOMATION/n8n/workflows/_REGISTRY.md` | 3144 | 24 | 2026-06-24 |
| `03_KNOWLEDGE/CLAUDE.md` | 3681 | 80 | 2026-05-23 |
| `03_KNOWLEDGE/ahmed-questions-kb-gaps.md` | 4922 | 65 | 2026-06-04 |
| `03_KNOWLEDGE/analytics-audit-2026-05-24.md` | 13292 | 272 | 2026-06-25 |
| `03_KNOWLEDGE/audit-channels.md` | 15357 | 277 | 2026-05-23 |
| `03_KNOWLEDGE/audit-tracking-funnel.md` | 6281 | 70 | 2026-05-23 |
| `03_KNOWLEDGE/client-context.md` | 23253 | 321 | 2026-06-10 |
| `03_KNOWLEDGE/competitor-analysis.md` | 21428 | 210 | 2026-06-03 |
| `03_KNOWLEDGE/comprehensive-audit.md` | 12626 | 143 | 2026-05-23 |
| `03_KNOWLEDGE/data/website-extract/05-pages-text.md` | 78815 | 1738 | 2026-06-03 |
| `03_KNOWLEDGE/data/website-extract/_generated-curriculum.md` | 15647 | 443 | 2026-06-03 |
| `03_KNOWLEDGE/gap-analysis-2026-06-11.md` | 9073 | 120 | 2026-06-11 |
| `03_KNOWLEDGE/gtm-roadmap-90days.md` | 16115 | 223 | 2026-05-23 |
| `03_KNOWLEDGE/knowledge-base.md` | 37426 | 353 | 2026-06-17 |
| `03_KNOWLEDGE/payment-leak-deep-dive-2026-06-24.md` | 6041 | 91 | 2026-06-24 |
| `03_KNOWLEDGE/payments-international-options.md` | 7736 | 87 | 2026-06-04 |
| `03_KNOWLEDGE/phase-0-audit.md` | 9491 | 152 | 2026-05-23 |
| `03_KNOWLEDGE/setup-integrations.md` | 18451 | 502 | 2026-05-23 |
| `CLAUDE.md` | 9839 | 160 | 2026-08-24 |
| `CONTEXT.md` | 2076 | 37 | 2026-08-24 |
| `HANDOFF.md` | 77844 | 554 | 2026-06-25 |
| `MIGRATION-DEPLOY-RUNBOOK.md` | 11229 | 170 | 2026-06-03 |
| `ROADMAP.md` | 7185 | 93 | 2026-06-14 |
| `_client-reports/bugs-report-en.md` | 11146 | 243 | 2026-06-02 |
| `_research/2026-06-01-claude-tooling-inventory.md` | 15230 | 158 | 2026-06-01 |
| `_research/2026-06-01-deep-research-marketing.md` | 26416 | 306 | 2026-06-01 |
| `_research/2026-06-01-final-recommendations.md` | 30159 | 270 | 2026-06-01 |
| `_research/2026-06-01-mcp-inventory.md` | 9539 | 87 | 2026-06-01 |
| `_research/2026-06-01-rs-aios-exploration.md` | 8777 | 147 | 2026-06-01 |
| `_research/2026-06-01-rspaac-automation-exploration.md` | 13219 | 156 | 2026-06-01 |
| `_research/2026-08-24-audience-market-competitors.md` | 71617 | 687 | 2026-08-24 |
| `_research/2026-08-24-crm-email-automation-state.md` | 37026 | 625 | 2026-08-24 |
| `_research/2026-08-24-revenue-order-forensics.md` | 51979 | 833 | 2026-08-24 |
| `_research/2026-08-24-traffic-funnel-tracking.md` | 41675 | 606 | 2026-08-24 |
| `_session-log.md` | 46591 | 286 | 2026-06-25 |
| `ahmed-asks-2026-05-24.md` | 6956 | 112 | 2026-05-24 |
| `docs/agents/domain.md` | 2033 | 51 | 2026-08-24 |
| `docs/agents/issue-tracker.md` | 3731 | 45 | 2026-08-24 |
| `docs/agents/triage-labels.md` | 1045 | 15 | 2026-08-24 |
| `lessons.md` | 30436 | 353 | 2026-06-11 |
| `specs/001-launch-unblock/plan.md` | 4083 | 68 | 2026-06-11 |
| `specs/001-launch-unblock/spec.md` | 6476 | 108 | 2026-06-11 |
| `specs/001-launch-unblock/tasks.md` | 6506 | 139 | 2026-06-11 |
| `specs/002-email-engine/README.md` | 1459 | 17 | 2026-06-11 |
| `specs/003-youtube-funnel/README.md` | 842 | 15 | 2026-06-11 |
| `specs/004-cro-tracking/README.md` | 1071 | 16 | 2026-06-11 |
| `specs/005-content-outbound/README.md` | 792 | 12 | 2026-06-11 |
