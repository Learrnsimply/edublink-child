# Dart Landing Page — `/dart`

> صفحة هبوط لقايمة انتظار كورس Dart على `learrnsimply.com/dart` — **جوه هيدر وفوتر الموقع الحقيقيين**. **الوجهة** لزراير الإيميلات ("احجز أسبقيتك") وأي ترافيك من اليوتيوب/تليجرام/سوشيال.
> **الحالة:** 🟢 **LIVE — v3.0 (2026-06-04)** · متوسّط + أنيميشن + هيدر/فوتر الموقع + **ستايل يطابق الـ Homepage** (غامق navy `#0a0f1a` + أزرق `#4077f3` + IBM Plex + كارت عرض أزرق — سحبنا tokens الـ homepage الحقيقية بالـ chromium). متأكّدة: HTTP 200 + screenshots حية ديسكتوب+موبايل (`dart-v3-*.png`) + فورم E2E (POST→200، contact بـ tag `dart-waitlist` دخل Mautic، اتمسح).
>
> **v2 (هيدر/فوتر الموقع):** بقت **صفحة WordPress حقيقية** (slug `dart`، id **39320**) بترندّر `get_header()` + الـ fragment المعزول (`#ls-dart-lp`) + `get_footer()`. كل الـ CSS بتاعنا **scoped تحت `#ls-dart-lp`** عشان ستايل الثيم ما يتسرّبش (والعكس). الـ **popup متوقّف على `/dart`** (الصفحة فيها الفورم أصلاً).
>
> ⚠️ **دروس deploy:** (1) لإنشاء الصفحة مرة واحدة: `wp post create --post_type=page --post_status=publish --post_name=dart --post_title="..."` ثم `wp rewrite flush`. (2) أرشيف/category بنفس الاسم `dart` كان بيكسب الـ route → الـ loader بيتحقّق `is_page('dart')` + path fallback. (3) **wp-optimize page cache** لازم purge بعد أي deploy (`wp eval 'wpo_cache_flush();'`).

## إيه اللي بتعمله
- صفحة كاملة RTL (نفس هوية الـ popup: بنفسجي + Cairo)، mobile-first.
- **فورم التسجيل** (في الـ hero + بلوك العرض) بيـ POST لنفس باكند الـ popup: `/wp-json/learnsimply/v1/dart-waitlist` → W2 → Mautic **segment 10** + tag `dart-waitlist`. فالداتا موحّدة من كل المصادر (popup + الصفحة).
- **عدّاد حي (JavaScript)** للإطلاق 15 يونيو 2026 (12 ظهراً القاهرة) — بيتحوّل لـ "نزل! 🎉" بعد الميعاد.
- **صورة أحمد الحقيقية** من الموقع (`/wp-content/uploads/2025/10/for-profile.png`).
- الأقسام: Hero · ليه Dart · ليه اتعلم ببساطة · بلوك العرض (350/700) · المدرّس + أرقامه · FAQ · فوتر.

## الملفات
| الملف | الدور |
|---|---|
| `learnsimply-dart-landing.php` | الـ loader — rewrite `/dart` + بيحقن الـ endpoint الحقيقي + بيـ render |
| `page.html` | الماركب نفسه (المصدر الوحيد — نفس اللي اتعمله screenshot). فيه token `__LS_ENDPOINT__` بيتبدّل server-side |

## التبعية
بيعتمد على **`learnsimply-dart-popup.php`** (هو اللي مسجّل الـ REST route للباكند). الاتنين mu-plugins بيتنشروا سوا.

## Deploy (SSH للاستضافة الجديدة)
```bash
# من جذر البراند — الـ alias learnsimply (key-based) شغّال
ssh learnsimply 'mkdir -p ~/domains/learrnsimply.com/public_html/wp-content/mu-plugins/dart-landing'
scp 01_WEB/mu-plugins/dart-landing/learnsimply-dart-landing.php \
    learnsimply:~/domains/learrnsimply.com/public_html/wp-content/mu-plugins/learnsimply-dart-landing.php
scp 01_WEB/mu-plugins/dart-landing/page.html \
    learnsimply:~/domains/learrnsimply.com/public_html/wp-content/mu-plugins/dart-landing/page.html
```
- الـ rewrite بيـ flush أوتوماتيك أول زيارة. لو `/dart` طلع 404: Settings → Permalinks → Save (مرة واحدة).
- ⚠️ لو فيه page cache (wp-optimize) → امسح الكاش بعد الـ deploy عشان الصفحة تبان.

## التحقق بعد الـ deploy
1. افتح `https://learrnsimply.com/dart` → HTTP 200 + الصفحة بتظهر (مش 404).
2. العدّاد بيعدّ، صورة أحمد ظاهرة.
3. سجّل إيميل اختبار → لازم يدخل Mautic segment 10 (tag `dart-waitlist`) → امسح الـ contact بعد التأكد.
4. screenshot للموقع الحي (مش بس محلي) — قاعدة `01_WEB/CLAUDE.md`.

## كِل سويتش
`define('LS_DART_LP_ENABLED', false);` في wp-config.php → الصفحة تطفي فوراً (404).

## بعد الإطلاق (TODO)
الصفحة دلوقتي = قايمة انتظار. بعد 15 يونيو لما منتج Dart يتعمل في WooCommerce: نحوّل `/dart` لصفحة بيع/شراء أو redirect لصفحة الكورس.
