# Dart Waitlist Popup — mu-plugin

> **الحالة:** 🟢 **LIVE — اتنشر 2026-06-04** (laptop PUZZLE، بعد نقل الموقع للاستضافة الجديدة). الملف في `wp-content/mu-plugins/learnsimply-dart-popup.php` على السيرفر الجديد. W2 backend = **ACTIVE**. اختبار end-to-end تم (POST→200 {success:true}، contact دخل Mautic بـ tag `dart-waitlist`، اتمسح بعد التأكيد). البانر 39310 متأكّد موجود بعد النقل.
> **بيغذّي:** W2 n8n workflow → Mautic tag `dart-waitlist` + segment **10** → يشغّل إيميل الترحيب (Mautic id 2) تلقائياً.
> **الكورس:** "Dart من الصفر" · **إطلاق (افتراض):** 15 يونيو 2026.

---

## إيه ده بالبلدي

ده **بوب-أب** بيظهر للزائر على الموقع، بيطلب الإيميل عشان يدخله في **قايمة انتظار** كورس Dart الجديد. أول ما حد يسجّل → اسمه بيروح Mautic (segment 10) → بيوصله إيميل ترحيب تلقائي → ويوم الإطلاق بياخد الخصم.

السلسلة كاملة:

```
الزائر يكتب إيميله في الـ popup
        ↓ (نفس الدومين — آمن)
WordPress (السيرفر) ──سراً──► n8n W2 webhook ──► Mautic (tag + segment 10)
        ↓                                                    ↓
   "تم تسجيلك 🎉"                            إيميل الترحيب التلقائي (Mautic campaign)
```

---

## القرار الأمني المهم (ليه مش fetch مباشر)

الـ W2 doc الأصلي بيقترح إن الـ popup يكلّم n8n **مباشرة من المتصفح**. المشكلة: الـ webhook URL فيه **توكن سري**؛ لو حطّيناه في JavaScript المتصفح، أي زائر يفتح "inspect" يشوفه — والتوكن ده هو الحارس الأساسي ضد حقن contacts عشوائية.

**الحل المتنفّذ هنا:** الـ popup بيكلّم **WordPress** (نفس الدومين، مفيش توكن)، وWordPress من السيرفر (مخفي) بيكلّم n8n بالتوكن. مكسب:

| الحماية | إزاي |
|---|---|
| التوكن مايبانش للزائر | بيتبعت server-side من PHP بس |
| Honeypot | حقل `website` مخفي — البوت بيملاه → يتسكّت بصمت |
| Rate limit | 5 محاولات/IP كل 10 دقايق + سقف عام 300/ساعة (يحدّ أي إساءة) |
| Email validation | السيرفر بيتأكد من الإيميل قبل ما يكلّم n8n |
| Same-origin | مفيش CORS headers على الـ route → POST من مواقع تانية مايقدرش يقرا الرد |

**أسوأ سيناريو لو حد حاول يسيء:** contact متعلّم `dart-waitlist` **مش بيتبعتله أي إيميل** لحد ما Omar ينشر حملة Mautic. صفر تسريب بيانات، صفر كتابة على الموقع.

> **تحسين اختياري (موصى به):** فعّل **double opt-in** في Mautic على segment 10 → الإيميل لازم يتأكّد قبل ما يدخل. بيقفل تماماً أي abuse عبر الـ endpoint.

> **القرار على التوكن (Omar 2026-06-03):** التوكن مكتوب inline في الملف دلوقتي للنشر السريع (الريبو خاص + أثره محدود). **بعد الإطلاق:** rotate + نقله لـ `wp-config` بس + تنظيف الـ docs — البنود في [runbook §5.5](../../MIGRATION-DEPLOY-RUNBOOK.md).

> **اتعمل security review** (2026-06-03): مفيش critical. اتصلّح: تنظيف رسالة n8n الراجعة (`sanitize_text_field`) + حساب IP من `REMOTE_ADDR` (مش `X-Forwarded-For` القابل للتزوير على استضافة من غير Cloudflare).

---

## النشر (3 خطوات — بكرة)

```bash
# 1. lint قبل أي حاجة (متأكد مفيش syntax error)
php -l learnsimply-dart-popup.php          # لازم يطلع: No syntax errors detected

# 2. انسخه لمجلد mu-plugins (بيتحمّل تلقائياً — مفيش "تفعيل")
#    لو المجلد مش موجود، اعمله الأول.
mkdir -p /path/to/public_html/wp-content/mu-plugins
cp learnsimply-dart-popup.php /path/to/public_html/wp-content/mu-plugins/

# 3. (اختياري لكن أنضف) شيل التوكن من الملف → حطه في wp-config.php
#    أضف للـ wp-config.php:
#       define('LS_DART_WEBHOOK_URL', 'https://n8n.learrnsimply.com/webhook/dart-waitlist-XXXX');
#    وامسح الـ inline fallback من الـ mu-plugin.
```

بعد النسخ مباشرة:
1. **فعّل W2** في n8n (toggle واحد — `n8n_update_partial_workflow activateWorkflow`).
2. افتح الموقع في نافذة خفيّة (incognito) → استنى 12 ثانية أو اسكرول 45% → الـ popup يظهر.
3. سجّل إيميل تجريبي → لازم يظهر "تم تسجيلك 🎉" → اتأكد إنه دخل **segment 10** في Mautic → امسح التجريبي.

---

## الإعدادات (constants — عدّل أعلى الملف أو في wp-config)

| Constant | الافتراضي | بيعمل إيه |
|---|---|---|
| `LS_DART_WEBHOOK_URL` | n8n W2 URL | وجهة n8n (server-side). يُفضّل في wp-config. |
| `LS_DART_ENABLED` | `true` | **مفتاح إيقاف فوري** — `false` يخفي الـ popup كله. |
| `LS_DART_LAUNCH_DATE` | `2026-06-15` | بعد التاريخ ده الـ popup بيبطّل يظهر. |
| `LS_DART_BANNER_ID` | `39310` | ID صورة البانر من Media Library (768×512). `0` = من غير صورة. |
| `LS_DART_DELAY_MS` | `12000` | يظهر بعد 12 ثانية... |
| `LS_DART_SCROLL_PCT` | `45` | ...أو بعد اسكرول 45% (أيهما أسبق). |
| `LS_DART_IP_MAX` / `LS_DART_IP_WINDOW` | `5` / `600` | حد المحاولات لكل IP في النافذة. |
| `LS_DART_HOUR_MAX` | `300` | السقف العام في الساعة (مكبح إساءة). |
| `LS_DART_BEHIND_CF` | `false` | الموقع وراء Cloudflare؟ `false` على Hostinger الحالي → الـ rate-limit بيعتمد على `REMOTE_ADDR` (مش قابل للتزوير). خليه `false` إلا لو اتحطّت CF فعلاً. |

**أين لا يظهر:** صفحات الأدمن، سلة/checkout/حسابي (عشان مايعطّلش الشراء)، وبعد تاريخ الإطلاق.
**مرة واحدة للزائر:** localStorage — لو سجّل خلاص ميظهرش تاني؛ لو قفله من غير تسجيل ميرجعش إلا بعد 7 أيام.

---

## ⚠️ قرارات محتاجة تأكيد أحمد (decision-light عمداً)

الـ popup **مش بيعرض سعر ولا كوبون** (دول قرارات يوم الإطلاق) — فمحتاجش تأكيد كتير. بس راجع:

| البند | الحالي في الكود | يتأكد |
|---|---|---|
| اسم الكورس المعروض | "Dart من الصفر" | ✅ (متطابق مع سيكوينس الإيميلات) |
| تاريخ الإطلاق | 15 يونيو 2026 | ⏳ أحمد يأكّد (constant واحد) |
| صورة البانر | attachment **39310** | ⏳ اتأكد إن الـ ID ده لسه صح **بعد النقل** (الـ IDs ممكن تتغير) |
| النص/الصياغة | "خصم الإطلاق الحصري" (مطابق إيميل الترحيب) | ✅ |

---

## الربط بباقي النظام

- **W2 workflow:** [`../../02_AUTOMATION/n8n/workflows/W2-dart-waitlist-popup.md`](../../../02_AUTOMATION/n8n/workflows/W2-dart-waitlist-popup.md) — الـ backend (مبني + متختبر + INACTIVE).
- **إيميلات السيكوينس:** [`../../02_AUTOMATION/mautic/campaigns/email-copy-drafts.md`](../../../02_AUTOMATION/mautic/campaigns/email-copy-drafts.md) — إيميل الترحيب (id 2) اللي بيتشغّل من انضمام segment 10.
- **runbook النشر بكرة:** [`../../MIGRATION-DEPLOY-RUNBOOK.md`](../../MIGRATION-DEPLOY-RUNBOOK.md).
