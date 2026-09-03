# Research & Decisions: Premium Course Experience

> Phase 0. معظم الـ "research" هنا = **audit حي للنظام القائم** (2026-06-25)، مش بحث نظري — عشان الـ plan grounded في الواقع.

## D1 — تفاعل المنهج / المعاينة (FR-004)

**Decision**: نمرّر `permalink` + `_is_preview` (+ `_video_duration` موجود) لكل `content_item` في `tutor/single-course.php`؛ في `single-course.twig` نرندر الدرس القابل للمعاينة كـ **عنصر clickable** يفتح **مشغّل معاينة** (modal أو inline)، وغير القابل للمعاينة يفضل **مقفول** (أيقونة قفل، نص).

**Rationale**: الـ audit أثبت إن المنهج **داتا ديناميكية من Tutor بالفعل** (`get_topics` + `get_course_contents_by_topic`) لكن `content_item` بيتبني بـ `{id, type, title, duration}` فقط — **مفيش URL ولا preview flag** — والـ twig بيرندر `<p class="lecture-title">` (نص، مش `<a>`). الـ preview feature مستخدمة (**19 درس `_is_preview=1`**). فالفجوة في **التمرير + العرض**، مش في وجود الداتا → أقل تغيير + أعلى أثر.

**Alternatives considered**: (B) ربط/توسيع بصري بس — مرفوض، مبيحققش طلب أحمد «تدخّل الطالب». (C) deep-link للمسجّلين فقط — مرفوض كحل أساسي (الهدف تحويل غير-المشترين)، لكن يفضل سلوك «ابدأ التعلم» الموجود للمسجّلين.

**Open for plan/Ahmed**: لكل كورس مستهدف، تأكيد وجود ≥1 درس preview (وإلا أحمد يعلّمهم في Tutor).

## D2 — الاتجاه البصري الـ premium (FR-001/005/006/007)

**Decision**: ترقية بصرية مقصودة لـ `single-course.twig` + `style.css`: مقدمة/هيرو فوق الطية (قيمة + مدرّس + حقائق)، إثبات اجتماعي ككروت (تقييمات/مراجعات)، **زر شراء مثبّت/مرساة على الموبايل**، وهرمية واضحة — **branded لـ Learn Simply** (tokens الموقع: navy `#0a0f1a`، أزرق `#4077f3`، IBM Plex Sans Arabic).

**Rationale**: audit المنافس (usamaelgendy) أظهر إن «الفخامة» عنده = sticky header + countdown + كروت reviews + تسعير ملوّن (فردي/باقة) + هرمية نظيفة — **مش منهج تفاعلي** (منهجه نص). صفحتنا أصلاً فيها نفس الأقسام، فالترقية بصرية + المعاينة التفاعلية تتجاوزه. **مش تقليد** (قرار أحمد الصريح) → نسحب نقاط القوة بس بهوية البراند.

**Alternatives considered**: نسخ تخطيط المنافس — مرفوض (تقليد + off-brand).

## D3 — منتج الباقة (FR-011/012)

**Decision**: منتج **`easy_product_bundle` جديد** لـ جافا + هياكل البيانات، بسعر/خصم مجمّع أقل من المنفصل؛ يتعمل عبر wp-cli أو أحمد (wp-admin).

**Rationale**: audit أثبت إن البنية شغّالة — بلجن `easy-product-bundles-for-woocommerce` **مفعّل**، و product **33336** («Java Basics + OOP»، 849 ج) **باقة حية** = نموذج مجرّب. باقة أحمد المطلوبة (جافا+**DS**) مختلفة عن 33336 (جافا+OOP) → منتج جديد.

**Alternatives considered**: restyle بصري بلا منتج — مرفوض (مبيحققش «شراء واحد + قيمة مجمّعة حقيقية»).

**Open for plan/Ahmed**: الـ product IDs لكورسي جافا + DS · السعر المجمّع (قرار تسعير لأحمد).

## D4 — كروت الهومبيج (FR-008/009/010)

**Decision**: تطوير `featured-courses-section.twig` (الموجود — فيه باقة جافا 33336 مثبّتة من PR #16) لكروت premium متّسقة، وضمان ظهور Dart بالسعر/الخصم **الحي** من WooCommerce، كل كارت يربط لصفحة الكورس.

**Rationale**: السكشن موجود، فالعمل = ترقية بصرية + إضافة Dart + ربط السعر الحي (بلا خصم وهمي — FR-010).

## D5 — مشغّل المعاينة (تقني، P1)

**Decision**: مشغّل خفيف — modal يفتح فيديو المعاينة (Tutor lesson preview / HTML5)، **lazy-loaded** (مايحمّلش غير عند الضغط)، RTL، يقفل بـ Esc/خلفية.

**Rationale**: يحافظ على الأداء (صفر تأثير على التحميل الأساسي) + بسيط. نتجنّب iframe تقيل أو مكتبة كبيرة.

**Alternatives considered**: صفحة معاينة منفصلة — مرفوض (احتكاك + يخرج الزائر من صفحة البيع).

## D6 — الاختبار والنشر

**Decision**: `php-lint.sh` بعد كل تعديل PHP · `npm run audit` (Playwright) before/after @ 375/768/1440 على single-course + home (صفر regression، صفر overflow) · تحقق حي بـ curl/browser (المعاينة بتشتغل، السعر حي، عنوان واحد) · deploy scp+md5+wpo purge · **PR لأحمد**.

**Rationale**: نفس نمط البراند المثبّت (woo-guard/wp-tutor runbooks) — verify-before-claim بـ Playwright مش افتراض.
