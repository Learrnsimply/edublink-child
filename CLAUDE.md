# edublink-child — دليل المشروع

Child theme لـ WordPress لموقع **اتعلم ببساطة** (learrnsimply.com) — منصة كورسات برمجة عربية، RTL، وضع داكن.

## الستاك

- **WordPress** + الثيم الأب `edublink`
- **Timber 2.3 + Twig** لطبقة القوالب (الـ`vendor/` متكوميت في الريبو)
- **Tutor LMS** — الكورسات، لوحة التحكم، الشهادات
- **WooCommerce** — المتجر، السلة، الدفع
- **Elementor** موجود في الموقع، والثيم ده بيحاول يوقّفه على صفحات كتير

## البنية

```
functions.php      ~3,600 سطر — قلب الثيم (توجيه القوالب، enqueue، تخصيصات Woo)
*.php              قوالب الصفحات — بتبني $context وبعدين Timber::render()
views/             قوالب Twig
  layouts/base.twig    القالب الأساسي
  components/          header, footer, whatsapp-button
  sections/home/       8 سكاشن للهوم بيدج (بنية Mosh، سبتمبر ٢٠٢٦)
assets/            CSS/JS بفولدر لكل صفحة — بيتحمّلوا أوتوماتيك حسب نوع الصفحة
  global/tokens.css    ⭐ مصدر الحقيقة لكل قيم التصميم
woocommerce/       override لقوالب WooCommerce
tutor/             override لقوالب Tutor LMS
```

## 🎨 نظام التصميم — اقرأ ده قبل أي شغل CSS

**كل قيم التصميم في `assets/global/tokens.css`** (63 توكن). بيتحمّل أول حاجة عن طريق
`learnsimply_enqueue_design_tokens()` في `functions.php` (~سطر 43).

### قواعد إلزامية

1. **أي CSS جديد يستخدم `var(--ls-*)`. ممنوع hex خام جديد.**
2. **كل مرجع توكن لازم يبقى معاه fallback:** `var(--ls-blue, #4077f3)`
   — شبكة أمان لو الملف مرحش السيرفر. مش مصدر تاني للحقيقة.
3. **زوّد `LS_ASSETS_VERSION`** (`functions.php` ~سطر 1308، صيغة `YYYYMMDD-N`) مع أي push فيه CSS/JS.
   أغلب الملفات على `filemtime` وبتتحدث لوحدها، لكن اللي بيتحمّل من Twig بـ`?v={{ assets_version }}`
   بيعتمد على الرقم ده.

### التوكنز الأساسية

```
--ls-bg #0a0f1a · --ls-surface #141924 · --ls-surface-2 #1b2133 · --ls-surface-3 #232d45
--ls-blue #4077f3 · --ls-blue-hover #5a8eff · --ls-blue-active #3568d4
--ls-text #ffffff · --ls-text-2 #e2e6f0 · --ls-text-muted #999eb2 · --ls-text-dim #7a7f96
--ls-success #18a963 · --ls-danger #f96a7b · --ls-warning #ffbb54 · --ls-star #ffce31
```

**📖 التوثيق برة الريبو عن قصد.**

الثيم ده بيتعمله deploy على السيرفر، وأي ملف جواه بيبقى مقروء على الإنترنت
(`learrnsimply.com/wp-content/themes/edublink-child/...`). التوثيق فيه تحليل
إيرادات وأرقام وثغرات، فمكانه برة.

**مكانه على جهاز أحمد:**

```
/Applications/learn simply/docs/
   CHANGELOG.md          كل اللي اتعمل ومتى — اقرأه الأول
   CHECKOUT-AUDIT.md     قمع الشرا وقائمة الشغل
   ACADEMY-PLAN.md       خطة تحويل المنصة لأكاديمية
   BUSINESS-CONTEXT.md   الكتالوج والجمهور وأرقام GA
   DESIGN-SYSTEM.md      نظام التوكنز

/Applications/learn simply/omar-workspace-backup/
   أرشيف ورشة عمر — 137 باگ موثّق · تدقيقات · تحليل 2,936 أوردر
   ⚠️ فيه mu-plugins شغالة على السيرفر مش موجودة في الثيم:
      skip-cart · dart-landing · dart-popup
```

> ⚠️ **الخطة الحالية (٣ سبتمبر ٢٠٢٦):** أكاديمية بقسمين — برمجة وذكاء اصطناعي.
> الشغل التقني على CSS **متوقف عن قصد**.

## 📝 قاعدة إلزامية — التوثيق مع كل تغيير

أي تغيير بيتكتب في `/Applications/learn simply/docs/CHANGELOG.md` **قبل** الكوميت.
الصيغة: **ليه** · **اللي اتعمل** · **النتيجة** · **الفرع**.

## حاجات مهمة تعرفها عن الكود ده

- **الثيم شغّال بمنطق "اكسب المعركة":** `!important` على `*`، أولويات `999999` و`10000`،
  وحقن CSS في `wp_footer` عشان ييجي بعد كل حاجة. أي تعديل جديد بيدخل نفس السباق.
  (فيه 4,969 `!important` — تنضيفهم مرحلة ٤ في خريطة التصميم.)
- **1,200+ سطر CSS مكتوبة كـ strings جوه `functions.php`** ومحقونة في `wp_footer`.
  مش داخلة في أي كاش ومش شايفة التوكنز. (مرحلة ٣.)
- **IDs مكتوبة بالإيد** في توجيه القوالب: `9834`, `22662`, `21`, `21744`.
  لو اتغيرت على السيرفر، التوجيه هيتكسر بصمت.
- **استعلام مباشر على جدول بلجن** في `edublink_child_load_page_assets()`:
  `{$wpdb->prefix}asnp_wepb_simple_bundle_items`. لو البلجن اتشال، ده هيرمي DB error على كل صفحة منتج.
- **فلاتر Twig آمنة موجودة:** استخدم `|safe_html` (wp_kses_post) و`|safe_embed` بدل `|raw`.

## سير العمل

- الموقع بيعمل deploy من فرع **`main`** أوتوماتيك. اشتغل على فرع منفصل واعمل PR.
- بعد أي deploy، الفحص السريع في الـ console:
  ```javascript
  getComputedStyle(document.documentElement).getPropertyValue('--ls-blue')
  ```
  لازم يرجّع `#4077f3`. لو فاضي، `tokens.css` مش بيتحمّل.
- **الموقع الحي:** https://learrnsimply.com
- PHP مش متثبت على جهاز المطور — `php -l` مش هيشتغل محليًا.
