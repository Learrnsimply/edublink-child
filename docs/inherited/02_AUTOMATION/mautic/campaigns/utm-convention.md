# UTM Convention — Email → WooCommerce attribution

> **ليه:** WooCommerce Order Attribution بيقرا `utm_source/medium/campaign` من لينك الـ landing.
> أي لينك إيميل **بدون UTM** → المبيعة بتتسجّل **"(direct)"** ونفقد العزو. ده اللي حصل في إطلاق Dart
> (إيميلات 1/2/4 اتبعتت بلينكات بلا UTM → كل المبيعات ظهرت direct/youtube/google، مقدرناش نعزو الإيميل).

## القاعدة

كل لينك **on-site** (`learrnsimply.com`) في أي إيميل لازم يحمل:

```
?utm_source=mautic&utm_medium=email&utm_campaign=<حملة>&utm_content=<نسخة>
```

- `utm_source` = **mautic** (دايماً — ده المصدر اللي هيظهر في WC order attribution)
- `utm_medium` = **email** (دايماً)
- `utm_campaign` = اسم الحملة الثابت (يجمع التقارير): `dart_launch` · `dart_lastchance` · `dart_waitlist` · `reengagement_13k`
- `utm_content` = أي إيميل جوّه الحملة: `announce` · `launch` · `lastchance` · `welcome` · `soft_reminder`

**متلمسش:** لينكات خارجية (YouTube/Telegram/Linktree) ولا الـ Mautic tokens (`{unsubscribe_url}`).

## إزاي تطبّقها

- **إيميلات الأداة** (`_tools/build_campaign_emails.py`): أوتوماتيك عبر `tag_utm()` + خريطة `UTM_BY_KEY` — مفيش شغل يدوي.
- **إيميلات يدوية / Mautic UI**: حُط الـ UTM بإيدك في الـ href. مثال (آخر فرصة، email id5، اتبعت 2026-06-23):
  `https://learrnsimply.com/dart?utm_source=mautic&utm_medium=email&utm_campaign=dart_lastchance&utm_content=soft_reminder`

## التحقق

بعد ما الإيميل يجيب كليكات، شوف العزو في WooCommerce:

```bash
# orders with utm_source=mautic (آخر شهر)
curl -s -u "$WC_KEY:$WC_SECRET" \
  "https://learrnsimply.com/wp-json/wc/v3/orders?after=2026-06-01T00:00:00&per_page=100&status=any" \
  | python3 -c "import sys,json;[print(o['id'],[m['value'] for m in o['meta_data'] if m['key']=='_wc_order_attribution_utm_source']) for o in json.load(sys.stdin)]"
```

لو ظهر `mautic` → العزو شغّال. (قبل 2026-06-23 كان بيظهر "(direct)" لكل مبيعات الإيميل.)
