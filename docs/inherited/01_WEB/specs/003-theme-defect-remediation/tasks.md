# Tasks: Theme Defect Remediation (edublink-child)

**Input**: Design documents from `01_WEB/specs/003-theme-defect-remediation/`
**Prerequisites**: [plan.md](plan.md) ✅ · [spec.md](spec.md) ✅ · [research.md](research.md) ✅ · [quickstart.md](quickstart.md) ✅
**Tests**: لا توجد unit tests (ثيم WordPress، مفيش test framework). الـ "اختبار" = `php-lint.sh` + Playwright UI audit + فحص بصري (راجع quickstart.md).

## Format: `[ID] [P?] [Story] Description`
- **[P]**: ملفات مختلفة، يقدروا يجروا بالتوازي
- **[US#]**: يخدم أي user story
- كل المسارات نسبية لـ `brands/learn-simply/` · الكود في submodule `01_WEB/website/`

## User Stories (من spec.md)
- **US1** (P1) — نص عربي صحيح + مايكرو-فيتشرز شغّالة: FR-001, 004, 006, 007, 008, 009, 010, 011
- **US2** (P1) — بيانات صحيحة على الهومبيج: FR-005
- **US3** (P2) — ثيم خفيف بلا تكرار: FR-002, 003

> **مبدأ التنظيم:** بالـ **execution lanes** (file-ownership) مش بالـ user story وحدها — لأن `functions.php` ملف مشترك حسّاس لازم يتعمل في **حارة واحدة بالتسلسل** (L0، Phase 2) قبل ما الـ twig/js يستهلكوا الـ handles. كل مهمة متعلّمة بالـ [US#] + FR بتاعها.

---

## Phase 1: Setup

- [ ] T001 إنشاء code branch في الـ submodule: `git -C 01_WEB/website checkout -b 003-theme-defect-remediation` (من HEAD `ffdff55`)
- [ ] T002 [P] التأكد من الـ lint المحلي: `01_WEB/_tools/php-lint.sh functions.php` يرجّع "No syntax errors"
- [ ] T003 [P] التقاط baseline screenshots قبل التعديل: `cd 01_WEB/_tools/ui-audit && npm run audit` على (home · single-course · archive · footer · about_me) عند 375/768/1440 → حفظ في `01_WEB/_evidence/003-baseline/`

---

## Phase 2: Foundational — L0 `functions.php` (BLOCKING · ملف واحد · بالتسلسل · أعلى خطر)

**Purpose**: الملف المشترك الحسّاس. **⚠️ لازم يخلص قبل L1** (الـ handles بتتعمل هنا). كل المهام في **نفس الملف** → **مش [P]** (بالتسلسل). lint بعد كل تعديل.

- [ ] T004 [US3] FR-002: حذف الدالة المكررة `edublink_child_enqueue_fonts()` (~أسطر 1728-1737) + الإبقاء على `learnsimply_enqueue_ibm_plex_font()` (~33) في `01_WEB/website/functions.php`
- [ ] T005 [US3] FR-003: استخراج helper `edublink_child_get_page_type()` من المنطق المكرر بين `edublink_child_load_page_assets()` (~1810) و `edublink_child_add_page_css_late()` (~1912)؛ الدالتين يندهوا الـ helper — في `01_WEB/website/functions.php`
- [ ] T006 [US1] FR-001 (server): إضافة `wp_localize_script('edublink-global-scripts', 'learnsimplyAjax', array('ajaxurl' => admin_url('admin-ajax.php')))` في `01_WEB/website/functions.php` (~1050، جنب الـ promo localize)
- [ ] T007 [US1] FR-004 (server): إضافة `wp_localize_script('edublink-single-course-script', 'learnsimplyThemeUri', get_stylesheet_directory_uri())` بعد enqueue نود single-course (~1893) في `01_WEB/website/functions.php`
- [ ] T008 [US1] FR-011 (precondition): قراءة countdown في `01_WEB/website/assets/global/script.js` وتأكيد إنه duration-based (`deadline - Date.now()`). **لو مش duration-based → STOP، أجّل FR-011 لـ Audit v2** وعلّم T009 كـ deferred
- [ ] T009 [US1] FR-011: استبدال حساب الـ deadline في JS صفحة الأدمن (~أسطر 1011-1025) بـ PHP server-side `new DateTime("$y-$n-$j $h:$min", new DateTimeZone('Africa/Cairo'))` في مسار حفظ `learnsimply_promo_deadline` — في `01_WEB/website/functions.php` (يعتمد على T008)
- [ ] T010 **Lint gate L0**: `01_WEB/_tools/php-lint.sh functions.php` لازم نظيف → commit `fix(theme): L0 functions.php FR-002/003/001/004/011`

**✅ Checkpoint L0:** `php-lint.sh functions.php` نظيف + الموقع لسه بيـ render (مفيش WSOD).

---

## Phase 3: US1 — single-course consume + copy (L1) — يعتمد على Phase 2

**Goal**: استهلاك الـ handles من L0 + تصحيح نص الخصم. **Independent test:** صفحة كورس تفتح، ميزة الـ ajax تشتغل مع defer، الصور تحمّل، بانر الخصم من غير "3 أيام".

- [ ] T011 [US1] FR-001 (twig): حذف `window.ajaxurl` inline (~سطر 694) من `01_WEB/website/views/single-course.twig`
- [ ] T012 [P] [US1] FR-001 (js): تعديل `01_WEB/website/assets/single-course/script.js` (~252) يقرا `learnsimplyAjax.ajaxurl` مع fallback
- [ ] T013 [P] [US1] FR-004 (js): تعديل `01_WEB/website/assets/single-course/script.js` (~68-70) يستخدم `window.learnsimplyThemeUri` (المُعرّف الآن من L0)
- [ ] T014 [US1] FR-010: في `01_WEB/website/views/single-course.twig` (~سطر 23) استبدال "لمدة 3 أيام فقط" → "لفترة محدودة فقط"
- [ ] T015 **Verify L1**: `node -c 01_WEB/website/assets/single-course/script.js` → commit `fix(theme): L1 single-course FR-001/004/010`

---

## Phase 4: US1 + US2 — home sections (L2) — ملفات مستقلة [P]

**Goal**: زر تواصل + سلامة بيانات الـ loop. **Independent test:** زر "تواصل معي" يفتح واتساب الدعم؛ بيانات الهومبيج بعد الـ loop سليمة.

- [ ] T016 [P] [US1] FR-009: في `01_WEB/website/views/sections/home/about-me-section.twig` تغيير `href` زر "تواصل معي" → `https://wa.me/201030127228`
- [ ] T017 [P] [US2] FR-005: إضافة `wp_reset_postdata()` بعد الـ loop الداخلي للدروس (~سطر 138) في `01_WEB/website/front-page.php`
- [ ] T018 **Verify L2**: `01_WEB/_tools/php-lint.sh front-page.php` نظيف → commit `fix(theme): L2 home FR-009/005`

---

## Phase 5: US1 — copy/spelling (L3) — ملفات مختلفة [P]

**Goal**: تصحيحات نصّية. **Independent test:** الإملاء الصح + "غير محدد" ظاهرين على الصفحات.

- [ ] T019 [P] [US1] FR-006: في `01_WEB/website/views/archive-courses.twig` (~258) `default('40 ساعة')` → `default('غير محدد')`
- [ ] T020 [P] [US1] FR-006: في `01_WEB/website/views/sections/home/featured-courses-section.twig` (~177) `default('40 ساعة')` → `default('غير محدد')`
- [ ] T021 [P] [US1] FR-007: في `01_WEB/website/views/about-me.twig` (~3) "نبذه عني" → "نبذة عني"
- [ ] T022 [US1] FR-007+008: في `01_WEB/website/views/components/footer.twig` — "نبذه عني"→"نبذة عني" (~79) + "الرئيسيه"→"الرئيسية" (~78)
- [ ] T023 **Verify L3**: مراجعة بصرية للنصوص → commit `fix(theme): L3 copy FR-006/007/008`

---

## Phase 6: Polish & Cross-Cutting

- [ ] T024 Lint كامل: `01_WEB/_tools/php-lint.sh --all` → كله نظيف
- [ ] T025 [P] `node -c` على كل JS اتغيّر (single-course/script.js, global/script.js لو اتلمس)
- [ ] T026 UI audit كامل: `cd 01_WEB/_tools/ui-audit && npm run audit` على (home · single-course · archive · footer · about_me) @ 375/768/1440 — مقارنة بـ baseline T003، **صفر regression**
- [ ] T027 [US1] FR-011 TZ test (لو T009 اتعمل): حفظ deadline من wp-admin → تأكيد `learnsimply_promo_deadline` يطابق توقيت القاهرة + فحص العداد من جهازين timezones مختلفة
- [ ] T028 **Scope guard**: `git -C 01_WEB/website diff ffdff55...003-theme-defect-remediation --stat` — تأكيد **صفر** تغيير في كود out-of-scope/already-fixed
- [ ] T029 عرض الـ diff الكامل لـ Omar → مراجعة + **merge** (Omar) → bump submodule HEAD في workspace repo + push ريبو learn-simply

---

## Dependencies

```text
Phase 1 (Setup)
  └─> Phase 2 (L0 functions.php — BLOCKING) ──┬─> Phase 3 (L1 single-course — needs L0 handles)
                                              ├─> Phase 4 (L2 home — مستقل، [P] مع Phase 3/5)
                                              └─> Phase 5 (L3 copy — مستقل، [P] مع Phase 3/4)
  Phase 3 + 4 + 5  ──> Phase 6 (Polish + Omar merge)
```

- **L0 (Phase 2) blocking:** FR-001/FR-004 بيعملوا localize الـ handles اللي L1 (T011-T013) بيستهلكها.
- **L2 + L3 مستقلين تماماً** عن L1 وعن بعض → بعد L0 يقدروا يجروا بالتوازي مع L1.
- داخل L0: بالتسلسل (نفس الملف، صفر [P]).

## Parallel Opportunities

```text
بعد Phase 2 (L0) يخلص:
  T012 ∥ T013   (js — منفصل عن twig)
  T016 ∥ T017   (about-me-section.twig ∥ front-page.php)
  T019 ∥ T020 ∥ T021   (3 ملفات مختلفة)
  T022 لوحده (footer.twig، فيه تعديلين)
```

## Implementation Strategy (MVP-first)

- **MVP = US1 + US3 الآمنة:** Phase 1 → Phase 2 (L0) → Phase 5 (copy) = أكبر قيمة بأقل خطر (إملاء + خط مكرر + dedup).
- **التدرّج:** كل phase = commit مستقل قابل للـ revert. L0 الأخطر (functions.php) — lint بعد كل مهمة + `.bak` على السيرفر وقت النشر.
- **FR-011 (العداد) قابل للتأجيل:** لو T008 كشف إن الـ frontend مش duration-based → يتأجّل لـ Audit v2 من غير ما يأثّر على باقي المهام.
- **الإطلاق (15 يونيو):** Phase 4 (FR-009) ومسار الباقة الديناميكي **مجمّد** (DD-6 مؤجّل أصلاً) → صفر خطر على مسار الشراء.

## Task Count
- **Total: 29 tasks** · Setup 3 · L0 7 · L1 5 · L2 3 · L3 5 · Polish 6
- **US1: 18** · US2: 1 · US3: 2 (الباقي setup/verify/polish)
- **Parallel: 8 tasks** قابلين للتوازي (موضّحين فوق)
