---
tags: [brand]
brand: learn-simply
client: Ahmed Adel (أحمد عادل)
status: phase-0-audit-complete
type: GTM Engineering (inbound + outbound + measurement + email)
tech: WordPress 6.9.4 + Tutor LMS + WooCommerce + Timber/Twig
locale: Egyptian Arabic — RTL
domain: learrnsimply.com
---

# Learn Simply — Brand Routing

> Omar = GTM Engineer للبراند ده · Ahmed = الـ client (مؤسس + مدرّس + مطوّر الموقع بنفسه).
> **First session هنا؟** اقرا بالترتيب: `03_KNOWLEDGE/client-context.md` → `03_KNOWLEDGE/comprehensive-audit.md` → CURRENT STATE تحت.
> أعمق سياق إنت محتاجه عن الـ workspace ككل: `../../CLAUDE.md` (root).

---

## ⚠️ متلخبطش — `learrnsimply.com` بدوبل r

الدومين الفعلي **`learrnsimply.com`** (`r` مكررة) — مش typo. أي حد يحاول يصلّحها لـ `learnsimply` هيكسر الروابط. ده اكتشاف false-positive في الـ audit اتأكد منه. (الـ marketing-wise مربك ومحتاج قرار لاحق، بس تكنيكاً صح.)

---

## 🗺️ الـ Structure — 3 Teams (mirroring rspaac pattern)

```
brands/learn-simply/
├── CLAUDE.md                    ← (this file) — brand routing
├── lessons.md                   ← brand-wide accumulated lessons
│
├── 01_WEB/                      ← فريق الويب (technical)
│   ├── CLAUDE.md                ← team routing
│   ├── website/                 ← submodule: Learrnsimply/edublink-child
│   ├── bugs-*.md                ← 9 bug reports (137 total bugs)
│   ├── audit-*.md               ← 3 technical audits (code, commerce, tech)
│   ├── specs/                   ← Spec Kit plans (001-bug-remediation-90day)
│   ├── _tools/ui-audit/         ← Playwright UI audit script
│   └── _evidence/               ← Playwright screenshots
│
├── 02_AUTOMATION/               ← فريق الأتوميشن (scheduled tasks)
│   ├── CLAUDE.md                ← team routing
│   └── backups/                 ← submodule: omarabdo516/learn-simply-backups
│
└── 03_KNOWLEDGE/                ← قاعدة المعرفة + GTM
    ├── CLAUDE.md                ← team routing
    ├── client-context.md        ← Ahmed profile + ICP + catalog + numbers
    ├── comprehensive-audit.md   ← master audit (10-dim scorecard)
    ├── gtm-roadmap-90days.md
    ├── audit-channels.md        ← YouTube/Telegram/FB/TikTok
    ├── audit-tracking-funnel.md ← GA4/Meta Pixel
    ├── setup-integrations.md
    ├── phase-0-audit.md
    └── data/                    ← JSON snapshots (tutor courses, editors)
```

### وقت تفتح إيه (سريع)

| محتاج إيه؟ | روح فين |
|---|---|
| تعرف Ahmed وعميله | [03_KNOWLEDGE/client-context.md](03_KNOWLEDGE/client-context.md) |
| تعرف الـ business strategy + scorecard | [03_KNOWLEDGE/comprehensive-audit.md](03_KNOWLEDGE/comprehensive-audit.md) |
| تعرف خطة الـ 90 يوم | [03_KNOWLEDGE/gtm-roadmap-90days.md](03_KNOWLEDGE/gtm-roadmap-90days.md) |
| تعرف الـ bugs اللي ممكن نشتغل عليها | [01_WEB/bugs-report.md](01_WEB/bugs-report.md) |
| تعرف الـ Sprint plan + tasks | [01_WEB/specs/001-bug-remediation-90day/README.md](01_WEB/specs/001-bug-remediation-90day/README.md) |
| تشتغل على الكود نفسه | [01_WEB/website/](01_WEB/website/) (clone مع submodule update) |
| تعمل UI Audit | `cd 01_WEB/_tools/ui-audit && npm run audit` |
| تعرف الـ backup system | [02_AUTOMATION/backups/](02_AUTOMATION/backups/) + `02_AUTOMATION/CLAUDE.md` |
| تعرف الـ lessons من sessions قديمة | [lessons.md](lessons.md) |
| تاريخ كل الـ sessions السابقة | [_session-log.md](_session-log.md) |


---

## 🚀 CURRENT STATE — 2026-06-25

> ⭐ **اقرا الأول:** memory `project_focus_pivot_website_tracking_2026-06-25` (أحدث) + `project_omar_agent_live_state_2026-06-10` + `HANDOFF.md`.
> 📜 **تاريخ كل الـ sessions بالتفصيل**: [_session-log.md](_session-log.md).

**🎯 الفوكس الحالي (قرار Omar 2026-06-25):** موقع **bug-free** + **tracking جاهز 100% للإعلانات ونقدر نقرر بالأرقام**. استرجاع السلة + الإيميل **مؤجّلين** (مش ملغيين). بوابة الدفع **سليمة** — متغيّرهاش.

**اللي اتعمل النهاردة (LIVE + متأكّد):**
- 🐛 **الموقع bug-free:** BUG-003 (عنوان `<title>` مكرر سيت-وايد) اتقفل → Rank Math المصدر الوحيد + غلطتين إملائيتين. منشور + متأكّد حي + **PR #18** (Learrnsimply/edublink-child). سبيك 003 كان 26/29 منهم اتعمل خلاص في PRs سابقة.
- 📊 **التتبّع جاهز (4 قنوات):** **GA4 ecommerce** (swap لمصدر واحد — Google Analytics for WooCommerce بدل MonsterInsights، events متوصّلة) · **Clarity** LIVE سيت-وايد (project `xckdxrkgej`) · **Meta** متوصّلة FBE2 (browser pixel + CAPI) · **TikTok** MAPI. التفاصيل: memory `project_focus_pivot_website_tracking_2026-06-25`.

**اللي شغّال من قبل (مستمر):**
- 🤖 **مساعد واتساب "عمر"** بيرد على كل العملاء (`201030127228`) — memory `project_omar_agent_live_state_2026-06-10`.
- 🛒 **W4 استرجاع السلة** (`Er1W6KSwqOiEmgrd`) شغّال (التوسعة مؤجّلة) · **Mautic + Brevo** قناة الإيميل · إصلاح hold-stock (60→1440).

**الفهم الصح للاقتصاد** (deep-dive 2,936 أوردر): الخسارة الصافية ≈ **14.3K ج/شهر** (مش 195K/سنة) — السبب **timeout** مش البوابة. اكتشاف: **صدمة السعر** (الإكمال 79%→37% كل ما السعر يعلى).

**الجاي:**
- ⏳ **تأكيد Omar للداشبوردين:** `purchase` في GA4 Realtime + Event Match Quality في Meta Events Manager → يقفل "100% ad-ready".
- أحمد يعمل merge لـ PR #18 (منشور حيّ خلاص — للسجل).
- (مؤجّل بقرار Omar) W5 blast استرجاع السلة (~186K ج) + تنشيط الـ 13K إيميل.

---

## 📊 الأرقام اللي محتاج تعرفها دايماً

| الرقم | القيمة | المصدر |
|---|---|---|
| إيراد شهري | ~67K EGP | WooCommerce API (مارس 2024 – مايو 2026) |
| إيراد إجمالي | 1,131,000 EGP في 27 شهر | — |
| نمو 2026 vs 2025 | **+172%** | — |
| Email subscribers | 13,140 (Mautic ~13.7K contact) | صفر email marketing قبل 2026-06 |
| YouTube | **369K مشترك** · 18.5M مشاهدة | عداد About |
| Telegram | 24.4K · تفاعل 60-86% | — |
| نزيف الدفع الصافي | **~14.3K ج/شهر** (timeout abandonment، مش فشل بوابة) | deep-dive 2026-06-24 |

---

## 🎯 أكبر الفرص (محدّث 2026-06-25)

1. **🟢 جاهز — التتبّع للإعلانات** (GA4 ecommerce + Clarity + Meta FBE2 + TikTok) → القرار بالأرقام + إطلاق إعلانات بثقة.
2. **✅ اتقفل — الموقع bug-free** (BUG-003 + إملائي، PR #18) + نزيف hold-stock + احتكاك checkout.
3. **🟢 LIVE — مساعد واتساب "عمر"** بيرد على كل العملاء.
4. **(مؤجّل بقرار Omar)** استرجاع السلة W5 (~186K ج) + تنشيط الـ 13K إيميل عبر Mautic+Brevo.
5. (اختياري) تقليل صدمة السعر / تقسيط على الباقات · LiteSpeed Cache.

> ⚠️ **متفنّد (متبنيش عليه):** «Kashier migration لـ 195K/سنة» و«909 فشل CC» — السبب الحقيقي timeout مش البوابة. **متغيّرش بوابة الدفع.**

---

## 🗣️ How Omar talks about this brand

من الـ root CLAUDE.md (`../../CLAUDE.md`)، بس بإضافات خاصة بالعميل:

- **اللهجة:** Egyptian Arabic — Ahmed جمهوره مصري + خليجي بيدور على المحتوى البسيط
- **العملة:** الجنيه المصري (EGP) — لو Pricing أجنبي ابعتله مقابل EGP في النص
- **المصطلحات:** "كورس" (مش ورشة زي rspaac)، "طالب" (مش متدرّب)، "محتوى" مش "محاضرة"
- **الصوت:** بسيط، قريب، صادق. Ahmed positioning كله مبني على "البرمجة مش صعبة، بس محتاجة حد يعرف يشرحها."
- **RTL + Mobile-first:** كل الجمهور تقريباً على موبايل، والـ traffic كله RTL Arabic

---

## ⚠️ تنبيهات

- **مفيش push على main برّا PR review من Ahmed** — هو owner الـ repo، إحنا collaborators بـ PR workflow
- **مفيش `wp db export` بدون أول ما تجرّب** — في CageFS بيـ fail silent. استخدم `mysqldump` بالـ credentials من `wp config get`
- **مفيش `crontab -e` من SSH** — Hostinger shared plan بيمنعه. كل cron يتدار من hPanel UI
- **مفيش `rm -rf` على السيرفر بدون ما تعرضه على Omar** — حتى لو الـ path واضح، اطلب confirmation


---

## Agent skills

### Issue tracker

Issues live as GitHub issues on `omarabdo516/learn-simply` (private), driven by the `gh` CLI. See `docs/agents/issue-tracker.md`.

### Triage labels

Default five-role vocabulary, label strings identical to the role names. See `docs/agents/triage-labels.md`.

### Domain docs

Single-context: `CONTEXT.md` + `docs/adr/` at the repo root (created lazily by `/domain-modeling`). See `docs/agents/domain.md`.
