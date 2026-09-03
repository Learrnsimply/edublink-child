# Implementation Plan: Theme Defect Remediation (edublink-child)

**Branch**: `003-theme-defect-remediation` (code branch in the `website/` submodule — NOT a workspace branch) | **Date**: 2026-06-14 | **Spec**: [spec.md](spec.md)
**Input**: 11 functional requirements (FR-001..011) from the spec, grounded in the 2026-06-14 verify workflow + a code-level read pass during planning.

## Summary

نطبّق **11 إصلاح متحقَّق منه** على ثيم `edublink-child` (Timber/Twig + WooCommerce + Tutor LMS): 8 آمنة ميكانيكية + 3 محسومة في الـ clarify (واتساب الدعم · شيل "3 أيام" · توقيت العداد). التنفيذ على **branch داخل الـ submodule**، منظّم **بالملف** (عشان صفر تعارض)، متحقَّق بـ **server `php -l`** (مفيش php محلي) + **Playwright UI audit** للـ rendering، وبعدها diff لـ Omar يـ merge. أعلى خطر = `functions.php` (WSOD) + عداد العرض (ميزة حيّة).

## Technical Context

**Language/Version**: PHP 7.4/8.x (WordPress 6.9), Twig 3 (Timber 2), vanilla JS (ES5-safe), CSS
**Primary Dependencies**: WordPress · Timber/Twig · WooCommerce · Tutor LMS · edublink parent theme (ThemeGrill)
**Storage**: N/A — لا تخزين جديد؛ نقرأ options/meta موجودة فقط (`learnsimply_promo_deadline`, إلخ)
**Testing**: **php محلي عبر Docker** (`01_WEB/_tools/php-lint.sh`, image `php:8.2-cli-alpine` — مفيش تنصيب نظام) للـ syntax لكل ملف PHP اتغيّر · Playwright `_tools/ui-audit/` للـ rendering regression · فحص بصري يدوي 375/768/1440 · `node -c` للـ JS. (ملاحظة infra: SSH alias `learnsimply` **مش بيتحلّ من omar-central** → server-lint/deploy من جهاز Omar اللي بيوصل السيرفر.)
**Target Platform**: WordPress على Hostinger (host جديد `46.202.158.231`, path `/home/u791284659/domains/learrnsimply.com/public_html`)
**Project Type**: WordPress child theme (submodule `Learrnsimply/edublink-child`, HEAD `ffdff55`)
**Performance Goals**: -1 HTTP request (خط مكرر) · -162 سطر تكرار في `functions.php` · صفر regression
**Constraints**: صفر WSOD على `functions.php` · صفر rendering regression · **مسار شراء الباقة في الهومبيج مجمّد** (DD-6 مؤجّل) · إطلاق Dart 15 يونيو
**Scale/Scope**: 11 FR عبر ~9 ملفات

## Constitution Check

*GATE: لازم يعدّي قبل Phase 0.*

| المبدأ | الحالة |
|---|---|
| **II — Two-Layer** (شغل البراند جوه `brands/learn-simply/`) | ✅ كل التعديلات جوه `01_WEB/website/` (submodule) + الـ spec جوه `01_WEB/specs/` |
| **V — Spec-Driven** (gates) | ✅ ماشيين specify→clarify→**plan**→tasks→implement؛ الـ plan هيعدّي plannotator gate |
| **ملكية الريبو** (Ahmed owner، PR review) | ✅ Omar عنده merge authority الآن؛ التنفيذ على branch + diff review قبل merge |
| **قواعد الفولدر** (01_WEB/CLAUDE.md): مفيش push على main بلا review · مفيش rm على السيرفر بلا إذن · verify EFFECTIVE state · `npm run audit` بعد أي visual | ✅ مُدمجة في خطة التحقق تحت |
| **Two-Tier Lessons** | ✅ أي درس → `01_WEB/lessons.md` |

**النتيجة: PASS** — مفيش مخالفة دستورية. (الدستور أساساً عن بنية الـ workspace؛ الفيتشر ده تعديل كود براند ملتزم بقواعد الفولدر.)

## Project Structure

### Documentation (this feature)

```text
01_WEB/specs/003-theme-defect-remediation/
├── spec.md            # ✅ (specify + clarify)
├── plan.md            # ✅ هذا الملف
├── research.md        # ✅ Phase 0 — القرارات التقنية المؤكَّدة من الكود
├── quickstart.md      # ✅ Phase 1 — runbook التنفيذ + التحقق + rollback
├── checklists/
│   └── requirements.md # ✅ spec quality (PASS)
└── tasks.md           # ⏳ /speckit-tasks (مش هنا)
```

> `data-model.md` و `contracts/` = **N/A** — الفيتشر مفيهوش data model جديد ولا external API. (نقرأ options/meta + WP REST موجودين فقط.)

### Source Code (الملفات اللي هتتغيّر — submodule `01_WEB/website/`)

```text
website/
├── functions.php                               # FR-001,002,003,004,011 (الحارة الحرجة — agent واحد)
├── front-page.php                              # FR-005
├── views/
│   ├── single-course.twig                      # FR-001(remove inline), FR-010
│   ├── about-me.twig                           # FR-007
│   ├── archive-courses.twig                    # FR-006
│   ├── components/footer.twig                  # FR-007, FR-008
│   └── sections/home/
│       ├── featured-courses-section.twig       # FR-006
│       └── about-me-section.twig               # FR-009
└── assets/
    ├── single-course/script.js                 # FR-001(consume), FR-004(consume)
    └── global/script.js                        # FR-011 (read-only confirm — likely no change)
```

## Execution Lanes (file-ownership — صفر تعارض)

ترتيب التنفيذ بالملف. **`functions.php` = حارة واحدة فقط** (كل FR اللي بتلمسه تتعمل بالتسلسل في agent واحد) عشان مايحصلش تعارض. باقي الملفات مستقلة.

| Lane | الملفات المملوكة | FRs | خطر |
|---|---|---|---|
| **L0 — functions.php** (حسّاس، الأول، لوحده) | `functions.php` | FR-002 (حذف خط مكرر) · FR-003 (extract page-type helper) · FR-001 (localize ajaxurl) · FR-004 (localize themeUri) · FR-011 (deadline Cairo server-side) | 🔴 WSOD + ميزة حيّة |
| **L1 — single-course** | `views/single-course.twig` · `assets/single-course/script.js` | FR-001 (شيل inline ajaxurl) · FR-010 (شيل "3 أيام") · FR-004 (consume themeUri) | 🟡 |
| **L2 — home sections** | `views/sections/home/about-me-section.twig` · `front-page.php` | FR-009 (زر واتساب) · FR-005 (wp_reset_postdata) | 🟢 |
| **L3 — copy/spelling** | `views/about-me.twig` · `views/components/footer.twig` · `views/archive-courses.twig` · `views/sections/home/featured-courses-section.twig` | FR-006 (غير محدد) · FR-007 (نبذة) · FR-008 (الرئيسية) | 🟢 |

> **ملاحظة تبعية:** FR-001 و FR-004 بيتقسموا بين L0 (الـ localize في functions.php) و L1 (شيل/استهلاك في الـ twig/js). عشان كده **L0 يتعمل الأول**، وبعدها L1 يعتمد إن الـ handles بقت متاحة. L2/L3 مستقلين تماماً → ممكن يجروا بالتوازي مع L1 بعد L0.

## Per-FR Implementation Approach

> التفاصيل الدقيقة (سطور، old→new) في [research.md](research.md). هنا الـ approach + المعيار.

- **FR-002** — حذف دالة `edublink_child_enqueue_fonts()` (سطر 1728) + الـ `add_action` (1737). الإبقاء على `learnsimply_enqueue_ibm_plex_font()` (33). *معيار:* HTML بيـ enqueue الخط مرة واحدة.
- **FR-003** — استخراج `edublink_child_get_page_type()` helper من المنطق المكرر بين `edublink_child_load_page_assets()` (1810) و `edublink_child_add_page_css_late()` (1912)؛ الدالتين يندهوا الـ helper. *معيار:* السلوك زي ما هو، التكرار اتشال، `php -l` نظيف.
- **FR-001** — `wp_localize_script('edublink-global-scripts', 'learnsimplyAjax', ['ajaxurl'=>admin_url('admin-ajax.php')])` في functions.php + شيل `window.ajaxurl` inline من single-course.twig + الـ JS يقرا `learnsimplyAjax.ajaxurl` مع fallback. *معيار:* الميزة تشتغل مع defer.
- **FR-004** — `wp_localize_script('edublink-single-course-script', 'learnsimplyThemeUri', get_stylesheet_directory_uri())` بعد enqueue نود single-course (1893). *معيار:* الصور تحمّل صح من غير fallback غير معرّف.
- **FR-011** — 🔴 **(راجع research.md — التشخيص اتصحّح):** الـ frontend countdown آمن TZ (بيقارن epochs مطلقة). الغموض في **الـ admin-side** (سطور 1016-1022): الـ epoch بيتحسب بتوقيت متصفّح أحمد. الإصلاح الآمن: نقل حساب الـ deadline من JS المتصفّح لـ **PHP server-side** بـ `new DateTime("$y-$m-$d $h:$min", new DateTimeZone('Africa/Cairo'))` (DST-correct — يونيو = UTC+3 في مصر؛ "+2 ثابت" غلط). *معيار:* SC-009 — نفس الوقت من جهازين TZ مختلفة + الـ deadline المخزّن صح بعد ما أحمد يحفظه من أي TZ.
- **FR-005** — إضافة `wp_reset_postdata()` بعد الـ loop الداخلي للدروس في front-page.php (~138). *معيار:* بيانات الصفحة بعد الـ loop سليمة.
- **FR-006** — `default('40 ساعة')` → `default('غير محدد')` في archive-courses.twig + featured-courses-section.twig. *معيار:* "غير محدد" تظهر.
- **FR-007/008** — تصحيح إملائي "نبذه"→"نبذة" (about-me.twig + footer.twig) · "الرئيسيه"→"الرئيسية" (footer.twig). *معيار:* النص الصح ظاهر.
- **FR-009** — زر "تواصل معي" في about-me-section.twig: `href` → `https://wa.me/201030127228`. *معيار:* الزر يفتح واتساب الدعم.
- **FR-010** — single-course.twig:23 شيل "لمدة 3 أيام" → "لفترة محدودة فقط". *معيار:* مفيش "3 أيام".

## Validation Strategy (التحقق — خط الدفاع، لإن مفيش php محلي)

1. **Syntax (إجباري لكل ملف PHP اتغيّر) — محلي:** `01_WEB/_tools/php-lint.sh <file>.php` (Docker `php:8.2-cli-alpine`، مفيش تنصيب نظام) → لازم "No syntax errors". ممنوع merge بلا ده. *(قرار من plannotator review 2026-06-14: ننزل php محلي — أسرع وأأمن، خصوصاً إن السيرفر مش متوصل من omar-central. server `php -l` تأكيد نهائي اختياري من جهاز Omar — الـ syntax version-agnostic.)*
2. **JS:** `node -c` على أي JS اتغيّر.
3. **Rendering regression:** `cd 01_WEB/_tools/ui-audit && npm run audit` (Playwright) على الصفحات المتأثرة (home · single-course · archive · footer · about) — قبل/بعد، مقارنة screenshots.
4. **Visual يدوي:** 375 / 768 / 1440 على الصفحات المتأثرة بعد deploy + cache purge.
5. **FR-011 خاص:** اختبار من جهازين بـ timezones مختلفة + تأكيد الـ epoch المخزّن صح.
6. **Scope guard:** `git diff` يتراجع — صفر تغيير في كود out-of-scope/already-fixed.

## Branch + Deploy + Rollback

- **Branch:** جوه الـ submodule: `git -C 01_WEB/website checkout -b 003-theme-defect-remediation` (من `ffdff55`). كل لين = commit منفصل برسالة واضحة.
- **Deploy:** بعد merge (Omar)، scp الملفات المتغيّرة للسيرفر (مفيش CI) + wp-optimize cache purge.
- **Rollback:** الـ branch معزول؛ أي مشكلة = `git revert` للـ commit + إعادة scp النسخة القديمة. `functions.php` نحتفظ بنسخة `.bak` على السيرفر قبل أي scp.

## Risk Register

| خطر | احتمال | أثر | تخفيف |
|---|---|---|---|
| WSOD من syntax error في functions.php | متوسط | 🔴 الموقع كله | **محلي `php-lint.sh` إجباري بعد كل تعديل** + `.bak` على السيرفر + commit منفصل لكل تعديل |
| كسر العداد الحي (FR-011) | متوسط | 🟠 ميزة promo | تشخيص مصحَّح (server-side Cairo) + اختبار TZ + لو شك → نأجّله لـ Audit v2 |
| FR-003 dedup يغيّر سلوك تحميل الأصول | منخفض | 🟠 أصول صفحة | helper يحافظ على نفس المنطق حرفياً + UI audit |
| regression بصري صامت | منخفض | 🟡 | Playwright before/after + visual 3 مقاسات |
| لمس كود out-of-scope بالغلط | منخفض | 🟡 | scope guard على الـ diff |

## Phase 2 (القادم — /speckit-tasks)

tasks.md هيفكّك الـ 11 FR لمهام قابلة للتنفيذ مرتّبة بالـ lanes (L0 الأول)، كل مهمة بـ acceptance + ملف التحقق. **مش بيتعمل هنا.**
