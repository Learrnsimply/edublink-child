# Tasks: Premium Course Experience (004)

**Input**: design docs من `01_WEB/specs/004-premium-course-experience/`
**Prerequisites**: [plan.md](plan.md) ✅ · [spec.md](spec.md) ✅ · [research.md](research.md) ✅ · [data-model.md](data-model.md) ✅ · [contracts/](contracts/) ✅
**Tests**: مفيش unit framework (ثيم WP). "الاختبار" = `php-lint.sh` + Playwright `ui-audit` + verify حي (راجع [quickstart.md](quickstart.md)). مفيش test tasks منفصلة (مش مطلوبة).

> كل المسارات نسبية لـ `brands/learn-simply/` · الكود في submodule `01_WEB/website/` · أي دمج **عبر PR لأحمد** · RTL/موبايل · deploy = scp+md5+wpo.

> ⚠️ **Reconciliation (2026-06-25 implement session):** الـ audit بتاع الـ spec كان على نسخة قديمة من `single-course.php`/`.twig`. **معظم US1+US2 متعمل خلاص** في commits سابقة (`a6f22cf`, `fcf2678`, PRs #14–#17): PHP wiring (permalink+is_preview)، رندر preview clickable/locked، hero/facts، reviews، FAQ، كروت الهومبيج بسعر حي + Dart. **اللي اتعمل في السيشن ده** (قرار Omar): زر شراء sticky موبايل (FR-006) + صقل بصري إضافي (FR-007) + إزالة `<title>` المتحطوط بالإيد (no-regress لـ BUG-003). **modal المعاينة (T007) متأجّل بقرار Omar** (المعاينة بتفتح تاب جديد وشغّالة). الباقي gated على أحمد أو deploy.

## User Stories (من spec.md)
- **US1** (P1) — صفحة كورس فخمة بتبيع: FR-001..007 (معاينة شغّالة + مقدمة + sticky buy + إثبات) — **MVP**
- **US2** (P2) — كروت الهومبيج: FR-008/009/010
- **US3** (P3) — باقة جافا+DS: FR-011/012

> **مبدأ التنظيم:** MVP-first. US1 = الـ MVP (أعلى أثر على البيع). الـ PHP wiring (Phase 2) **blocking** لـ US1. US2 + US3 مستقلين بعد US1.

---

## Phase 1: Setup

- [X] T001 إنشاء فرع في الـ submodule: `git -C 01_WEB/website checkout -b 004-premium-course-experience` (من main)
- [X] T002 [P] baseline lint: `php-lint.sh tutor/single-course.php` → **No syntax errors** ✅
- [X] T003 [P] baseline screenshots: `npm run audit` → baseline لـ home + single-course + courses-archive @ 375/768/1440 محفوظ في `01_WEB/_evidence/004-baseline/` ✅

---

## Phase 2: Foundational (BLOCKING — data wiring لـ US1)

**⚠️ لازم يخلص قبل US1** — رندر المنهج (T006) بيستهلك الحقول دي.

- [X] T004 [US1] FR-004 (backend): **متعمل خلاص** — `single-course.php:171-187` بيمرّر `is_preview` (bool) + `url` (= `get_permalink`، للـ preview بس) + `preview_count`/`first_preview_url` (375-393). (الحقل اسمه `url` مش `permalink` في الكود — والـ twig بيستهلكه صح.)
- [X] T005 **Lint gate L0**: `php-lint.sh` نظيف ✅ (الـ wiring اتعمل في commit سابق — مفيش PHP اتغيّر السيشن ده)

**✅ Checkpoint:** الموقع لسه بيـ render (مفيش WSOD) + الحقول الجديدة متاحة للـ twig.

---

## Phase 3: US1 (P1) — صفحة الكورس الفخمة 🎯 MVP — يعتمد على Phase 2

**Goal**: معاينة مجانية شغّالة + مقدمة فخمة + sticky buy + إثبات بصري. **Independent test**: [quickstart.md](quickstart.md) §US1.

- [X] T006 [US1] FR-004 (twig): **متعمل خلاص** — `single-course.twig:404-447`: preview = `<a href="{{ content.url }}" target="_blank">` بـ badge «معاينة مجانية»؛ غيره مقفول (قفل، بلا لينك مكشوف). ✅ القاعدة الأمنية محفوظة.
- [X] T007 [US1] FR-004 — **اتعمل بشكل أفضل من الـ modal** (قرار Omar): **المنهج كله بقى clickable بتوجيه ذكي** — مشترِك→الدرس جوّه Tutor · مش مشترِك+preview→العينة · مش مشترِك+مقفول→`#course-buy`. PHP بيمرّر permalink لكل الدروس (يتعرض للمشترِك/preview بس). **PR #20 merged → main `3fb4794` + منشور حي**.
- [X] T008 [P] [US1] FR-004 (css): ستايل preview/locked **موجود خلاص**؛ ستايل الـ modal اتساب (T007 متأجّل). + اتضاف hover affordance للـ `.lecture-preview-link` السيشن ده.
- [X] T009 [US1] FR-001/006: مقدمة/حقائق (مدة/دروس/مستوى/لغة) **موجودة خلاص** (sidebar details). **زر شراء sticky موبايل اتضاف السيشن ده** — `.course-sticky-buy` (twig:672-684 + CSS append، يظهر ≤1024px للحالة paid-not-enrolled).
- [X] T010 [P] [US1] FR-005/007: premium overrides + reviews/FAQ **موجودين خلاص**؛ اتضاف صقل additive السيشن ده (focus-visible states + preview hover + scroll-margin).
- [ ] T011 [US1] FR-004 (محتوى، **gated Ahmed**): ⚠️ **متأكّد حي:** Dart (39654) عنده **0 درس preview** (ولا python/java/flutter). الكود شغّال بس مفيش داتا تعرضها → **أحمد لازم يعلّم ≥1 درس `_is_preview=1`** (Notion Assets/Tasks).
- [ ] T012 **Verify US1** (post-deploy): `php-lint.sh` ✅ · `npm run audit` single-course @ breakpoints vs baseline (صفر regression/overflow) + verify حي (sticky bar يظهر موبايل بلا overflow) — **بعد deploy**.

---

## Phase 4: US2 (P2) — كروت الهومبيج [مستقل، ∥ مع Phase 5 بعد MVP]

**Goal**: كروت كورسات premium + Dart بالسعر الحي. **Independent test**: [quickstart.md](quickstart.md) §US2.

- [X] T013 [US2] FR-008/010 (php): **متعمل خلاص** — `front-page.php` بيبني `courses` context بسعر حي (`get_raw_course_price`) + `discount_percent` محسوب حي + `is_free`/duration/lessons/instructor.
- [X] T014 [US2] FR-008/009 (twig): **متعمل خلاص** — `featured-courses-section.twig` كروت premium (regular + bundle) بسعر/خصم حي + `stretched-link` لصفحة الكورس.
- [X] T015 [P] [US2] (css): ستايل الكروت premium **موجود خلاص** (`course-card`/`bundle-card-premium`).
- [ ] T016 **Verify US2**: ✅ **متأكّد حي:** كارت Dart ظاهر في الهومبيج (9 كروت + 2 باقة، Dart linked). audit home post-deploy للـ overflow.

---

## Phase 5: US3 (P3) — باقة جافا+DS [مستقل]

**Goal**: منتج باقة حقيقي + شراء واحد. **Independent test**: [quickstart.md](quickstart.md) §US3.

- [ ] T017 [US3] FR-012 (data, **gated Ahmed**): اعمل منتج `easy_product_bundle` لـ جافا + هياكل البيانات. **متوقّف على أحمد:** product IDs لكورسي جافا+DS + قرار السعر المجمّع. (الموجود 33336 = جافا+**OOP** مش DS.)
- [ ] T018 [US3] FR-011 (twig/card): اعرض الباقة في الهومبيج/الصفحات المناسبة بالسعر الحي + لينك
- [ ] T019 **Verify US3**: شراء تجريبي — الكورسين في **سلة واحدة** (سلوك Easy Product Bundles) + السعر الحي → commit `feat(commerce): US3 Java+DS bundle`

---

## Phase 6: Polish & Cross-cutting + النشر

- [ ] T020 audit كامل (post-deploy): `npm run audit` (single-course + home) @ 375/768/1440 vs baseline — **صفر regression/overflow**
- [X] T021 [P] فحص BUG-003: ⚠️ الفرع كان مبنيّ على #17 (قبل fix #18/`97ee0f4`) → كان فيه `<title>` متحطوط بالإيد هيرجّع الـ duplicate. **اتشال السيشن ده** (Rank Math = المصدر الوحيد). حي حالياً = عنوان واحد ✅.
- [X] T022 deploy: ✅ **منشور حي** — scp التلات ملفات (php+twig+css) لـ `…/themes/edublink-child` + **md5 مطابق 100%** (server==repo) + wpo_cache_flush. تأكيد حي على Dart: sticky bar=6، 71 درس clickable→#course-buy، عنوان واحد، checkout→39670.
- [X] T023 ✅ diff اتعرض لـ Omar → **PR #19 لـ Learrnsimply/edublink-child** → merge (squash) → main `75cef22` → bump submodule pointer في ريبو البراند (`aa58ca9`). #18 اتعمله merge قبلها (`42e5a7b`) والتعليق byte-identical فمفيش conflict.

---

## Dependencies

```text
Phase 1 (Setup)
  └─> Phase 2 (T004 wiring — BLOCKING) ──> Phase 3 (US1 / MVP)
                                              ├─> Phase 4 (US2 — مستقل [P] مع Phase 5)
                                              └─> Phase 5 (US3 — مستقل [P] مع Phase 4)
  Phase 3 + 4 + 5 ──> Phase 6 (Polish + deploy + PR)
```

- **US1** يعتمد على T004 (تمرير permalink+is_preview).
- **US2 + US3** مستقلين عن بعض وعن US1 (بعد MVP يقدروا يتعملوا بالتوازي).

## Parallel Opportunities

```text
T002 ∥ T003                 (setup)
بعد T006: T007 ∥ T008 ∥ T010 (js ∥ css ∥ css — ملفات/مناطق مختلفة)
Phase 4 ∥ Phase 5           (US2 ∥ US3 بعد MVP)
```

## Implementation Strategy (MVP-first)

- **MVP = US1** (Phase 1→2→3): المعاينة الشغّالة + الصفحة الفخمة = أكبر أثر على البيع → يتشحن **PR مستقل**.
- US2 (كروت) + US3 (باقة) = increments تالية، كل واحدة PR/verify مستقلة.
- **Gated على أحمد:** T011 (دروس preview لو ناقصة) · T017 (سعر الباقة + product IDs).

## Task Count
- **Total: 23** · Setup 3 · Foundational 2 · US1 7 · US2 4 · US3 3 · Polish 4
- **Parallel: ~6** · **Gated on Ahmed: 2** (T011, T017)
