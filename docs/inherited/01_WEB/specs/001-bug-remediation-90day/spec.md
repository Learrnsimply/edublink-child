# Feature Specification: Learn Simply Bug Remediation (90-Day Plan)

**Feature Branch**: `001-bug-remediation-90day`
**Created**: 2026-05-23
**Last verified**: 2026-05-23 (re-verification pass — 4 critical claims revised)
**Status**: Draft → ready for plan
**Brand**: learn-simply (`brands/learn-simply/`)
**Input**: 134 documented bugs across 8 audit reports + the bug class caught by the new `_tools/ui-audit/` Playwright tool.

> ⚠️ **Verification Pass 2026-05-23:** After Ahmed confirmed Kashier was actually working, ran SSH re-verification on all critical claims. Four critical findings were downgraded:
> - `bugs-plugins.md C-1` (Kashier): Critical → Low (plugin is official, processed 102 orders last 30 days)
> - `bugs-integrity.md C-1` (From-Address Gmail): Critical → Medium (wp-mail-smtp overrides with contact@learrnsimply.com)
> - `bugs-integrity.md C-2` (909 failed CC): Critical → High (gateway not broken — optimization opportunity, not migration)
> - `bugs-integrity.md H-5` (1645 active sessions): scope corrected. **Re-verified 2026-05-24:** 1307 expired + 412 active = 1719 total (normal WC 48h turnover). Cart recovery serves FUTURE abandonment، not "rescue" of existing carts.
> Estimated ROI revised from ~600K-1M EGP/year to ~400K-1M EGP/year (still high; now realistic).

---

## السياق (Context)

### اللي إحنا فيه دلوقتي (verified 2026-05-23)

موقع learrnsimply.com بيـ generate إيراد ~67K EGP/شهر بـ **+172% نمو سنوي** — لكن في تحته:

**اتأكد ✅:**
- **39% cancel rate على البطاقات في آخر 30 يوم** (43 cancel + 5 fail vs 75 success)
- **345 processing order بـ 92K EGP حقيقي** (+316 processing بـ 0 EGP = anomaly منفصل)
- **316 processing order بـ 0 EGP** — bug تاني (probably coupon/test flood)
- **1719 WC sessions** (1307 expired + 412 active، verified 2026-05-24) — normal turnover، not a "stale" problem
- **72 confirmed MailPoet subscribers** legacy + 1401 unconfirmed (mailpoet table موجودة بس plugin متشال)
- **13,406 user account** (gmail 11K، microsoft 484، yahoo 372، other 1380) — معظمهم بدون consent للـ marketing
- **3 سيستمات backup متضاربين** + 5.6 GB من النسخ القديمة (ai1wm-backups)
- **61 plugin active** (5 منهم Elementor extras، 4 backup، 3 SVG/SEO duplicates)
- **ثغرات أمنية:** SMTP password بـ plain-text في DB، wp-content/.htaccess فاضي، xmlrpc.php مفتوح
- **Course 29368 (مشاريع بايثون)** publish بس مفيش WC product (مش يمكن شراؤه)
- **67 trashed course** (66 منهم فيهم lessons، بس 3-5 demo/test obvious + ~10 demo content من 2023)

**اتعدّل ❌ (overstated في الـ initial audit):**
- ~~"909 failed CC = gateway leak"~~ → Kashier شغّال، الـ failures عبارة عن natural abandonment + 3DS friction (راجع integrity.md C-2 المعدّل)
- ~~"WC From-Address = Gmail disaster"~~ → wp-mail-smtp بيـ override بـ contact@learrnsimply.com (راجع integrity.md C-1 المعدّل)
- ~~"1645 active cart sessions = 150K recovery"~~ → كلهم stale، مفيش "rescue" opportunity على دول

**Meta Pixel:** الـ Facebook for WC plugin active لكن الـ Pixel ID setting ما ظهرش في `wp option get` — محتاج verification في الـ admin UI (مش في spec قاطع لسه)

### إيه اللي اتعمل قبل الـ spec ده

Phase 0 خلصت بالفعل في session 2026-05-23 (مرجع: bugs-report.md):

1. **Sprint 1 PR (#1, #2)** — 8 إصلاحات كود: XSS sanitization، REST users endpoint مقفول، debug files محذوفة، Buy button، footer link، duplicate `<title>`، unify "ادفع الآن" buttons
2. **Checkout polish (PR #5-#11)** — 7 إصلاحات: password field width، header overlap، content under fixed header (4 CSS layers conflicting)
3. **Backup system live** — Hostinger daily + GitHub weekly snapshots + Telegram alerts
4. **UI Audit tool** — Playwright script في `_tools/ui-audit/` بيـ catch 4 categories of regression

### المشكلة المتبقّية

137 bug من Wave 1 + Wave 2 audits، موزّعين على 8 تقارير:

| التقرير | Critical | High | Medium | Low | الإجمالي |
|---|---|---|---|---|---|
| bugs-code.md | 4 | 8 | 8 | 6 | 27 |
| bugs-functional.md | 3 | 5 | 6 | 4 | 18 |
| bugs-data.md | 3 | 8 | 7 | 1 | 21 |
| bugs-runtime.md | 2 | 3 | 7 | 1 | 13 |
| bugs-integrity.md | 5 | 6 | 6 | 1 | 19 |
| bugs-plugins.md | 4 | 5 | 6 | 2 | 17 |
| bugs-perf.md | 1 | 4 | 5 | 1 | 11 |
| bugs-security-deep.md | 3 | 3 | 4 | 1 | 11 |
| **الإجمالي** | **25** | **42** | **49** | **17** | **137** |

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 — أحمد (الـ Founder) يبيع كورس بدون فشل دفع تقني (Priority: P1)

أحمد بيـ promote كورس جديد على YouTube بـ ~369K مشترك. حد بيدوس على اللينك، بيكمل الـ checkout، بيدخل بيانات البطاقة. الـ payment gateway بيـ confirm النجاح أو الفشل بشكل صريح، الـ order بيـ flip لـ "completed" تلقائياً، الزبون بياخد الإيميل (مش في الـ spam) وبيدخل الكورس.

**Why this priority**: 30.2% cancel rate دلوقتي = خسارة مباشرة من كل dollar مدفوع على ads. أي تحسين هنا له أكبر ROI.

**Independent Test**:
- اعمل test purchase بـ بطاقة testing
- لاحظ: الـ order ينطبع كـ "completed" فوراً، الإيميل يوصل لـ inbox (مش spam)، الكورس يفتح للطالب
- في الـ WC dashboard، عدد الـ processing orders < 5 (مش 662)

**Acceptance Scenarios**:
- ✅ 3 test orders متتالية تنجح بدون فشل
- ✅ الـ From-Address على الإيميل = `contact@learrnsimply.com` (الـ business email الوحيد المؤكد من Ahmed — مفيش noreply@/admin@/support@)
- ✅ معدّل الفشل اليومي على بطاقات الائتمان < 10% (دلوقتي ~28%)

---

### User Story 2 — الطالب اللي حط حاجة في السلة ونسي يكمل، بيرجع (Priority: P1)

طالب بيدخل صفحة كورس، يدوس "ادفع الآن"، بيتنقل لصفحة المنتج، بيدوس Add to Cart. يخرج من الموقع بدون ما يكمل (سبب ما). بعد ساعة، بيوصله إيميل: "لسه كورس X في سلتك — كمّل دلوقتي". لو ما رجعش، بعد 24 ساعة بيوصله إيميل ثاني فيه discount code. بنسبة 5-10%، بيرجع ويكمل الشراء.

**Why this priority**: 412 active sessions الحالية (verified 2026-05-24) = inventory مستقبلي قابل للاسترداد لما email automation (Mautic on VPS) تكون live. حتى 5% recovery على traffic ثابت = ~80 customer جديد كل أسبوعين متى Phase 1.5 خلصت.

**Independent Test**:
- ضف منتج للسلة، اقفل المتصفح
- بعد ساعة: إيميل يجي بـ Subject + رابط مباشر للـ checkout
- بعد 24 ساعة: إيميل ثاني بـ discount code
- معدّل الـ recovery > 5% بعد شهر

**Acceptance Scenarios**:
- ✅ Plugin (CartFlows أو Recapture) مفعّل ومحفوظ في الـ workflow
- ✅ Test cart abandonment trigger يعمل email فعلاً
- ✅ Email open rate > 30%، click rate > 5% (متوسط الـ industry)

---

### User Story 3 — عمر (الـ GTM Engineer) يبعت email campaign للـ 13K subscriber (Priority: P1)

عمر بيـ trigger email campaign للقائمة الموجودة من orders قديمة. الـ emails بتـ deliver لـ inbox (مش spam)، فيها tracking للـ open + click. الـ ROI واضح: X% فتحوا، Y% دوسوا الرابط، Z% اشتروا.

**Why this priority**: 13K subscriber = أكبر زبائن assets موجود لكن بدون استخدام = إيراد ضايع شهرياً.

**Independent Test**:
- ابعت test campaign لـ 100 subscriber
- > 30% open rate (يدل على deliverability)
- > 2% click rate
- 0 spam complaints

**Acceptance Scenarios**:
- ✅ FluentCRM أو Mautic مركّب + متصل بـ SMTP صحي
- ✅ List import من WC + MailPoet القديم (لو فيه)
- ✅ Segmentation متاحة (مشتري Java vs مشتري Data Structure)
- ✅ Unsubscribe link شغّال (GDPR/CAN-SPAM compliance)

---

### User Story 4 — أحمد بيدخل WP admin بأمان من جهاز جديد (Priority: P2)

أحمد بيدخل من جهاز جديد، يفتح `/wp-admin`. الـ login form ما يفتحش بدون 2FA code. لو حد جرب brute-force passwords، الـ IP بتاعه يتـ block بعد 5 محاولات. الـ SMTP credentials اللي بيـ send الإيميلات مش readable من DB dump.

**Why this priority**: حاجة محتملة الحدوث بس مدمّرة لو حصلت. الـ effort صغير، الـ insurance عالية.

**Independent Test**:
- جرب login بـ password صح بدون 2FA — يـ block
- جرب 6 password attempts غلط — IP يـ block
- export الـ DB، grep على wp_mail_smtp.pass — تكون encrypted أو constant

**Acceptance Scenarios**:
- ✅ Limit Login Attempts Reloaded plugin مفعّل + threshold = 5
- ✅ Two-Factor plugin مفعّل على admin accounts
- ✅ xmlrpc.php returns 403 على POST
- ✅ SMTP password كـ `define('WPMS_SMTP_PASS', ...)` في wp-config (مش في DB)

---

### User Story 5 — عمر يـ deploy تعديل صغير بدون regression hidden (Priority: P2)

عمر بيـ commit تعديل CSS صغير، بيـ push لـ main. قبل ما الـ deploy يحصل، الـ CI بيشغّل UI Audit Playwright run ضد staging. لو فيه >0 failures، الـ deploy يتوقف. لو passing، الـ deploy يكمّل + Telegram alert. الـ regression class بتاعت 2026-05-23 ما تكرّرش أبداً.

**Why this priority**: مش revenue مباشرة، بس بيحمي كل الـ revenue. واحد regression خفي ممكن يكلف يوم/أسبوع shadow loss.

**Independent Test**:
- اعمل PR فيه CSS بيكسر layout عمداً
- الـ CI fail الـ PR قبل merge
- اعمل PR فيه fix — CI pass، deploy يكمّل، Telegram يقول "✅ deployed"

**Acceptance Scenarios**:
- ✅ GitHub Action `.github/workflows/ui-audit.yml` موجود + بيـ trigger على PR
- ✅ Action بيـ run ضد staging URL
- ✅ Failure يـ block الـ merge
- ✅ Pass يـ post comment على الـ PR بالـ report URL

---

## Success Criteria (مقاييس النجاح)

### Quantitative (أرقام قابلة للقياس)

| المقياس | اليوم (Baseline) | الهدف بعد 90 يوم | المصدر |
|---|---|---|---|
| Monthly revenue | ~67K EGP | **~120K EGP** (+80%) | WC reports |
| Order cancel rate | 30.2% | **< 15%** | wp_wc_orders status breakdown |
| Failed CC transactions/شهر | ~30 | **< 5** | WC + gateway logs |
| Email open rate | 0% (مش مرسل) | **> 30%** (typical) | FluentCRM/Mautic |
| Active plugins | 61 | **~43** | wp plugin list |
| wp-content disk | 13 GB | **< 5 GB** | du -sh |
| Autoload size | 987 KB | **< 500 KB** | wp_options query |
| UI Audit failures | varies | **0** على main branch | _tools/ui-audit/ |
| Lighthouse Mobile Performance | TBD | **> 75** | Lighthouse run |
| Average admin page load | 3-5s | **< 1.5s** | DevTools timing |

### Qualitative (مش بأرقام لكن قابلة للملاحظة)

- Ahmed بيفتح الـ admin يحس بسرعة (مش "لما يحمّل")
- زبائن بيشتروا بدون شكاوى دفع
- الإيميلات بتوصل (مش spam) — verified بـ test campaigns
- لما حد يـ commit تعديل CSS، أي layout regression بتظهر في الـ CI fail قبل deploy

---

## Out of Scope (مش هنعمل)

- **Redesign كامل للموقع** — هنشتغل على الـ broken stuff فقط، مش جديد UX
- **Migration لـ WP لـ Next.js أو platform تاني** — overhead عالي، benefit مش clear
- **بناء mobile app** — separate spec لو جه الـ time
- **اللغة الإنجليزية للموقع** — السوق المصري الخليجي هو الأولوية الحالية
- **Refactor كامل للـ theme بـ tailwind/تايب-سكريبت** — التايم لقاء الـ business priorities أكبر بكثير
- **Integration مع courses platforms تانية (Udemy, Skillshare)** — الـ revenue مش هناك حالياً

---

## Constraints (قيود)

- **Hostinger Business hosting** — مش بنـ control الـ server (shared)
- **CageFS restrictions** — `wp db export` بيـ fail silent، `crontab` محظور
- **No staging environment حالياً** — الـ testing بيحصل على production أو محلياً
- **Ahmed = solo developer للـ theme** — تعديلات لازم تتعرض عليه قبل merge
- **Omar = solo GTM engineer** — مفيش team operations
- **Budget محدود** — مفيش $$ لـ enterprise tools (Cloudflare Pro, Datadog، إلخ)

---

## Constitution Alignment

ده مشروع brand-internal under `brands/learn-simply/` (Two-Layer Architecture, Constitution v2.0.0 Principle II). بيستخدم Spec Kit format للتنظيم لكن:

- مش بيمر بـ Spec Kit gates الإجبارية (`speckit-specify` mandatory gate) — لأن الـ spec ده لـ tactical brand work مش agency feature
- الـ "lessons learned" بتتسجل في `brands/learn-simply/lessons.md` (per Principle III, two-tier lesson flow)
- الـ cross-brand promotion (لو حد من الـ tools/audit ها يفيد brand تانية مثل dentera/voya/kitc) = manual decision (per Principle I)

---

## Dependencies

| Dependency | الحالة |
|---|---|
| Backup system (3 layers) | ✅ Done في Phase 0 |
| UI Audit tool | ✅ Done في Phase 0 |
| Sprint 1 PR merged | ✅ Done |
| Checkout polish (PR #5-11) merged | ✅ Done |
| Hostinger SSH access | ✅ Established |
| GitHub repo `Learrnsimply/edublink-child` | ✅ Available |
| Ahmed availability للـ reviews | ⏳ Ongoing |
| Payment gateway alternative (Paymob/Geidea) | ❓ Phase 1 decision |
