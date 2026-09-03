# W4 — Cart Recovery (pending-order email reminders)

> **Status:** 🟢 **LIVE** (2026-06-24) — متفعّل، poll كل 20د. أول go-live كشف **بق Mark Sent** (اتصلّح، §البق تحت). الأوردرين pending الحاليين (ghada 39923 + أوردر Omar 39925) logged + متخطّيين.
> **Workflow ID:** `Er1W6KSwqOiEmgrd` · **n8n:** https://n8n.learrnsimply.com
> **Plan:** [`W4-W5-cart-recovery-PLAN.md`](W4-W5-cart-recovery-PLAN.md) · **التحليل:** [`../../../03_KNOWLEDGE/payment-leak-deep-dive-2026-06-24.md`](../../../03_KNOWLEDGE/payment-leak-deep-dive-2026-06-24.md)
> **الغرض:** يلحق الأوردرات `pending` (الدفع ماكملش) في نافذة الـ24 ساعة قبل ما تتلغي، ويبعت تذكير إيميل بلينك استكمال الدفع المباشر + بديل المحفظة. الفرصة: ~22K ج/شهر مستمر (التوصية #2 في الـ deep dive).

---

## Pipeline

```
Schedule (every 20m, 8am–11pm Cairo)
  → WC Pending Orders (HTTP, read-only)      آخر 30h من status=pending
  → Load Sent Log (Postgres)                 omar.recovery_log آخر 3 أيام (alwaysOutputData)
  → Decide Sends (Code, all-items)           احسب العمر → اختَر stage (R1/R2) → ابنِ الإيميل
  → Send Recovery Email (Brevo SMTP)          per item
  → Mark Sent (Postgres upsert)              يقفل re-send
```

| Node | بيعمل إيه |
|---|---|
| **Every 20m (8am–11pm Cairo)** | `scheduleTrigger` cron `*/20 8-22 * * *`، timezone `Africa/Cairo`. النافذة 8ص–10:40م تضمن إن R2 يلحق يتبعت قبل إلغاء الـ24 ساعة (فجوة الليل 9س20د < نافذة R2 الـ12س). |
| **WC Pending Orders** | `GET /wc/v3/orders?status=pending&after=<now-30h>&dates_are_gmt=true&_fields=...,order_key,billing,line_items`. WC read-only key `5k7PIPwao0Vkoczb` (Basic). retry 3×/2s · onError continue. |
| **Load Sent Log** | `SELECT order_id, r1_sent_at, r2_sent_at FROM omar.recovery_log WHERE created_at > now()-interval '3 days'`. cred Postgres omar `zCWs84St5G8Iw8TS`. **alwaysOutputData** (عشان Decide يشتغل حتى لو اللوج فاضي). |
| **Decide Sends** | Code (runOnceForAllItems). يقرا الأوردرات + اللوج (بالاسم via `$()`). يحسب العمر من `date_created_gmt` (UTC). يفلتر `status==='pending'` + إيميل صالح. يختار stage، يبني HTML RTL + لينك الدفع + UTM. |
| **Send Recovery Email** | `emailSend` v2.1، Brevo SMTP `YTlcYxVu93s62OuL`، from `contact@learrnsimply.com`. retry 2×/3s · onError continue (إيميل واحد بايظ ميوقفش الدفعة). |
| **Mark Sent** | `executeQuery` بـ `={{ $('Decide Sends').item.json.markSql }}` — الـ SQL كامل (INSERT…ON CONFLICT…COALESCE) متبني في Decide بـ template literal (id رقم، إيميل escaped، column مفلتر). **بيقرا من Decide مش `$json`** لأن node الإرسال بيستبدل الـ item (راجع §البق). |

**Error workflow:** W3d `YktkjLMI12YUGWfc` (Telegram alert) متربوط في settings.

---

## منطق التوقيت (Decide)

العمر بالدقائق من `date_created_gmt`:

| stage | الشرط | الرسالة |
|---|---|---|
| **(تخطّي)** | عمر < 45 دقيقة | الدافع الحقيقي بيدفع في ~4 دقايق — منزعّجوش |
| **R1** | 45د ≤ عمر < 10س · `r1` مش مبعوت · `r2` مش مبعوت | تذكير ودود: "أوردرك لسه مستنيك" + لينك + بديل محفظة |
| **R2** | عمر ≥ 10س · `r2` مش مبعوت | آخر تذكير (إلحاح صادق — فعلاً بيتلغي): "هيتقفل قريب" |

- ضمان: ≤1 R1 + ≤1 R2 لكل أوردر. لو الأوردر ظهر متأخر (>10س) من غير R1، بياخد R2 كلمسة واحدة (مفيش zero-touch).
- **الـ stop condition** = الـ poll بيجيب `pending` بس. أول ما العميل يدفع → الأوردر مبقاش pending → اختفى → مفيش إرسال. أأمن من أي flag.

---

## لينك استكمال الدفع (magic link)

```
https://learrnsimply.com/checkout/order-pay/{id}/?pay_for_order=true&key={order_key}
  &utm_source=brevo&utm_medium=email&utm_campaign=cart_recovery_r1|r2&utm_content=recovery
```
- متأكّد HTTP 200 لضيف (الـ key بيصرّح). بيرجّع العميل لنفس الأوردر — مش سلة جديدة.
- UTM → WooCommerce Order Attribution يعزو المبيعة (مش "(direct)").

---

## الـ idempotency store

`omar.recovery_log` على `omar-pgvector` (DB `omar_agent`):
```sql
order_id bigint PK, customer_email text, course text, total numeric, currency text,
r1_sent_at timestamptz, r2_sent_at timestamptz, created_at timestamptz, updated_at timestamptz
```
> **قرار:** Postgres بدل n8n Data Table (المقترح في الخطة) — لأن الـ in-workflow Data Table node مش متاح/مؤكّد على n8n 2.59.4، بينما Postgres مثبّت في الـ stack (W3b بيستخدمه). نفس التصميم بالظبط، مسار أمتن.

---

## معالجة الحالات الخاصة (متلمسهاش)

- **منتجات الباقة (bundle):** المنتج 33336 وغيره بيتفجّر لـ parent + children (سعر 0، meta `_asnp_wepb_parent_id`). `courseLabel()` بيفلتر الـ children + يزيل التكرار + يحدّد لاسمين + "وكورسات تانية". (لولا ده الإيميل بيبان فيه قايمة 10 أسطر.)
- **`on-hold`/`processing`/`completed` متجاهلين** — query بيجيب `pending` بس. اللي دفع محفظة (on-hold مستني تأكيد أحمد) أو مدفوع — عمره ما يتبعتله.
- **`date_created_gmt` = UTC** — بنضيف `Z` قبل `Date.parse`. الفلترة بالعمر هي المرجع (مش `after` في الـ query، اللي نافذته واسعة 30h).

---

## اختبار (2026-06-24 — كله متأكّد، صفر إيميل لعميل حقيقي)

| الاختبار | النتيجة |
|---|---|
| WC auth + query عبر n8n (الـ credential الحقيقي) | ✅ قرا أوردرين pending حقيقيين (39923 ghada، 39925 أوردر Omar التجريبي) |
| Decide: عمر → stage | ✅ الاتنين age ~14h → R2 صح |
| تنظيف اسم الكورس (bundle) | ✅ أوردر الباقة بقى "كورس جافا للمبتدئين + كتاب هدية، كتاب لغة جافا وكورسات تانية" |
| لينك الدفع الحقيقي (39923) | ✅ HTTP 200 (مفيش redirect لوجين) |
| UTM على اللينكات | ✅ `utm_campaign=cart_recovery_r2` |
| idempotency (علّمت 39925 R2-sent → أعدت التشغيل) | ✅ Decide طلّع 39923 بس (39925 اتخطّى) |
| تسليم Brevo (إيميلين preview لـ Omar) | ✅ وصلوا الـ inbox، RTL + الزرار سليمين |
| HTML render | ✅ عربي RTL، إلحاح R2، سطر المحفظة، فوتر |

**الطريقة:** webhook مؤقت + تعطيل Send/Mark → فحص مخرجات Decide على داتا حقيقية. الإرسال اتأكّد بـ Brevo SMTP مباشر لـ Omar. اتشال الـ webhook + اترجّع الإرسال بعد الاختبار.

---

## 🐞 البق اللي اتصلّح في أول go-live (2026-06-24)

**العَرَض:** أول تفعيل (بـ cron مؤقت كل دقيقة للتأكد السريع) بعت R2 صح، بس **Mark Sent فشل** (`there is no parameter $1` ثم `Failed query: undefined`) → الـ log مكنش بيتكتب → مع الـ cron الدقيقة، **ghada (عميلة حقيقية) استلمت R2 أربع مرات مكررة** (15:58–16:01) قبل ما نوقف.

**السبب الجذري:** node الإرسال (`emailSend`) **بيستبدل الـ item بنتيجة SMTP** (accepted/messageId) → الحقول بتاعة Decide (order_id/markSql) بتختفي قبل Mark → الـ query بيتقيّم على `undefined`.

**الإصلاح:** (1) Mark بيقرا الـ SQL من Decide عبر الـ paired item: `={{ $('Decide Sends').item.json.markSql }}` بدل `$json`. (2) الـ SQL كامل متبني في Decide (template literal، صفر parameter-binding). متأكّد حي: 39925 اتكتب بواسطة Mark · إعادة تشغيل = "No item" (صفر إرسال مكرر).

**الاحتواء:** الأوردرين (39923+39925) اتسجّلوا يدوي كـ r2_sent فور اكتشاف البق → مفيش إيميلات إضافية ليهم. **درس عام:** أي node بعد `emailSend`/أي node بيحوّل الـ item لازم يرجع لـ source node بالاسم (`$('Node').item`) مش `$json`.

## Go-live (LIVE 2026-06-24)

أول تفعيل = **catch-up**: الأوردرات الـ pending (>10س) بتاخد R2 فوراً. حالياً الأوردرين logged ومتخطّيين → steady-state = صفر إرسال لحد ما أوردر pending جديد يظهر. Brevo 300/يوم → الحجم المتوقّع ~5 إيميل/يوم، تحت السقف بكتير.

**الإيقاف:** deactivate (reversible فوراً). **مراقبة:** executions n8n + `omar.recovery_log` + W3d Telegram لأي خطأ.

### مراقبة بعد الإطلاق
- نتابع `omar.recovery_log` + executions n8n.
- نقيس التحويل عبر `utm_campaign=cart_recovery_*` في WooCommerce/GA4.
- baseline: 41% بيرجعوا لوحدهم (deep dive) — أي زيادة فوقها = أثر W4 الصافي.

---

## n8n credentials used
| Name | Type | ID |
|---|---|---|
| WooCommerce ReadOnly — Learn Simply | httpBasicAuth | `5k7PIPwao0Vkoczb` |
| Postgres — omar_agent | postgres | `zCWs84St5G8Iw8TS` |
| SMTP Brevo | smtp | `YTlcYxVu93s62OuL` |

## القادم (Phase B — W5)
blast تاريخي لمرة واحدة للـ313 عميل اللي ماكمّلوش أبداً (~186K ج) — عبر Mautic (unsubscribe + throttle). يتبني بعد ما W4 يثبت في الإنتاج.
