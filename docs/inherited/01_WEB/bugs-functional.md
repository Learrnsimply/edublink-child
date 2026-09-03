# تقرير فحص الأخطاء الوظيفية — learrnsimply.com
**تاريخ الفحص:** 2026-05-23  
**المنهجية:** فحص READ-ONLY بـ curl + تحليل HTML + WebFetch  
**النطاق:** الصفحة الرئيسية، الكورسات، المنتجات، السلة، الدفع، الداشبورد، الصفحات القانونية، robots.txt، sitemap

---

## الإحصائيات

| الخطورة | العدد |
|---------|-------|
| 🔴 Critical | 3 |
| 🟠 High | 5 |
| 🟡 Medium | 6 |
| 🟢 Low | 4 |
| **الإجمالي** | **18** |

---

## 🔴 Critical

### BUG-001 — صفحة المدونة 404 مع روابط نشطة في كل الموقع
**المكان:** `/blog/` — مُرتبط من 3 أماكن مختلفة  
**الوصف:** صفحة `/blog/` بترجّع `HTTP 404`. الرابط ده موجود في:
1. **قائمة التنقل العلوية** (الهيدر): "المدونة" ← `https://learrnsimply.com/blog/`
2. **زرار "عرض كل المقالات"** في section المقالات على الرئيسية
3. **الفوتر**: "المقالات" ← `https://learrnsimply.com/blog`

البراوزر بيطلّع صفحة "Page Not Found" مع title مزدوج يُثبت الـ 404.  
**الخطورة:** 🔴 Critical  
**الأثر:** أي زائر بيضغط على "المدونة" أو "عرض كل المقالات" بيقع في صفحة 404 — تجربة مستخدم مكسورة تماماً، ومحتوى الـ SEO على المدونة مش وصّال.  
**الإصلاح:** إما إنشاء صفحة WordPress بـ slug `blog` وتعيينها كـ Posts Page في الإعدادات، أو تغيير الروابط الـ 3 لـ `/category/blog/` أو أي URL موجود للمقالات.

---

### BUG-002 — رابط "نبذه عني" في الفوتر بيعطي 404
**المكان:** فوتر الرئيسية — `https://learrnsimply.com/about-me`  
**الوصف:** الفوتر فيه رابط `href="https://learrnsimply.com/about-me"` (بـ hyphen) بيرجّع `HTTP 404`. الصفحة الصحيحة موجودة على `https://learrnsimply.com/about_me/` (بـ underscore). 

الروابط الصح (بـ underscore) موجودة في الهيدر وفي جسم الصفحة، لكن الفوتر بالغلط فيه النسخة المكسورة.  
**الخطورة:** 🔴 Critical  
**الأثر:** رابط "نبذه عني" في الفوتر مكسور — كل من يضغط عليه من الفوتر بيقع في 404.  
**الإصلاح:** تغيير رابط الفوتر من `/about-me` لـ `/about_me/` في إعدادات الـ footer widget أو ملف الـ theme.

---

### BUG-003 — كل صفحة في الموقع فيها تاغين `<title>` متعارضين
**المكان:** جميع الصفحات — الرئيسية، الكورسات، السلة، المتجر، الـ about، إلخ  
**الوصف:** كل صفحة بتطلع بـ **2 تاغ `<title>` مختلفين** في نفس الـ `<head>`:
- **التاغ الأول (سطر 6):** الـ title القديم المحفور في الـ theme (hardcoded في `header.php`)
- **التاغ التاني (سطر 9/14):** التاغ الصح اللي Rank Math SEO بيحقنه

أمثلة:
```
الرئيسية:
  1: <title>اتعلم ببساطة - تعلم البرمجة من الصفر</title>
  2: <title>اتعلم ببساطة – تعلم البرمجة خطوة بخطوة</title>

كورس Java:
  1: <title>كورس جافا للمبتدئين + كتاب هدية 🎁 - اتعلم ببساطة</title>
  2: <title>كورس جافا للمبتدئين + كتاب مجاني PDF 🎁 | اتعلم ببساطة</title>

المتجر:
  1: <title>المتجر - اتعلم ببساطة</title>
  2: <title>Shop - اتعلم ببساطة</title>  ← (إنجليزي!)
```
البراوزرات بتستخدم الأول، ومحركات البحث ممكن تستخدم أي منهم — وهذا يضر SEO ضرراً بالغاً.  
**الخطورة:** 🔴 Critical  
**الأثر:** SEO مكسور على كل صفحة — Google بتشوف عنوانين متناقضين وعادةً بتتجاهل عنوان Rank Math المتحسّن. كمان المتجر عنده "Shop" بالإنجليزي ظاهر للبراوزر.  
**الإصلاح:** حذف السطر `<title>...</title>` الموجود في `header.php` بتاع الـ child theme قبل `<?php wp_head(); ?>` — Rank Math هيتكفل بعمل الـ title صح لوحده.

---

## 🟠 High

### BUG-004 — REST API بيكشف بيانات المستخدمين والـ admin status
**المكان:** `https://learrnsimply.com/wp-json/wp/v2/users`  
**الوصف:** الـ WordPress REST API متاح للعموم بدون أي authentication وبيرجّع:
- ID وبيانات المستخدمين الاثنين (Ahmed Adel + Omar)
- **`"is_super_admin": true`** لكل منهم
- User slugs (`ahmedadel123422`, `omar`) — ممكن تُستخدم في هجمات brute-force
- WooCommerce meta data وإعدادات Elementor الشخصية

**الخطورة:** 🟠 High  
**الأثر:** أي مهاجم يقدر يعرف الـ usernames الصحيحة ويبدأ هجوم brute-force على `/wp-login.php`. المعلومات دي تسهّل اختراق الموقع.  
**الإصلاح:** إضافة الكود ده في `functions.php` لتعطيل endpoint المستخدمين:
```php
add_filter('rest_endpoints', function($endpoints) {
    if (isset($endpoints['/wp/v2/users'])) unset($endpoints['/wp/v2/users']);
    if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
    return $endpoints;
});
```

---

### BUG-005 — 10+ صفحات staging مكررة مفهرسة في Google
**المكان:** `https://learrnsimply.com/page-sitemap.xml`  
**الوصف:** الـ sitemap بيحتوي على صفحات مكررة تبدو إنها تجارب قديمة أو staging:
- `/tutor-login-2/`, `/tutor-login-3/`, `/tutor-login-4/`, `/tutor-login-5/`
- `/user-register-2/`, `/user-register-2-2/`
- `/dashboard-2/`
- `/checkout-3/`
- `/my-account-4/`
- `/cart-1/%d8%b7%d8%b1%d9%82-%d8%a7%d9%84%d8%af%d9%81%d8%b9/` (URL غريب)

كل صفحة منهم فيها canonical لنفسها (مش للصفحة الأصلية)، معناها Google بتفهرسهم كلهم.  
**الخطورة:** 🟠 High  
**الأثر:** Duplicate content يضر SEO بشكل كبير، وبيخلي Google مش عارفة أي صفحة تـrank — بتتوزع الـ authority على 5 صفحات login بدل ما تتركّز في صفحة واحدة.  
**الإصلاح:** حذف الصفحات المكررة من WordPress، أو على الأقل إضافة `noindex` عليها وإزالتها من الـ sitemap.

---

### BUG-006 — زرار "تواصل معي" بيروح لصفحة "عن المدرب" مش لتواصل
**المكان:** الرئيسية — section "عن المدرب" — الزرار الثاني  
**الوصف:** في الكارت التانية في section المدرب، فيه زرين:
1. "تعرّف عليّ أكثر" ← `https://learrnsimply.com/about_me/` ✅ منطقي
2. **"تواصل معي"** ← `https://learrnsimply.com/about_me/` ❌ نفس الرابط!

الزرار الثاني المفروض يروح لصفحة تواصل أو section تواصل — مش نفس صفحة "نبذه عني".  
**الخطورة:** 🟠 High  
**الأثر:** الزائر اللي عايز يتواصل بيضغط الزرار ويتفاجأ إنه على نفس الصفحة — تجربة مستخدم مكسورة ومُربكة، وبيضيّع فرصة تحويل (conversion).  
**الإصلاح:** تغيير رابط "تواصل معي" لصفحة تواصل حقيقية أو anchor لـ section الـ contact في الصفحة.

---

### BUG-007 — صفحة `/signup/` عندها title إنجليزي "signup"
**المكان:** `https://learrnsimply.com/signup/`  
**الوصف:** صفحة التسجيل عندها `<title>signup - اتعلم ببساطة</title>` — كلمة "signup" إنجليزية خام في عنوان صفحة عربية بالكامل. هذا يعني إن Rank Math مش معمّل لها title عربي.  
**الخطورة:** 🟠 High  
**الأثر:** في SERP (نتايج Google)، صفحة التسجيل بتظهر بعنوان إنجليزي غريب — بيقلل الـ CTR ويبدو غير احترافي. كمان بيدل على إن الصفحة مش مُهتم بيها من ناحية SEO.  
**الإصلاح:** فتح صفحة signup في WordPress وتعيين Rank Math title عربي مناسب زي "إنشاء حساب - اتعلم ببساطة".

---

### BUG-008 — صفحة المتجر `/shop-2/` عندها title إنجليزي "Shop" من Rank Math
**المكان:** `https://learrnsimply.com/shop-2/`  
**الوصف:** الـ title الأول (theme) = `المتجر - اتعلم ببساطة` ✅، لكن Rank Math بيحقن `Shop - اتعلم ببساطة` ❌. ده معناه إن إعداد Rank Math للمتجر مكتوب إنجليزي.  
**الخطورة:** 🟠 High  
**الأثر:** طبقاً لـ BUG-003، البراوزر بيستخدم الـ title الأول (theme) — الآن الصح. لكن بعد إصلاح BUG-003، هيظهر "Shop" الإنجليزي. لازم يتصلح قبل إصلاح BUG-003.  
**الإصلاح:** تعديل Rank Math title لصفحة المتجر ليكون "المتجر - اتعلم ببساطة" أو "كتب ودورات برمجية".

---

## 🟡 Medium

### BUG-009 — robots.txt فيه كتلة `User-agent: *` مكررة
**المكان:** `https://learrnsimply.com/robots.txt`  
**الوصف:** الـ robots.txt فيه تعريفين `User-agent: *` منفصلين:
```
User-agent: *
Disallow: /wp-content/uploads/wc-logs/
...
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php
Sitemap: https://learrnsimply.com/sitemap_index.xml

User-agent: *
Disallow: /wp-content/uploads/wpo/wpo-plugins-tables-list.json
```
**الخطورة:** 🟡 Medium  
**الأثر:** بعض الـ crawlers ممكن تعتبر الكتلة الثانية override للأولى — السلوك مش موحّد بين Google و Bing وغيرهم.  
**الإصلاح:** دمج الكتلتين في كتلة واحدة وإزالة التكرار.

---

### BUG-010 — صفحة `/prompt/` فيها 4 مستندات HTML كاملة متداخلة
**المكان:** `https://learrnsimply.com/prompt/`  
**الوصف:** الصفحة دي عندها **5 تاغات `<title>`** (!) لأن محتوى الـ Elementor widget بيحتوي على 4 مستندات HTML كاملة (`<!DOCTYPE html>` و `<html>` و `</html>`) متداخلة داخل `<body>` الصفحة الأصلية. ده خلق HTML مشوّه تماماً.  
**الخطورة:** 🟡 Medium  
**الأثر:** البراوزر بيتعامل مع الـ HTML المتداخلة باستخدام "error recovery" — مش مضمون كيف تتعرض. محركات البحث ممكن تتجاهل المحتوى ده. قد يسبب مشاكل عرض على بعض البراوزرات.  
**الإصلاح:** استخراج المحتوى من المستندات المتداخلة وحقنه كـ HTML عادي داخل Elementor بدون `<!DOCTYPE>` و `<html>` wrapper.

---

### BUG-011 — أخطاء إملائية في الفوتر (لغة عربية)
**المكان:** الفوتر — كل الصفحات  
**الوصف:** الفوتر فيه كلمتين مغلوطتين إملائياً:
- `الرئيسيه` ← الصح: `الرئيسية`
- `نبذه عني` ← الصح: `نبذة عني`

**الخطورة:** 🟡 Medium  
**الأثر:** يُقلل من مصداقية الموقع ويبدو غير احترافي — خصوصاً إن الموقع ده تعليمي.  
**الإصلاح:** تصحيح النصين في إعدادات الـ footer widget أو قالب الفوتر في الـ child theme.

---

### BUG-012 — صفحة "عني" عندها title مختلف بين الـ theme وRank Math
**المكان:** `https://learrnsimply.com/about_me/`  
**الوصف:** تاغ الـ theme: `نبذه عني` ← تاغ Rank Math: `نبذة عنّي` — الاختلاف في الكتابة الإملائية والشدة.  
**الخطورة:** 🟡 Medium  
**الأثر:** بعد إصلاح BUG-003، البراوزر هيعرض الـ title الصح (Rank Math). لكن التناسق مهم.  
**الإصلاح:** تصحيح title الـ theme ليطابق Rank Math (جزء من إصلاح BUG-003).

---

### BUG-013 — صفحة Java Course عندها عنوانين مختلفين في التاغين
**المكان:** `https://learrnsimply.com/courses/java-course-level1/`  
**الوصف:**
- Theme title: `كورس جافا للمبتدئين + كتاب هدية 🎁 - اتعلم ببساطة`
- Rank Math title: `كورس جافا للمبتدئين + كتاب مجاني PDF 🎁 | اتعلم ببساطة`

الاختلاف في "هدية" vs "مجاني PDF" — احتمال إن أحدهم قديم أو خطأ.  
**الخطورة:** 🟡 Medium  
**الأثر:** البراوزر يعرض النسخة القديمة "كتاب هدية". بعد إصلاح BUG-003، هيعرض النسخة الصحيحة.  
**الإصلاح:** إصلاح BUG-003 ومراجعة الـ title في الـ theme.

---

### BUG-014 — الفوتر يحتوي على رابط `https://learrnsimply.com` بدون trailing slash
**المكان:** الفوتر — رابط "الرئيسيه"  
**الوصف:** `href="https://learrnsimply.com"` بدل `href="https://learrnsimply.com/"` — مش مشكلة وظيفية بحتة لكنه تناسق.  
**الخطورة:** 🟡 Medium  
**الأثر:** ضعيف جداً — البراوزر بيعمل redirect تلقائي. لكن يُحسّن consistency الـ canonical.  
**الإصلاح:** إضافة trailing slash للـ homepage link.

---

## 🟢 Low

### BUG-015 — صفحة المدونة `404` تحتوي على title خاص بها (ازدواجية)
**المكان:** `https://learrnsimply.com/blog/` — HTTP 404  
**الوصف:** رغم إن الصفحة بترجّع 404، إلا إن الـ theme بيحقن `<title>المقالات - اتعلم ببساطة</title>` بينما صفحة الـ 404 بتحقن `<title>Page Not Found - اتعلم ببساطة</title>` — دليل إضافي على التعارض في BUG-003 والـ 404 في BUG-001.  
**الخطورة:** 🟢 Low (بيُثبّت BUG-001 و BUG-003)  
**الإصلاح:** يُحل مع BUG-001 و BUG-003.

---

### BUG-016 — الـ sitemap XSL بيستخدم `//` بدل `https://`
**المكان:** `https://learrnsimply.com/sitemap_index.xml` — سطر 1  
**الوصف:** `<?xml-stylesheet type="text/xsl" href="//learrnsimply.com/main-sitemap.xsl"?>` — البروتوكول ناقص (protocol-relative URL).  
**الخطورة:** 🟢 Low  
**الأثر:** مش مشكلة وظيفية — البراوزرات بتتعامل معها كـ HTTPS. لكن بعض الـ XML validators بيشتكوا منها.  
**الإصلاح:** تحديث Rank Math أو تغيير الرابط لـ `https://learrnsimply.com/main-sitemap.xsl`.

---

### BUG-017 — Author pages مكشوفة (usernames enumeration)
**المكان:** `https://learrnsimply.com/author/ahmedadel123422/` و `/author/omar/`  
**الوصف:** صفحات الـ author متاحة للعموم وبتكشف الـ username الحقيقي للدخول على الموقع.  
**الخطورة:** 🟢 Low (مرتبط بـ BUG-004)  
**الأثر:** جزء من إشكالية الـ user enumeration — بتسهّل هجمات brute force.  
**الإصلاح:** إضافة redirect لصفحات الـ author أو تعطيلها في `functions.php`.

---

### BUG-018 — `/checkout/` بيعمل 302 redirect لـ `/cart-1/`
**المكان:** `https://learrnsimply.com/checkout/`  
**الوصف:** الـ checkout بيعمل `302 Found` redirect للسلة لما تكون فاضية. ده سلوك WooCommerce الطبيعي عموماً، لكن استخدام 302 (temporary) بدل 307 ممكن يكون محيّر لبعض الـ crawlers.  
**ملاحظة:** كما ذُكر في التعليمات، الـ redirect لما السلة فاضية متوقّع — لكن مدرج هنا للإشارة إن checkout URL نفسه مش إعداده كصفحة WooCommerce قياسية (إعدادها يدوي).  
**الخطورة:** 🟢 Low  
**الأثر:** ضعيف. الـ redirect صح وظيفياً.  
**الإصلاح:** لو أُريد، التحقق إن WooCommerce Settings → Advanced → Page Setup بتشير للـ checkout page الصح.

---

## ملاحظات إضافية (مش bugs بالمعنى الدقيق)

| الملاحظة | التفاصيل |
|----------|----------|
| لا أخطاء PHP | لم تظهر أي `Notice:` أو `Warning:` أو `Fatal error:` في أي صفحة محفوظة ✅ |
| لا mixed content | مفيش موارد `http://` على صفحات `https://` ✅ |
| لا صور مكسورة | 17 صورة فُحصت — كلها 200 OK ✅ |
| anchor #courses | الزرار "ابدأ الآن" بيروح `#courses` والعنصر `id="courses"` موجود ✅ |
| robots.txt موجود | يُعطى 200 وفيه Sitemap مُشار إليه ✅ |
| sitemap موجود | sitemap_index.xml صحيح ويحتوي 7 sub-sitemaps ✅ |
| لا HTTPS → HTTP | الموقع كامل على HTTPS ✅ |

---

## خريطة الأولويات

```
الأسبع الأولى (Critical + High):
├── BUG-003: إصلاح التاغ المكرر <title> في header.php
├── BUG-008: تصحيح عنوان المتجر في Rank Math (قبل BUG-003)
├── BUG-007: تصحيح عنوان signup في Rank Math
├── BUG-001: إصلاح صفحة /blog/ (إنشاء أو تغيير الروابط)
├── BUG-002: إصلاح رابط about-me في الفوتر
├── BUG-006: إصلاح زرار "تواصل معي"
└── BUG-004: تعطيل REST API users endpoint

الأسبوع الثاني (Medium + Low):
├── BUG-005: حذف/noindex صفحات staging المكررة
├── BUG-009: دمج كتلتي User-agent في robots.txt
├── BUG-011: تصحيح الأخطاء الإملائية في الفوتر
└── BUG-010: إصلاح HTML المتداخلة في صفحة /prompt/
```
