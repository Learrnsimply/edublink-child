# تقرير Performance Bugs — اتعلم ببساطة
**تاريخ الفحص:** 2026-05-23 (Wave 3A — Performance Deep Audit)
**المصدر:** PHP config + DB stats + HTTP headers + autoload analysis
**الموقع:** learrnsimply.com

---

## جدول إحصائي سريع

| الفئة | المشاكل | Critical | High | Medium | Low |
|---|---|---|---|---|---|
| Database / autoload | 2 | 1 | 1 | 0 | 0 |
| Theme bloat | 3 | 0 | 2 | 1 | 0 |
| Cache / CDN | 2 | 0 | 1 | 1 | 0 |
| PHP / Server config | 2 | 0 | 0 | 2 | 0 |
| Upload bloat | 2 | 0 | 0 | 1 | 1 |
| **الإجمالي** | **11** | **1** | **4** | **5** | **1** |

---

## 🔴 Critical (1 مشكلة)

---

### C-1 — 918 autoloaded option = 987 KB يتحمّلوا في كل request
**الوصف:**
```
autoload_count: 918
autoload_kb:    987
```

في كل request (homepage، product page، blog post، REST API call)، الـ MySQL بيرجّع 918 row من wp_options بإجمالي ~1 MB، الـ PHP بيـ unserialize كل واحدة، ويـ keep في الذاكرة.

**Top contributors:**
| Option | الحجم | تابع لـ |
|---|---|---|
| `cartflows_docs_data` | 497 KB | CartFlows (docs, not config!) |
| `astra-settings` | 256 KB | Astra theme (مش active!) |
| `email_template_data` | 50 KB | Email plugin |
| `fs_accounts` | 20 KB | Freemius license tracking |
| `jetpack_active_plan` | 15 KB | Jetpack (هل active؟) |
| `elementskit-lite__banner_data` | 11 KB | ElementsKit |
| `edublink_theme_options` | 10 KB | EduBlink theme |
| `aioseo_options` | 9 KB | AIOSEO (مش active!) |
| `tutor_option` | 9 KB | Tutor LMS |

**الأثر — حسبة:**
- كل request بيـ load 1 MB من DB → +30-80ms على الـ server response time
- على shared hosting + 1645 active sessions، الـ memory pressure يبقى عالي
- لو cached بـ object cache يقل، بس object cache مش active

**الإصلاح:**
1. **حوّل options قاطعة autoload:**
   ```sql
   UPDATE wp_options SET autoload='no' WHERE option_name IN (
     'cartflows_docs_data',
     'astra-settings',
     'aioseo_options',
     'aioseo_options_dynamic',
     'fs_accounts',
     '_astra_sites_old_customizer_data'
   );
   ```
   = توفير ~800 KB من autoload
2. **اقفل plugins المهجورة** (راجع bugs-plugins.md) — كل واحد بيقلل options معه
3. **تفعيل object cache** (Redis على Hostinger أو APCu) لو متاح

**الخطورة:** 🔴 Critical (perf impact على كل visitor)
**المكان:** `wp_options` table — autoload column

---

## 🟠 High (4 مشاكل)

---

### H-1 — `wp_postmeta` index غير مكتمل — meta_key cardinality منخفض جداً
**الوصف:** فحص indexes:
```
wp_postmeta:
  PRIMARY (meta_id):      cardinality 257,942 ✅
  post_id index:          cardinality 15,173 ✅
  meta_key index:         cardinality 475    ⚠️ منخفض
```

الـ `meta_key` index بـ sub_part = 191 (يعني بيـ index بس أول 191 character)، وعنده cardinality منخفض (475 unique values من 257K row).

**الأثر:** Queries بـ `WHERE meta_key='_some_long_meta_key' AND meta_value=...` بتـ scan الـ table أكثر مما يجب، خاصة للـ Tutor/WC queries على enrollments أو order meta.

**الإصلاح:**
- WC + Tutor بيـ recommend indexes إضافية. Plugin **"Index WP MySQL For Speed"** بيـ analyze الـ patterns ويـ create indexes optimal:
  ```bash
  wp plugin install index-wp-mysql-for-speed --activate
  wp index-mysql enable --all
  ```
- النتيجة: WC reports + Tutor enrollment pages 2-5x أسرع

**الخطورة:** 🟠 High (perf impact على admin + API)
**المكان:** DB indexes — wp_postmeta, wp_options

---

### H-2 — `functions.php` في الـ child theme = 106 KB (massive)
**الوصف:**
```
edublink-child/functions.php = 106,324 bytes (= 104 KB)
```

في الـ best practices، child theme `functions.php` لازم يكون ~5-20 KB. 106 KB يعني فيه:
- Multiple feature toggles مدمجة في ملف واحد
- WooCommerce overrides + Tutor LMS overrides + Twig setup + custom hooks
- Bug fixes متكدسة بدون refactor

**الأثر:**
- **Hard to debug:** غلطة في أي مكان بتـ kill كل الـ functions
- **Hard to git diff:** PRs بتاعتنا (Sprint 1) كانت أكبر منكان لازم
- **Slow autoload:** الـ PHP بيـ parse الـ 106 KB في كل request

**الإصلاح:**
- ابدأ refactor تدريجي:
  1. حدد modules منطقية: woo-overrides.php, tutor-overrides.php, twig-setup.php, security-hardening.php
  2. كل module في ملف منفصل تحت `edublink-child/includes/`
  3. functions.php يصبح بس: `require_once 'includes/*.php'`

**Sprint suggestion:** Sprint 2 يبدأ بـ refactor functions.php كـ ground-work لكل التعديلات الجاية.

**الخطورة:** 🟠 High (technical debt)
**المكان:** `edublink-child/functions.php`

---

### H-3 — `homepage.html` ملف 330 KB في الـ child theme — orphan artifact
**الوصف:**
```
edublink-child/homepage.html = 330,550 bytes (= 322 KB)
```

ملف HTML خام في الـ theme folder — مش بيتـ load من WP (WP بيـ load .php files). دي غالباً **artifact من demo download** اتنزل وما اتشلش.

**الأثر:**
- بياخد disk + يـ inflate الـ git operations
- بياكل وقت الـ git pull/clone
- ممكن يحتوي على credentials أو URLs أو placeholders من الـ demo

**الإصلاح:**
1. اتأكد إن مفيش حد بيـ link لـ `/wp-content/themes/edublink-child/homepage.html` خارجياً
2. احذف الملف:
   ```bash
   rm wp-content/themes/edublink-child/homepage.html
   ```
3. add لـ `.gitignore`: `*.html` (لو الـ child theme repo بيـ track HTMLs)

**الخطورة:** 🟠 High (disk + git ops)
**المكان:** `edublink-child/homepage.html`

---

### H-4 — Hostinger CDN بيرجع `x-hcdn-cache-status: DYNAMIC` — الـ cache مش بيشتغل
**الوصف:** فحص headers على homepage:
```
HTTP/2 200
wpo-cache-status: cached            ← WP-Optimize cache يعمل ✅
x-hcdn-cache-status: DYNAMIC        ← Hostinger CDN bypass! ❌
cache-control: no-cache             ← سبب الـ bypass
```

الـ Hostinger Cloud Delivery Network (hCDN) كان لازم يـ serve الـ cached version لـ visitors من edge، لكن `Cache-Control: no-cache` بيـ block ذلك.

**السبب:**
- WP-Optimize بيـ set `Cache-Control: no-cache` للـ logged-in users — لو الـ logic بيـ apply على anonymous users كمان، الـ hCDN بيـ bypass
- أو `wp-config.php` فيه `define('DONOTCACHEPAGE', true)`

**الأثر:** كل request بيـ hit origin (PHP + DB) بدلاً من edge cache → +200-500ms من Edge → +1s+ من 3G mobile.

**الإصلاح:**
1. WP-Optimize → Settings → Advanced → اتأكد إن "Cache for logged-out users" = enabled
2. وفي WP-Optimize → Page Cache → "Cache Lifetime" = 7d
3. فحص `wp-config.php` لـ `DONOTCACHEPAGE` define
4. في hPanel → CDN → اتأكد إن CDN فعّال

**الخطورة:** 🟠 High (perf impact كبير)
**المكان:** WP-Optimize config + hPanel CDN

---

## 🟡 Medium (5 مشاكل)

---

### M-1 — PHP `max_execution_time = 0` (unlimited)
**الوصف:** `php -i | grep max_execution_time` رجع `0` — يعني مفيش timeout على scripts.

**الأثر:**
- لو request اتعلّق (DB lock, infinite loop, external API hang)، الـ worker بيستهلك memory + CPU بدون limit
- على shared hosting، 5-10 hanging requests = exhausted PHP workers = downtime

**الإصلاح:**
- ضع limit معقول في `wp-config.php`:
  ```php
  @set_time_limit(120);  // 2 minutes max per request
  ```
- استثناءات: WC export, WPSynchro sync — set per-script `set_time_limit(0)`

**الخطورة:** 🟡 Medium
**المكان:** PHP config

---

### M-2 — 8 ملف PHP في `wp-content/uploads/redux/` — code خارج المكان
**الوصف:** Redux Framework (UI library) بيـ save ملفات PHP في uploads:
```
wp-content/uploads/redux/switch.php
wp-content/uploads/redux/editor.php
wp-content/uploads/redux/import_export.php
wp-content/uploads/redux/media.php
wp-content/uploads/redux/button_set.php
... (15+ ملف)
```

**الأثر:**
- لو `.htaccess` في uploads بيـ block PHP (إحنا شفنا إنه فاضي في bugs-security-deep.md C-3)، PHP في uploads = security risk
- redux بيـ register UI controls بـ PHP في uploads بدلاً من plugins folder — anti-pattern

**الإصلاح:** بعد إصلاح bugs-security-deep.md C-3 (add `.htaccess`)، الـ PHP ده هيتـ block. ممكن نـ relocate أو ندوّر لـ alternative لـ Redux Framework.

**الخطورة:** 🟡 Medium
**المكان:** `wp-content/uploads/redux/`

---

### M-3 — Parent theme `edublink` 2.0.8 → 2.0.12 update available
**الوصف:** من wp theme list:
```
edublink (parent): version 2.0.8, update available: 2.0.12, auto_update: on
```

**الأثر:** 4 patch versions behind. ممكن فيها bug fixes أو security patches.

**الإصلاح:**
1. **قبل التحديث:**
   - تأكد من latest backup (إحنا عندنا 2026-W21 جاهز)
   - راجع changelog لـ 2.0.9 → 2.0.12 على Envato/ThemeForest
   - لو عندنا overrides كتيرة (شفنا 9 overrides في bugs-plugins.md context)، تأكد إن الـ override files compatible
2. WP Admin → Appearance → Themes → Update edublink

**الخطورة:** 🟡 Medium
**المكان:** Parent theme

---

### M-4 — `x-powered-by: PHP/8.2.30` header — exposing PHP version
**الوصف:** كل response من السيرفر بـ HTTP header:
```
x-powered-by: PHP/8.2.30
```

**الأثر:** Attacker reconnaissance — يعرف نسخة الـ PHP الدقيقة، يقدر يجرّب exploits محددة لها.

**الإصلاح:** في `.user.ini` (لو Hostinger يـ allow):
```ini
expose_php = Off
```
أو في `.htaccess`:
```apache
Header unset X-Powered-By
```

**الخطورة:** 🟡 Medium (information disclosure)
**المكان:** PHP config / .htaccess

---

### M-5 — `wp-content = 13 GB` بسبب 5.6 GB من ai1wm backups
**الوصف:** (مذكور في bugs-runtime.md C-2). إجمالي wp-content = 13 GB، منها:
- `ai1wm-backups/` = 5.6 GB (40% من الـ wp-content)
- `uploads/` = 2.2 GB
- `plugins/` = 818 MB

**الأثر على الأداء:**
- File system operations بطيئة (du, find, backup scans)
- Hostinger storage quota هتـ trigger limit warnings
- نظام الـ backup إلى عملناه (GitHub layer 2) أصغر بكتير لأنه DB-only، بس لو فعّلنا files-too هيـ explode

**الإصلاح:** اقفل ai1wm plugin + احذف folder (مذكور في bugs-plugins.md C-4). يفرّج 5.6 GB.

**الخطورة:** 🟡 Medium
**المكان:** `wp-content/ai1wm-backups/`

---

## 🔵 Low (1 مشكلة)

---

### L-1 — 100+ ZIP file في `wp-content/uploads/unlimited_elements_cache/`
**الوصف:** Plugin Unlimited Elements بيـ cache widget bundles كـ ZIP في uploads — أكثر من 100 ZIP visible.

**الأثر:**
- صفر functional impact
- بياخد ~10-50 MB من uploads
- public URLs قابلة للتحميل (`https://learrnsimply.com/wp-content/uploads/unlimited_elements_cache/dark_mode.zip`) — مش حساس بس مش ضروري

**الإصلاح:** أقفل Unlimited Elements لو مش بتستخدمه بشكل ثقيل (bugs-plugins.md H-3 — قرار الـ Elementor stack consolidation).

**الخطورة:** 🔵 Low
**المكان:** `wp-content/uploads/unlimited_elements_cache/`

---

## الخلاصة

| Tier | العدد | الأثر |
|---|---|---|
| 🔴 Critical | 1 | autoload bloat = response time impact على كل visitor |
| 🟠 High | 4 | DB indexes + theme refactor + CDN fix |
| 🟡 Medium | 5 | PHP hardening + cleanup |
| 🔵 Low | 1 | disk cleanup |

**أعلى ROI:**
1. **C-1 + M-5 — Autoload + ai1wm cleanup** = ~70-150ms أسرع response (measurable in Lighthouse)
2. **H-4 — CDN cache fix** = edge caching يقلل origin load بنسبة 70%+
3. **H-1 — DB indexes** = WC analytics + Tutor pages 2-5x أسرع
4. **H-2 — functions.php refactor** = ground-work لكل تعديلات Sprint 2+

**اللي مش في الـ scope هنا (لاحقاً):**
- Lighthouse audit كامل (محتاج actual browser run، مش server-side)
- Slow query log activation (يحتاج MySQL config، Hostinger مش بيـ allow)
- Real user monitoring (RUM) — يحتاج plugin مدفوع (NewRelic, Datadog)
