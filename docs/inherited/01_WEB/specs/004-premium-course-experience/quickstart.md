# Quickstart: التحقق من Premium Course Experience

> كيف نتأكد إن كل user story اشتغلت — verify بدليل حقيقي (Playwright/browser/curl)، مش افتراض.

## مرجع سريع
- كورس Dart: id **39654** · `https://learrnsimply.com/courses/dart/`
- باقة نموذج: product **33336** (`easy_product_bundle`, جافا+OOP, 849)
- preview lessons في النظام: 19 (`_is_preview=1`)
- أدوات: `01_WEB/_tools/php-lint.sh` · `01_WEB/_tools/ui-audit` (`npm run audit`)

## US1 — صفحة الكورس الفخمة (P1)
1. **معاينة شغّالة:** افتح `/courses/dart/` كزائر غير مسجّل → دوس على درس **معاينة مجانية** → **الفيديو يشتغل** (مشغّل). درس غير-preview → **مقفول** (قفل، مفيش تشغيل).
2. **مقدمة/حقائق:** فوق الطية: قيمة + مدرّس + (مدة/دروس/مستوى/لغة).
3. **موبايل:** عند 375px — زر الشراء/السعر في المتناول مع الـ scroll، صفر overflow.
4. **إثبات + FAQ:** تقييمات/مراجعات + FAQ واضحين.
5. **before/after:** `npm run audit` على single-course @ 375/768/1440 — مقارنة بالـ baseline، صفر regression + ترقية بصرية واضحة.
6. **PHP سليم:** `php-lint.sh tutor/single-course.php` نظيف.

## US2 — كروت الهومبيج (P2)
1. افتح الهومبيج → كارت **Dart** ظاهر بالسعر/الخصم **الحي**.
2. دوس الكارت → يهبط على `/courses/dart/` الصح.
3. خصم منتهي/صفر → السعر بس (مفيش خصم وهمي).
4. `npm run audit` على home — صفر overflow @ كل breakpoints.

## US3 — الباقة (P3)
1. الباقة (جافا + هياكل البيانات) ظاهرة بسعر مجمّع **أقل** من مجموع الكورسين.
2. شراء الباقة → الكورسين في **معاملة/سلة واحدة** (سلوك Easy Product Bundles).
3. تأكيد السعر الحي من WooCommerce.

## بوابة القبول النهائية
- ✅ كل php-lint نظيف · ✅ Playwright صفر regression/overflow · ✅ المعاينة بتشتغل حي · ✅ السعر/الخصم حي · ✅ عنوان واحد لكل صفحة (BUG-003 ما يرجعش) · ✅ **PR لأحمد** + مراجعته.
