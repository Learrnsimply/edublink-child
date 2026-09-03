---
name: ls-wp-tutor
description: >-
  Operational runbook للموقع الحي لـ Learn Simply (اتعلم ببساطة) — WordPress 6.9 + Tutor LMS +
  WooCommerce + Kashier على Hostinger shared (CageFS). استخدمها دايماً لـ: بناء/تعديل الثيم
  (edublink-child) أو mu-plugin · deploy آمن من غير ما تكسر الإنتاج · أي شغل على قاعدة البيانات
  ("أسحب backup"/migration/إصلاح) · مشاكل الدفع ("الكاشير بيرمي error"، أسعار الكورسات) ·
  "مشكلة في السيرفر"/SSH/wp-cli · أي شكوى عميل عن الموقع/الطلبات. استخدمها حتى لو ما قُلت الاسم
  مباشرة — أغلب مشاكل الموقع/الدفع جذرها هنا. بتجمع: نمط deploy آمن + قيود CageFS اللي بتفشل صامت
  (wp db export، crontab) + verify-before-act (Playwright مش curl) + gotchas الـ audit. (مش لكود
  عام غير مرتبط بـ Learn Simply.)
---

# Learn Simply — الموقع التقني (WordPress + Tutor + Woo على Hostinger) — Runbook

> ⚠️ **موقع إنتاج حي بيكسب ~67K ج/شهر.** Ahmed هو owner الريبو — إحنا collaborators بـ **PR workflow** (مفيش push على main من غير مراجعته).
> **المصدر الكامل للحقيقة:** `03_KNOWLEDGE/comprehensive-audit.md` (scorecard + Master Action List) · `01_WEB/bugs-report.md` (137 bug) · `MIGRATION-DEPLOY-RUNBOOK.md` (الـ deploy) · `lessons.md` (كل الدروس).

---

## الـ Stack + الوصول

- **WordPress 6.9.4** + **Tutor LMS Pro 3.9.11** + **WooCommerce** + ثيم **edublink-child** (repo `Learrnsimply/edublink-child`).
- **الدفع:** Kashier (بوابة مصرية) — **توزيعها الرسمي عبر GitHub** (`Kashier-payments/Kashier-WooCommerce-Plugin`)، مش WordPress.org. لاحقة `-master` طبيعية.
- **الاستضافة:** Hostinger **shared (CageFS)** — `46.202.158.231:65002` · user `u791284659` · WP path `/home/u791284659/domains/learrnsimply.com/public_html` · SSH **key-based** (alias `ssh learnsimply`) · wp-cli 2.12.0.
- **الكاش:** WP-Optimize page cache.
- **التتبع:** Meta Pixel `699717432496147` + TikTok + GA4 `G-DT3Z0RSEBK`.

> ⚠️ **الدومين `learrnsimply.com` بـ `r` مكررة — مش typo.** أي "إصلاح" لـ `learnsimply` بيكسر كل الروابط.

## 🛑 قيود Hostinger CageFS (بتفشل **صامت** — أخطر حاجة)

1. **`wp db export` بيفشل صامت** (exit 0، صفر بايت) → استخدم **`mysqldump`** بالـ credentials من `wp config get`. (النمط في `02_AUTOMATION/backups/scripts/backup.sh`.)
2. **`crontab -e` مقفول** (CageFS بيشيل البايناري) → الكرون **من hPanel UI بس** (Advanced → Cron Jobs). على الـ UI ده: weekday `7` = الأحد.
3. **مفيش Docker / workers / 1-min cron / >300s exec / >1.5GB memory** — أي حاجة محتاجة دي (Mautic/n8n) بتعيش على الـ **VPS**، مش هنا.
4. **`rm -rf` على السيرفر = اعرضه على Omar الأول** حتى لو المسار واضح.

## نمط الـ Deploy (الآمن)

1. عدّل في الريبو المحلي → **PR لـ Ahmed** (هو بيـ merge على main). مفيش push مباشر.
2. للنشر على السيرفر: **`scp`** الملف → **تأكد md5** (السيرفر = الريبو) → **purge WP-Optimize cache** (الكاش بيخفي التغييرات/الـ popups عن الزوار!).
3. mu-plugins الحالية: `dart-landing` · `dart-announce` · `learnsimply-dart-popup` (في `wp-content/mu-plugins/`).
4. بعد تعديل الثيم: bump الـ submodule لـ main.

## 🚨 verify-before-act (الـ audit طلّع false-positives كتير — اتأكد بنفسك)

- **HTTP findings:** اتأكد بـ **Playwright (متصفح حقيقي)** مش `curl` — Hostinger/bot-protection بيرد على non-browser UA بـ 404 (مثال: `/blog/` بيشتغل في المتصفح، 404 لـ curl).
- **"option = قيمة غلط":** اتأكد من **الحالة الفعّالة** مش المخزّنة — plugins/constants بتـ override صامت (مثال: `woocommerce_email_from_address` = Gmail لكن `wp-mail-smtp` بـ `from_email_force` بيستخدم contact@).
- **"pixel/plugin active":** "active" بيكذب — اتأكد إنه بيـ fire في **frontend HTML** فعلاً (`pixel_id` مش 0 + `access_token` مش فاضي).
- **"folder مشبوه" في uploads:** شغّل `wp plugin list` الأول — اللاحقة الهاش طبيعية (wpsynchro/wpforms/cartflows). متحذفش قبل ما تتأكد.
- **Kashier "مكسور":** 39% cancel = abandonment طبيعي + 3DS friction، **مش** تكامل مكسور (102 order نجحوا آخر 30 يوم). محتاج تحسين (أقساط/Apple Pay)، مش migration.
- **autoload audit (WP 6.6+):** استخدم `WHERE autoload IN ('yes','on','auto','auto-on')` — مش `'yes'` بس (هتفوّتك ~38%).
- **permalinks:** `/%postname%/` (المقالات في `/<slug>/` مش `/blog/<slug>/`) — أي تغيير = rewrite لكل بوست = كارثة SEO.
- **git history:** `mrrobot5-a` = أحمد (هويته الشغّالة، نفس إيميل `Ahmed Adel`). الـ commits "test/asd" = أحمد بيجرّب على الإنتاج.

## Tutor LMS / WooCommerce — نقاط حساسة

- **CVE-2026-0953 (9.8 admin takeover عبر Social Login)** — اتصلّح في **3.9.11**. متعطّلش Tutor (بيقتل الكورسات) — حدّث بس.
- **WC REST key = read-only** (للقراءة من n8n/الأدوات — مفيش كتابة).
- **HPOS:** لسه مش مفعّل (sync بيشتغل بدونه = أوردرات في جدولين). تفعيله = مهمة منفصلة محتاجة حذر.
- **`wp db export` ممنوع** (فوق) — أي شغل DB بـ mysqldump.

## الـ Backup (3 طبقات)
1. Hostinger daily (built-in، 7-30 يوم) = الأساس.
2. GitHub weekly snapshot (offsite redundancy) — submodule `omarabdo516/learn-simply-backups`، الأحد 02:00 القاهرة (hPanel cron). **مفيش disable بدون إذن Omar.**
3. كود الثيم في `01_WEB/website/`.

## قبل أي تعديل (checklist)
1. اقرأ `HANDOFF.md` (أحدث) + `comprehensive-audit.md` لو محتاج السياق.
2. التعديل في الريبو → PR لأحمد (مش push مباشر).
3. أي شغل DB → mysqldump مش `wp db export`. أي كرون → hPanel.
4. بعد scp → md5 verify → purge WP-Optimize.
5. أي finding من audit → **اعيد إنتاج العَرَض بنفسك** قبل ما تشحن "إصلاح".
6. `rm -rf`/حذف على السيرفر → اعرضه على Omar.
