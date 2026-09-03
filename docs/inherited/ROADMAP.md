# Learn Simply — Implementation Roadmap

> **Single source of truth** لترتيب الـ phases والـ tasks — على نمط rs-website-community.
> **كل session تبدأ من هنا:** اقرا الجدول → افتح الـ phase النشطة → اقرا spec + plan + tasks بتوعها → نفّذ.
> الحالة التفصيلية لكل task جوه `tasks.md` بتاع الـ phase — **مش هنا**. هنا الـ dashboard بس.

---

## 🎯 North Star

تحويل أصول أحمد الموجودة فعلاً (350K+ يوتيوب · 13.7K إيميل · 24.4K تليجرام · ~67K EGP/شهر) لماكينة إيراد منظمة: إيميل engine شغال + يوتيوب funnel + checkout من غير نزيف الـ 30% إلغاءات. الهدف: مضاعفة الإيراد الشهري خلال 90 يوم.

---

## 📅 Track 1 — GTM Execution (`specs/`)

| Phase | الاسم | المدة | Status | Folder |
|-------|------|------|--------|--------|
| 001 | Launch Unblock — Dart + قناة الإرسال | أيام (deadline 15 يونيو!) | 🔄 ACTIVE — specs جاهزة للمراجعة | [`specs/001-launch-unblock/`](./specs/001-launch-unblock/) |
| 002 | Email Engine Completion | ~2 أسابيع | 📋 Placeholder | [`specs/002-email-engine/`](./specs/002-email-engine/) |
| 003 | YouTube Funnel | ~2 أسابيع | 📋 Placeholder | [`specs/003-youtube-funnel/`](./specs/003-youtube-funnel/) |
| 004 | CRO + Tracking Completion | ~2 أسابيع | 📋 Placeholder | [`specs/004-cro-tracking/`](./specs/004-cro-tracking/) |
| 005 | Content & Outbound (SEO/B2B/Affiliate) | ~3 أسابيع | 📋 Placeholder | [`specs/005-content-outbound/`](./specs/005-content-outbound/) |

## 📅 Track 2 — Website Audit & Remediation (`01_WEB/specs/`)

| Phase | الاسم | المدة | Status | Folder |
|-------|------|------|--------|--------|
| 001 | Bug Remediation 90-day (legacy) | — | 🟡 جزئياً (20/113 task) — اتجمّد، البنود الحية اتنقلت لـ 002/003 | [`01_WEB/specs/001-bug-remediation-90day/`](./01_WEB/specs/001-bug-remediation-90day/) |
| 003 | Theme Defect Remediation (verified closeout) | أيام | 🟡 spec+clarify+plan+**tasks** done (2026-06-14) — ⏳ `/speckit-implement` · 29 task / 11 FR | [`01_WEB/specs/003-theme-defect-remediation/`](./01_WEB/specs/003-theme-defect-remediation/) |
| 002 | Website Audit v2 — Re-audit + Closeout | ~3 أسابيع | 📋 Placeholder | [`01_WEB/specs/002-website-audit-v2/`](./01_WEB/specs/002-website-audit-v2/) |

---

## ✅ اللي خلص قبل الـ Roadmap ده (credit — مايتعملش تاني)

مرجعه الكامل: [`03_KNOWLEDGE/gap-analysis-2026-06-11.md`](./03_KNOWLEDGE/gap-analysis-2026-06-11.md)

- ✅ **Infrastructure:** VPS + Mautic 7 (hardened) + n8n (queue mode) + Traefik/SSL — كله LIVE
- ✅ **Data:** 13,711 contact في Mautic + 11 custom fields + 9 segments + W1 (WC→Mautic) live
- ✅ **Email channel:** Brevo = PRIMARY sender (live من 2026-06-06، مربوط بـ Mautic ومتأكد، 300/يوم free) — استبدل Hostinger SMTP الميّت
- ✅ **Tracking:** Meta Pixel + CAPI live · TikTok live · GA4 (PageView بس)
- ✅ **مساعد واتساب "عمر":** FULLY LIVE للجميع على 201030127228 (RAG + صوت/صور + تصعيد Telegram)
- ✅ **Dart pre-launch:** صفحة /dart + popup + شريط sitewide + 74+ waitlist + إيميلات 1-5 drafted
- ✅ **Website security:** Sprint 1 PR merged · htaccess/xmlrpc/HSTS/chmod · Tutor 3.9.11 (CVE 9.8 مقفولة)
- ✅ **Website perf:** +10.1GB disk · autoload -70% · DB indexes (Ollie Jones)

---

## ⚠️ Blockers الحية (محدّثة 2026-06-11)

| Blocker | الحالة | بيمنع إيه |
|---|---|---|
| 🔴 **منتج Dart في WooCommerce** | مستني أحمد (قبل 15 يونيو) | حملة إطلاق Dart كلها — **البلوكر الوحيد للإطلاق** |
| 🟡 موافقة أحمد على إيميلات 2-5 | على Notion | نشر السيكوينس |
| 🟡 **AWS SES production access** | اترفض 2026-06-08 — **مش بلوكر إطلاق** (Brevo بيغطي) — appeal في Phase 002 لسعة الـ 13K الأرخص | تحسين سعة مستقبلي بس |
| 🟡 قرارات أحمد المفتوحة | 67 trashed courses · social proof · تقسيط Kashier | Phases 004 + Audit v2 |

---

## ✅ Phase Approval Log

> كل phase بتستنى `APPROVED ✅` صريحة من Omar قبل ما التنفيذ يبدأ. الاعتماد بيتسجل هنا بالتاريخ.

- Phase 001 (GTM — Launch Unblock): spec.md APPROVED ✅ (2026-06-11) · plan.md + tasks.md ⏳ مراجعة
- Phase 002-005 (GTM): 📋 لسه مش spec'd — بيتعملوا spec عند دورهم
- Phase 003 (Theme Defect Remediation): spec ✅ + clarify ✅ + plan ✅ + plannotator ✅ + **tasks ✅** (2026-06-14) · 11 FR / **29 task** · plannotator: ملاحظة "php محلي" اتنفذت (docker php-lint + `_tools/php-lint.sh`) · ⏳ `/speckit-implement` (L0 functions.php الأول). اكتشاف تخطيط: FR-011 (العداد) تشخيصه اتصحّح — البق admin-side + فخ DST (يونيو=UTC+3)، مش frontend.
- Phase 002 (Audit v2): 📋 لسه مش spec'd

---

## 🧭 Workflow (لكل session)

1. اقرا الجدولين فوق → حدد الـ phase النشطة (🔄)
2. افتح فولدر الـ phase → اقرا `spec.md` (الإيه وليه) → `plan.md` (الإزاي) → `tasks.md` (الخطوات)
3. نفّذ task واحدة في المرة — بالترتيب — وعلّم `[x]` مع **دليل التحقق الفعلي** (مش "تم" وخلاص)
4. آخر السيشن: حدّث progress header في `tasks.md` + سطر Current work تحت

## 📏 القواعد

- **مفيش phase تبدأ قبل APPROVED** من Omar في الـ log فوق
- **Task واحدة في المرة** — مفيش قفز
- **الـ acceptance لازم يتأكد بدليل حقيقي** (screenshot / API response / Playwright) — مش افتراض
- **اللي يتأجل يتكتب صريح** بسهم → Phase رقم كذا — مفيش "هنشوفه بعدين" عائم
- بنود stale أو اتدحضت → تتعلم 🗑️ OBSOLETE في مكانها مع السبب

---

## 📍 Current work

**2026-06-14:** Phase 003 (Theme Defect Remediation) spec.md اتكتبت بعد verification workflow أعاد فحص الـ 134 bug ضد الكود الحالي (13 already-fixed، 7 stale، 8 آمنة في النطاق، 8 deferred decisions، 2 لـ Audit v2). `.specify/feature.json` بيشاور على 003 (كان على agency 009 — recoverable). الجاي: `/speckit-clarify` يحسم DD-1..DD-8.

**2026-06-11:** Phase 001 (Launch Unblock) specs اتكتبت — مستنية مراجعة Omar. تصحيح مهم: قناة الإرسال مش بلوكر (Brevo شغّال PRIMARY من 2026-06-06). البلوكر الوحيد للإطلاق = منتج Dart من أحمد. SES رُفض 2026-06-08 لكنه بقى تحسين سعة في Phase 002. الجرد الكامل: `03_KNOWLEDGE/gap-analysis-2026-06-11.md`.
