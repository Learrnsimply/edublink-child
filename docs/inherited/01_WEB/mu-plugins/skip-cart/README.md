# Skip Cart + Lean Checkout — 2026-06-24

تقليل احتكاك الـ checkout لمتجر منتجاته كلها رقمية (9 منتجات، ~99% بيشتري كورس/باقة واحدة).

## ليه
القمع القديم: `منتج → [إضافة، redirect_after_add=yes] → صفحة السلة → checkout → دفع`.
صفحة السلة خطوة زيادة بلا قيمة (مفيش "عربة تسوّق" حقيقية). + 5 منتجات كانت مش معلّمة `virtual` → WooCommerce كان بيطلب **حقول شحن** على منتج رقمي (احتكاك، وممكن يمنع الـ checkout لو مفيش shipping zone).

## اللي اتعمل (كله reversible)

### 1) شيل صفحة السلة — `learnsimply-skip-cart.php` (mu-plugin)
- `woocommerce_add_to_cart_redirect` → بيرجّع `wc_get_checkout_url()` → أي "إضافة للسلة" بتروح **الـ checkout على طول** (CartFlows-aware).
- بيوقف AJAX add-to-cart + redirect-to-cart (runtime filters، مفيش DB writes).
- **رجوع:** احذف `wp-content/mu-plugins/learnsimply-skip-cart.php`.
- ✅ متأكّد: `?add-to-cart=33336` → `302 → /checkout/`.

### 2) تعليم المنتجات virtual (إصلاح حقول الشحن من الجذر)
عبر wp-cli: `set_virtual(true)` للـ 5 منتجات اللي كانت physical:
`39043` (هياكل البيانات L1+L2) · `33336` (Java Basics+OOP) · `11694` (هياكل L1) · `28056` (كتاب جافا) · `28009` (كتاب C++).
- بعدها: كل الـ 9 منتجات `virtual=Y · needs_shipping=N` → قسم الشحن اختفى من الـ checkout.
- الكتابين فضلوا `downloadable=Y` (التسليم شغّال).
- Tutor LMS بيمنح الوصول عبر hooks مستقلة عن flag الـ virtual → مفيش تأثير على تسليم الكورس.
- **رجوع:** `wp eval` بـ `set_virtual(false)` لنفس الـ IDs.
- ✅ متأكّد: `cart_needs_shipping=N` بعد إضافة منتج.

## حقول الـ checkout النهائية
اسم أول + اسم أخير + موبايل + إيميل (كلهم مطلوبين) + ملاحظات (اختياري). **مفيش عنوان/مدينة/بلد/شركة/شحن.**

## ملاحظة
- ده بيصلّح مرحلة **قبل** الأوردر (تسرّب في السلة/checkout). مكمّل لإصلاح hold-stock + flow الاسترجاع (بعد الأوردر).
- تأثير جانبي إيجابي محتمل: «دورة هياكل البيانات» (أسوأ منتج إكمالاً 44.8%) كانت physical → دلوقتي virtual، يُفترض يتحسّن.
- مفيش قياس funnel دقيق لتسرّب السلة (محتاج GA4) — التغيير best-practice مدعوم بالإعداد (9 منتجات + redirect=yes + shipping على رقمي).
