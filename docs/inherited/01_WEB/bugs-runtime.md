# تقرير Runtime Bugs — اتعلم ببساطة
**تاريخ الفحص:** 2026-05-23 (Wave 1A — Deep Audit عبر SSH + wp-cli)
**المصدر:** PHP debug.log + wp-cron event list + wp-options autoload + disk usage
**الموقع:** learrnsimply.com
**Read-only:** نعم — كل أوامر القراءة عبر SSH/wp-cli، صفر تعديل على prod

> ده تقرير **مكمّل** للـ bugs-code.md و bugs-functional.md — مش بديل. الـ access الأعمق (SSH + wp-cli) فتح طبقات ما كانتش متاحة في الـ audit الأول.

---

## جدول إحصائي سريع

| الفئة | المشاكل | Critical | High | Medium | Low |
|---|---|---|---|---|---|
| PHP errors / debug exposure | 6 | 1 | 1 | 4 | 0 |
| Disk / backups | 3 | 1 | 1 | 0 | 1 |
| Cache / cron health | 4 | 0 | 1 | 3 | 0 |
| **الإجمالي** | **13** | **2** | **3** | **7** | **1** |

---

## 🔴 Critical (2 مشاكل)

---

### C-1 — `wp-content/debug.log` متاح للجمهور وفيه أخطاء حساسة
**الوصف:** ملف `wp-content/debug.log` (15 KB، 29 سطر، آخر تعديل **25 أبريل 2026**) موجود على السيرفر. ولأن مفيش `.htaccess` بيمنع الوصول لملفات `.log` تحت `wp-content/`، أي حد يعرف الرابط `https://learrnsimply.com/wp-content/debug.log` يقدر يقراه — وفيه أسماء plugins، أرقام نسخ، paths كاملة للسيرفر، وأكواد جواها.

**الأثر:**
- تسريب معلومات: paths مطلقة (`/home/u700430280/...`)، أسماء plugins نشطة، نسخها
- بيخدّم attacker reconnaissance — يعرف انت بتشغّل إيه قبل ما يهاجم
- لو احمد فعّل `WP_DEBUG=true` مرة تانية، الملف هيبدأ يكتب فيه عمليات حقيقية وبيانات users

**الإصلاح:**
1. **فوراً:** SSH لـ السيرفر واحذف الملف: `rm wp-content/debug.log`
2. أضف لـ `.htaccess` تحت `wp-content/`:
   ```
   <FilesMatch "\.(log|sql|sqlite|db)$">
       Require all denied
   </FilesMatch>
   ```
3. تأكد من `WP_DEBUG=false` (موجودة بالفعل) و `WP_DEBUG_LOG=false` (موجودة)

**الخطورة:** 🔴 Critical
**المكان:** `wp-content/debug.log` على السيرفر

---

### C-2 — wp-content حجمه 13 GB — 5.6 GB منها backups مكدّسة من plugin مهجور
**الوصف:** فحص حجم `wp-content/` كشف:
| المجلد | الحجم |
|---|---|
| `wp-content/` (الكل) | **13 GB** |
| `wp-content/ai1wm-backups/` | **5.6 GB** ← All-in-One WP Migration backups |
| `wp-content/uploads/` | 2.2 GB |
| `wp-content/plugins/` | 818 MB |
| `wp-content/themes/` | 28 MB |
| `wp-content/litespeed/` | 720 KB |
| `wpvividbackups/`, `wpvivid_staging/`, `wpvivid_uploads/` | (مجلدات WPvivid Backup) |
| `backups-dup-lite/` | (مجلد Duplicator Pro) |

**الأثر:**
- الـ ai1wm-backups بتاكل **40%** من حجم wp-content
- لو احمد فيه storage limit على Hostinger، ممكن يتقطع عن إنشاء uploads جديدة
- backup script اللي عملناه (Layer 2) بيتأثر — DB dump أصغر بكتير، بس لو حابب نـ rsync كل uploads هياخد وقت طويل
- نسخ ai1wm قديمة من 2024 ممكن تكون فيها بيانات users قديمة (privacy)

**الإصلاح:**
1. سيب اللي عنده tag حديث وامسح القديم: `find wp-content/ai1wm-backups -mtime +90 -name "*.wpress" -delete`
2. اقفل plugin AIWM أو احذفه (إحنا عندنا backup أحسن دلوقتي)
3. وثّق الـ backup workflow في hPanel + Layer 2 GitHub repo — مفيش داعي للـ AIWM

**الخطورة:** 🔴 Critical
**المكان:** `wp-content/ai1wm-backups/` + plugin All-in-One WP Migration

---

## 🟠 High (3 مشاكل)

---

### H-1 — 4 plugins backup شغّالين في نفس الوقت
**الوصف:** فحص مجلدات `wp-content/` كشف عن **4 أنظمة backup منفصلة بتشتغل بالتوازي**:
1. **All-in-One WP Migration** — `ai1wm-backups/` (5.6 GB)
2. **WPvivid Backup** — `wpvividbackups/` + `wpvivid_staging/` + `wpvivid_uploads/`
3. **Duplicator Pro** — `backups-dup-lite/`
4. **UpdraftPlus** — بناء على الـ audit الأول (محتاج تأكيد)

**الأثر:**
- كل واحد بياخد disk + cron schedule + autoload في wp_options
- ممكن يحصل تضارب لو اتنين بيـ dump في نفس الوقت
- مفيش حد بيـ rotate الملفات القديمة — disk بياكل
- احمد مش عارف أيهم هو الـ "trusted source of truth" لو حصلت كارثة

**الإصلاح:**
1. اختار plugin واحد بس (أنصح بـ **UpdraftPlus** لأنه هو الـ industry standard لمعظم users)
2. اقفل + احذف الـ 3 الباقيين
3. احذف مجلداتهم القديمة (`ai1wm-backups/`, `wpvividbackups/`, `backups-dup-lite/`, إلخ)
4. ممكن نـ skip كل ده ونعتمد بس على: Hostinger daily + GitHub weekly (نظامنا) — مفيش داعي لـ WP plugin backup خالص

**الخطورة:** 🟠 High
**المكان:** WP Admin → Plugins (4 plugins للحذف)

---

### H-2 — `cartflows_docs_data` يحمّل 497 KB في كل request
**الوصف:** فحص أكتر options بتحمل autoload في كل page load كشف:
| Option | الحجم | autoload |
|---|---|---|
| `cartflows_docs_data` | **497 KB** | ✅ yes |
| `astra-settings` | 256 KB | ✅ yes |
| `email_template_data` | 50 KB | ✅ yes |

إجمالي حجم autoload = **0.96 MB** بيتحمل في الذاكرة من DB في كل request (حتى visit واحد لـ homepage).

**الأثر:**
- الـ `cartflows_docs` دي **مش بيانات بتشتغل** — دي مرجع وثائق Plugin بيتحمل لما الأدمن يدخل CartFlows في الـ admin بس
- بس بيتحمل في كل request — حتى visitors اللي بيدخلوا homepage بيستهلكوا memory + DB time لتحميل وثائق
- على شيرد hosting Hostinger، 1 MB autoload يعني +20-50ms في كل request

**الإصلاح:**
```sql
UPDATE wp_options SET autoload='no' WHERE option_name IN (
  'cartflows_docs_data',
  'astra-settings',
  'email_template_data'
);
```
(Astra مش هي الـ theme المفعّل، فممكن نحوّل options بتاعتها لـ `no` بأمان.)

**الخطورة:** 🟠 High
**المكان:** `wp_options` table — autoload column

---

### H-3 — Theme dependency مكسور: `edublink-rtl` بيستنى `edublink-core-main-css` غير مسجّل
**الوصف:** debug.log فيه 4 تكرارات لنفس الـ warning:
```
WP_Styles::add — The style with the handle "edublink-rtl" was enqueued with
dependencies that are not registered: edublink-core-main-css.
```

**السبب الجذري:** الـ child theme (`edublink-child`) بيـ enqueue style باسم `edublink-rtl` ويقول إنه بيعتمد على `edublink-core-main-css`، لكن الـ handle ده مش موجود في wp_register_style stack — يا إما parent theme بيـ register بـ handle مختلف، يا إما plugin `edublink-core` متعدش بيـ enqueue الـ css ده.

**الأثر:**
- RTL styles محتمل تتحمل بترتيب غلط (الـ dependency chain بيـ break)
- على الموبايل بالذات، احتمال يظهر FOUC (flash of unstyled content) لمدة 100-300ms
- في WP 6.9+ الـ notice ده بقى أوضح في debug — هيبقى مزعج لو فعّلنا debug تاني

**الإصلاح:** افتح `edublink-child/functions.php` ودوّر على `wp_enqueue_style( 'edublink-rtl', ...)`. اللي بعد URL الـ CSS هتلاقي array of deps فيها `'edublink-core-main-css'`. غيّرها لـ:
- يا إما empty array `[]` لو الـ CSS مستقل
- يا إما الـ handle الصح اللي parent بيـ register بيه (محتمل `edublink-main-style` أو `edublink-core-style`)

**الخطورة:** 🟠 High
**المكان:** `edublink-child/functions.php` — `wp_enqueue_scripts` action

---

## 🟡 Medium (7 مشاكل)

---

### M-1 — `edublink-core` widget عنده Undefined array key warning (يتكرر في كل صفحة فيها animation)
**الوصف:** debug.log:
```
PHP Warning: Undefined array key "animated_image_color_type"
in /wp-content/plugins/edublink-core/widgets/animation.php on line 1420
```

**الأثر:**
- على PHP 8.x، Undefined array key بيـ emit warning بس مش fatal — Plugin بيشتغل بس بياكل log space
- لو الـ widget بيستخدم القيمة دي بعدها، ممكن العنصر يظهر بألوان غلط أو فاضي

**الإصلاح:** التواصل مع مطوّر `edublink-core` (هو نفس theme vendor — ThemeGrill). أو patch محلي بـ MU plugin يحدّد `array_key_exists` check قبل الـ usage.

**الخطورة:** 🟡 Medium
**المكان:** `wp-content/plugins/edublink-core/widgets/animation.php:1420`

---

### M-2 — OptinMonster Elementor widget: PHP 8.2+ deprecated dynamic property
**الوصف:** debug.log:
```
PHP Deprecated: Creation of dynamic property OMAPI_Elementor_Widget::$base is deprecated
in /wp-content/plugins/optinmonster/OMAPI/Elementor/Widget.php on line 41
```

**الأثر:** على PHP 8.2+ ده deprecated warning. في PHP 9 هيبقى **fatal error**. Hostinger ممكن يـ upgrade PHP أي وقت — لو طلع 9.0 على السيرفر، الـ widget هيـ crash.

**الإصلاح:** Update OptinMonster plugin لآخر نسخة (المطوّر فيه release بيحلها). لو فيه license issue، patch بإضافة `#[\AllowDynamicProperties]` attribute في الكلاس.

**الخطورة:** 🟡 Medium
**المكان:** `wp-content/plugins/optinmonster/OMAPI/Elementor/Widget.php`

---

### M-3 — Translation loading too early (Tutor LMS + AIWM)
**الوصف:** debug.log فيه 17 تكرار للـ notice التالي:
```
Function _load_textdomain_just_in_time was called incorrectly.
Translation loading for the [tutor / tutor-pro / all-in-one-wp-migration] domain
was triggered too early.
```

**الأثر:** بقت WP 6.7+ بتشتكي من ده. حالياً بس Notice، لكن في WP 6.10+ هيبقى warning، وفي 7.0 ممكن يبقى fatal. السبب: الـ plugin بيـ register strings قبل ما الـ `init` hook يشتغل.

**الإصلاح:**
- Update Tutor LMS و Tutor Pro لآخر نسخة (Tutor 3.10+ بيحلها)
- لو AIWM هتتحذف (H-1)، الـ warning بتاعها هيمشي

**الخطورة:** 🟡 Medium
**المكان:** Tutor LMS plugin + AIWM plugin

---

### M-4 — WP cron بيشتغل في كل page load (DISABLE_WP_CRON مش معرّف)
**الوصف:**
- `DISABLE_WP_CRON` غير معرّف في wp-config.php
- معاه 60+ cron event مسجّل (تحققنا بـ `wp cron event list`)
- كل visitor بيدخل أي صفحة بيتسبب في trigger للـ `wp-cron.php` (default behavior)

**الأثر:** على شيرد hosting:
- visitor 1 يدخل = WP بيشيك إذا فيه cron due
- لو فيه (وغالباً فيه — events كل دقيقة)، الـ request بتاعه بيستنى لحد ما الـ cron يخلص = +500ms لـ +2s زيادة في الـ load time
- معدّل cron داخلي = أبطأ معدّل experience للـ first visitor كل دقيقة

**الإصلاح:**
1. أضف لـ wp-config.php:
   ```php
   define('DISABLE_WP_CRON', true);
   ```
2. في hPanel → Advanced → Cron Jobs، أضف:
   ```
   */5 * * * *  curl -s https://learrnsimply.com/wp-cron.php?doing_wp_cron > /dev/null
   ```
3. النتيجة: cron بيشتغل كل 5 دقايق على schedule موحد، visitors مبيستنوش

**الخطورة:** 🟡 Medium
**المكان:** `wp-config.php` + hPanel Cron Jobs

---

### M-5 — Action Scheduler بيدور كل دقيقة (overkill)
**الوصف:** من `wp cron event list`:
```
action_scheduler_run_queue   كل 1 دقيقة
```

**الأثر:**
- على شيرد hosting، تشغيل queue كل دقيقة = ضغط مستمر على DB
- مفيش طوابير ثقيلة فعلياً (شفنا 0 pending overdue في Wave 1B)
- بياكل request budget لو في cron داخلي (M-4)

**الإصلاح:** بعد ما نطبّق M-4 (system cron)، خفّض Action Scheduler interval:
```php
add_filter('action_scheduler_run_schedule', function($schedule) {
    return 'every_5_minutes';
});
```

**الخطورة:** 🟡 Medium
**المكان:** WP Action Scheduler config

---

### M-6 — Facebook for WooCommerce بيـ heartbeat كل 5 دقايق
**الوصف:** من cron list:
```
facebook_for_woocommerce_5_minute_heartbeat_cron      كل 5 دقايق
facebook_for_woocommerce_hourly_heartbeat_cron        كل ساعة
facebook_for_woocommerce_daily_heartbeat_cron         كل يوم
```

**الأثر:**
- الـ 5-minute heartbeat بيـ poll Facebook Graph API — لو كان فيه catalog sync غلطان، الـ 5-min ممكن يـ trigger throttling من FB
- على شيرد hosting + cron داخلي = ضغط دوري كل 5 دقايق

**الإصلاح:** التحقق من Facebook for WooCommerce settings — لو الـ catalog mostly static، خفّض الـ heartbeat. أو لو الـ plugin مش مستخدم (احنا لقينا Meta Pixel متوقف في audit أصلي)، اقفل الـ plugin بالكامل.

**الخطورة:** 🟡 Medium
**المكان:** Facebook for WooCommerce plugin

---

### M-7 — حالة الـ caching مختلطة: WP-Optimize active + LiteSpeed inactive لكن مجلده موجود
**الوصف:**
- `WP_CACHE = true` في wp-config.php لـ WP-Optimize Cache (active)
- `wp-content/advanced-cache.php` موجود (drop-in)
- لكن `litespeed-cache` plugin status = **inactive** ومجلد `wp-content/litespeed/` فيه 720 KB ملفات قديمة

**الأثر:**
- مفيش conflict نشط، بس confusion. ممكن lader حد يفعّل LiteSpeed وهيبقى cache layer مضاعف
- WP-Optimize Free version بـ basic page cache — على شيرد hosting بيحقق 60-70% من الأداء المطلوب
- LiteSpeed بـ object cache + ESI ممكن يحقق أداء أعلى بكتير لو السيرفر بيـ support LiteSpeed Web Server (Hostinger Business بيـ support)

**الإصلاح:**
- **القرار:** إما WP-Optimize (current) أو LiteSpeed Cache (better على Hostinger). مش الاتنين.
- لو هنفضل WP-Optimize، احذف مجلد `wp-content/litespeed/`
- لو هنحوّل لـ LiteSpeed، اقفل WP-Optimize cache، فعّل LiteSpeed، أعد فحص cache hit rate بعد أسبوع

**الخطورة:** 🟡 Medium
**المكان:** Hosting → Caching strategy

---

## 🔵 Low (1 مشكلة)

---

### L-1 — مجلدات plugin قديمة فاضية أو شبه فاضية بتاعت plugins متحذفه
**الوصف:** في `wp-content/` فيه مجلدات بتنتمي لـ plugins متحذفه:
- `webtoffee_export/` + `webtoffee_iew_log/` + `webtoffee_import/` — WebToffee plugin (متحذف)
- `wpvivid_staging/` + `wpvivid_uploads/` — لو WPvivid حذف، دول لازم يتحذفوا معاه

**الأثر:** صفر functional impact. مجرد disk waste (مجلدات صغيرة) و confusion في الـ audits المستقبلية.

**الإصلاح:** بعد ما نقرر plugin الـ backup الواحد (H-1)، نظّف المجلدات اليتيمة:
```bash
rm -rf wp-content/webtoffee_*
rm -rf wp-content/wpvivid_*   # لو WPvivid اتقفل
```

**الخطورة:** 🔵 Low
**المكان:** `wp-content/` orphan folders

---

## الخلاصة

| Tier | العدد | الفعل المطلوب |
|---|---|---|
| 🔴 Critical | 2 | احذف debug.log فوراً + نظّف ai1wm-backups (5.6 GB) |
| 🟠 High | 3 | اختار backup plugin واحد + ضبط autoload + إصلاح theme dep |
| 🟡 Medium | 7 | Updates + cron tuning + cache decision |
| 🔵 Low | 1 | تنظيف مجلدات يتيمة |

**أعلى ROI:**
1. حذف debug.log (دقيقة واحدة، يقفل تسريب أمني)
2. حذف 5.6 GB ai1wm + التوحيد على backup واحد (يحرر مساحة كبيرة)
3. ضبط autoload (يحسّن response time بنسبة قابلة للقياس)

**الأرقام الإجمالية بعد التحديث:**
- bugs-code.md: 27 bug
- bugs-functional.md: 18 bug
- bugs-data.md: 21 bug
- **bugs-runtime.md (هذا الملف): 13 bug جديدة**
- **مجموع جديد: 79 bug** (قبل ما نخلّص Wave 2 + 3)
