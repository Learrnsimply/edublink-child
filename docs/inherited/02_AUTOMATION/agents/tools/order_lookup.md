# 🔎 Tool Spec — `order_lookup`

> **النوع:** n8n sub-workflow (tool) بيتنده من الـ AI Agent node (Gemini Flash) في `omar-inbound`.
> **الغرض:** يجاوب سؤال واحد بس: *"العميل ده دفع؟ اشترى إيه؟ وحالة الأوردر إيه؟"* — بالتليفون أو الإيميل.
> **المصدر:** WooCommerce REST (`/wp-json/wc/v3/`) على `learrnsimply.com` (الـ double-r مقصود).
> **القراءة بس (read-only):** الأداة دي **بتقرا فقط**. عمرها ما بتعدّل أوردر، ما بتفعّل كورس، ما بتغيّر status. أي تفعيل = بشري (تصعيد).
> **النمط المرجعي:** نفس باترن الـ HTTP Request + الـ defensive JS اللي في [`../../n8n/workflows/W1-wc-mautic-sync.md`](../../n8n/workflows/W1-wc-mautic-sync.md). الـ credentials Mautic موجودة هناك؛ دي محتاجة **WooCommerce read-only key** جديد (placeholder تحت).
> **الحالة:** 📝 SPEC — مفيش بناء لسه. blocker وحيد: WooCommerce read-only API key من Ahmed/الموقع.

---

## 1. ليه الأداة دي موجودة (في سياق رحلة عمر)

عمر بيستخدمها في حالتين بس من مسار B (الدعم):

| Flow | الموقف | عمر بيستخدم `order_lookup` ليه |
|---|---|---|
| **B-payfail** | "الدفع فشل / اتخصم وملقتش الكورس" | يشوف هل فيه أوردر فعلاً، وحالته إيه (`failed`؟ `pending`؟ `processing`؟ `completed`؟) قبل ما يصعّد — عشان يدّي الفريق context دقيق. |
| **B-F2 (access)** | "دفعت وملقتش الكورس" | يأكّد إن فيه أوردر مدفوع (`processing`/`completed`) لنفس الإيميل + يجيب اسم الكورس + رقم الأوردر، فيتصعّد التصعيد بمعلومة مؤكّدة مش بادعاء العميل. |

> **الأداة مش بتقرر حاجة.** هي بتجمع حقائق للتصعيد بس. القرار النهائي (تفعيل، استرجاع، إعادة دفع) دايماً بشري. (راجع القاعدة الذهبية 3 في `omar-system-prompt.md`.)

---

## 2. الـ Endpoint (WooCommerce REST)

### 2.1 البحث الأساسي — `search`
```
GET https://learrnsimply.com/wp-json/wc/v3/orders
      ?search=<phone-or-email>
      &per_page=10
      &orderby=date
      &order=desc
```

- **`search`** بيدوّر في حقول الأوردر (شامل `billing.email` و `billing.phone` والاسم) — مناسب للبحث بإيميل أو تليفون من غير ما نعرف الـ customer id.
- **`per_page=10`** سقف معقول؛ أحدث 10 أوردرات كفاية (عميل عنده أكتر من كده نادر، والأحدث هو المهم).
- **`orderby=date&order=desc`** → أحدث أوردر الأول (هو اللي بيخص الشكوى غالباً).

### 2.2 fallback — البحث بالإيميل عبر العميل (اختياري، لو `search` رجّع فاضي بإيميل)
الـ `search` أحياناً بيكون أدق لما نربطه بالعميل نفسه:
```
GET /wp-json/wc/v3/customers?email=<email>&per_page=1     →  ياخد customer.id
GET /wp-json/wc/v3/orders?customer=<id>&per_page=10&orderby=date&order=desc
```
> ده **fallback بس** — نستخدمه فقط لو الإدخال إيميل **و** `search` رجّع `[]`. لو الإدخال تليفون، مفيش customers lookup بالتليفون فـ بنكتفي بـ `search`.

### 2.3 الحقول اللي بنرجّعها بس (تقليل الـ payload)
ضيف `&_fields=id,status,date_created,total,currency,billing,line_items,payment_method_title,date_paid` على الـ orders call عشان الرد يبقى أخف ومايتسربش بيانات زيادة للموديل.

---

## 3. المصادقة (Auth) — read-only

WooCommerce REST بياخد **HTTP Basic Auth** = `consumer_key` (username) + `consumer_secret` (password)، **Read permission بس**.

| البند | القيمة |
|---|---|
| النوع في n8n | `httpBasicAuth` credential مخصوص للأداة دي |
| Username | `<WC_READONLY_CONSUMER_KEY>` — placeholder (يبدأ بـ `ck_`) |
| Password | `<WC_READONLY_CONSUMER_SECRET>` — placeholder (يبدأ بـ `cs_`) |
| Scope | **Read** بس (يتولّد من wp-admin → WooCommerce → Settings → Advanced → REST API → Add key → Permissions = Read) |
| Transport | HTTPS فقط (الموقع كله HTTPS + HSTS) |

> **مهم — credential منفصلة:** متستخدمش نفس Mautic Basic creds (`HcKVugtv8k1Yr47c`). دي WooCommerce، عايزة key مخصوص read-only scope مربوط بـ `learrnsimply.com` بس. خزّنه في n8n credentials + سجّله في الـ brand `.env` (نفس باترن W1 §credentials).
>
> ⚠️ **بعد نقل الاستضافة:** الـ key لازم يتولّد من جديد على الاستضافة الجديدة (الـ keys مرتبطة بالـ DB/site). متفترضش القديم شغّال.

---

## 4. الإدخال (Input schema) — اللي عمر بيبعته

الأداة بتاخد **واحد** من الاتنين (الأقل واحد مطلوب):

```json
{
  "phone": "201011516829",   // اختياري — E.164 digits-only أو local مصري
  "email": "ahmed@example.com" // اختياري — لو موجود الأولوية ليه (أدق)
}
```

| الحقل | النوع | ملاحظات |
|---|---|---|
| `email` | string \| null | **الأولوية ليه لو موجود** — أدق من التليفون (الأوردرات مفهرسة بالإيميل). يتـ lowercase + trim. |
| `phone` | string \| null | يتـ normalize قبل البحث (راجع §4.1). |

**قاعدة:** لو الاتنين فاضيين → الأداة ترجّع `not_found` فوراً من غير ما تنده WooCommerce (متضيّعش call).

### 4.1 تطبيع التليفون (Phone normalization) — حرج
الـ Postgres `omar.contacts.phone` بيتخزّن **E.164 digits-only** (مثال `201011516829`)، لكن WooCommerce `billing.phone` بيتسجّل غالباً بالصيغة المصرية المحلية (`01011516829`) أو بصيغ متنوعة (مسافات، `+20`، `0020`). عشان كده الأداة بتجرّب **أكتر من variant** في الـ `search`:

```
المدخل: 201011516829  (E.164)
variants للبحث (بالترتيب، أول واحد يلاقي أوردر يكسب):
  1) 01011516829     ← المصري المحلي (الأرجح في billing.phone)
  2) 201011516829    ← E.164 من غير +
  3) +201011516829   ← E.164 بـ +
  4) 1011516829      ← من غير الصفر/كود الدولة (آخر 10 أرقام)
```
> منطق التطبيع: شيل أي رموز غير رقمية؛ لو بيبدأ بـ `20` وطوله 12 → الـ local = `0` + آخر 10. لو بيبدأ بـ `0` وطوله 11 → ده local بالفعل. خزّن الأربع variants وكرّر الـ `search` لحد ما واحد يرجّع نتيجة (max 4 calls، بنوقف عند أول نجاح). الإيميل دايماً call واحد.

---

## 5. الإخراج (Output schema) — اللي عمر بيقراه

الأداة بترجّع **object واحد ثابت الشكل** (الموديل بيقرا منه). الحقول دايماً موجودة (null لو مش متاح) عشان الموديل مايتلخبطش.

```json
{
  "found": true,
  "match_type": "email",            // "email" | "phone" | "none"
  "order_count": 1,                  // عدد الأوردرات اللي اتلاقت (بعد التصفية)
  "latest_order": {
    "order_id": 48213,
    "status": "processing",          // raw WC status
    "status_label": "قيد المعالجة",  // ترجمة عربية مبسّطة (راجع §5.1)
    "is_paid": true,                 // مشتق: status ∈ {processing, completed}
    "date_created": "2026-06-01 14:32:05",
    "date_paid": "2026-06-01 14:33:10",
    "total": "450.00",
    "currency": "EGP",
    "payment_method": "Kashier",
    "items": ["كورس جافا للمبتدئين"]  // line_items[].name — الأسماء العربية كاملة
  },
  "all_orders": [                     // ملخص خفيف لكل أوردر (≤10) للـ context
    { "order_id": 48213, "status": "processing", "is_paid": true,
      "total": "450.00", "items": ["كورس جافا للمبتدئين"], "date_created": "2026-06-01 14:32:05" }
  ],
  "note": null                        // رسالة تشخيصية لعمر لو فيه حالة خاصة (راجع §6/§7)
}
```

### 5.1 خريطة الحالات (WC status → label عربي + `is_paid`)
| WC status | `status_label` | `is_paid` | معنى لعمر |
|---|---|---|---|
| `completed` | مكتمل | ✅ true | دفع وتمّ — لو ملقاش الكورس = مشكلة access بحتة (B-F2). |
| `processing` | قيد المعالجة | ✅ true | **دفع بالفعل** (راجع §8 قاعدة الـ 662) — تفعيل يدوي شغّال؛ صعّد access بثقة. |
| `on-hold` | معلّق | ❌ false | مستني تأكيد دفع (محفظة/تحويل). متقولش "اتدفع". |
| `pending` | في انتظار الدفع | ❌ false | الأوردر اتعمل والدفع ما اكتملش. |
| `failed` | فشل الدفع | ❌ false | **ده مفتاح B-payfail** — الدفع فشل تقنياً (الـ Kashier/CC issue). |
| `cancelled` | ملغي | ❌ false | اتلغى. |
| `refunded` | مُسترجع | ❌ false | اتسترجع قبل كده. |
| `trash` / غير معروف | — | ❌ false | يتجاهل (مايتعرضش كأوردر صالح). |

> **`is_paid`** مشتق محلياً = `status ∈ {processing, completed}`. عمر بيعتمد عليه، مش بيفسّر الـ raw status بنفسه.

---

## 6. حالة "مش موجود" (not-found)

لو مفيش أوردر بأي variant (تليفون) ولا بالإيميل:
```json
{ "found": false, "match_type": "none", "order_count": 0,
  "latest_order": null, "all_orders": [],
  "note": "no_order_for_identifier" }
```
**عمر بيعمل إيه وقتها (مهم):**
- **مايقولش للعميل "إنت مدفعتش"** — ممكن دفع بإيميل/تليفون تاني، أو الأوردر guest بصيغة مختلفة.
- يقول بلطف: *"مش لاقي أوردر بالبيانات دي — ممكن تكون دفعت بإيميل تاني؟ ابعتلي الإيميل اللي وصلك عليه تأكيد الدفع."* (سؤال واحد في المرة.)
- لو لسه not_found بعد المحاولة التانية → **يصعّد بأي حال** (نوع `access` أو `payment`) مع `note` إن الـ lookup مرجّعش أوردر، عشان الفريق يدوّر يدوي. **عدم وجود نتيجة ≠ العميل بيكذب.**

---

## 7. الأخطاء والـ rate/error handling

نفس فلسفة W1: **الأداة عمرها ما بترمي exception توقف الـ agent.** أي فشل بيرجّع object صالح بـ `note` تشخيصية، وعمر بيكمّل بأمان (يصعّد بشري).

| الحالة | السلوك |
|---|---|
| **WooCommerce 5xx / timeout** | `retryOnFail: 3× / 2s` (نفس W1). لو فضل فاشل → ترجّع `{found:false, note:"wc_unreachable"}`. عمر يقول "النظام عندي مش بيوصل لبيانات الأوردر دلوقتي، بس وصّلت الموضوع للفريق" + يصعّد. |
| **401/403 (key غلط/منتهي/اتولّد من جديد بعد النقل)** | ترجّع `{found:false, note:"wc_auth_failed"}`. **مايتعرضش للعميل** — يصعّد + الـ note بتنبّه الفريق إن الـ key محتاج تجديد. |
| **429 (rate limit)** | احترم الـ `retryOnFail` backoff. WooCommerce read نادراً يـ throttle، بس لو حصل → عامله معاملة الـ 5xx. |
| **الرد مش JSON / شكل غير متوقع** | الـ defensive JS بيلفّ الـ parse في try/catch → `{found:false, note:"wc_bad_response"}`. |
| **أكتر من أوردر بحالات مختلطة** | رجّعهم كلهم في `all_orders`؛ `latest_order` = الأحدث. عمر بيلخّص الأحدث للعميل ويبعت الكل للفريق في التصعيد. |
| **حماية الـ payload** | استخدم `_fields` (§2.3) — مانرجّعش بيانات أوردر كاملة للموديل (مفيش عناوين/ملاحظات حساسة تتسرّب في الـ prompt). |

> **حد أقصى للـ calls في النداء الواحد:** 4 (تليفون variants) أو 1 (إيميل) + fallback customers call واحد = **5 calls كحد أقصى**. بنوقف عند أول نجاح.

---

## 8. ⚠️ قاعدة الـ 662 أوردر "processing" (read-only context — متلمسهاش)

> **خلفية تشغيلية مهمة، مش action.** على الموقع فيه حالياً **~662 أوردر بحالة `processing`** بيتم تفعيلها/إنرولها **يدوياً بواسطة Ahmed**. ده **مقصود** — مش bug ولا queue معطّل.

**التبعات على `order_lookup`:**
1. **الأداة read-only تجاهها 100%.** عمرها ما بتغيّر status، ما بتـ complete أوردر، ما بتعمل enrol. لو لقت `processing` → بتقراها وخلاص.
2. `processing` = **مدفوع** (`is_paid: true`). فلو عميل في B-F2 قال "دفعت وملقتش الكورس" والأداة لقت `processing` → ده **متوقّع** (Ahmed لسه بيفعّل يدوي). عمر:
   - يطمّن: *"تمام، الدفع وصل والكورس بيتفعّل على حسابك — خليني أأكّد للفريق يسرّعهولك."*
   - يصعّد `access` بثقة (معاه order_id + الكورس مؤكّدين).
   - **متوعدش بوقت محدد** (مفيش SLA مؤكّد لسه).
3. **متقولش للعميل "أوردرك processing يعني مش متفعّل"** — ده تفصيل داخلي. للعميل: "الدفع وصل والفريق بيظبّطلك الوصول."

> أي تغيير على الـ 662 (batch enrol, status flip) = **قرار بشري لـ Ahmed/Omar**، خارج نطاق الأداة دي تماماً.

---

## 9. ربط الأداة في الـ Flows (بالظبط إزاي عمر بيستخدمها)

### 9.1 Flow **B-payfail** — "الدفع فشل / اتخصم وملقتش الكورس"
الترتيب اللي عمر بيمشي بيه (من `omar-system-prompt.md` §B-payfail):
1. اعتذار لطيف.
2. اجمع الإيميل (سؤال واحد) → + رقم الأوردر لو عند العميل.
3. **نداء `order_lookup`** بالإيميل (أو التليفون لو مفيش إيميل):
   - **`status = failed`** → اتأكّد إن الدفع فشل تقنياً (غالباً Kashier/CC). عمر: *"شكلها معاملة دفع ما كمّلتش — وصّلت التفاصيل للفريق يراجعوها ويظبّطوك."* → `escalate` نوع **`payment`** مع `order_id` + `status` في الملخص.
   - **`is_paid = true`** (processing/completed) لكن العميل بيقول ملقاش الكورس → ده **مش payfail، ده access** → حوّل لمسار **B-F2** (صعّد نوع `access` مش `payment`).
   - **`not_found`** → اسأل عن إيميل تاني مرة واحدة (§6)؛ لسه لا → `escalate` نوع **`payment`** مع `note` الـ lookup.
4. **متأكدش إن الفلوس اتخصمت أو هترجع** (قاعدة ذهبية 3). الأداة بتقول status بس، مش حركة الفلوس البنكية.

### 9.2 Flow **B-F2 (access)** — "دفعت وملقتش الكورس"
الترتيب (من `omar-system-prompt.md` §B-F2 + قاعدة الـ 662):
1. طمّن: *"الكورس المفروض بيتفعّل تلقائياً بعد الدفع — خليني أتأكدلك وأظبّطه."*
2. اجمع الإيميل (سؤال واحد) + اسم الكورس.
3. **نداء `order_lookup`** بالإيميل:
   - **`is_paid = true`** (`processing` أو `completed`) → الدفع مؤكّد. عمر يصعّد **`access`** ومعاه **order_id + اسم الكورس + status** مؤكّدين (مش بس ادعاء العميل). رسالة العميل: *"الدفع وصل، وصّلت الموضوع للفريق هيتفعّلهولك ونبلّغك فوراً 🙌."*
   - **`status = failed/pending/on-hold`** → يبقى **مدفعش فعلياً** → حوّل لمنطق B-payfail (الأداة كشفت إن المشكلة دفع مش access). عمر: *"شكل الدفع ما كملش — تحب نحاول نظبّطه؟"* → `escalate` نوع `payment`.
   - **`not_found`** → §6 (اسأل عن إيميل تاني، وبعدين صعّد `access` بأي حال مع note).
4. **خطوات الدخول الحرفية لسه GAP** (مستنية Ahmed) — الأداة بتأكّد الدفع بس، مش بتدي خطوات الدخول. لحد ما Ahmed يبعت الخطوات، السلوك = أكّد الدفع + صعّد.

> **الفرق الجوهري اللي الأداة بتحسمه بين الـ flowين:** هل المشكلة **دفع** (`failed/pending` → B-payfail/`payment`) ولا **access** (`is_paid=true` بس مفيش كورس → B-F2/`access`). من غير الأداة، عمر بيخمّن؛ معاها، بيصعّد بالنوع الصح ومعلومة مؤكّدة.

---

## 10. تنفيذ n8n (skeleton) — للبناء لاحقاً

نفس باترن W1: sub-workflow بـ 3 مراحل + defensive JS.

```
Tool Trigger (من الـ Agent)
  → Normalize Input (Code)         ← lowercase email, build phone variants (§4.1), early not_found لو الاتنين فاضيين
  → WC Search Orders (HTTP Request) ← GET /wc/v3/orders?search=…&_fields=…  · httpBasicAuth · retryOnFail 3×/2s
       (loop/branch على phone variants لحد أول نجاح؛ أو email call واحد + customers fallback)
  → Shape Output (Code)            ← map status→label، اشتق is_paid، خد latest + ملخص all_orders، حطّ note
  → return للـ Agent
```

| Node | النوع | بيعمل إيه |
|---|---|---|
| **Tool Trigger** | Workflow Tool (sub-workflow) | يستقبل `{phone?, email?}` من الـ Agent node. |
| **Normalize Input** | Code (JS) | يطبّع الإيميل/التليفون، يبني الـ variants، يرجّع `not_found` بدري لو مفيش identifier. **never throws.** |
| **WC Search Orders** | HTTP Request | `httpBasicAuth` (WC read-only key). `retryOnFail: 3 / 2000ms`. `onError: continueRegularOutput` (الفشل يبقى note، مش crash). |
| **Shape Output** | Code (JS) | يلفّ الـ parse في try/catch، يبني الـ output schema الثابت (§5)، يترجم الحالات، يشتق `is_paid`. |

**Credential مطلوب:** WooCommerce read-only Basic Auth (placeholder §3) — يتضاف لـ n8n credentials + brand `.env`.

---

## 11. اختبار (قبل go-live — كله بأرقام تجريبية، مفيش live call دلوقتي)

| Case | Input | المتوقّع |
|---|---|---|
| إيميل بأوردر `completed` | `{email}` | `found:true, is_paid:true, status_label:"مكتمل"`، items صح، عربي سليم. |
| إيميل بأوردر `processing` | `{email}` | `is_paid:true` + سلوك B-F2 (صعّد access بثقة). راجع §8. |
| إيميل بأوردر `failed` | `{email}` | `is_paid:false, status_label:"فشل الدفع"` → سلوك B-payfail. |
| تليفون E.164 (`201…`) | `{phone}` | الـ variants (§4.1) تلاقي الأوردر المسجّل بـ `01…`. |
| إيميل ملوش أوردر | `{email}` | `found:false, note:"no_order_for_identifier"` → سلوك §6 (متقولش "مدفعتش"). |
| الاتنين فاضيين | `{}` | `not_found` فوراً، **0 calls** لـ WooCommerce. |
| key غلط (401) | أي | `{found:false, note:"wc_auth_failed"}` — مايتعرضش للعميل، يصعّد. |
| WC down (timeout) | أي | retry 3× → `{found:false, note:"wc_unreachable"}` → صعّد بأمان. |
| Arabic في `line_items` | أوردر بكورس عربي | الأسماء العربية كاملة (`كورس جافا للمبتدئين`) — UTF-8 سليم (زي W1). |

---

## 12. تنبيهات (متعملهاش)

- **Read-only مطلق.** الأداة دي GET بس. عمرها ما تـ POST/PUT/DELETE على WooCommerce. أي تفعيل/استرجاع = بشري عبر `escalate`.
- **متفسّرش حركة الفلوس.** الـ status بيقول حالة الأوردر، **مش** هل الفلوس اتخصمت من البنك فعلاً. عمر مايقولش "الفلوس اتخصمت/هترجع" (قاعدة ذهبية 3).
- **الإيميل أولوية على التليفون** لو الاتنين موجودين — أدق وأرخص (call واحد).
- **بعد نقل الاستضافة:** جدّد الـ WooCommerce read-only key على الاستضافة الجديدة، وأكّد الـ base URL لسه `learrnsimply.com` (double-r). اختبر `wc_auth_failed` مايظهرش.
- **متخلطش credentials:** WooCommerce key ≠ Mautic Basic (`HcKVugtv8k1Yr47c`). credential منفصلة، scope read، مربوطة بـ WooCommerce بس.
- **الـ 662 processing = مقصود** (Ahmed بيفعّل يدوي). الأداة بتقراها كـ "مدفوع"، متعتبرهاش خطأ، ومتلمسهاش (§8).
