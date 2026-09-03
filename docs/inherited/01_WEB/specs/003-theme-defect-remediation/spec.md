# Feature Specification: Theme Defect Remediation (edublink-child)

**Feature Branch**: `003-theme-defect-remediation` *(code branch created in the `website/` submodule at implement-time, NOT a workspace branch)*
**Created**: 2026-06-14
**Status**: Draft → ready for `/speckit-clarify`
**Brand**: learn-simply (`brands/learn-simply/01_WEB/`)
**Input**: 2026-06-14 read-only re-verification of the 2026-05-23 audit (134 bugs / 8 reports) against the CURRENT theme code at submodule HEAD `ffdff55`. Evidence: verify workflow output `wvmntfcla.output` (41 reported defects checked, one agent per file).

> ⚠️ **لماذا spec جديدة وليست استكمال 001؟** الـ 001 (bug-remediation-90day) اتجمّد بعد نقل الاستضافة (2026-06-04) والورق بقى stale. الـ verification الجديد (2026-06-14) أثبت إن **معظم البنود الحرجة اتصلّحت فعلاً** في الكود الحالي. الـ spec ده بيقفل **الباقي المتحقَّق منه فقط** في كود الثيم — مش re-audit شامل (ده شغل 002).

---

## السياق (Context)

موقع `learrnsimply.com` (WordPress + Timber/Twig child theme `edublink-child`, owner = Ahmed, Omar = collaborator بصلاحية merge). الـ audit الأصلي (2026-05-23) رصد 134 bug. بعد نقل الاستضافة (2026-06-04) + Sprint 1 PR + PRs الـ checkout، أعدنا التحقق من كل bug ضد الكود الحقيقي يوم 2026-06-14:

**نتيجة التحقق (41 بند تمّ فحصه):**
- ✅ **13 already-fixed** — كل الـ Critical فيهم: ثغرتي XSS (دلوقتي `|safe_embed` / `|safe_html`)، كود cart.php الميت (اتشال commit `1072a10`)، ملفات الدِباج (مفيش require)، تسريب `/wp/v2/users`، guard زرار الشراء، إملاء "جنيه"، typo الـ 404.
- 🔍 **7 not-found / stale** — الكود المذكور مش موجود (تقرير قديم).
- 🔧 **19 still-broken** → منهم **8 آمنة ميكانيكية** (نطاق الـ spec ده) + **~9 محتاجة قرار/عناية** (يتحسموا في `/speckit-clarify`).
- 🚩 **2 محتاجين فحص حي** (مش كود ثيم): `/blog` 404 + تكرار `<title>` → **Audit v2 (002)**.

**القيمة:** زوّار أحمد (طلاب) يشوفوا موقع نضيف، نصّ عربي صح، مايكرو-فيتشرز شغّالة، وأداء أحسن — مع كود ثيم متماسك بلا تكرار/كود ميت. كله من غير أي regression قبل إطلاق Dart (15 يونيو).

---

## Clarifications

### Session 2026-06-14

- Q: البنود المحتاجة بيانات أحمد أو فيها خطر (DD-1 مسمى المدرّب · DD-4 أرقام الهيرو · DD-6 الباقة الديناميكية · DD-7 base.twig · DD-8 refactor functions.php) — تتعمل في 003؟ → A: **لأ — تتأجّل كلها برّا 003** (DD-1/DD-4 → أسئلة بيانات لأحمد · DD-6 → ما بعد إطلاق Dart / Audit v2 · DD-7 → لاحقاً · DD-8 → 002 §D Theme Refactor).
- Q: زر "تواصل معي" يوديّ فين؟ → A: **واتساب الدعم** (`wa.me/201030127228` — خط الدعم اللي مساعد "عمر" بيرد عليه؛ مش رقم أحمد الشخصي).
- Q: نص "لمدة 3 أيام" الثابت في بانر خصم الكورس؟ → A: **يتشال النص الثابت** → "لفترة محدودة فقط" (مفيش رقم أيام ثابت مضلّل).
- Q: تصحيح توقيت عداد العرض (Cairo/DST)؟ → A: **يتصلّح دلوقتي بحذر** (يتثبّت على توقيت القاهرة UTC+2 + اختبار) — **داخل نطاق 003**.

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 — نص عربي صحيح ومايكرو-فيتشرز شغّالة (Priority: P1)

زائر يفتح صفحات الموقع فيلاقي الإملاء العربي صح، ولا يشوف قيم وهمية، والخصائص المعتمدة على JS (اللي محتاجة `ajaxurl` و `themeUri`) بتشتغل حتى لو السكربت اتأخّر تحميله.

**Why this priority**: نص غلط ("نبذه"، "الرئيسيه") أو زر/صورة ما تشتغلش = ضرر مباشر على المصداقية والتجربة — وده وش الموقع لكل زائر.

**Independent Test**: افتح `/`، صفحة كورس، الفوتر، وأرشيف الكورسات → الإملاء صح، الـ duration fallback = "غير محدد" مش "40 ساعة"، وميزات الـ JS بتنفّذ من غير اعتماد على متغيّر غير معرّف.

**Acceptance Scenarios**:
1. **Given** زائر على أي صفحة فيها الفوتر, **When** يقرأ روابط الفوتر, **Then** يلاقي "نبذة عني" و"الرئيسية" بالإملاء الصحيح.
2. **Given** زائر على صفحة كورس وسكربت الصفحة اتأخّر (defer/async), **When** يتفاعل مع ميزة محتاجة `ajaxurl`, **Then** الميزة تشتغل (لأن القيمة جاية من `wp_localize_script` مش inline).
3. **Given** كورس مالوش `duration` data, **When** يظهر في كارت الكورس, **Then** يكتب "غير محدد" بدل قيمة "40 ساعة" المضلِّلة.

---

### User Story 2 — بيانات صحيحة على الصفحة الرئيسية (Priority: P1)

زائر على الصفحة الرئيسية يشوف بيانات الكورسات/المواضيع صح من غير تلوّث ناتج عن loop داخلي مش بيـ reset الـ post context.

**Why this priority**: نسيان `wp_reset_postdata()` في loop متداخل بيخلّي WordPress يقرأ من post context غلط بعد الـ loop → بيانات/روابط ممكن تطلع خاطئة في باقي الصفحة. عيب صامت بيأثّر على دقة المحتوى.

**Independent Test**: افتح `/`، اتأكد إن قوائم الكورسات/المواضيع المتداخلة بتعرض البيانات الصح وإن أي عنصر بعد الـ loop (روابط/عناوين) سليم.

**Acceptance Scenarios**:
1. **Given** الصفحة الرئيسية فيها loop مواضيع جواه loop دروس, **When** الصفحة تـ render, **Then** كل عنصر بعد الـ loops يقرأ الـ post context الصح (مفيش تسرّب).

---

### User Story 3 — ثيم خفيف بلا تكرار (Priority: P2)

الفريق يحافظ على ثيم متماسك: تحميل الخط مرة واحدة، ومنطق تحديد نوع الصفحة موجود في مكان واحد بدل تكراره.

**Why this priority**: تكرار enqueue للخط = HTTP request + CSS زيادة كل request. تكرار 162 سطر من منطق page-detection = خطر تضارب صيانة. مكسب أداء + قابلية صيانة، لكن مش وش مباشر للزائر = P2.

**Independent Test**: افحص HTML المُولّد → خط IBM Plex Sans Arabic متسجّل **مرة واحدة**. افحص `functions.php` → منطق page-type موجود في دالة واحدة (helper) مش متكرر.

**Acceptance Scenarios**:
1. **Given** أي صفحة, **When** نفحص الـ enqueued styles, **Then** خط IBM Plex Sans Arabic بيتحمّل من مصدر واحد.
2. **Given** `functions.php`, **When** نراجع منطق تحديد نوع الصفحة, **Then** موجود تعريف واحد مُعاد استخدامه (مفيش بلوك مكرر).

---

### User Story 4 — تحسينات محكومة بقرار (Priority: P3 — تتحسم في `/speckit-clarify`)

بنود لسه مكسورة لكن إصلاحها يحتاج قرار بيزنس/بيانات أو يحمل خطر regression. **خارج النطاق الملتزَم به** لحد ما تتوضّح في مرحلة الـ clarify/plan. مدرجة في "Deferred Decisions" تحت.

**Why this priority**: إصلاح متسرّع لأي منها ممكن يكسر ميزة حيّة (عداد العرض، زر شراء الباقة قبل الإطلاق) أو يعرض بيانات غلط (مسمى مدرّب من مصدر خاطئ). القيمة موجودة لكنها مشروطة بقرار واعٍ.

**Independent Test**: لكل بند — بعد ما يتحدد مصدره/قراره في الـ clarify، يبقى قابل للاختبار بمعيار صريح.

---

### Edge Cases

- **`php -l` مش متاح محلياً** → أي تعديل PHP لازم يتحقق منه عبر `php -l` على السيرفر (alias `learnsimply`) قبل الـ merge؛ ممنوع merge لملف PHP مش متأكدين من صحته نحوياً.
- **تعديل `functions.php`** = خطر "white screen" لو فيه syntax error → كل تعديل عليه يتراجَع عدائياً + server lint قبل أي نشر.
- **يوم الإطلاق (15 يونيو)** → أي تعديل يلمس مسار شراء الباقة في الهومبيج ممنوع في نطاق ده الـ spec (راجع Deferred Decisions: الباقة الديناميكية).
- **Timber `instructors[0].title`** غير معرّف → fallback `'مدرب'` لازم يفضل سليم لحد ما المصدر الصح يتحدد (مايتكسرش لـ blank).
- **تعديل نص/إملاء** → بعد النشر، تأكيد بصري + cache purge قبل اعتبار البند مكتمل.

---

## Requirements *(mandatory)*

### Functional Requirements — In-Scope (8 إصلاحات آمنة متحقَّق منها)

- **FR-001**: الثيم MUST يوفّر `ajaxurl` لسكربتات الكورس عبر `wp_localize_script` (في `functions.php`) بدل تعريفه inline في `views/single-course.twig`، بحيث الخاصية تشتغل بغض النظر عن ترتيب/تأجيل تحميل السكربت. *(يلمس: functions.php + single-course.twig + assets/single-course/script.js)*
- **FR-002**: الثيم MUST يسجّل خط IBM Plex Sans Arabic **مرة واحدة فقط**؛ يُحذف الـ enqueue المكرر (`edublink_child_enqueue_fonts()`) ويُبقى المصدر القانوني (`learnsimply_enqueue_ibm_plex_font()`). *(functions.php)*
- **FR-003**: منطق تحديد نوع الصفحة في `functions.php` MUST يكون معرّفاً مرة واحدة (helper مُعاد استخدامه)؛ يُزال البلوك المكرر (~162 سطر) من غير تغيير السلوك.
- **FR-004**: سكربت صفحة الكورس MUST يحصل على `learnsimplyThemeUri` عبر `wp_localize_script` (بدل fallback مكتوب يدوي قد يكون غير معرّف). *(functions.php + assets/single-course/script.js)*
- **FR-005**: الـ loop المتداخل في `front-page.php` MUST يستدعي `wp_reset_postdata()` بعد الـ loop الداخلي للدروس عشان مايحصلش تسرّب post-context.
- **FR-006**: كروت الكورسات MUST تعرض "غير محدد" بدل القيمة الثابتة "40 ساعة" لما الـ duration فاضية. *(views/archive-courses.twig + views/sections/home/featured-courses-section.twig)*
- **FR-007**: نصوص الثيم MUST تستخدم الإملاء الصحيح "نبذة" بدل "نبذه". *(views/about-me.twig + views/components/footer.twig)*
- **FR-008**: رابط الفوتر MUST يستخدم "الرئيسية" بدل "الرئيسيه". *(views/components/footer.twig)*
- **FR-009**: زر "تواصل معي" MUST يوجّه لواتساب الدعم `wa.me/201030127228` (الخط اللي مساعد "عمر" بيرد عليه)، مش `/about_me/` ولا رقم أحمد الشخصي. *(views/sections/home/about-me-section.twig)* — حُسم في Clarifications (DD-3).
- **FR-010**: بانر خصم الكورس MUST يشيل نص المدة الثابت "لمدة 3 أيام" ويستبدله بـ "لفترة محدودة فقط" (مفيش رقم أيام مضلّل). *(views/single-course.twig:23)* — حُسم في Clarifications (DD-2).
- **FR-011**: عداد العرض MUST يحسب الـ deadline بتوقيت القاهرة (UTC+2) مش توقيت متصفّح الزائر، بحيث كل الزوار يشوفوا نفس الوقت المتبقي. *(functions.php + assets/global/script.js)* — حُسم في Clarifications (DD-5). ⚠️ ميزة حيّة — اختبار إجباري + server `php -l` قبل merge.

> كل FR-001..011 testable بمعيار واضح. FR-001..008 موجة آمنة بحتة؛ FR-009..011 حُسموا في الـ clarify (DD-3/DD-2/DD-5). **FR-011 يلمس ميزة حيّة (العداد) → أعلى حذر.**

### Decisions Resolved + Deferred (حُسمت في Clarifications 2026-06-14)

**✅ اتحسمت ودخلت النطاق:**
- **DD-2 → FR-010**: نص "3 أيام" يتشال → "لفترة محدودة فقط".
- **DD-3 → FR-009**: زر "تواصل معي" → واتساب الدعم `wa.me/201030127228`.
- **DD-5 → FR-011**: عداد العرض يتثبّت على توقيت القاهرة (UTC+2) + اختبار.

**⏸️ اتأجّلت برّا 003:**
- **DD-1 — مصدر مسمى المدرّب**: `instructors[0].title` غير معرّف؛ الإصلاح الساذج بـ `get_current_user_id()` خاطئ (المستخدم الحالي ≠ مدرّب الكورس). محتاج مفتاح user-meta للمدرّب الحقيقي → **سؤال بيانات لأحمد**. لحد ما يتحدد، fallback "مدرب" يفضل.
- **DD-4 — أرقام الهيرو** (+1000/98%/+7Y): محتاج مصدر بيانات حقيقي أو قرار → **سؤال لأحمد**.
- **DD-6 — أسعار/ID الباقة الديناميكي** (bundles-section: 33336، 849/2150): القيم **صح دلوقتي** (مش bug حالي)؛ خطر على زر شراء الباقة في الهومبيج قبل الإطلاق → **ما بعد إطلاق Dart / Audit v2**.
- **DD-7 — single-product/single-post → base.twig**: خطر regression rendering → **لاحقاً**.
- **DD-8 — refactor `functions.php`** (106KB → modular includes): شغل كبير منفصل → **002 §D Theme Refactor**.

### Out of Scope (مايتعملش — متحقَّق إنه done/stale)

- الـ **13 already-fixed** (XSS×2، cart dead code، debug files، REST users، buy-button guard، إملاء جنيه، 404 typo، إلخ).
- الـ **7 not-found/stale** (view-all link، author archive، edublink-rtl handle، page-prompt nested HTML، mu-plugin animation widget، …).
- **`/blog` 404 + تكرار `<title>`** → مش كود ثيم (Rank Math/permalink وقت التشغيل) → **Audit v2 (002)**.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: كل الـ 8 إصلاحات (FR-001..008) متطبّقة، و `php -l` **نظيف** على كل ملف PHP اتغيّر (متحقَّق على السيرفر).
- **SC-002**: **صفر regression** بصري على: الهومبيج، صفحة كورس، single-product، أرشيف الكورسات، الفوتر — على المقاسات 375 / 768 / 1440 (Playlist/screenshot).
- **SC-003**: HTML المُولّد بيـ enqueue خط IBM Plex Sans Arabic **مرة واحدة** (مش مرتين).
- **SC-004**: النص العربي المصحّح ("نبذة"، "الرئيسية"، "غير محدد") ظاهر على الصفحات الحيّة بعد cache purge.
- **SC-005**: Omar يراجع الـ diff الكامل ويـ merge بنفسه؛ **صفر تغيير** في كود out-of-scope أو already-fixed.
- **SC-006**: الميزات المعتمدة على JS (`ajaxurl`, `themeUri`) بتشتغل لما السكربت يتأخّر تحميله.
- **SC-007**: زر "تواصل معي" يفتح واتساب الدعم `wa.me/201030127228` (مش `/about_me/`).
- **SC-008**: بانر خصم الكورس مفيهوش "3 أيام" (→ "لفترة محدودة فقط").
- **SC-009**: عداد العرض يعرض نفس الوقت المتبقي بغض النظر عن توقيت جهاز الزائر (متحقَّق من جهازين بـ timezones مختلفة).

---

## Assumptions

- المرجع = submodule HEAD `ffdff55`؛ التحقق اتعمل 2026-06-14 ضد الكود الحالي (مش ضد تقرير 2026-05-23 القديم).
- Ahmed يملك ريبو `edublink-child`؛ Omar عنده صلاحية merge كاملة (أحمد بيشرف على النتيجة النهائية مش الـ process).
- مفيش `php` CLI محلي → التحقق النحوي عبر `php -l` على السيرفر (alias `learnsimply`).
- النشر للـ submodule عبر SFTP + cache purge (مفيش CI deploy).
- إطلاق Dart يوم 15 يونيو → مسار شراء الباقة في الهومبيج **ممنوع المساس بيه** في نطاق الـ spec ده (سبب تأجيل DD-6).
- الشغل كله على **code branch داخل الـ submodule** (`website/`)، مش على branch للـ workspace كله.
- مفيش local php = الاختبار البصري (Playwright عبر `_tools/ui-audit/`) هو خط الدفاع الأساسي ضد regression الـ rendering، جنب server `php -l` للـ syntax.
