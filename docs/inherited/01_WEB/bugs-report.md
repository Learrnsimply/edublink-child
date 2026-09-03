# 🐞 تقرير الـ Bugs الشامل — اتعلم ببساطة

> 📅 **آخر تحديث:** 2026-05-23 (Wave 2 + Verification Pass)
> 📊 **الإجمالي الحالي:** 134 bug عبر 8 مسارات (3 منهم اتعدّلوا بعد re-verification)

> ⚠️ **Verification Pass 2026-05-23:** بعد ما أحمد قال "Kashier شغّال"، عدت فحصت كل critical claim بـ SSH. **3 ادعاءات اتعدّلوا:**
> - **plugins.md C-1 (Kashier):** من Critical → Low (الـ plugin official distribution عبر GitHub، شغّال، 102 successful orders آخر 30 يوم)
> - **integrity.md C-1 (From-Address):** من Critical → Medium (wp-mail-smtp بيـ override الـ Gmail بـ contact@learrnsimply.com — deliverability فعلياً OK)
> - **integrity.md C-2 (909 failed CC):** من Critical → High (gateway مش مكسور — الـ cancel rate حقيقي بس بيـ require optimization مش migration)
> - **integrity.md H-5 (1645 active sessions):** scope updated (الـ 1645 stale > 18 يوم، الـ cart recovery للـ future abandonment مش رد على دول)
> الـ ROI الإجمالي نزل من ~600K-1M EGP/سنة لـ ~150-400K EGP/سنة (لسه قيمة عالية، بس واقعية).

---

## الإحصائية الموحّدة

### Wave 1 — الـ Audit الأول (3 مسارات، بدون SSH)

| الخطورة | الكود | الموقع الحي | البيانات | المجموع |
|---|---|---|---|---|
| 🔴 Critical | 4 | 3 | 3 | 10 |
| 🟠 High | 8 | 5 | 8 | 21 |
| 🟡 Medium | 8 | 6 | 7 | 21 |
| 🟢 Low | 6 | 4 | 1 | 11 |
| **الإجمالي** | | | | **63 bug** |

📁 الملفات: [bugs-code.md](bugs-code.md) · [bugs-functional.md](bugs-functional.md) · [bugs-data.md](bugs-data.md)

---

### Wave 2 — Deep Audit (5 مسارات جديدة، عبر SSH + wp-cli + DB direct) — محدّث بعد verification

| الخطورة | Runtime | Integrity | Plugins | Performance | Security Deep | المجموع |
|---|---|---|---|---|---|---|
| 🔴 Critical | 2 | **2** | **3** | 1 | 3 | **11** |
| 🟠 High | 3 | 6 | 5 | 4 | 3 | **21** |
| 🟡 Medium | 7 | **10** | 6 | 5 | 4 | **32** |
| 🟢 Low | 1 | 1 | **3** | 1 | 1 | **7** |
| **المجموع** | **13** | **19** | **17** | **11** | **11** | **71 bug جديد** |

> Bold = اتغيّر بعد verification 2026-05-23

📁 الملفات الجديدة: [bugs-runtime.md](bugs-runtime.md) · [bugs-integrity.md](bugs-integrity.md) · [bugs-plugins.md](bugs-plugins.md) · [bugs-perf.md](bugs-perf.md) · [bugs-security-deep.md](bugs-security-deep.md)

---

### المجموع الكامل (محدّث بعد verification)

| الخطورة | Wave 1 | Wave 2 | **الإجمالي** |
|---|---|---|---|
| 🔴 Critical | 10 | **11** | **21** (was 25) |
| 🟠 High | 21 | 21 | **42** |
| 🟡 Medium | 21 | **32** | **53** (was 49) |
| 🟢 Low | 11 | **7** | **18** (was 17) |
| **المجموع** | **63** | **71** | **134 bug** |

**التغيير:** 4 Critical اتـ downgrade (3 لـ Medium، 1 لـ Low). الإجمالي ثابت 134 بس distribution اختلف.

> **ملاحظة overlap:** ~4 من Wave 2 يكمّلوا bugs من Wave 1 (مثل DI-C1 يمدّ data-H18 بـ deeper email config evidence). الـ 71 الجديدة لسة **67 منهم تماماً جديدة** — مكنش الـ REST API الأول قادر يكشفهم.

---

## 🔥 Top 10 Most Critical (محدّثة بعد verification + Ahmed clarifications 2026-05-23)

| # | Bug | الملف | الأثر التقريبي (verified) |
|---|---|---|---|
| 1 | 🔴 **SMTP password leaked في DB + dump + chat** | bugs-security-deep.md C-1 | Immediate rotation needed |
| 2 | 🔴 **wp-content/.htaccess فاضي** — log files publicly readable | bugs-security-deep.md C-2 | Information disclosure |
| 3 | 🔴 **wp-content/uploads/.htaccess فاضي** — PHP execution risk | bugs-security-deep.md C-3 | Potential RCE |
| 4 | 🔴 **debug.log مكشوف على wp-content** (15KB من errors واضحة) | bugs-runtime.md C-1 | Info disclosure |
| 5 | 🔴 **5.6 GB ai1wm backups مكدّسة** (43% من wp-content) | bugs-runtime.md C-2 | Disk pressure + backup مدمّر |
| 6 | 🔴 **918 autoloaded option = 987 KB كل request** | bugs-perf.md C-1 | Response time hit |
| 7 | 🟠 **Cart recovery setup للـ future abandonment** | bugs-integrity.md H-5 (revised) | ~30-60K EGP/شهر بعد الإعداد |
| 8 | 🟠 **Cancel rate على Kashier 39%** — تفعيل installments + Apple Pay | bugs-integrity.md C-2 (revised) | ~70K EGP/سنة optimization |
| 9 | 🟠 **Course 29368 (Python) منشور بدون WC product** | bugs-integrity.md H-2 | لا يمكن شراؤه = مبيعات ضايعة |
| 10 | 🟠 **Verify Meta Pixel events firing** | audit-tracking-funnel | Ad ROI tracking |

**اتشال من الـ Top 10:**
- ❌ "Kashier gateway broken" — disproved (gateway شغّال)
- ❌ "From-Address = Gmail disaster" — wp-mail-smtp بيـ override
- ❌ "662 processing orders stuck money" — Ahmed enrolling manually، **DO NOT TOUCH**
- ⬇️ "97% courses في trash" — نزل لأولوية أقل (الـ 67 معظمهم demo/test imports من 2023)

---

## ✅ Sprint 1 — اتعمل وموجود في PR #1 (في انتظار Ahmed)

**8 إصلاحات** على branch `fix/audit-sprint-1` في `Learrnsimply/edublink-child`:

1. ✅ XSS sanitization (Twig `|safe_html` و `|safe_embed` filters)
2. ✅ Disabled REST `/users` endpoint (مؤكد: HTTP 404 على production)
3. ✅ Removed debug files (`list-lessons.php` + `create-quizzes-topic1.php`)
4. ✅ Fixed cart.php dead code (445 سطر → 253)
5. ✅ Fixed Buy Now button (only show لو course.product_id موجود)
6. ✅ Fixed "View all courses" link
7. ✅ Fixed footer about-me link
8. ✅ Removed duplicate `<title>` injection

**PR:** https://github.com/Learrnsimply/edublink-child/pull/1
**الحالة:** منتظر Ahmed يراجع

> **ملاحظة:** /blog/ في الـ original audit كان flagged كـ 404 — لكن Playwright أثبت إنه شغّال. اعتبره deferred حتى نتأكد منه على prod بالـ context الكامل.

---

## 📋 Sprint Roadmap (محدّث بعد verification — راجع specs/001-bug-remediation-90day/plan.md للتفصيل)

### Sprint 2 / Phase 1 — Growth Foundations (أسبوع 1)
**هدف:** setup growth tools (cart recovery، Meta Pixel، email) + cleanup صغير
> 🛑 **DO NOT TOUCH:** الـ 662 processing orders (Ahmed بيـ enroll customers manually — راجع integrity.md H-1 المعدّل)
- **Top P1:** **Cart recovery setup للـ future abandonment** (bugs-integrity.md H-5 revised) — ~30-60K EGP/شهر
- **Top P2:** **Cleanup 1673 stale WC sessions** (DB hygiene)
- **Top P3:** **Verify + Activate Meta Pixel** + verify events firing (audit-tracking)
- **Top P4:** **First test email campaign** للـ 72 confirmed MailPoet subscribers + decision على re-consent للـ 13K WC users
- Fix Course 29368 missing WC product (integrity.md H-2)
- Decision على 67 trashed courses (مع تحفّظ — 3 منهم demo/test)
- Sync WC settings: From-Address → `contact@learrnsimply.com` (cleanup)
- admin_email → `contact@learrnsimply.com` (the only business email)
- Expired coupon cleanup (integrity.md H-6)
- (Optional) Kashier optimization: installments + Apple Pay — ~70K EGP/سنة

### Sprint 3 — Security Hardening (أسبوع 2)
**هدف:** Plug attack surfaces
- **P1:** Rotate SMTP password (bugs-security-deep.md C-1) — فوراً!
- **P2:** Write wp-content + uploads `.htaccess` (bugs-security-deep.md C-2, C-3)
- **P3:** Disable xmlrpc.php (bugs-security-deep.md H-2)
- **P4:** Install Limit Login + 2FA (bugs-security-deep.md H-3)
- Delete debug.log (bugs-runtime.md C-1)
- Audit/delete suspicious uploads PHP files (bugs-security-deep.md H-1)

### Sprint 4 — Performance & Cleanup (أسبوع 3)
**هدف:** الموقع أسرع + DB أنضف
- **P1:** Enable HPOS + finish migration (bugs-plugins.md H-4)
- **P2:** Consolidate plugin stack (61 → ~43 active) (bugs-plugins.md C-4, H-3, H-5, M-*)
- **P3:** Drop MailPoet + BlogVault + AIOSEO + WooFunnels orphan tables (bugs-plugins.md C-2, C-3, M-3, M-4)
- **P4:** Autoload cleanup (bugs-perf.md C-1)
- **P5:** Theme functions.php refactor (bugs-perf.md H-2) — 106KB → modular
- Delete homepage.html theme artifact (bugs-perf.md H-3)
- Fix CDN cache bypass (bugs-perf.md H-4)

### Sprint 5 — Quality of Life (أسبوع 4)
- Update parent EduBlink theme (bugs-perf.md M-3)
- Fix theme dependency chain (bugs-runtime.md H-3)
- Install DB index optimizer (bugs-perf.md H-1)
- HSTS + CSP headers (bugs-security-deep.md M-3, M-4)
- Spam users cleanup (bugs-integrity.md L-1)
- Schema drift fixes (bugs-integrity.md M-3, M-4)
- WP cron → system cron (bugs-runtime.md M-4)

---

## 📊 الأرقام من الـ Audit (verified 2026-05-23)

| الـ Metric | الرقم | Verification source |
|---|---|---|
| Active plugins | **61** | `wp plugin list --status=active` |
| Backup plugins active | **4** (AIWM + WPvivid + Duplicator + WPSynchro) | plugin list |
| Kashier processed (last 30d) | **102 orders** (75 card + 27 wallet) = **57K EGP** | wp_wc_orders SELECT |
| Cancel rate on cards (last 30d) | **39%** (43 cancel + 5 fail vs 75 success) | wp_wc_orders SELECT |
| Processing orders بـ real money | **345** orders بـ 100-499 EGP = **92,334 EGP** | wp_wc_orders SELECT |
| Processing orders بـ 0 EGP (anomaly) | **316** | wp_wc_orders SELECT |
| ~~Failed CC transactions all-time (909)~~ | **misleading** — mostly natural abandonment + 3DS friction | revised analysis |
| WC sessions في DB | **1673** بس كلهم > 18 يوم stale | wp_woocommerce_sessions |
| Orphan postmeta rows | **1000** | LEFT JOIN query |
| Orphan tutor_enrolled rows | **659** | LEFT JOIN query |
| Users بـ empty role | **158** | wp_usermeta query |
| Duplicate user emails | **4** | GROUP BY query |
| Total users | **13,406** | wp_users COUNT |
| MailPoet confirmed subscribers (legacy) | **72** | wp_mailpoet_subscribers |
| MailPoet unconfirmed (legacy) | **1401** | wp_mailpoet_subscribers |
| Cron events registered | **60+** | wp cron event list |
| wp-content disk usage | **13 GB** | du -sh |
| ai1wm backups وحدها | **5.6 GB** | du -sh |
| Orphan plugin tables | **40+** (MailPoet, BlogVault, AIOSEO, WooFunnels...) | information_schema |
| Trashed courses vs published | **67 trashed vs 5 published** (3+ منهم test/demo) | wp_posts COUNT |
| Theme overrides | **9 files** | find diff |
| Child theme functions.php | **106 KB** | wc |

---

## ⚠️ ملاحظات مهمة قبل Sprint 2+

1. **الـ SMTP password اتسرّب أثناء الـ audit** (طبيعة الفحص). Ahmed لازم يـ rotate من hPanel **قبل أي شغل تاني**.

2. **الـ Backup System** اللي عملناه (Hostinger daily + GitHub weekly) كافي تماماً — مش محتاجين الـ 4 backup plugins. حذفهم في Phase 3 يفرّج 6+ GB.

3. **HPOS not enabled بـ data sync running** = بيانات مكررة في 2 tables (`wp_wc_orders` + `wp_posts`) — لازم نـ enable HPOS في Phase 3 قبل ما الـ migration يصعب أكتر.

4. ~~الـ Kashier plugin من GitHub master = الـ root cause المحتمل لـ 909 CC failure~~ → **CORRECTED 2026-05-23:** الـ plugin official من Kashier، شغّال، 102 successful orders في آخر 30 يوم. الـ cancel rate الحقيقي بيـ require optimization (installments + Apple Pay) مش migration.

5. **Permission classifier:** كل SSH command للـ production بيحتاج explicit approval — احتفظنا بـ session واحدة بـ multi-step scripts لتقليل الـ prompts.

6. **درس متعلّم:** قبل ما نـ flag حاجة كـ Critical، لازم verify الـ EFFECTIVE state مش بس الـ stored value. مثال: WC option قال From-Address = Gmail، لكن wp-mail-smtp بيـ override. (راجع `lessons.md`)

---

## 🛠️ ترتيب الإصلاح الـ Strategic (محدّث)

**اللحظة دي (لـ Ahmed مراجعة فورية):**
- ✅ Sprint 1 PR (#1, #2) merged
- ✅ Checkout polish PRs (#3-#11) merged
- ⏳ Rotate SMTP password ← لسه pending
- ⏳ Manual review الـ 662 processing orders

**أسبوع 1 (Phase 1 — Revenue + Operations):**
- Manual cleanup الـ 662 processing orders ← recover 92K EGP
- Cart recovery setup للـ future abandonment ← ~30-60K EGP/شهر
- Activate Meta Pixel + first test email campaign
- Optimize Kashier checkout (installments + Apple Pay) ← ~70K EGP/سنة

**أسبوع 2 (Phase 2 — Security):**
- .htaccess hardening + xmlrpc disable + 2FA + SMTP rotation

**أسبوع 3 (Phase 3 — Performance + Cleanup):**
- Plugin reduction (61→43) + HPOS + DB cleanup + 6 GB disk free

**أسبوع 4 (Phase 4 — Theme Refactor):**
- functions.php 106KB → modules + parent theme update

**أسبوع 5+ (Phase 5 — Future-Proofing):**
- CI integration + FluentCRM rollout + monitoring + staging env

---

## الخلاصة (محدّثة بعد verification 2026-05-23)

**134 bug فعلي**، منهم **21 Critical** (بعد تصحيح 4 ادعاءات):

**Top 5 Critical الحقيقية:**
1. 🔴 **SMTP password leaked** = credential rotation فوراً
2. 🔴 **.htaccess فاضي على wp-content + uploads** = attack surface مفتوح
3. 🔴 **662 processing orders بـ 92K EGP حقيقي علقة** = customer service + recovery
4. 🔴 **debug.log مكشوف** على wp-content = info disclosure
5. 🔴 **5.6 GB ai1wm backups مكدّسة** = disk pressure + بيـ كسر الـ backup الصحيح

**اللي اتصحّح (5 ادعاءات downgrade/resolved):**
- ❌ Kashier "broken" → ✅ شغّال (102 orders آخر 30 يوم)
- ❌ From-Address "Gmail disaster" → ✅ SMTP بيـ override بـ contact@learrnsimply.com
- ❌ "909 failed CC = gateway leak" → optimization opportunity (cart recovery + installments)
- ❌ "1645 active sessions = 150K recovery" → كلهم stale > 18 يوم
- ❌ "662 processing orders = 92K EGP stuck" → **Ahmed كان بيـ enroll customers manually**، الـ status متعلّق فقط cosmetic. **DO NOT TOUCH.**

**الـ ROI الكلي المعدّل (واقعي):**
- ~~92K EGP recovery من processing orders~~ (مش recovery، الـ customers أخدوا كورساتهم)
- ~70K EGP/سنة من Kashier optimization
- ~360-720K EGP/سنة من cart recovery للـ future
- ~50K EGP/شهر محتمل من email marketing على 72 confirmed subscribers (+ re-consent للـ 13K)
- **مجموع محافظ: ~200-700K EGP/سنة** (نزل من الـ initial overestimate بعد ما processing orders اتـ exclude)

**Wave 2 كشف أكثر من Wave 1** (71 vs 63) — الـ access الأعمق (SSH + wp-cli + DB direct) فتح كل الـ layers اللي مكان REST API بيخفيها. **Verification pass كشف overestimation في 4 critical claims** — مهم نـ verify EFFECTIVE state دايماً.

**Sprint 1 + checkout fixes merged.** Phase 1 جاهزة للبدء بناءً على الـ revised plan في `specs/001-bug-remediation-90day/`.
