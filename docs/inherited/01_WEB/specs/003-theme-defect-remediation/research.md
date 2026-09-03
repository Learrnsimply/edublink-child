# Phase 0 Research — Theme Defect Remediation

> القرارات التقنية مؤكَّدة من قراءة الكود الفعلي (HEAD `ffdff55`) أثناء التخطيط — مش من تقرير 2026-05-23 القديم. كل قرار: Decision / Rationale / Alternatives.

## R1 — Enqueue handles (FR-001, FR-004)

**Decision:** نستخدم الـ handles الموجودة فعلاً:
- `edublink-global-scripts` (مسجّل في `edublink_child_enqueue_global_assets`, سطر 1788) = السكربت العام على كل الصفحات → عليه نعمل `wp_localize_script(... 'learnsimplyAjax' ...)` لـ FR-001.
- `edublink-single-course-script` (مُولّد بالنمط `'edublink-' . $page_type . '-script'`, enqueue سطر 1893 جوه `edublink_child_load_page_assets`) → عليه نعمل localize لـ `learnsimplyThemeUri` لـ FR-004، **مباشرة بعد سطر الـ enqueue**.

**Rationale:** `learnsimplyPromoDeadline` متعمله localize على `edublink-global-scripts` بالظبط (سطر 1050) — نفس النمط مثبت وشغّال. الـ themeUri خاص بصفحة الكورس فالأنسب على الـ handle بتاعها.

**Alternatives:** إنشاء handle جديد (رفضناه — زيادة complexity بلا داعي) · `wp_add_inline_script` (أقل وضوحاً من localize للبيانات).

## R2 — Font duplication (FR-002)

**Decision:** حذف `edublink_child_enqueue_fonts()` (تعريف سطر 1728 + `add_action` سطر 1737) بالكامل. الإبقاء على `learnsimply_enqueue_ibm_plex_font()` (سطر 33، hook سطر 32) كمصدر وحيد.

**Rationale:** الدالتين بيـ enqueue نفس الـ handle (`ibm-plex-sans-arabic`) من نفس Google Fonts URL على نفس priority (1) → تحميل مكرر. `@import` في features-section.twig (سطر 5) **مختلف** (Inter + Readex Pro) → مايتلمسش.

**Alternatives:** الإبقاء على edublink وحذف learnsimply (رفضناه — learnsimply هو الـ naming convention بتاع البراند).

## R3 — Page-type dedup (FR-003)

**Decision:** استخراج `edublink_child_get_page_type()` helper يرجّع نوع الصفحة (string)، ونده عليه من `edublink_child_load_page_assets()` (1810) و `edublink_child_add_page_css_late()` (1912) بدل تكرار منطق الـ detection.

**Rationale:** المنطق (is_404/is_front_page/is_product/is_shop/is_cart/tutor checks/slug fallbacks) متكرر حرفياً بين الدالتين (~162 سطر). helper واحد = مصدر حقيقة واحد، أقل خطر تضارب صيانة.

**Alternatives:** ترك التكرار (رفضناه — هو نفسه العيب) · static cache في الـ helper (تحسين اختياري — نسيبه بسيط دلوقتي، YAGNI).

**Risk note:** لازم الـ helper يرجّع **نفس** القيم بالظبط للمنطقين؛ أي اختلاف بسيط في الـ slug fallbacks ممكن يغيّر أي أصول تتحمّل. → نسخ المنطق حرفياً + UI audit بعدها.

## R4 — Promo countdown timezone (FR-011) 🔴 التشخيص اتصحّح

**Decision:** **مالناش دعوة بالـ frontend countdown.** ننقل حساب الـ deadline من **JS المتصفّح في صفحة الأدمن** لـ **PHP server-side** باستخدام `DateTimeZone('Africa/Cairo')`.

**التشخيص المصحَّح (مهم):**
- الـ frontend: `learnsimplyPromoDeadline` = **epoch-ms مطلق** (سطر 1050: `$deadline * 1000`). الـ countdown في `assets/global/script.js` بيطرح `deadline - Date.now()` → **مدة** (duration) → **آمن TZ لكل الزوار** بغض النظر عن بلدهم. التشخيص الأصلي ("عميل في بلد تاني يشوف وقت غلط") **غير دقيق** لو العداد بيعرض مدة.
- الـ bug الحقيقي **admin-side** (سطور 1016-1022): `var ts = Math.floor(new Date(y, m, d, h, min, 0).getTime() / 1000)` — الـ `new Date(...)` بيفسّر القيم بـ **timezone متصفّح الأدمن**. لو أحمد حافظ من متصفّح مش بتوقيت القاهرة → الـ deadline المخزّن غلط.
- **فخ DST:** مصر بترجّع DST من 2023 → **يونيو = UTC+3**، شتاءً UTC+2. أي إصلاح بـ offset ثابت "+2" = **غلط في الصيف**. لازم timezone حقيقي.

**الإصلاح:** الفورم يبعت مكوّنات الـ wall-clock (y/m/d/h/min) زي ما هي؛ PHP يحوّلها في مسار حفظ `learnsimply_promo_deadline` (sanitize callback في `register_setting`, سطر 854، أو `pre_update_option`):
```php
$dt = DateTime::createFromFormat('Y-n-j G:i', "$y-$m-$j $h:$min", new DateTimeZone('Africa/Cairo'));
$epoch = $dt ? $dt->getTimestamp() : /* fallback */;
```
شيل بلوك الـ JS (1011-1025) اللي بيحسب الـ ts، وخلي الفورم يبعت الحقول الخام.

**Rationale:** PHP `DateTimeZone('Africa/Cairo')` بيتعامل مع DST تلقائياً → صح في الصيف والشتا، ومستقل عن متصفّح الأدمن.

**Alternatives:** offset ثابت +2 (مرفوض — DST) · +3 ثابت (مرفوض — غلط شتاءً) · حساب TZ في JS بـ Intl (أعقد + لسه client-side).

**⚠️ شرط تنفيذ:** قبل أي تعديل، **اقرا `assets/global/script.js` countdown** وأكّد إنه duration-based فعلاً. لو طلع بيعرض وقت/تاريخ مطلق، التشخيص يتوسّع. لو فيه أي شك → **نأجّل FR-011 لـ Audit v2** (الـ spec بيسمح، ميزة حيّة).

## R5 — wp_reset_postdata (FR-005)

**Decision:** إضافة `wp_reset_postdata()` بعد الـ loop الداخلي للدروس (`tutor_utils()->get_course_contents_by_topic()`) في front-page.php قبل ما الـ loop الخارجي يكمّل.

**Rationale:** أي `WP_Query`/loop داخلي بيعدّل `$GLOBALS['post']`؛ من غير reset، الكود بعد الـ loop بيقرأ من post context غلط.

**Alternatives:** لا شيء — ده الـ idiom القياسي في WP.

## R6 — Copy / link fixes (FR-006, 007, 008, 009, 010)

**Decision:** تعديلات نصّية مباشرة:
- FR-006: `|default('40 ساعة')` → `|default('غير محدد')` (archive-courses.twig + featured-courses-section.twig).
- FR-007: "نبذه" → "نبذة" (about-me.twig:3 block title + footer.twig link).
- FR-008: "الرئيسيه" → "الرئيسية" (footer.twig).
- FR-009: زر "تواصل معي" `href` → `https://wa.me/201030127228` (about-me-section.twig). **ملاحظة:** ده **خط الدعم** (مساعد "عمر" بيرد) مش رقم أحمد الشخصي `201102681074` (اللي الـ KB بتقول مش للعملاء).
- FR-010: "خصم X% لمدة 3 أيام فقط" → "خصم X% لفترة محدودة فقط" (single-course.twig:23).

**Rationale:** تغييرات نص بحتة، صفر منطق، صفر خطر. تتأكد بصرياً بعد النشر.

**Alternatives:** لا شيء يستحق.

## ملخص القرارات

| FR | الملف(ات) | الـ idiom | خطر |
|---|---|---|---|
| 001 | functions.php + single-course.twig + script.js | wp_localize_script على `edublink-global-scripts` | 🟡 |
| 002 | functions.php | حذف دالة مكررة (1728/1737) | 🟢 |
| 003 | functions.php | extract `edublink_child_get_page_type()` | 🟠 |
| 004 | functions.php + script.js | localize على `edublink-single-course-script` | 🟢 |
| 005 | front-page.php | `wp_reset_postdata()` | 🟢 |
| 006 | archive-courses.twig + featured | default 'غير محدد' | 🟢 |
| 007/008 | about-me.twig + footer.twig | تصحيح إملائي | 🟢 |
| 009 | about-me-section.twig | href → wa.me/201030127228 | 🟢 |
| 010 | single-course.twig:23 | شيل "3 أيام" | 🟢 |
| 011 | functions.php (admin save) | DateTimeZone('Africa/Cairo') — **بعد تأكيد الـ frontend** | 🔴 |
