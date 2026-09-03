# تقرير Plugin Matrix Bugs — اتعلم ببساطة
**تاريخ الفحص:** 2026-05-23 (Wave 2A — Plugin Matrix + Orphan Tables)
**المصدر:** `wp plugin list` + `du` على wp-content/plugins + DB tables map
**الموقع:** learrnsimply.com

> فحص شامل لـ **61 plugin** (56 active + 3 inactive + 2 must-use). الـ audit الأول قال 55 plugin — العد الصحيح أعلى لأنه كان فاكر mu-plugins وdropins.

---

> ⚠️ **تحديث 2026-05-23 (إعادة فحص قبل التنفيذ):** C-1 (Kashier) **اتعدّل من Critical لـ Low** بعد verification. الـ plugin شغّال فعلياً وبيـ process payments (102 successful orders في آخر 30 يوم). الاسم بـ "-master" مش كان indicator لمشكلة — هو طريقة Kashier الرسمية في الـ distribution عبر GitHub. تفاصيل تحت C-1 المعدّل.

## جدول إحصائي سريع (محدّث بعد إعادة الفحص)

| الفئة | المشاكل | Critical | High | Medium | Low |
|---|---|---|---|---|---|
| Payment & Critical Infra | 1 | 0 | 0 | 0 | 1 |
| Duplicate functionality | 4 | 1 | 3 | 0 | 0 |
| Security-risk plugins | 3 | 0 | 2 | 1 | 0 |
| Orphan plugin tables | 4 | 2 | 0 | 2 | 0 |
| Possibly-abandoned plugins | 5 | 0 | 0 | 3 | 2 |
| **الإجمالي** | **17** | **3** | **5** | **6** | **3** |

**الفرق عن النسخة الأولى:**
- Critical: 4 → 3 (C-1 Kashier اتعدّل لـ Low)
- Low: 2 → 3

---

## 📝 New Finding 2026-05-24 (deeper Task 2.9 audit)

**Airlift plugin orphan cache:** `wp-content/uploads/al_opt_content/` = **236 MB** of stale optimization cache from Airlift plugin (currently inactive). Last write: 2025-10-21 (~7 months ago). Plugin folder still in `wp-content/plugins/airlift/`. Will be cleaned naturally when Phase 3.2a deactivates+deletes airlift, but the disk recovery is significant — bumping `Phase 3.8 disk cleanup` (currently lists ai1wm 5.6 GB) to also include this folder.

**Effective state correction (lesson learned):**
- ~~wpsynchro-69e64efa3d42c/~~ flagged as "suspicious uploads PHP files" → **ACTIVE plugin v1.14.0**, hash is install ID not malware marker
- ~~redux/~~ class files → Redux Framework v4.5.11 ACTIVE (these are framework UI definitions)
- ~~wpforms/~~ cache → WPForms Lite v1.10.0.5 ACTIVE
- ~~wpo/~~ logs → WP-Optimize v4.5.3 ACTIVE
- Only truly orphan: `mailpoet/` (plugin uninstalled, 27-byte defensive index.php)

See `../lessons.md` 2026-05-24 entry "Re-verify active plugin list before flagging folders as suspicious".

---

## 🔴 Critical (3 مشاكل)

---

### C-1 (CORRECTED، downgrade لـ Low) — Kashier Plugin = official distribution عبر GitHub
**الوصف الأصلي (غلط):** "Kashier downloaded from GitHub master branch، مش official، السبب وراء 909 فشل CC"

**اللي اتأكدنا منه فعلياً:**
- اسم الـ plugin: `Kashier-WooCommerce-Plugin-master` ✓
- **Plugin URI في الـ header:** `https://github.com/Kashier-payments/Kashier-WooCommerce-Plugin/` — **ده repo Kashier الرسمي**
- **Author:** Kashier
- **Version:** 1.3.0
- "-master" في اسم الـ folder = طبيعة GitHub ZIP downloads (مش بـ signature لـ issue)
- **Kashier بتـ distribute عبر GitHub** (مش WordPress.org) — ده الـ official channel بتاعهم
- **Live API keys مضبوطين**: merchant_id `MID-25505-543`، test mode = no
- **102 completed order في آخر 30 يوم** (75 card + 27 wallet = 57K EGP processed)
- أحدث completed order: 2026-05-22 22:49 (امبارح)

**الـ Real Risks الحقيقية (Low severity):**
1. **مفيش auto-update من admin UI** — لو Kashier طلعوا 1.4.0، Ahmed لازم يـ download manually من GitHub
2. **اسم الـ folder بـ "-master" مربك** — أي audit مستقبلي ممكن يـ flag-ها كـ red flag (زي ما حصل دلوقتي)

**الإصلاح المقترح (Low priority cleanup):**
1. **Rename الـ folder** لـ `kashier-woocommerce-plugin/` (شيل الـ -master suffix) عشان اسم نظيف
2. **Subscribe لـ GitHub releases** على `Kashier-payments/Kashier-WooCommerce-Plugin` عشان يوصل notification لما release جديد ينزل
3. **Document في brand CLAUDE.md** إن الـ plugin ده official Kashier — مش red flag

**الخطورة:** 🔵 **Low** (downgraded من Critical) — naming inconsistency فقط، الـ plugin يشتغل تماماً

**ما يخص الـ cancel rate الحقيقي:** راجع `bugs-integrity.md C-2 (المعدّل)` — الـ optimization opportunity موجود لكنه مش plugin migration. الـ approach الجديد: تفعيل التقسيط + Apple Pay/Google Pay + cart recovery للـ future abandonment.

**Verification 2026-05-23:** SSH أكّد إن Kashier processed 102 successful orders في آخر 30 يوم بـ 57K EGP.

---

### C-2 — MailPoet uninstalled لكن 20+ table متروكة في الـ DB (~5 MB)
**الوصف:** فحص الـ DB tables كشف:
```
wp_mailpoet_automations
wp_mailpoet_automation_runs
wp_mailpoet_automation_run_logs
wp_mailpoet_automation_run_subjects
wp_mailpoet_automation_triggers
wp_mailpoet_automation_versions
wp_mailpoet_custom_fields
wp_mailpoet_dynamic_segment_filters
wp_mailpoet_feature_flags
wp_mailpoet_forms
wp_mailpoet_log
wp_mailpoet_migrations
wp_mailpoet_newsletters
wp_mailpoet_newsletter_links
wp_mailpoet_newsletter_option
wp_mailpoet_newsletter_segment
wp_mailpoet_newsletter_templates  (2.52 MB!)
wp_mailpoet_scheduled_tasks
wp_mailpoet_segments
wp_mailpoet_settings
wp_mailpoet_statistics_clicks
wp_mailpoet_statistics_newsletters
wp_mailpoet_statistics_opens
wp_mailpoet_subscribers  (0.86 MB)
wp_mailpoet_subscriber_segment
... (20+ tables)
```

لكن MailPoet **مش في** active/inactive plugin list. يعني الـ plugin اتحذف بدون "cleanup on uninstall".

**الأثر:**
- **Privacy/GDPR:** `wp_mailpoet_subscribers` فيه إيميلات + الـ subscription status لمستخدمين قدامى
- `wp_mailpoet_statistics_*` فيه click/open tracking قديم — بيانات شخصية
- لو حد عمل export للـ DB (إحنا عملنا!)، المعلومات دي بتنتقل
- بيشغّل مساحة DB بلا فايدة

**الإصلاح:**
1. **قبل ما نمسح:** اعمل export لـ `wp_mailpoet_subscribers` (إيميلات المشتركين القدامى) — لو فيهم ناس مش في الـ 13,154 subscribers الحاليين، ممكن نـ re-import في FluentCRM أو Mautic لاحقاً
2. **drop الـ tables:**
   ```sql
   SELECT CONCAT('DROP TABLE IF EXISTS `', table_name, '`;') FROM information_schema.tables
   WHERE table_schema=DATABASE() AND table_name LIKE 'wp_mailpoet_%';
   ```
   (شغّل النتيجة كـ statements)

**الخطورة:** 🔴 Critical
**المكان:** DB orphan tables — `wp_mailpoet_*`

---

### C-3 — BlogVault Airlift "inactive" لكن tables بـ 6+ MB
**الوصف:** Plugin `airlift 6.23` = **inactive**. لكن في DB tables:
- `wp_bv_airlift_config` = 3.5 MB
- `wp_bv_airlift_stats` = 2.5 MB
- `wp_bv_dynamic_sync` = 0.47 MB

BlogVault Airlift هي خدمة من BlogVault للـ caching/CDN. الـ plugin inactive يعني مش بيشتغل بس البيانات لسه موجودة.

**الأثر:**
- 6+ MB DB bloat
- لو الـ plugin اتفعّل غلطاً مرة تانية، هيبدأ يـ sync بيانات قديمة لـ BlogVault cloud (privacy)
- الـ `bv_dynamic_sync` table بيـ track changes في الـ posts — لو فعّل، هيـ sync كل حاجة جديدة

**الإصلاح:**
1. اتأكد إن الـ Airlift مش needed (احنا عندنا WP-Optimize كـ caching)
2. **حذف نهائي:** `wp plugin uninstall airlift`
3. drop tables:
   ```sql
   DROP TABLE IF EXISTS wp_bv_airlift_config;
   DROP TABLE IF EXISTS wp_bv_airlift_stats;
   DROP TABLE IF EXISTS wp_bv_dynamic_sync;
   ```

**الخطورة:** 🔴 Critical (data exfil risk if reactivated)
**المكان:** Plugin `airlift` + DB tables

---

### C-4 — 4 backup plugins active متوازي (تكرار + waste)
**الوصف:** (يكمّل bugs-runtime.md H-1). الـ active plugins بتحتوي على:
1. `all-in-one-wp-migration` 7.105 (active) — 5.6 GB backups
2. `wpvivid-backuprestore` 0.9.127 (active) — alpha version!
3. `duplicator` 1.5.16.1 (active)
4. `wpsynchro` 1.14.0 (active) — sync tool

بالإضافة لـ `backups-dup-lite` folder قديم.

**الأثر:**
- **WPvivid version 0.9.127** = pre-1.0 release — alpha-stage بيشتغل على production
- 4 cron schedules بيتنافسوا على نفس الـ resources
- `duplicator` مدفوع pro version غير منزّل، يعني free version basic capabilities

**الإصلاح:** بعد لـ نظام الـ backup إلي عملناه (Hostinger daily + GitHub weekly)، **مش محتاج plugin backup خالص**:
```bash
wp plugin uninstall all-in-one-wp-migration wpvivid-backuprestore duplicator
rm -rf wp-content/{ai1wm-backups,wpvividbackups,wpvivid_uploads,wpvivid_staging,backups-dup-lite}
```
**ده هيوفر ~6 GB من disk + يقفل 3 cron streams.**

**الخطورة:** 🔴 Critical (operational + disk)
**المكان:** 3 backup plugins + 5 stale folders

---

## 🟠 High (5 مشاكل)

---

### H-1 — `wp-file-manager 8.0.4` active على production (history of mass exploits)
**الوصف:** Plugin `wp-file-manager` = active. حجمه 23 MB. الـ plugin ده له تاريخ معروف:
- **CVE-2020-25213** (2020): RCE على 700,000+ موقع — اللي مكنوش حدّثوا اتسرّقوا كلهم
- بيـ allow admin يـ upload/edit/delete أي ملف من الـ WP admin UI
- Bypass لكل الـ "DISALLOW_FILE_EDIT" والـ "DISALLOW_FILE_MODS" constants

**النسخة الحالية 8.0.4 = آمنة** (آخر CVE-2024 اتصلحت في 7.1+)، **لكن:**
- لو admin account اتسرّق، الـ attacker عنده **full filesystem access** من admin UI
- مفيش audit log لـ file operations
- بيـ bypass الـ 2FA لو الـ admin اتسرّق

**الإصلاح:**
1. **اقفل الـ plugin** — مفيش داعي ليه يكون active دايماً
2. لو احمد محتاج file manager بشكل دوري، استخدم **Hostinger File Manager** من hPanel (محمي بـ Hostinger SSO + 2FA)
3. أو فعّل الـ plugin بس وقت الـ work، ثم اقفله

**الخطورة:** 🟠 High (latent — مفيش هجوم نشط، بس large attack surface)
**المكان:** `wp-content/plugins/wp-file-manager/`

---

### H-2 — `string-locator 2.6.7` active — Plugin بيـ allow ANY admin يـ edit أي PHP file
**الوصف:** Plugin string-locator بيوفر "search & replace across all files" + **file editor for any PHP file in WP** (themes, plugins, even wp-includes).

**الأثر:**
- بيـ bypass `DISALLOW_FILE_EDIT`
- لو admin compromised، الـ attacker يقدر يـ inject backdoor في أي plugin/theme من الـ UI
- مع `code-snippets` active كمان (PHP execution from admin)، هتلاقي 3 paths لـ arbitrary code:
  - `wp-file-manager` (H-1)
  - `string-locator` (H-2)
  - `code-snippets` (active — لـ Ahmed)

**الإصلاح:**
- لو احمد بيستخدم Ctrl+F في الـ admin يدور على strings، خليه يستخدم **WP-CLI** بدلاً منها:
  ```bash
  ssh learnsimply 'grep -rn "string-to-find" ~/domains/learrnsimply.com/public_html/wp-content/themes/edublink-child/'
  ```
- **اقفل الـ plugin** لما مش محتاجه

**الخطورة:** 🟠 High (latent attack surface)
**المكان:** `wp-content/plugins/string-locator/`

---

### H-3 — 6 Elementor extras active في نفس الوقت
**الوصف:** الـ active list فيها:
1. `elementor` 4.0.9 (93 MB — الأكبر!)
2. `elementor-pro` 4.0.4 (25 MB)
3. `royal-elementor-addons` 1.7.1062 (22 MB)
4. `unlimited-elements-for-elementor` 2.0.10 (33 MB — مع 100+ ZIPs في uploads)
5. `header-footer-elementor` 2.8.7 (12 MB)
6. `tutor-lms-elementor-addons` 3.0.2 (3 MB)

**إجمالي: 188 MB من الـ Elementor stack**

**الأثر:**
- **بطء editor:** كل widget من كل plugin بيتحمّل في الـ editor
- **بطء frontend:** كل واحد منهم بيـ register CSS/JS لو widget بتاعه مستخدم
- **conflicts:** widgets بنفس الاسم من plugins مختلفة بيكون فيها تعارض
- **حجم plugins/ folder = 818 MB** — نص الحجم من الـ Elementor stack

**الإصلاح:**
1. **افحص:** في Elementor → Tools → Replace URL → اعرف انت بتستخدم widgets من أي plugin فعلاً
2. لو 80%+ من الـ widgets من plugin واحد، اقفل البقية
3. **توصية:** سيب `elementor` + `elementor-pro` + `header-footer-elementor` (للـ header/footer override) — اقفل الباقي

**الخطورة:** 🟠 High (perf + maintenance complexity)
**المكان:** WP Admin → Plugins → Elementor stack

---

### H-4 — HPOS متوقف على WC 10.7 — لازم تتفعّل
**الوصف:**
- `woocommerce_custom_orders_table_enabled = no` (HPOS off)
- `woocommerce_custom_orders_table_data_sync_enabled = yes` (sync running both ways)
- WC version = 10.7.0 (HPOS مستقر من WC 8.2)
- في DB: `wp_wc_orders` فيها 4567 row + `wp_posts` فيها 4567 legacy `shop_order` row = **بيانات مكررة!**

**الأثر:**
1. **DB bloat:** كل order موجود مرتين (في الـ HPOS table + في wp_posts كـ legacy)
2. **Sync overhead:** Action Scheduler بيشغّل `woocommerce_custom_orders_table_background_sync` (شفناه 123 مرة في 7 أيام) — work مهدور
3. **Performance loss:** WC analytics + admin queries لسه بتعتمد على wp_posts (الـ legacy) بدل من wp_wc_orders (الأسرع بكتير)
4. **Future pain:** WC هيـ remove دعم الـ legacy في 11.x — لو ما اتـ enable، الـ migration هيكون painful بـ 4567 order

**الإصلاح:**
1. WP Admin → WooCommerce → Settings → Advanced → Features → فعّل HPOS
2. بعد ما يـ enable، WC هيـ verify الـ sync اكتمل
3. **ثم:** اقفل الـ sync (`woocommerce_custom_orders_table_data_sync_enabled = no`)
4. بعد أسبوعين بدون مشاكل: ممكن `wp wc shop_order delete` للـ legacy rows في wp_posts (احتياط: backup أولاً)

**الخطورة:** 🟠 High (perf + storage)
**المكان:** WC → Settings → Advanced → Features → HPOS

---

### H-5 — Duplicate functionality: 2 SVG plugins active
**الوصف:**
- `safe-svg` 2.4.0 (active) — sanitizes SVG uploads
- `svg-support` 2.5.14 (active) — enables SVG uploads with CSS support

**الأثر:**
- اختلاف في الـ sanitization rules — حقن SVG ممكن يـ bypass واحد ويتعالج بالتاني
- conflict في الـ MIME type registration
- 2 menu items في الـ admin لنفس الـ feature

**الإصلاح:** اختار واحد:
- **safe-svg** لو الأولوية = أمان (sanitization أحسن)
- **svg-support** لو الأولوية = ergonomic (CSS support للـ SVG animations في theme)
- اقفل التاني

**الخطورة:** 🟠 High (security: SVG هو vector معروف لـ XSS)
**المكان:** WP Admin → Plugins

---

## 🟡 Medium (6 مشاكل)

---

### M-1 — `conditional-add-to-cart 1.0.0` — version stuck at 1.0.0 (potentially abandoned)
**الوصف:** Plugin بنسخة 1.0.0 وما اتحدّثش. مش معروف من الـ vendor.

**الأثر:** لو الـ plugin مش بيتشتغل عليه، أي WP 7.x feature تكسر معاه = خطر طويل المدى.

**الإصلاح:** افتح الـ plugin folder، شوف الـ readme.txt — لو آخر تحديث > سنتين، استبدله بـ alternative active.

**الخطورة:** 🟡 Medium
**المكان:** `wp-content/plugins/conditional-add-to-cart/`

---

### M-2 — `wp-events-manager 2.2.4` — هل بتستخدم events؟
**الوصف:** Plugin events بيـ register `tp_event` post type (شفنا 29 published event في wp_posts breakdown).

**سؤال:** هل احمد فعلاً بيشغّل events دورياً؟ لو لأ، الـ plugin بـ tables + post type + admin UI بدون قيمة.

**الإصلاح:** افحص الـ 29 event — لو قديمة كلها (> سنة) ومحدش بيـ create جديد، اقفل الـ plugin + archive الـ tables.

**الخطورة:** 🟡 Medium
**المكان:** Plugin `wp-events-manager`

---

### M-3 — `aioseo_*` tables في الـ DB رغم إن rank-math هو الـ active SEO
**الوصف:**
- Plugins active: `seo-by-rank-math` + `seo-by-rank-math-pro`
- لكن DB فيه `wp_aioseo_posts` (2.59 MB) + `wp_aioseo_cache` + `wp_aioseo_notifications`

AIOSEO (All-in-One SEO) اتحذف لكن tables متروكة.

**الأثر:** نفس C-2 (Mailpoet) — DB bloat. ولو حد فعّل AIOSEO بالخطأ، هيشتغل مع rank-math في تضارب.

**الإصلاح:** drop الـ aioseo tables:
```sql
DROP TABLE IF EXISTS wp_aioseo_posts;
DROP TABLE IF EXISTS wp_aioseo_cache;
DROP TABLE IF EXISTS wp_aioseo_notifications;
```

**الخطورة:** 🟡 Medium
**المكان:** DB orphan tables

---

### M-4 — WooFunnels (bwf_/bwfan_) tables — Plugin مش في active list
**الوصف:**
```
wp_bwf_actions, wp_bwf_contact_fields, wp_bwf_conversion_tracking,
wp_bwfan_*  (15+ tables)
```

دي tables بتاعت WooFunnels Funnel Builder / Autonami (الاسم الأصلي). الـ plugin مش active، لكن TableS موجودة + جزء منها فيه bytes (>1 MB total).

**الأثر:** نفس النمط — orphan data من plugin اتحذف.

**الإصلاح:** drop tables بعد التأكد إن CartFlows الـ active مش بيستخدمها (CartFlows مالكها = WooFunnels، فممكن fields بتنتمي للـ ecosystem).

**الخطورة:** 🟡 Medium
**المكان:** DB orphan tables

---

### M-5 — `astra-sites 4.6.0` active — لـ import demo content
**الوصف:** Plugin بيـ allow استيراد demos من Astra/StarterTemplates. الـ folder = 25 MB. الـ DB فيها `ast-block-templates-sites-9/8/10` (~400 KB).

**سؤال:** هل احمد بيـ import templates دورياً؟ لو لأ، الـ plugin مش محتاج active.

**الإصلاح:** اقفل لما مش محتاجه. يفضّل ندخّل demos يدوياً من الـ admin UI لما هيبقى محتاج.

**الخطورة:** 🟡 Medium
**المكان:** Plugin `astra-sites`

---

### M-6 — `envato-market 2.0.14` — فقط لـ updates EduBlink theme
**الوصف:** plugin بـ Envato بـ purpose واحد: SP updates للـ themes/plugins المشتراة من Themeforest/Codecanyon.

**الأثر:**
- يحتاج Envato API key (احمد كتبها؟)
- لو الـ EduBlink license انتهت، الـ plugin مش هيـ trigger update
- ممكن نـ update يدوياً بـ file upload

**الإصلاح:** سيبه active لو احمد عنده sub Envato فعّال. غير ذلك، اقفله.

**الخطورة:** 🟡 Medium
**المكان:** Plugin `envato-market`

---

## 🔵 Low (2 مشاكل)

---

### L-1 — 3 plugins inactive بياخدوا disk: `airlift` (2 MB), `litespeed-cache` (6 MB), `nextend-facebook-connect` (5 MB)
**الوصف:** plugins inactive بس مش متحذفه. مجموع disk: ~13 MB.

**الإصلاح:** بعد قرار C-3 (airlift) و M-7 في bugs-runtime.md (litespeed)، احذف اللي مش محتاج.

**الخطورة:** 🔵 Low
**المكان:** `wp-content/plugins/`

---

### L-2 — `tutor-lms-migration-tool 2.4.1` — احتمال مكنش محتاج بعد الـ migration الأصلي
**الوصف:** Tutor LMS Migration Tool بيـ migrate من LearnDash/LifterLMS لـ Tutor. لو الـ migration حصل قديم وما عاد ليه استخدام، الـ plugin مش محتاج.

**الإصلاح:** اقفل + احذف.

**الخطورة:** 🔵 Low
**المكان:** Plugin `tutor-lms-migration-tool`

---

## الخلاصة + ROI

| Tier | العدد | الأثر الأساسي |
|---|---|---|
| 🔴 Critical | 4 | الـ Kashier gateway = ~195K EGP recovery محتمل/سنة + orphan tables data leak |
| 🟠 High | 5 | Security latent (file editing plugins) + HPOS perf gains |
| 🟡 Medium | 6 | DB cleanup + Plugin consolidation |
| 🔵 Low | 2 | Disk cleanup |

**أعلى ROI:**
1. **C-1 — Kashier migration** = أكبر فرصة ROI في الـ audit كله (195K EGP محتمل)
2. **C-2 + C-3 + M-3 + M-4 — DB orphan tables drop** = ~15 MB DB freed + privacy fix
3. **H-4 — HPOS enable** = WC performance gains (analytics 10x أسرع)

**Plugin reduction target:** من 56 active لـ ~35 active بعد:
- اقفل 3 backup plugins (C-4)
- اقفل 2 SVG (H-5)
- اقفل 3 Elementor extras (H-3)
- اقفل 5 abandoned/redundant (M-1, M-2, M-5, M-6, L-2)
- = **حذف 13 plugin → 43 active بدل 56**
- = توفير ~150-200 MB في wp-content/plugins/
- = -10 cron events
- = -200 KB autoload (تقريب)

**العدد الإجمالي بعد التحديث:**
- bugs-code.md: 27
- bugs-functional.md: 18
- bugs-data.md: 21
- bugs-runtime.md: 13
- bugs-integrity.md: 19
- **bugs-plugins.md (هذا): 17**
- **مجموع كلي: 115 bug**
