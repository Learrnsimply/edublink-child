# `escalate` Tool + Workflow B (`omar-alert`) — Spec

> **الحالة:** 📐 spec فقط (في مرحلة التصميم — مفيش تنفيذ، مفيش نشر على prod). الملف ده هو العقد اللي n8n هيتبني عليه.
> **الملفات الأخوات:** `../omar-system-prompt.md` (العقل — متى ينده `escalate`) · `../postgres-schema.sql` (جدول `omar.escalations`) · `../omar-build-plan.md` §2 Workflow B · `../whatsapp-agent-design.md` §🔔 أداة الإنذار · `../../n8n/workflows/W1-wc-mautic-sync.md` + `W2-dart-waitlist-popup.md` (أنماط الـ HTTP/Mautic nodes اللي بنعيد استخدامها).
> **المصدر الوحيد للحقيقة للحقائق:** `../../../03_KNOWLEDGE/knowledge-base.md`. الأرقام/الإيميلات هنا منقولة منه ومن العقد (CANONICAL CONTRACT).

The agent (`عمر`) calls one tool — **`escalate`** — whenever a real human-handoff is needed. That tool call triggers a single n8n sub-workflow — **`omar-alert`** (Workflow B) — which does exactly two things:

1. **INSERT** one row into `omar.escalations` (the durable safety-net log).
2. **Fan out** an alert over **both** channels: **email** (Ahmed + Omar) **and** **WhatsApp/Evolution** (Omar `01011516829` + Ahmed `<AHMED_WHATSAPP>` placeholder).

> ⚠️ **`عمر` لا يقرر النتيجة، بس بيوصّل.** هو بيكتب الـ escalation ويطمّن العميل بجملة الطمأنة المناسبة. الحل بشري (Ahmed/Omar). راجع القاعدة الذهبية #3 في الـ system prompt: عمره ما يقرّر فلوس/استرجاع/خصم.

---

## 1) أداة `escalate` (اللي بيندهها `عمر`)

### إمتى يندهها (من الـ system prompt)
أي مشكلة حقيقية في مسار B / C / D / F:

| مسار | نوع التصعيد (`type`) | الأولوية الافتراضية |
|---|---|---|
| B-F2 — دفع وملقاش الكورس | `access` | `high` |
| B-cert — الشهادة مش ظاهرة | `certificate` | `normal` |
| B-payfail — فشل دفع داخلي / اتخصم وملقاش الكورس | `payment` | `high` |
| C — كارت أجنبي رفض (دفع برّه مصر) | `payment_intl` | `normal` |
| F1 — الموقع/الحصص مش بتفتح | `site_access` | `high` |
| F5a — طلب استرجاع | `refund` | `high` |
| F5b — عميل غاضب | `angry` | **`urgent` 🔴** |
| D — تواصل خاص لأحمد (private/تعاون/إعلان/طلب كورس) | `for_ahmed` | `normal` |

> ⚠️ **متلخبطش بين enum القيم.** القيم الحرفية (snake_case، تطابق `postgres-schema.sql` enum `omar.escalation_type`):
> `access` · `certificate` · `payment` · `payment_intl` · `site_access` · `refund` · `angry` · `for_ahmed`.
> (في الـ system prompt القديم اتكتبوا أحياناً بـ شرطة: `payment-intl`, `for-ahmed`, `site-access` — **الـ DB enum بـ underscore**. الـ tool layer لازم يطبّع dash→underscore دفاعياً قبل ما يكتب في الـ DB — راجع §Validation.)

### Input schema (اللي الموديل بيطلّعه كـ tool args)

```jsonc
{
  "type":            "site_access",            // REQUIRED — enum omar.escalation_type (8 قيم أعلاه)
  "summary":         "العميل دفع كورس جافا وملقاش الكورس على حسابه. دفع بإيميل x.",  // REQUIRED — ملخص عمر بالعربي
  "customer_name":   "أحمد محمد",              // optional — للعرض في الإنذار (مش عمود في DB؛ بييجي من omar.contacts.name)
  "customer_email":  "buyer@example.com",      // optional — الإيميل اللي العميل قاله (للأوردر/الدخول)
  "order_id":        "12345",                  // optional — رقم الأوردر لو العميل قاله
  "screenshot_url":  "https://.../media/abc",  // optional — لينك سكرين العميل (Evolution media URL) — F1 غالباً
  "priority":        "urgent"                  // optional — لو الموديل سابها، الـ workflow بيحسبها من type (راجع §Priority)
}
```

**ملاحظات input:**
- `phone` **مش** في الـ input — بييجي من سياق الـ workflow (الـ webhook الأصلي keyed على phone) عشان الموديل ما يقدرش يزوّره. الـ `escalate` tool node بيحقن `phone` من الـ execution context (E.164 digits-only، نفس مفتاح `omar.contacts.phone`).
- `context_snapshot` (آخر رسائل المحادثة) **مش** من الموديل — الـ workflow بيبنيه من `omar.messages` (آخر ~10 رسائل لنفس الـ phone) عشان يكون مصدر موثوق مش رواية الموديل.
- `summary` لازم عربي مصري بسيط (هو اللي بيتعرض لأحمد/عمر في الإنذار).

### Output schema (اللي الـ tool بيرجّعه للموديل)

```jsonc
{
  "ok": true,
  "escalation_id": 87,            // omar.escalations.id (BIGINT)
  "priority": "high",             // الأولوية النهائية اللي اتحسبت
  "type": "site_access",
  "alerted": { "email": true, "whatsapp": true },   // نجاح كل قناة (راجع §Partial failure)
  "reassurance_flow": "support"   // "support" → جملة "وصّلت الفريق…" · "ahmed" → "وصّلت طلبك لأحمد…"
}
```

> الموديل **مايعرضش** الـ `escalation_id` للعميل. بيستخدم `reassurance_flow` بس عشان يختار جملة الطمأنة الصح (§Customer reassurance) ويكمّل طبيعي.

### Customer-facing reassurance (الموديل بيقولها بعد ما الأداة ترجع)
الجملة بتختلف حسب نوع التصعيد — معرّفة في `whatsapp-agent-design.md` §🔔:

| `reassurance_flow` | الأنواع | الجملة (الموديل يقولها — متطابقة مع الـ flows) |
|---|---|---|
| **`support`** | `access`, `certificate`, `payment`, `payment_intl`, `site_access`, `refund`, `angry` | **"وصّلت الموضوع للفريق، هيراجعوه ويتواصلوا معاك بأسرع وقت."** (تنويعات per-flow في الـ system prompt: F1 = "هتتحل وهنبلّغك فوراً"، refund = "هيراجعوه ويردّوا عليك"، angry = "هيتواصلوا معاك بأسرع وقت".) |
| **`ahmed`** | `for_ahmed` | **"وصّلت طلبك لأحمد، وهيتواصل معاك في أقرب وقت."** |

> `reassurance_flow` بيتحدّد من `type`: `for_ahmed` → `ahmed`، أي حاجة تانية → `support`. الـ workflow بيرجّعه في الـ output عشان الموديل ما يغلطش.

---

## 2) Workflow B — `omar-alert` (sub-workflow)

### Pipeline

```
Execute Workflow Trigger (من tool escalate)
  → Normalize & Resolve (Code: طبّع type، احسب priority + reassurance_flow، اجلب phone)
  → Build context_snapshot (Postgres: آخر ~10 رسائل من omar.messages)
  → INSERT omar.escalations (Postgres)  →  [يرجّع escalation_id]
  → (fan-out, متوازي)
       ├─ Build Alert Message (Code: قالب الإنذار العربي الموحّد)
       ├─ Email → Ahmed + Omar          (SMTP / Send Email node)
       └─ WhatsApp → Omar + Ahmed       (Evolution sendText — HTTP Request, نمط آمن)
  → Mark alerted_at (Postgres UPDATE)  →  Respond (escalation_id + alerted{} للـ tool)
```

> **ليه INSERT الأول قبل الإنذار:** نفس مبدأ الـ durability في W1/W2 — الصف بيتكتب في `omar.escalations` **قبل** أي إرسال. حتى لو الإيميل/الواتساب فشلوا، التصعيد **مش بيضيع** (بيفضل `status='open'` ويظهر في `omar.open_escalations` view لحد ما حد بشري يقفله). `alerted_at` بيتسجّل بس لو الإنذار اتبعت فعلاً.

### Nodes — تفصيل

| # | Node | النوع | بيعمل إيه |
|---|---|---|---|
| 1 | **Escalate Trigger** | Execute Workflow Trigger | يستقبل الـ input من tool `escalate` (الحقول في §Input + المحقون `phone`). |
| 2 | **Normalize & Resolve** | Code (JS) | (a) `type` → lower + dash→underscore، يتحقق إنه من الـ 8 enum (مش كده → fallback `for_ahmed`/`support` + flag). (b) يحسب `priority` (§Priority). (c) يحسب `reassurance_flow`. (d) يتأكد `phone` digits-only E.164. **مايرميش أبداً** (نمط W2 defensive). |
| 3 | **Load Recent Messages** | Postgres (Select) | `SELECT role, body, created_at FROM omar.messages WHERE phone = $1 ORDER BY created_at DESC LIMIT 10` → يتعكس ترتيبه → يتحوّل JSON لـ `context_snapshot`. |
| 4 | **Insert Escalation** | Postgres (Insert/Query) | `INSERT INTO omar.escalations (...) RETURNING id` (الـ SQL تحت). بيرجّع `escalation_id`. |
| 5 | **Build Alert Message** | Code (JS) | يركّب نص الإنذار العربي الموحّد (§Alert template) من الصف + `customer_name` (من contacts) + `context_snapshot`. |
| 6 | **Email Alert** | Send Email (SMTP) | `to: ahmedadel123422@gmail.com, omarabdo385@gmail.com` · `subject` من القالب · body = نص الإنذار. `retryOnFail 3×/2s` + `onError: continueRegularOutput`. |
| 7 | **WhatsApp Alert (Omar)** | HTTP Request → Evolution `sendText` | لـ `01011516829`. `retryOnFail 3×/2s` + `onError: continueRegularOutput`. |
| 8 | **WhatsApp Alert (Ahmed)** | HTTP Request → Evolution `sendText` | لـ `<AHMED_WHATSAPP>` placeholder. **disabled/skipped لو الـ placeholder لسه مش متحطوط** (راجع §Placeholders). نفس الـ retry/onError. |
| 9 | **Mark Alerted** | Postgres (Update) | `UPDATE omar.escalations SET alerted_at = now() WHERE id = $1` — بس لو قناة واحدة على الأقل نجحت. |
| 10 | **Respond to Tool** | (last node) | يرجّع `{ ok, escalation_id, priority, type, alerted:{email,whatsapp}, reassurance_flow }`. |

> **ليه `onError: continueRegularOutput` على قنوات الإرسال:** نفس درس W2 — التصعيد اتكتب في الـ DB خلاص؛ فشل قناة واحدة (مثلاً Evolution مؤقتاً down) **ما ينفعش** يفجّر الـ tool call أو يمنع القناة التانية. كل قناة بتفشل بمفردها، والـ output بيقول أنهي قناة نجحت.

### SQL — INSERT (Node 4)

```sql
INSERT INTO omar.escalations
  (phone, type, priority, summary, customer_email, order_id, screenshot_url, context_snapshot)
VALUES
  ($1,    $2,   $3,       $4,      $5,             $6,       $7,             $8::jsonb)
RETURNING id, priority, type;
```

| param | المصدر | ملاحظة |
|---|---|---|
| `$1 phone` | execution context (محقون، مش من الموديل) | FK لـ `omar.contacts.phone`. لازم الـ contact موجود (الـ inbound workflow بيـ upsert الـ contact قبل أي escalate). |
| `$2 type` | `escalate.type` بعد التطبيع | enum `omar.escalation_type`. |
| `$3 priority` | محسوب (§Priority) | enum `omar.priority`. |
| `$4 summary` | `escalate.summary` | NOT NULL. ملخص عمر العربي. |
| `$5 customer_email` | `escalate.customer_email` | NULL لو مش موجود. |
| `$6 order_id` | `escalate.order_id` | TEXT، NULL لو مش موجود. |
| `$7 screenshot_url` | `escalate.screenshot_url` | NULL لو مفيش سكرين. |
| `$8 context_snapshot` | Node 3 output | JSONB — آخر ~10 رسائل. |

> الأعمدة `status` (default `open`)، `created_at` (default `now()`)، `updated_at` (trigger) **مش بنكتبها** — الـ schema بيحطها. `alerted_at` بيتحدّث في Node 9 بعد الإرسال. **متخترعش أعمدة مش في الـ schema.**

### Priority (Node 2)
- لو الموديل بعت `priority` صريح → استخدمه (بشرط إنه من الـ enum `normal|high|urgent`).
- غير كده، احسبه من `type`:

```js
const PRIORITY_BY_TYPE = {
  angry:        'urgent',   // 🔴 عميل غاضب = عاجل دايماً
  refund:       'high',
  payment:      'high',
  access:       'high',
  site_access:  'high',
  payment_intl: 'normal',
  certificate:  'normal',
  for_ahmed:    'normal',
};
```

> **`angry` = `urgent` 🔴 ثابت** (مذكور حرفياً في العقد + الـ design doc). الإيموجي 🔴 بيظهر في رأس الإنذار للأولوية `urgent`.

---

## 3) قالب رسالة الإنذار (Arabic — موحّد لكل القنوات)

نفس النص يتبعت إيميل + واتساب (مع اختلاف بسيط: الإيميل subject منفصل). الإيموجي للأولوية: `urgent`=🔴 · `high`=🟠 · `normal`=🟢.

**Subject (إيميل):**
```
[عمر] {priorityEmoji} تصعيد {typeLabel} — {customer_name} ({phone})
```

**Body (إيميل + واتساب — نفس النص):**
```
🔔 تصعيد جديد من عمر (خدمة عملاء اتعلم ببساطة)

🏷️ النوع: {typeLabel}
{priorityEmoji} الأولوية: {priorityLabel}

👤 العميل: {customer_name}
📱 واتساب: {phone}
📧 إيميل: {customer_email أو "—"}
🧾 رقم الأوردر: {order_id أو "—"}

📝 ملخص عمر:
{summary}

📎 سكرين شوت: {screenshot_url أو "مفيش"}

💬 آخر رسائل المحادثة:
{context_snapshot مفرود — كل سطر: "العميل: …" / "عمر: …"}

—————
🆔 escalation #{escalation_id} · الحالة: مفتوح
الحل بشري: ادخل ظبّط الموضوع، وبعد ما تخلص علّم التصعيد كـ resolved.
```

**جداول الـ labels (Node 5):**

```js
const TYPE_LABEL = {
  access:       'دفع وملقاش الكورس (تفعيل)',
  certificate:  'الشهادة مش ظاهرة',
  payment:      'فشل دفع داخلي',
  payment_intl: 'دفع من برّه مصر (كارت أجنبي)',
  site_access:  'الموقع/الحصص مش بتفتح',
  refund:       'طلب استرجاع',
  angry:        'عميل غاضب',
  for_ahmed:    'تواصل خاص لأحمد',
};
const PRIORITY_LABEL = { urgent: 'عاجل', high: 'مهم', normal: 'عادي' };
const PRIORITY_EMOJI = { urgent: '🔴',  high: '🟠',  normal: '🟢'  };
```

> رسالة الواتساب (Evolution) بتاخد نفس الـ Body. واتساب بيدعم الإيموجي + الأسطر عادي. اللينكات (سكرين) بتظهر clickable في واتساب تلقائياً.

---

## 4) وجهات الإنذار (CANONICAL — متطابقة مع العقد + design doc §Config)

| القناة | المستقبِل | القيمة | المصدر |
|---|---|---|---|
| 📧 إيميل | أحمد | `ahmedadel123422@gmail.com` | العقد + build-plan §2 |
| 📧 إيميل | عمر (Omar) | `omarabdo385@gmail.com` | العقد |
| 📱 واتساب | عمر (Omar) | `01011516829` | العقد + design doc §Config |
| 📱 واتساب | أحمد | `<AHMED_WHATSAPP>` ⏳ placeholder | **Omar هيسأل أحمد** — لسه مش متوفّر |

> **تمييز مهم (من design doc):** رقم أحمد للإنذار ≠ رقم واتساب المساعد اللي عمر بيشتغل عليه. الاتنين placeholders لحد ما أحمد يبعتهم.
> **إيميل الإرسال (from):** نفس بنية Mautic/SMTP (`contact@learrnsimply.com` أو الـ from المعتمد) — مش credential هنا، بييجي من n8n SMTP credential.

---

## 5) Evolution `sendText` — نمط آمن (Nodes 7+8)

> **inbound فقط هو القاعدة العامة للعميل** — بس **إنذارات الفريق الداخلية** (لعمر/أحمد) استثناء مقصود: ده outbound لأعضاء الفريق نفسهم، مش للعميل. عمر **عمره ما يبدأ outbound لعميل**.

HTTP Request node (نفس بنية HTTP الآمنة في W1/W2):

```
POST  {{EVOLUTION_BASE}}/message/sendText/{{EVOLUTION_INSTANCE}}
Headers:
  apikey: <EVOLUTION_API_KEY>          // placeholder — من n8n credential، مش inline
  Content-Type: application/json
Body (JSON):
  {
    "number": "201011516829",          // E.164 digits-only (placeholder لأحمد: <AHMED_WHATSAPP>)
    "text":   "{{ alertBody }}"
  }
Options: retryOnFail 3× / 2s · onError: continueRegularOutput
```

| placeholder | معناه |
|---|---|
| `{{EVOLUTION_BASE}}` | base URL لـ Evolution على الـ VPS (من `.env`/credential — مش متحط هنا). |
| `{{EVOLUTION_INSTANCE}}` | اسم instance رقم المساعد (⏳ من أحمد). |
| `<EVOLUTION_API_KEY>` | مفتاح Evolution — **n8n credential**، مش inline في الـ workflow. |
| `<AHMED_WHATSAPP>` | رقم أحمد للإنذار — ⏳ مش متوفّر. Node 8 **disabled** لحد ما يتحط. |

> **رقم عمر `01011516829`** محلي مصري → في الـ body يتبعت `201011516829` (E.164 digits-only، نفس تطبيع `omar.contacts.phone`).

---

## 6) Telegram (اختياري — لاحقاً، مش دلوقتي)

لو ضفنا تنبيه تليجرام للفريق لاحقاً (زي rspaac): **استخدم الـ bot الموجود** `_agency/notify.sh` (token + chat_id `6726176133` مدفونين فيه) — **متعملش bot جديد**. الـ pattern: `source _agency/notify.sh && notify "<alert text>" warn`. ده **خارج نطاق الـ spec ده** — مذكور بس عشان ما حدش يخترع bot تاني وقت الإضافة.

---

## 7) Validation & edge cases (نمط W2 الدفاعي — Node 2 مايرميش أبداً)

| الحالة | السلوك |
|---|---|
| `type` بـ dash (`for-ahmed`, `payment-intl`, `site-access`) | طبّع dash→underscore قبل الـ DB. |
| `type` مش من الـ 8 enum | fallback `for_ahmed` + `priority='normal'` + flag في `summary` ("⚠️ نوع غير معروف: <raw>") عشان يتراجع بشري — **مايفشلش الـ insert**. |
| `summary` فاضي | الـ DB `NOT NULL` يرفض → Node 2 يحط placeholder `"(عمر مبعتش ملخص — راجع المحادثة)"` عشان الصف يتكتب ومايضيعش. |
| `phone` مش موجود في `omar.contacts` | الـ inbound workflow بيـ upsert الـ contact **قبل** أي escalate، فالـ FK دايماً موجود. لو لأي سبب مش موجود → الـ insert يفشل بـ FK error؛ الـ workflow يـ log + يبعت الإنذار بدون escalation_id (degraded، نادر). |
| `customer_email` / `order_id` / `screenshot_url` فاضيين | NULL في الـ DB + "—"/"مفيش" في القالب. عادي (مش كل تصعيد فيه سكرين أو أوردر). |
| **Partial failure** (إيميل نجح، واتساب فشل أو العكس) | كل قناة `onError: continueRegularOutput` → الصف اتكتب، `alerted_at` يتحدّث لو **قناة واحدة على الأقل** نجحت، والـ output `alerted:{email:bool, whatsapp:bool}` بيقول الحقيقة. التصعيد يفضل `open` لحد ما حد يقفله بشري بغض النظر. |
| `<AHMED_WHATSAPP>` لسه placeholder | Node 8 (واتساب أحمد) **disabled** → `alerted.whatsapp` بيعكس نجاح عمر بس. أحمد بيوصله الإيميل عادي. أول ما الرقم يتحط: فعّل Node 8. |
| تصعيد مكرر لنفس المشكلة (العميل بعت تاني) | مفيش dedup على الـ escalation level في v1 — كل نداء `escalate` = صف جديد. (لو بقى مزعج، أضف dedup على `(phone, type, status='open')` لاحقاً — مش في v1.) |

---

## 8) Deploy (بعد موافقة Omar — مفيش حاجة اتنفّذت)

> ⚠️ نفس قاعدة الـ build-plan: **مفيش نشر على prod VPS بدون موافقة صريحة.** الـ spec ده file-authoring بس.

1. الـ Postgres schema لازم يكون منشور الأول (`postgres-schema.sql` — جدول `omar.escalations` + enums).
2. ابني Workflow B في n8n عبر n8n-MCP (Execute Workflow Trigger + الـ nodes أعلاه).
3. اربط credentials: Postgres (`omar_agent`)، SMTP (نفس Mautic/n8n SMTP)، Evolution (`<EVOLUTION_API_KEY>` + instance).
4. حدّث الـ placeholders بأرقام أحمد الحقيقية (`<AHMED_WHATSAPP>` + Evolution instance) أول ما يبعتهم → فعّل Node 8.
5. اختبر برقم تجريبي: نداء `escalate` بكل نوع → تأكّد (a) الصف اتكتب صح في `omar.escalations`، (b) الإيميل + الواتساب وصلوا بالقالب الصح، (c) `priority` صح (خاصةً `angry`→`urgent`🔴)، (d) الموديل قال جملة الطمأنة الصح.
6. شيل أي test rows من `omar.escalations` بعد الاختبار (نفس نظافة W1/W2).

---

## 9) Checklist مطابقة العقد (CANONICAL)

- [x] الأداة اسمها بالظبط `escalate` (مش `escalation`/`alert`).
- [x] الـ sub-workflow اسمه `omar-alert` (Workflow B).
- [x] بيكتب في `omar.escalations` بالأعمدة الموجودة فقط (مفيش اختراع أعمدة).
- [x] enum `type` بالـ 8 قيم بالظبط (underscore): `access, certificate, payment, payment_intl, site_access, refund, angry, for_ahmed`.
- [x] `priority` enum `normal|high|urgent` · `angry`=`urgent`🔴.
- [x] fan-out على **إيميل (أحمد+عمر) + واتساب (عمر `01011516829` + أحمد placeholder)**.
- [x] جملتا الطمأنة: `support` = "وصّلت الفريق…" · `ahmed` = "وصّلت طلبك لأحمد…".
- [x] Evolution = القناة، inbound-only للعميل (إنذار الفريق outbound داخلي مقصود).
- [x] أي secret/رقم مجهول = placeholder (`<AHMED_WHATSAPP>`, `<EVOLUTION_API_KEY>`).
- [x] Telegram = الـ bot الموجود بس (`_agency/notify.sh`) لو اتضاف لاحقاً — مفيش bot جديد.
- [x] مفيش live send / SSH / API call — spec فقط.
