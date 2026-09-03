# Technical Site Health Audit — اتعلم ببساطة (learrnsimply.com)
**تاريخ الـ audit:** 2026-05-23  
**المنهجية:** فحص read-only عبر HTTP headers + HTML source + REST API + Sitemaps  
**Stack:** WordPress 6.9.4 + Tutor LMS 3.9.11 + WooCommerce 10.7.0 + Elementor 4.0.9 + 55 plugin مفعّل

---

## Scorecard التنفيذي

| البُعد | الدرجة | الحالة |
|--------|--------|--------|
| **SEO — Technical** | 52 / 100 | 🔴 يحتاج تدخل عاجل |
| **SEO — Content** | 65 / 100 | 🟡 متوسط |
| **Performance** | 38 / 100 | 🔴 حرج |
| **Security** | 44 / 100 | 🔴 مخاطر واضحة |
| **الدرجة الإجمالية** | **50 / 100** | 🔴 يحتاج إصلاح فوري |

---

## ملخص تنفيذي

الموقع عليه محتوى قوي ومشروع حقيقي، بس في 3 مشاكل حرجة تستحق إصلاح فوري:

1. **أمان:** الـ REST API بيكشف اسم الأدمن + slug الحساب بالكامل (super admin) — ده بوابة للهجمات.
2. **أداء:** 85 ملف JavaScript على الصفحة الرئيسية، 0 صورة بـ lazy loading، الصفحة 342 KB HTML لوحدها — بيأثر جداً على الموبايل.
3. **SEO تقني:** الـ sitemap فيه 40+ صفحة مساعدة (login/register duplicates) مفروض تتمسح، ومفيش Course Schema على صفحات الكورسات.

---

## 1. SEO Audit

### 1.1 robots.txt
🟢 **كويس بشكل عام**

```
User-agent: *
Disallow: /wp-content/uploads/wc-logs/
Disallow: /wp-content/uploads/woocommerce_transient_files/
Disallow: /wp-content/uploads/woocommerce_uploads/
Disallow: /*?add-to-cart=
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php
Sitemap: https://learrnsimply.com/sitemap_index.xml
```

**الإيجابيات:**
- بيمنع الـ WooCommerce logs والـ uploads الحساسة ✅
- بيمنع URL parameters الـ add-to-cart ✅
- الـ sitemap مُعلَن فيه بشكل صح ✅

**الملاحظات:**
- 🟡 في `User-agent: *` مكرر مرتين — مش خطأ فادح بس يُنظَّف.
- 🟡 الـ `/wp-content/plugins/` مش محظور — الـ robots بتستطيع crawl ملفات الـ plugins (مش ضروري لكن أفضل تُخفى version info).

---

### 1.2 Sitemap
🟡 **موجود وشغّال — بس فيه تضخم**

الـ sitemap index فيه **12 sitemap** منهم:

| Sitemap | الملاحظة |
|---------|----------|
| post-sitemap.xml | ✅ آخر تحديث: أبريل 2026 |
| page-sitemap.xml | 🔴 فيه 40+ صفحة مساعدة مش لازم تتأشر |
| product-sitemap.xml | ✅ المنتجات موجودة |
| courses-sitemap.xml | ✅ 6 كورسات |
| lesson-sitemap1–5.xml | ✅ الدروس مأشرة |
| tutor_quiz-sitemap.xml | 🟡 Quiz pages في sitemap — قيّم إذا كانت تستحق أشر |
| cartflows_step-sitemap.xml | 🔴 صفحات الـ checkout funnel في sitemap — خطأ |
| local-sitemap.xml | 🟡 غير واضح المحتوى |

**مشكلة حرجة في الـ page-sitemap:**
الـ sitemap فيه صفحات مثل:
- `tutor-login/`, `tutor-login-2/`, `tutor-login-3/`, `tutor-login-4/`, `tutor-login-5/` — **5 نسخ من صفحة login!**
- `user-register/`, `user-register-2/`, `user-register-2-2/`, `user-register-3/` — **4 نسخ من صفحة register!**
- `user-login/`, `user-login-2/`, `user-login-3/`, `user-login-4/` — **4 نسخ من صفحة login!**
- `dashboard/`, `dashboard-2/`, `my-account/`, `my-account-2/`, `my-account-3/`, `my-account-4/`
- `forgot-password/`, `forgot-password-2/`, `reset-password/`, `reset-password-2/`

**ده بيعني Google بتحاول تأشر ~40 صفحة مش ذات قيمة بدل ما تركّز على الكورسات والمحتوى.**

> **التوصية:** حذف كل الصفحات المكررة القديمة من الـ WP Dashboard، أو noindex عليهم جميعاً من Rank Math.

---

### 1.3 Meta Titles & Descriptions

#### الصفحة الرئيسية
- **Title:** `اتعلم ببساطة - تعلم البرمجة من الصفر` (موجود في HTML مرتين!)
  - 🔴 **Title مكرر:** الـ HTML فيه `<title>` مرتين — ده بيحصل لما Elementor أو theme بيضيف title زيادة
  - الـ title الأول: `اتعلم ببساطة - تعلم البرمجة من الصفر`
  - الـ title التاني: `اتعلم ببساطة – تعلم البرمجة خطوة بخطوة` (من الـ Schema JSON-LD)
- **Description:** `اتعلم ببساطة هي منصة تعليمية عربية متخصصة في تعليم البرمجة من الصفر بطريقة فريدة تعتمد على التبسيط والوضوح. نؤمن أن البرمجة يمكن أن تكون سهلة وممتعة.`
  - 🟡 الـ description كويس الفكرة بس عام جداً — مفيش ذكر للكورسات أو الـ Java أو Python بالاسم
  - الطول تقريباً 130 حرف — مقبول

#### صفحة كورس Java (java-course-level1)
- **Title (مكرر هنا برضو):**
  - `كورس جافا للمبتدئين + كتاب هدية 🎁 - اتعلم ببساطة`
  - `كورس جافا للمبتدئين + كتاب مجاني PDF 🎁 | اتعلم ببساطة`
  - 🔴 نفس مشكلة تكرار الـ title في HTML
- **Description:** `ابدأ تعلم Java خطوة بخطوة مع كورس جافا للمبتدئين. شرح سهل وعملي + كتاب مجاني PDF يغطي الأساسيات ويأهلك لكتابة كود احترافي.` ✅ كويس

---

### 1.4 Structured Data / Schema
🟡 **موجود جزئياً — ناقصه الأهم**

**ما موجود (الصفحة الرئيسية):**
- `EducationalOrganization` + `Organization` ✅
- `WebSite` + `SearchAction` ✅
- `WebPage` ✅
- `Person` (Ahmed Adel) ✅
- `Article` على الصفحة الرئيسية 🟡 (Article مش المناسب للـ homepage)

**ما ناقص:**
- 🔴 **Course Schema** على صفحات الكورسات غائب تماماً — ده المهم لـ Google لعرض rich results مع course info في نتايج البحث
- 🔴 **Product Schema** على صفحات المنتجات (كتب PDF) — مهم لـ rich snippets بالسعر
- 🟡 **BreadcrumbList** غائب — يُحسّن شكل الـ URL في نتايج البحث
- 🟡 **FAQ Schema** لو في أسئلة شائعة على الصفحة

> **التوصية:** فعّل Course Schema من Rank Math SEO PRO على كل صفحة كورس (موجود في الـ plugin مجاناً كـ Post Type Schema).

---

### 1.5 H1 Tags
🔴 **مشكلة: 3 H1 على الصفحة الرئيسية**

الصفحة الرئيسية عندها 3 H1:
1. `اتعلم ببساطة – تعلم البرمجة خطوة بخطوة`
2. `البرمجة أسهل مما تتخيل… لو اتعلمتها ببساطة`
3. `من أول سطر كود… لحد أول مشروع ليك`

**لازم تبقى H1 واحدة فقط على أي صفحة.** الـ H2 و H3 للباقي.

**صفحة الكورس:** ✅ H1 واحدة: `كورس جافا للمبتدئين + كتاب هدية 🎁`

---

### 1.6 المدونة
🔴 **شبه غايبة**

- `/blog/` → **404 Not Found**
- المقالات على `/prompt/` (URL واحد فقط في الـ sitemap)
- الـ REST API بيكشف مقال واحد فقط: `ازاي تعمل موقعك الشخصي في 15 دقيقة` — نُشر **ديسمبر 2025**
- **5 أشهر بدون مقالة جديدة** (آخر تحديث للـ post-sitemap: أبريل 2026 — بس الـ URL الوحيد آخر مقالة ديسمبر 2025)

**فرص كلمات بحث عربية مهمة:**
| الكلمة | التنافسية | الأولوية |
|--------|-----------|----------|
| تعلم جافا بالعربي | منخفضة-متوسطة | 🔴 عالية |
| كورس Data Structure بالعربي | منخفضة | 🔴 عالية |
| برمجة للمبتدئين بالعربي | عالية | 🟡 متوسطة |
| تعلم Python بالعربي | متوسطة | 🟡 متوسطة |
| Java OOP بالعربي | منخفضة | 🔴 عالية |
| مشاريع Python للمبتدئين | منخفضة | 🔴 عالية |

---

### 1.7 Technical SEO — باقي النقاط

**Canonical:**
- ✅ الصفحة الرئيسية: `<link rel="canonical" href="https://learrnsimply.com/" />`
- ✅ صفحة الكورس: canonical صح

**lang/dir:**
- ✅ `<html lang="ar" dir="rtl">` — ممتاز

**Noindex:**
- 🔴 صفحات login/register المكررة مش عليها noindex — بتدخل الـ sitemap وبتأكل crawl budget

**مشكلة الـ duplicate pages في sitemap:**
الـ page-sitemap.xml فيه 40+ صفحة من النوع ده — كل ده مهدر لـ crawl budget وبيخلي Google تتخبط في أيهم "الأصلي".

---

## 2. Performance Audit

### 2.1 حجم الصفحة الرئيسية وعدد الـ Requests
🔴 **أرقام حرجة**

| المقياس | القيمة | التقييم |
|---------|--------|---------|
| حجم HTML (uncompressed) | **342 KB** | 🔴 كبير جداً |
| عدد ملفات JS المُحمَّلة | **85 ملف** | 🔴 كارثي |
| عدد ملفات CSS المُحمَّلة | **3 فقط** (CSS مجمّع) | ✅ كويس |
| عدد الصور في HTML | **104 صورة** | 🔴 كتير |
| Lazy loading على الصور | **0 صورة** | 🔴 كارثي |
| الـ HTTP requests المقدَّرة | **190+** | 🔴 حرج |
| وقت استجابة الـ server | ~0.14 ثانية | ✅ كويس (Hostinger CDN) |
| حالة الـ cache | **WPO cached** | 🟡 شغّال بس مش optimal |

### 2.2 الصور
🔴 **مشكلة أساسية**

- **0 صورة من 104** عليها `loading="lazy"` — كل الصور بتتحمل مع بعض أول ما الصفحة تفتح
- **2 صورة بـ `_scaled`** موجودة (python-projects-2048x1152.jpg, 140KB) — بيعني صور أصلية ضخمة
- **39 ملف صورة** يُرجَع إليها من الـ HTML — معظمها بدون أبعاد (width/height)
- مفيش WebP تلقائي (LiteSpeed Cache INACTIVE — الـ WebP conversion معطّل)
- ملاحظة: **بعض الصور بـ `_scaled`** بتعني المستخدم رفع صور أكبر من 2048px وWordPress عمل resize — الأصل ممكن يكون أكبر بكتير

### 2.3 JavaScript
🔴 **85 ملف JS على صفحة واحدة**

المصادر الرئيسية:
- **jQuery + jQuery Migrate** (أساسيات WP)
- **WooCommerce** (4 ملفات)
- **TutorLMS** + Tutor Pro (ملفات متعددة)
- **Elementor** (مجموعة كبيرة)
- **Royal Elementor Addons**
- **TutorLMS Elementor Addons**
- **MonsterInsights (Google Analytics)**
- **Contact Form 7**
- **TikTok for Business** (pixel)
- **Click to Chat WhatsApp**
- **10 SVG icons من cdn.jsdelivr.net** (خارجي)
- **React + ReactDOM** (مُحمَّلين بواسطة Elementor/WP)

**الخلاصة:** كل plugin بيحمّل JS الخاص بيه على كل صفحة — حتى WooCommerce بيحمّل على الصفحة الرئيسية رغم إنه مش محتاجه هناك.

### 2.4 Fonts
🟡 **Google Fonts بتُحمَّل 3 مرات منفصلة**

```
- IBM Plex Sans Arabic (كل الأوزان من 100 لـ 700)
- Cairo (من fonts.googleapis.com — طلبين منفصلين!)
- Cairo تاني مرة (من WP Rocket/Cache plugin)
```

**ده render-blocking وهدر لـ DNS lookups.**

### 2.5 الـ Caching
🟡 **WPO-Cache شغّال — بس الإعداد مش optimal**

**الوضع الحالي:**
- `WPO-Cache-Status: cached` → WP-Optimize Cache شغّال ✅
- `Cache-Control: no-cache` → الـ browser cache مش مفعّل 🔴
- `x-hcdn-cache-status: DYNAMIC` → Hostinger CDN مش cache الصفحة كـ static 🟡
- **LiteSpeed Cache مثبّت بس INACTIVE** — ده بيعني redundancy وconfusion

**التحليل:**
- WP-Optimize عنده Page Cache بيشتغل (بيظهر في الـ header)
- بس الـ `Cache-Control: no-cache` معناه البراوزر مش بيخزّن الصفحة محلياً
- LiteSpeed Cache أقوى بكتير (WebP + LSCAPI + full-page cache) — الأفضل تفعيله وتعطيل WP-Optimize Cache

### 2.6 Core Web Vitals (تقدير بناءً على التحليل)

| المقياس | التقدير | السبب |
|---------|---------|-------|
| **LCP** | ~4-6 ثانية 🔴 | صور ضخمة بدون lazy/preload، 85 JS يبطّء التحميل |
| **CLS** | متوسط-عالي 🟡 | 104 صورة بدون width/height → layout shift |
| **INP** | عالي 🔴 | 85 JS ملف = JavaScript thread مشغول، خصوصاً موبايل |
| **FCP** | ~2-3 ثانية 🟡 | الـ server response سريع لكن JS يعيق الـ rendering |

**ملاحظة موبايل:** معظم الزوار في مصر موبايل على شبكات 4G متوسطة — الـ 85 JS ملف بيبقى أسوأ تأثير على موبايل بكتير.

---

## 3. Security Audit

### 3.1 SSL/HTTPS
✅ **شغّال وصح**
- الموقع على HTTPS بشكل كامل
- `Content-Security-Policy: upgrade-insecure-requests` → بيحوّل HTTP لـ HTTPS تلقائياً

### 3.2 Security Headers
🔴 **ناقص الأساسي**

| Header | الحالة | المخاطرة |
|--------|--------|---------|
| `Strict-Transport-Security (HSTS)` | ❌ **غائب** | 🔴 عالية — بيسمح بـ HTTPS downgrade attacks |
| `X-Content-Type-Options` | ❌ **غائب** | 🟡 متوسطة — MIME sniffing |
| `X-Frame-Options` | ✅ `SAMEORIGIN` على /wp-admin | موجود على login فقط |
| `Referrer-Policy` | ✅ `strict-origin-when-cross-origin` على /wp-admin | موجود على login فقط |
| `Permissions-Policy` | ❌ **غائب** | 🟡 متوسطة |
| `Content-Security-Policy` | `upgrade-insecure-requests` فقط | 🔴 مش CSP حقيقي |
| `X-Powered-By` | ✅ `PHP/8.2.30` ظاهر | 🟡 بيكشف stack info |

**ملاحظة مهمة:** الـ X-Frame-Options والـ Referrer-Policy موجودين على `/wp-login.php` بس — مش موجودين على الصفحات العادية!

### 3.3 WordPress Version Disclosure
🟡 **متوسط**

- `<meta name="generator" content="WordPress 6.9.4" />` — **نسخة WP ظاهرة في الكود**
- `TutorLMS 3.9.11` ظاهر في meta
- `Elementor 4.0.9` ظاهر في meta
- نسخ الـ plugins ظاهرة في كل asset URL: `?ver=10.7.0`, `?ver=3.9.11`, إلخ.

**ده بيسهّل على hackers معرفة إذا في vulnerabilities موجودة في النسخ دي.**

> **التوصية:** إخفاء الـ generator meta tags من Rank Math أو functions.php. الـ asset versions أصعب تُخفى بس ممكن برقم hash.

### 3.4 wp-login.php
🟡 **مكشوف بدون حماية إضافية**

- `/wp-login.php` → **HTTP 200** — الصفحة متاحة لأي حد
- مفيش rate limiting واضح على الـ headers
- مفيش Two-Factor Authentication بيظهر
- مفيش plugin أمان (Wordfence/Sucuri) — **لا يوجد أي دليل على وجود WAF**

**المخاطرة:** Brute force attacks على الـ admin login سهلة جداً بدون حماية.

### 3.5 XML-RPC
✅ **محظور جزئياً**

- `/xmlrpc.php` → **HTTP 405 Method Not Allowed** (GET محظور)
- بس POST لسه مسموح بيه — ده الخطر الحقيقي (DDoS amplification + brute force عبر `system.multicall`)

> **التوصية:** Disable XML-RPC بالكامل من plugin مثل Disable XML-RPC أو من functions.php.

### 3.6 REST API — User Enumeration
🔴 **خطير: بيسرّب بيانات الأدمن**

الـ endpoint ده عام ومتاح لأي حد:
```
https://learrnsimply.com/wp-json/wp/v2/users
```

**البيانات المُسرَّبة:**
- **User ID:** `1`
- **الاسم الحقيقي:** `Ahmed Adel`
- **Username (slug):** `ahmedadel123422`
- **URL:** `https://learrnsimply.com`
- **`is_super_admin: true`** — ده خطير جداً! بيكشف إن الحساب ده super admin
- **User ID:** `2`, **Name:** `إتعلم ببساطة`, **slug:** `omar`
- بيانات Elementor + WooCommerce user preferences مكشوفة

**لو حد عايد يعمل brute force، هو عارف:**
- Username: `ahmedadel123422`
- أو: `omar`
- وعارف إن ID 1 هو الـ super admin

> **التوصية الفورية:** في Rank Math أو security plugin، disable users REST endpoint. أو أضف الكود ده لـ `functions.php`:
> ```php
> add_filter('rest_endpoints', function($endpoints) {
>   if (isset($endpoints['/wp/v2/users'])) unset($endpoints['/wp/v2/users']);
>   if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
>   return $endpoints;
> });
> ```

### 3.7 Plugin Security
🔴 **مخاطرة عالية بدون WAF**

- **55 plugin مفعّل** = سطح هجوم كبير جداً
- **مفيش Wordfence / Sucuri / iThemes Security** — لا يوجد أي firewall على مستوى الـ application
- **4 backup plugins** (All-in-One Migration, Duplicator, WPvivid, WP Synchro) — التعدد ده مش ضروري ومش محتاجه أكتر من واحد، وبعضهم بيكشف backup files لو مش مؤمَّن
- **Kashier WooCommerce Plugin** للدفع (بيانات بطاقات) — لازم يكون على آخر نسخة دايماً

### 3.8 Backups
🟡 **متوفر — بس مبالغ في التعدد**

4 backup plugins نشطة:
- All-in-One WP Migration
- Duplicator
- WPvivid
- WP Synchro

> **التوصية:** اختار واحد بس (WPvivid أو All-in-One Migration)، وتأكد إن الـ backup files مش accessible من URL عام.

---

## 4. ملخص المشاكل والأولويات

### 🔴 عالية — إصلاح فوري (خلال أسبوع)

| # | المشكلة | البُعد | الإصلاح |
|---|---------|--------|---------|
| 1 | **REST API بيكشف usernames + super admin flag** | Security | أضف filter لإخفاء `/wp/v2/users` endpoint |
| 2 | **0 صورة بـ lazy loading من 104** | Performance | فعّل LiteSpeed Cache (image lazy load تلقائي) أو أضف `loading="lazy"` |
| 3 | **85 ملف JS على الصفحة الرئيسية** | Performance | فعّل JS minify + combine في LiteSpeed Cache |
| 4 | **HSTS header غائب** | Security | أضف من Hostinger hPanel أو `.htaccess` |
| 5 | **صفحات login/register مكررة (~40 صفحة) في sitemap** | SEO | noindex أو احذف الصفحات القديمة |
| 6 | **3 H1 على الصفحة الرئيسية** | SEO | سيب H1 واحدة، حوّل الباقي لـ H2 |

### 🟡 متوسطة — إصلاح خلال شهر

| # | المشكلة | البُعد | الإصلاح |
|---|---------|--------|---------|
| 7 | **Course Schema غائب على صفحات الكورسات** | SEO | فعّل Course Schema من Rank Math PRO |
| 8 | **Title مكرر في HTML (مرتين)** | SEO | اكتشف مصدر الـ title التاني وشيله |
| 9 | **LiteSpeed Cache INACTIVE مع وجود WPO** | Performance | اختر واحد — LiteSpeed أقوى |
| 10 | **Google Fonts محمّلة 3 مرات** | Performance | اجمع طلبات الـ fonts في طلب واحد |
| 11 | **XML-RPC لسه بيقبل POST** | Security | Disable بالكامل |
| 12 | **WordPress version ظاهر في meta** | Security | أخفِ generator tags |
| 13 | **X-Content-Type-Options غائب** | Security | أضف للـ headers |
| 14 | **Browser cache (Cache-Control) غير مفعّل** | Performance | اضبط من LiteSpeed Cache أو `.htaccess` |
| 15 | **cartflows_step في sitemap** | SEO | استثنِ CartFlows funnel pages من sitemap |
| 16 | **المدونة: مقالة واحدة منذ 5 أشهر** | SEO Content | جدول نشر منتظم (1-2 مقالة/شهر) |

### 🟢 تحسينات مستقبلية (اختيارية)

| # | التحسين |
|---|---------|
| 17 | إضافة WAF (Wordfence free كافي للبداية) |
| 18 | إضافة BreadcrumbList Schema |
| 19 | تحويل الصور لـ WebP (LiteSpeed Cache بيعمل ده تلقائياً) |
| 20 | تقليل الـ plugins من 55 لأقل (audit كل plugin ضروري فعلاً) |
| 21 | Permissions-Policy header |
| 22 | 2FA على حساب الأدمن |
| 23 | إخفاء asset version strings |
| 24 | إضافة Breadcrumbs على الصفحات لتحسين UX والـ Schema |

---

## 5. خطة عمل مقترحة (Quick Wins أولاً)

```
الأسبوع الأول — Security + SEO حرج:
✅ أخفِ /wp/v2/users endpoint (5 دقايق)
✅ أضف noindex على صفحات login/register المكررة (من Rank Math)
✅ فعّل LiteSpeed Cache + lazy loading
✅ أضف HSTS header من hPanel

الأسبوع التاني — SEO:
✅ صلّح H1 على الصفحة الرئيسية (سيّب واحدة بس)
✅ فعّل Course Schema من Rank Math على كل كورس
✅ امسح أو noindex صفحات login/register القديمة

الأسبوع التالت — Performance:
✅ فعّل JS minification + combine
✅ اضبط Google Fonts في طلب واحد
✅ فعّل Browser Cache headers
✅ راجع Sitemap وامسح cartflows_step

الشهر التاني:
✅ Disable XML-RPC
✅ نشر 2 مقالة بكلمات مفتاحية مستهدفة
✅ Audit الـ 55 plugin وشيل المش مستخدَم
```

---

## 6. ملاحظات فنية إضافية

- **PHP 8.2.30** ✅ نسخة حديثة وآمنة
- **WooCommerce 10.7.0** ✅ نسخة حديثة
- **Elementor 4.0.9** ✅ محدَّث
- **Theme:** edublink-child (child theme مفعّل ✅ — بيعني التعديلات آمنة)
- **Hostinger hCDN:** موجود ✅ بيوفر سرعة
- **Google Analytics (MonsterInsights):** شغّال ✅
- **TikTok Pixel:** موجود ✅

---

*Audit by Claude Code — read-only analysis, no changes made.*
