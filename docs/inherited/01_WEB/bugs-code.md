# تقرير Bug Hunt — ثيم اتعلم ببساطة

**تاريخ الفحص:** 2026-05-23  
**الملف المفحوص:** Child theme لـ EduBlink — WordPress + Timber/Twig + Tutor LMS + WooCommerce  
**المسار:** `c:/Users/PUZZLE/Documents/Claude/brands/learn-simply/website/`

---

## جدول إحصائي

| المستوى | العدد | الرمز |
|---|---|---|
| 🔴 Critical | 4 | خطأ تقني يكسر الصفحة أو ثغرة أمنية مباشرة |
| 🟠 High | 9 | خطأ وظيفي واضح أو سلوك غلط في production |
| 🟡 Medium | 8 | سلوك مش متوقع أو بيانات hardcoded غلط |
| 🟢 Low | 6 | typo أو كود زيادة أو تفصيلة صغيرة |
| **المجموع** | **27** | |

---

## 🔴 Critical (4 bugs)

---

### CRIT-01 — `exit;` و `}` ظاهرين كـ literal text في HTML

**الملف:** `tutor/ecommerce/cart.php` — السطور 254-255  
**الخطورة:** 🔴 Critical  
**التأثير:** السطران يُرسمان في HTML الصفحة كنص خام مرئي للمستخدم والـ search engines. الـ template الكامل مضاعف (سطر 258-448 هو نسخة ثانية كاملة من الـ cart) بـ CSS classes مختلفة — الـ server يرسل كلام الاتنين لكل زائر.

**السبب:** الكود في السطور 254-255 موجود خارج أي `<?php ?>` tag:
```
253: </div><!-- .lc-cart -->
254: 	exit;
255: }
256:
257: <?php
258: use Timber\Timber;
```

`exit;` و `}` دول PHP syntax مكتوبة كـ raw text في مكان HTML output — مش داخل PHP tags. كمان الـ `use` statements في السطور 263-266 بتيجي بعد HTML output اتعمل فعلاً، وده مش هيشتغل صح في بيئات strict.

**الإصلاح:** احذف السطور 257-448 بالكامل (الـ implementation التانية القديمة). الـ implementation الأولى (السطور 1-253) هي اللي شغالة حالياً بالتصميم الصح.

---

### CRIT-02 — XSS في صفحة الكورس عبر `|raw` على embed code

**الملف:** `views/single-course.twig` — السطر 220  
**الخطورة:** 🔴 Critical  
**التأثير:** أي مدير/مدرب بيحط video embed code في Tutor LMS ممكن يحقن arbitrary HTML/JavaScript في صفحة الكورس. لو المنصة بتسمح للمدرسين بإدخال الـ embed code، الـ vector ده مباشر.

**الكود:**
```twig
{{ course_video.embed_code|raw }}
```

**الإصلاح:** استخدم `wp_kses` في الـ PHP context قبل تمرير القيمة لـ Twig، أو على الأقل whitelist الـ tags المسموح بيها (iframe, div, script بـ src محدد). في الـ PHP:
```php
$allowed = ['iframe' => ['src' => true, 'width' => true, 'height' => true, 'allowfullscreen' => true]];
$context['course_video_embed'] = wp_kses($course_video->embed_code, $allowed);
```

---

### CRIT-03 — XSS في وصف الكورس عبر `|raw` على محتوى غير sanitized

**الملف:** `views/single-course.twig` — السطور 293 و 344  
**الخطورة:** 🔴 Critical  
**التأثير:** محتوى الكورس ووصفه بيتعرضوا مباشرة في HTML بدون أي sanitization. لو الـ admin أو مدرس أضاف محتوى فيه XSS (أو لو الـ database اتاختُرق)، بيتنفذ على browser المستخدم.

**الكود:**
```twig
{{ descText|replace({...})|raw }}   {# السطر 293 #}
{{ course_content|raw }}            {# السطر 344 #}
```

**الإصلاح:** استخدم `wp_kses_post` في الـ PHP قبل تمرير المتغيرات، بدل `|raw` في Twig. نفس المشكلة موجودة في:
- `views/single-product.twig` السطر 200: `{{ descText|replace({...})|raw }}`
- `views/single-product-bundle.twig` السطر 228: `{{ descText|replace({...})|raw }}`
- `views/single-product-bundle.twig` السطر 316: `{{ product_content|raw }}`

---

### CRIT-04 — ملف debug مضاف دايماً في production

**الملف:** `functions.php` — السطر 14-15 + ملفات `list-lessons.php` و `create-quizzes-topic1.php`  
**الخطورة:** 🔴 Critical  
**التأثير:** ملفين debug بيتحملوا في كل request على الموقع بغض النظر عن المستخدم. `create-quizzes-topic1.php` بيستجيب لـ GET parameter `?create_quizzes_topic1=1` بإنشاء quizzes، و`?delete_quizzes_topic1=1` بالحذف. `list-lessons.php` بيستجيب لـ `?list_lessons=1`. حتى لو في capability check، وجود debug endpoints في production خطر أمني وذاكرة ضايعة في كل request.

**الكود في functions.php:**
```php
require_once get_stylesheet_directory() . '/list-lessons.php';
require_once get_stylesheet_directory() . '/create-quizzes-topic1.php';
```

**الإصلاح:** احذف أو comment السطرين دول. الملفين نفسهم إزالتهم من الـ theme directory.

---

## 🟠 High (9 bugs)

---

### HIGH-01 — ملف temp قديم في theme root

**الملف:** `archive-courses_temp.php`  
**الخطورة:** 🟠 High  
**التأثير:** ملف debugging قديم موجود في الـ theme. يستخدم `query_posts()` (deprecated منذ WordPress 3.1)، يعرض UI إنجليزي ("View Course", "Duration:", "Students:"). WordPress ممكن يعمله include في contexts معينة. حضوره علامة على unprofessional codebase ويبقى attack surface محتمل.

**الإصلاح:** احذف الملف.

---

### HIGH-02 — Font مضاف مرتين بنفس الـ handle

**الملف:** `functions.php` — السطر 21-28 والسطر 1631-1639  
**الخطورة:** 🟠 High  
**التأثير:** الـ font `IBM Plex Sans Arabic` بيتضاف مرتين بنفس الـ handle `ibm-plex-sans-arabic`. WordPress بيتجاهل التسجيل الثاني بسبب تكرار الـ handle، لكن لو الأولى تعطّلت لأي سبب، الثانية مش هتعمل replace صح. كمان الـ `priority` مختلف (الأولى على 1 والثانية على 1 برضو). في الفعل بيتبعتوا لـ Google Fonts في `features-section.twig` (السطر 5) عبر `@import` جوه CSS — ده third call ثالت لنفس الفونت.

**الإصلاح:** شيل الـ function في السطر 1631 (`edublink_child_enqueue_fonts`) واحتفظ بـ `learnsimply_enqueue_ibm_plex_font` فقط. اشيل الـ `@import` من `features-section.twig`.

---

### HIGH-03 — Buy button بتتعمل render حتى لو `product_id` = null

**الملف:** `views/sections/home/featured-courses-section.twig` — السطر 222  
**الخطورة:** 🟠 High  
**التأثير:** زرار "أضف للسلة" يظهر بـ URL زي `/cart-1/?add-to-cart=` (من غير قيمة) لو الكورس مش مربوط بـ WooCommerce product. الضغط عليه بيبعت request غلط لـ WooCommerce.

**الكود:**
```twig
{# هنا: السطر 220 #}
{% if course.product_id %}
    {# زرار cart صح #}
{% endif %}

{# السطر 222: الزرار ده خارج الـ if — دايماً بيتعرض! #}
<a href="{{ cart_url }}?add-to-cart={{ course.product_id }}" class="buy-button">اشتر الآن</a>
```

**الإصلاح:** حرّك زرار "اشتر الآن" جوه الـ `{% if course.product_id %}`.

---

### HIGH-04 — `courses_archive_url` بيتعمل access غلط في Twig

**الملف:** `views/sections/home/featured-courses-section.twig` — السطر 231  
**الخطورة:** 🟠 High  
**التأثير:** الـ variable `courses_archive_url` موجود في الـ context بشكل مباشر (كـ `$context['courses_archive_url']` في PHP)، لكن الـ Twig بيستدعيه كـ `site.courses_archive_url`. في Timber، `site` هو object الـ site وليس الـ context المباشر. النتيجة: الرابط دايماً null وزرار "عرض كل الدورات" broken.

**الكود:**
```twig
{{ site.courses_archive_url }}
```

**الإصلاح:**
```twig
{{ courses_archive_url }}
```

---

### HIGH-05 — `instructor.title` property غير موجودة في Timber User

**الملف:** `views/single-course.twig` — السطر 311  
**الخطورة:** 🟠 High  
**التأثير:** Timber User object مش عنده `.title` property كـ built-in. القيمة دايماً هتكون falsy، يعني `{% if instructors[0].title %}` دايماً false، والكود بيعرض الـ fallback `'مدرب'` بدل العنوان الفعلي للمدرب.

**الكود:**
```twig
{{ instructors[0].title ?: 'مدرب' }}
```

**الإصلاح:** في `tutor/single-course.php`، لما بيجيب بيانات المدرب، حط الـ title صراحة في الـ array كـ field عشان يتعدى لـ Twig، أو استخدم `instructor_title` اللي موجود في الـ context.

---

### HIGH-06 — URL بـ typo (`learrnsimply.com` بدل `learnsimply.com`) في مكانين

**الملفات:**  
- `views/sections/home/cta-section.twig` — السطران 166-170  
- `views/single-course.twig` — السطر 321  
- `views/components/footer.twig` — السطر 38 (في email العنوان)  

**الخطورة:** 🟠 High  
**التأثير:** روابط مكسورة كاملة — بيوجهوا المستخدم لدومين غلط (`learrnsimply.com` بدوبل `r`). في cta-section ده buy buttons مباشرة، يعني purchases خسرانة. في الـ footer ده email العنوان الظاهر للعملاء.

**الكود في cta-section.twig:**
```html
href="https://learrnsimply.com/checkout/?add-to-cart=..."
```

**الكود في single-course.twig:**
```html
href="https://learrnsimply.com/about_me/"
```

**الإصلاح:** استبدل كل `learrnsimply.com` بـ `learnsimply.com` (بـ `r` واحدة).

---

### HIGH-07 — hardcoded product ID `33336` في bundles section

**الملف:** `views/sections/home/bundles-section.twig` — السطر 129  
**الخطورة:** 🟠 High  
**التأثير:** زرار "اشترك في الباقة" في الهوم بيحمل product_id hardcoded `33336`. لو الـ product ID اتغير في WooCommerce (migration، staging→production، database reset)، الزرار هيوجه لمنتج غلط أو يطلع error.

**الكود:**
```html
href="{{ cart_url|default(site.url ~ '/cart-1/') }}?add-to-cart=33336"
```

**الإصلاح:** اجيب الـ product ID من الـ context ديناميكياً عبر PHP كـ `$context['java_bundle_product_id']`.

---

### HIGH-08 — أسعار hardcoded في bundles section مش من WooCommerce

**الملف:** `views/sections/home/bundles-section.twig` — السطران 123-125  
**الخطورة:** 🟠 High  
**التأثير:** السعر المعروض `849 ج.م` والسعر القديم `2,150 ج.م` hardcoded في الـ Twig. لو الأسعار اتغيرت في WooCommerce، الصفحة الرئيسية هتعرض أسعار غلط — discrepancy بين الـ home وصفحة المنتج الفعلية.

**الإصلاح:** جيب الأسعار في `front-page.php` من WooCommerce عبر `$product->get_price()` وحطها في الـ context.

---

### HIGH-09 — `ajaxurl` hardcoded في source الصفحة (Single Course)

**الملف:** `views/single-course.twig` — السطر 677  
**الخطورة:** 🟠 High  
**التأثير:** `window.ajaxurl` بيتضاف كـ inline script داخل الـ Twig template:
```twig
window.ajaxurl = '{{ site.url }}/wp-admin/admin-ajax.php';
```
هذا يكشف المسار الـ admin للموقع في source كل صفحة كورس. كمان الطريقة الصح هي `wp_localize_script` في PHP وليس inline في HTML. في multisite أو حالات خاصة الـ URL ممكن يكون غلط.

**الإصلاح:** احذف السطر من الـ Twig. في `tutor/single-course.php` استخدم:
```php
wp_localize_script('your-script-handle', 'learnsimplyData', [
    'ajaxurl' => admin_url('admin-ajax.php')
]);
```

---

## 🟡 Medium (8 bugs)

---

### MED-01 — Promo countdown بيستخدم local browser timezone بدل Cairo

**الملف:** `assets/global/script.js` — السطور 466-507 / `functions.php` — السطور 957-966  
**الخطورة:** 🟡 Medium  
**التأثير:** الـ deadline للـ promo timer بيتحسب في `functions.php` بـ PHP time (server time)، لكن لما بيتمرر لـ JavaScript كـ Unix timestamp، الـ countdown بيشتغل بـ `Date.now()` اللي هو browser local time. زائر من timezone مختلف هيشوف وقت مختلف. Fallback في JS لو `deadline` مش موجود: بيعمل `Date.now() + 3 days` ومش بيدي إشارة ان الـ timer fake.

**الإصلاح:** تأكد إن `learnsimplyPromoDeadline` دايماً بييجي من PHP، وفي الـ fallback عمل `else { return; }` بدل إنشاء deadline وهمية من الـ browser.

---

### MED-02 — Logic كشف نوع الصفحة متكررة مرتين بالكامل

**الملف:** `functions.php` — السطور 1713-1808 والسطور 1815-1908  
**الخطورة:** 🟡 Medium  
**التأثير:** نفس الـ block من الكود (page type detection) موجود مرتين كاملتين بدون أي فرق. أي تعديل لازم يتعمل في مكانين، وده مصدر للأخطاء المستقبلية.

**الإصلاح:** احذف النسخة الثانية (السطور 1815-1908) بالكامل.

---

### MED-03 — خطأ إملائي "جنية" بدل "جنيه" في 4 أماكن

**الملفات:**
- `views/single-course.twig` — السطران 139 و 141
- `views/single-product.twig` — السطران 109 و 111
- `views/single-product-bundle.twig` — السطران 130 و 132

**الخطورة:** 🟡 Medium  
**التأثير:** الكلمة الظاهرة للمستخدم "جنية مصري" بدل "جنيه مصري". خطأ لغوي في صفحات البيع الرئيسية.

**الإصلاح:** استبدل كل `جنية مصري` بـ `جنيه مصري` في الملفات المذكورة.

---

### MED-04 — "3 أيام" hardcoded في discount banner (3 ملفات)

**الملفات:**
- `views/single-course.twig` — السطر 23
- `views/single-product.twig` — السطر 23
- `views/single-product-bundle.twig` — السطر 23

**الخطورة:** 🟡 Medium  
**التأثير:** النص `لمدة 3 أيام فقط` مش مرتبط بالـ promo timer الفعلي الموجود في الهيدر. ممكن الـ timer يوصل صفر لكن النص لسه بيقول `3 أيام`.

**الإصلاح:** جيب المدة من نفس قيمة `learnsimplyPromoDeadline` الموجودة في PHP، أو اعمله ديناميكي.

---

### MED-05 — `learnsimplyThemeUri` مش متعرف في JS لـ single course

**الملف:** `assets/single-course/script.js` — السطور 67-70  
**الخطورة:** 🟡 Medium  
**التأثير:** الـ script بيستخدم `window.learnsimplyThemeUri` لبناء image paths:
```js
const themeUri = window.learnsimplyThemeUri || '/wp-content/themes/edublink-child';
```
لكن الـ variable ده مش بيتضاف في أي مكان في PHP (لا `wp_localize_script` ولا inline script). النتيجة: الـ fallback path الـ hardcoded دايماً بيتستخدم. لو الـ theme directory اتغير المسم، الـ images هتتكسر.

**الإصلاح:** في `tutor/single-course.php` أو `functions.php` أضف:
```php
wp_localize_script('learnsimply-single-course', 'learnsimplyThemeUri', get_stylesheet_directory_uri());
```

---

### MED-06 — `wp_reset_postdata()` ناقص جوه nested loop في front-page.php

**الملف:** `front-page.php` — السطور ~131-145  
**الخطورة:** 🟡 Medium  
**التأثير:** في loop اكتشاف أول lesson، بعد استدعاء `get_course_contents_by_topic()` جوه الـ inner loop، مفيش `wp_reset_postdata()` بعد الـ inner query. الـ outer loop قد تاخد `$post` غلط في iterations التالية.

**الإصلاح:** أضف `wp_reset_postdata()` في نهاية كل inner loop يستخدم `WP_Query` أو functions بتغير global `$post`.

---

### MED-07 — Social proof toast بـ أسماء fake hardcoded

**الملف:** `views/sections/home/cta-section.twig` — السطور 262-270  
**الخطورة:** 🟡 Medium  
**التأثير:** Array بيحتوي على أسماء حقيقية المظهر (محمد، أحمد، فاطمة، إلخ) بتتعرض كـ toast notifications "اشترى للتو". ده dark pattern مضلل للمستخدمين. في بعض الأسواق، إعلانات شراء وهمية مخالفة لقوانين حماية المستهلك.

**الإصلاح:** اربط الـ toast بـ real recent purchases من WooCommerce، أو شيل الـ feature ده كلياً.

---

### MED-08 — `instructor_title` hardcoded كـ 'مهندس برمجيات' في PHP context

**الملف:** `functions.php` — السطر 1039 (تقريباً) في tutor single course context builder  
**الخطورة:** 🟡 Medium  
**التأثير:** العنوان الوظيفي للمدرب موجود كـ hardcoded value في PHP بدل ما يجي من user profile أو custom field. لو المنصة اتوسعت وفيها مدرسين بعناوين مختلفة، كلهم هيظهروا كـ "مهندس برمجيات". نفس القيمة بتظهر في `bundles-section.twig` (السطر 97) و`archive-courses.twig` (السطر 114) و`featured-courses-section.twig`.

**الإصلاح:** استخدم user meta (`get_user_meta($instructor_id, 'job_title', true)`) وارجع للـ hardcoded كـ fallback فقط.

---

## 🟢 Low (6 bugs)

---

### LOW-01 — إحصائيات الهيرو hardcoded (+1000 طالب، 98%، +7Y)

**الملف:** `views/sections/home/hero-section.twig` — السطور 154-169  
**الخطورة:** 🟢 Low  
**التأثير:** الأرقام مش من database. لو الأعداد الفعلية اتغيرت، لازم تعديل يدوي في الكود.

**الإصلاح:** جيب `students_count` من `tutor_utils()->count_enrolled_users_by_course()` وادي option لتعديل الأرقام من الـ dashboard.

---

### LOW-02 — "باقي 12 مكان بس" hardcoded كـ fake scarcity

**الملف:** `views/sections/home/cta-section.twig` — السطر 154  
**الخطورة:** 🟢 Low  
**التأثير:** المعلومة دي مش حقيقية وغير مرتبطة بـ WooCommerce stock. مضللة وغير متزامنة مع الواقع.

**الإصلاح:** اربطه بـ `$product->get_stock_quantity()` أو شيله.

---

### LOW-03 — `archive-courses.twig` السطر 258 fallback "40 ساعة" hardcoded

**الملف:** `views/archive-courses.twig` — السطر 258  
**الخطورة:** 🟢 Low  
**التأثير:** لو الكورس مش عنده `duration` metadata، بيظهر `40 ساعة` كـ fallback. نفس المشكلة موجودة في `featured-courses-section.twig` السطر 177.

**الإصلاح:** استخدم `'غير محدد'` كـ fallback أو شيل الـ stat من الـ card لو مش موجود.

---

### LOW-04 — `single-product.twig` و `single-product-bundle.twig` مش بيستخدموا `layouts/base.twig`

**الملفات:** `views/single-product.twig` — السطر 1 / `views/single-product-bundle.twig` — السطر 1  
**الخطورة:** 🟢 Low  
**التأثير:** الملفين بيعرفوا HTML كامل من `<!doctype>` بدل `{% extends "layouts/base.twig" %}`. يعني أي تغيير في الـ header أو footer أو base layout مش هيتعكس تلقائياً على صفحات المنتجات. الكود متكرر بدون لازمة.

**الإصلاح:** حوّل الملفين لـ `{% extends "layouts/base.twig" %}` مع blocks.

---

### LOW-05 — `single-post.twig` مش بيستخدم `layouts/base.twig`

**الملف:** `views/single-post.twig` — السطر 1  
**الخطورة:** 🟢 Low  
**التأثير:** نفس مشكلة LOW-04. صفحة المقال بتعرف HTML كامل بدل الـ base layout. التغييرات على الـ header/footer مش هتتعكس.

**الإصلاح:** حوّل لـ `{% extends "layouts/base.twig" %}`.

---

### LOW-06 — `"خطاء"` بدل `"خطأ"` في صفحة 404

**الملف:** `views/404.twig` — السطر 44  
**الخطورة:** 🟢 Low  
**التأثير:** خطأ إملائي في نص ظاهر للمستخدم: `خطاء` بدل `خطأ`.

**الإصلاح:** صلّح الكلمة.

---

## ملخص بالملفات المتضررة

| الملف | عدد الـ bugs | أعلى خطورة |
|---|---|---|
| `tutor/ecommerce/cart.php` | 1 | 🔴 Critical |
| `views/single-course.twig` | 4 | 🔴 Critical |
| `views/single-product.twig` | 3 | 🔴 Critical |
| `views/single-product-bundle.twig` | 3 | 🔴 Critical |
| `functions.php` | 4 | 🔴 Critical |
| `list-lessons.php` + `create-quizzes-topic1.php` | 1 (shared) | 🔴 Critical |
| `views/sections/home/cta-section.twig` | 3 | 🟠 High |
| `views/sections/home/bundles-section.twig` | 2 | 🟠 High |
| `views/sections/home/featured-courses-section.twig` | 2 | 🟠 High |
| `views/components/footer.twig` | 1 | 🟠 High |
| `archive-courses_temp.php` | 1 | 🟠 High |
| `assets/single-course/script.js` | 1 | 🟡 Medium |
| `assets/global/script.js` | 1 | 🟡 Medium |
| `front-page.php` | 1 | 🟡 Medium |
| `views/sections/home/hero-section.twig` | 1 | 🟢 Low |
| `views/archive-courses.twig` | 1 | 🟢 Low |
| `views/404.twig` | 1 | 🟢 Low |

---

*نهاية التقرير — 27 bug موثق*
