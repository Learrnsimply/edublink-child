# Implementation Plan: Premium Course Experience

**Branch**: `004-premium-course-experience` (theme repo `Learrnsimply/edublink-child`, on implement) | **Date**: 2026-06-25 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `01_WEB/specs/004-premium-course-experience/spec.md`

## Summary

ترقية تجربة الكورس على learrnsimply.com لتبقى premium وأعلى تحويلاً: **(P1)** صفحة الكورس الواحد — مقدمة/هيرو قوية + منهج فيه دروس **معاينة مجانية تشتغل بضغطة** + إثبات اجتماعي + زر شراء مثبّت؛ **(P2)** كروت كورسات premium في الهومبيج (شامل Dart بالسعر/الخصم الحي)؛ **(P3)** منتج باقة جافا + هياكل البيانات. **مبني على audit حي:** المنهج داتا ديناميكية من Tutor بس معروض كنص — الـ PHP (`tutor/single-course.php`) لازم يمرّر `permalink` + `_is_preview` لكل درس، والـ twig يرندر القابل للمعاينة كـ clickable + مشغّل معاينة. بنية الباقات موجودة (Easy Product Bundles + باقة 33336 نموذج). كله RTL/موبايل وعبر PR.

## Technical Context

**Language/Version**: PHP 8.x (WordPress 7.0) · Twig (Timber) · vanilla JS (ES6) · CSS3
**Primary Dependencies**: Tutor LMS 3.9.11 · WooCommerce · Timber/Twig · Easy Product Bundles for WooCommerce · Rank Math
**Storage**: WordPress DB — topics/lessons عبر Tutor (`tutor_utils()->get_topics()`, `get_course_contents_by_topic()`, meta `_is_preview` + `_video_duration`)؛ المنتجات عبر WooCommerce (نوع `easy_product_bundle`)
**Testing**: `01_WEB/_tools/php-lint.sh` (PHP syntax) · `01_WEB/_tools/ui-audit` Playwright (visual/responsive @ 375/768/1440) · curl + browser verify حي · before/after screenshots في `_evidence/`
**Target Platform**: Web · RTL عربي · mobile-first · Hostinger shared (CageFS)
**Project Type**: WordPress child theme (`edublink-child`) + WooCommerce product data
**Performance Goals**: صفر تدهور في زمن التحميل · مشغّل المعاينة lazy-loaded · صفر layout shift (CLS) · أصول الموبايل بحجم العرض
**Constraints**: كل تعديل ثيم عبر **PR** (أحمد owner) · قيود CageFS (مفيش `wp db export`/SSH crontab) · RTL + موبايل بلا overflow (320–1440px) · بلا إلحاح مفبرك · deploy = scp+md5+wpo purge
**Scale/Scope**: 5 كورسات منشورة · 992 درس · 19 درس preview · موقع واحد

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- ✅ **Principle I + II (Two-Layer / brand work in `brands/`)**: الـ spec والشغل كله داخل `brands/learn-simply/01_WEB/` — صفر تسريب لطبقة الـ agency.
- ✅ **PR workflow (Ahmed owns theme repo)**: كل تعديل ثيم يشحن عبر PR لـ `Learrnsimply/edublink-child` — FR-014.
- ✅ **Principle V (Spec-Driven Execution)**: ماشيين specify → clarify → plan → tasks؛ الـ gate ده offered.
- ✅ **بلا cross-brand coupling جديد**؛ بنعيد استخدام المنصة الحالية (Tutor/Woo/Timber).
- **Result: PASS** — صفر مخالفات، الـ Complexity Tracking مش مطلوب.

## Project Structure

### Documentation (this feature)

```text
01_WEB/specs/004-premium-course-experience/
├── plan.md              # هذا الملف
├── research.md          # Phase 0 — قرارات الـ audit
├── data-model.md        # Phase 1 — الكيانات + عقد PHP→Twig
├── quickstart.md        # Phase 1 — كيف نتحقق
├── contracts/           # Phase 1 — عقد بيانات الـ template
└── tasks.md             # Phase 2 (/speckit-tasks — لاحقاً)
```

### Source Code (theme submodule `01_WEB/website/`)

```text
tutor/single-course.php                         # [P1] backend: تمرير permalink + _is_preview لكل content_item
views/single-course.twig                        # [P1] render المنهج clickable + قفل غير-المعاينة + مقدمة/هيرو + sticky buy
assets/single-course/script.js                  # [P1] مشغّل المعاينة (modal/inline) + lazy
assets/single-course/style.css                  # [P1] ستايل premium للصفحة (RTL/موبايل)
views/sections/home/featured-courses-section.twig  # [P2] كروت كورسات premium + Dart
front-page.php / front-page.twig                # [P2] context كروت الهومبيج (السعر/الخصم الحي)
assets/global/script.js + style.css             # [P2] سلوك/ستايل الكروت
functions.php                                   # [P1/P2] enqueue أي asset جديد (handle واحد، بلا تكرار)
```

> **بيانات (مش كود):** منتج باقة جافا+DS = `easy_product_bundle` يتعمل عبر wp-admin/wp-cli (مش في الريبو). دروس المعاينة لكل كورس = إعداد Tutor (`_is_preview`) — تأكيد/طلب أحمد.

**Structure Decision**: WordPress child theme — الـ backend wiring في `tutor/single-course.php`، العرض في Twig views، السلوك في JS لكل سكشن، الستايل في CSS لكل سكشن. الباقة = بيانات WooCommerce (بلا كود). مفيش build system جديد. كل deploy عبر PR + scp.

## Complexity Tracking

N/A — Constitution Check عدّى بلا مخالفات.
