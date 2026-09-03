---
tags:
  - team
brand: learn-simply
team: knowledge
---

# 📚 قاعدة المعرفة + GTM — Learn Simply

> **⚠️ اقرأ `../CLAUDE.md` (brand router) + `../lessons.md` أول حاجة**

## دور الفولدر

ده **مصدر الحقيقة (Source of Truth)** لكل معلومة عن:
- العميل (Ahmed Adel) + الـ ICP + الكاتالوج
- الـ GTM strategy + الـ 90-day roadmap
- Marketing channels (YouTube, Telegram, FB, IG, TikTok)
- Tracking + funnel analysis
- Integration setup steps

أي قرار marketing/business/strategy للـ Learn Simply يرجع هنا أولاً.

---

## الملفات الرئيسية

### 🎯 العميل + الـ Strategy

| الملف | بيدّيك |
|---|---|
| [client-context.md](client-context.md) | **Full client profile** — الـ founder Ahmed، الـ ICP، الكاتالوج، الـ funnel، الأرقام (316 سطر) |
| [comprehensive-audit.md](comprehensive-audit.md) | **الـ master audit** — Scorecard 10-بُعد + 14 action على Master Action List. النقطة المرجعية الأولى |
| [gtm-roadmap-90days.md](gtm-roadmap-90days.md) | خطة 90 يوم — Phase 0/1/2/3 + KPIs |
| [phase-0-audit.md](phase-0-audit.md) | الـ access الأولي + الـ Phase 0 findings |

### 📡 الـ Channels + الـ Marketing

| الملف | الموضوع |
|---|---|
| [audit-channels.md](audit-channels.md) | YouTube (369K)، Telegram (24.4K)، FB (مشتّت)، TikTok |
| [audit-tracking-funnel.md](audit-tracking-funnel.md) | GA4، Meta Pixel (متوقّف!)، الـ funnel leaks |
| [setup-integrations.md](setup-integrations.md) | Roadmap ربط القنوات (YouTube، GitHub، FB، IG، إلخ) |

### 📊 الـ Data Snapshots

| الملف | بيدّيك |
|---|---|
| [data/tutor_wp_courses.json](data/tutor_wp_courses.json) | Snapshot لـ Tutor LMS courses من الـ REST API |
| [data/editors.json](data/editors.json) | مرجع للـ editor list (ناقص لسه) |

---

## محتوى محتمل في الـ Future

| Sub-folder | الغرض |
|---|---|
| `brand-voice.md` | Voice + tone guidelines (الـ "بساطة" angle) |
| `content/` | Content templates، email copy، ad copy |
| `presentations/` | Strategy decks، pitch decks |
| `competitor-research/` | تحليل Udemy/Skillshare/الـ Arabic competitors |
| `customer-personas/` | Detailed personas للـ ICP segments |

---

## أهم 5 حقائق لازم تعرفهم عن الـ Brand

1. **Ahmed Adel** = founder + instructor + developer (solo). ~250K YouTube subs.
2. **الجمهور:** مصري + خليجي، 18-35، طلاب وخريجين CS، أغلبية mobile-first
3. **العملة:** الجنيه المصري (EGP). متوسط الـ order ~430 EGP.
4. **الـ Catalog:** 5 published courses (~92K subscribed students)، 67 trashed (معظمهم demo/test). Java + Data Structure + Python + OOP.
5. **الـ Voice:** "البرمجة مش صعبة، بس محتاجة حد يعرف يشرحها." — بسيط، قريب، صادق

---

## ⚠️ قواعد العمل في الفولدر ده

1. **مفيش تعديل على client-context.md** بدون تأكيد من Omar — هو الـ source of truth للـ business facts
2. **الـ comprehensive-audit.md = canonical** — لو حاجة تختلف بينه وبين drill-down audit، الـ comprehensive يكسب
3. **الأرقام لازم تتـ verify من source live** — لو مكتوب "13K subscribers"، اتأكد بـ `wp db query` قبل ما تـ quote في deck
4. **اللهجة:** Egyptian Arabic عشان Ahmed جمهوره مصري + خليجي. مفيش MSA أو Khaleeji.
