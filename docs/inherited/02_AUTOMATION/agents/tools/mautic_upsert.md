# 🧰 Tool Spec — `mautic_upsert`

> **مين بيستخدمها:** "عمر" (الـ AI Agent node في n8n) — مسار **A-close** (lead متردد ماقفلش) أو أي عميل محتاج متابعة بإيميل لاحقاً.
> **بتعمل إيه باختصار:** بتسجّل/بتحدّث الـ lead في **Mautic** بشكل idempotent (بحث بالإيميل → create أو update)، بتحطّ الـ tags الصح، وبتضيفه للـ segment المناسب — عشان فريق التسويق يتابعه بإيميل بعدين.
> **المصدر اللي بنعيد استخدامه (مش بنعيد اختراعه):** نمط الـ upsert من **W1** (`../../n8n/workflows/W1-wc-mautic-sync.md`) + خطوة إضافة الـ segment من **W2** (`../../n8n/workflows/W2-dart-waitlist-popup.md`). نفس الـ credential، نفس الـ endpoints، نفس قاعدة "اكتب اللي موجود بس، متمسحش بفاضي".
> **القناة:** inbound فقط. الأداة دي **بتسجّل** الـ lead، **مابتبعتلوش** أي رسالة. المتابعة بإيميل بتيجي من campaign تسويقي منفصل في Mautic (مش من عمر).

---

## 1. إمتى عمر يستخدمها (من الـ system prompt)

| الموقف | المسار | يستخدمها؟ |
|---|---|---|
| عميل اتردّد وماشتراش، وعنده إيميل | **A-close** | ✅ آه — ده الاستخدام الأساسي |
| عميل عايز يتابع معاه الفريق بعدين (نية شراء واضحة بس مش دلوقتي) | A-close | ✅ آه |
| عميل عنده مشكلة حقيقية (دفع/access/شهادة/غاضب/استرجاع) | B / C / F | ❌ لأ — ده شغل `escalate` مش `mautic_upsert` |
| عميل اشترى خلاص | — | ❌ لأ — الشراء بيتسجّل أوتوماتيك عبر **W1** (WC→Mautic). متعملش double-write |
| عميل سأل سؤال واترد وخلاص (مفيش نية متابعة) | A-direct | ⚪ اختياري — سجّله بس لو في إشارة اهتمام تستاهل متابعة |

> **قاعدة:** الأداة دي للـ **leads + متابعة**، مش للمشاكل (دي بتروح `escalate`) ولا للمشتريات (دي بتيجي من W1).
> **شرط أساسي:** لازم يبقى عندك **إيميل**. من غير إيميل مفيش upsert (الإيميل هو مفتاح الـ dedup). لو معندكش إيميل، اطلبه بأدب الأول؛ لو رفض، سيبه ومتصعّدش لمجرد ده.

---

## 2. Input schema (اللي عمر بيبعته للأداة)

```jsonc
{
  "email":          "string  (REQUIRED) — إيميل العميل. مفتاح الـ upsert. lowercased + trimmed.",
  "name":           "string  (optional) — اسم العميل كامل زي ما كتبه. بيتقسم firstname/lastname.",
  "tags":           "string[] (optional) — زيادة فوق الأساسي. الأداة دايماً بتضيف 'whatsapp-lead'.",
  "course_interest":"string  (optional) — الكورس اللي مهتم بيه (slug من الجدول §6، أو نص حر عربي).",
  "phone":          "string  (optional) — E.164 digits-only (مثال 201011516829). بيتكتب في حقل whatsapp_phone.",
  "country":        "string  (optional) — بلد العميل (يساعد مسار C الدفع الدولي لاحقاً)."
}
```

### قواعد التحقق (validation — على حدود النظام)
- **`email`** — مطلوب. لو فاضي أو مش شكل إيميل صحيح (regex بسيط `/^[^\s@]+@[^\s@]+\.[^\s@]+$/`) → **متعملش الـ call**، رجّع `skipped` (راجع §4). متخترعش إيميل.
- **`name`** — لو فاضي، الأداة مابتكتبش firstname/lastname خالص (مابتمسحش اسم موجود ببلانك). أول كلمة = `firstname`، الباقي = `lastname`.
- **`tags`** — الأداة دايماً بتضمّن `whatsapp-lead`. أي tag زيادة من عمر بيتضاف فوقه.
- **`phone`** — digits only، بدون `+`. لو فيه رموز، نظّفها. بيتكتب في الحقل المخصص `whatsapp_phone` (مش في حقل `phone` الافتراضي — ده للتليفون العادي).
- **كله trimmed.** صفر قيم فاضية بتتبعت لـ Mautic (راجع قاعدة non-empty-only تحت).

---

## 3. السلوك (Behavior) — idempotent upsert + segment add

نفس منطق W1/W2 بالظبط:

```
1. ابنِ الـ contact body من الـ input (field mapping §5) — اكتب الحقول الموجودة بس.
2. POST /api/contacts/new   (HTTP Basic Auth)
       → Mautic بيـ dedupe على الإيميل: لو الإيميل موجود = UPDATE، لو جديد = CREATE.
       → بيرجّع contact.id.
3. أضف للـ segment الصح:
       POST /api/segments/{SEGMENT_ID}/contact/{contact.id}/add
       (للـ WhatsApp lead: segment 8 whatsapp_contacts — راجع §7 لاختيار الـ segment)
4. (اختياري) رجّع contact.id لعمر عشان يخزّنه في omar.contacts.mautic_contact_id.
```

### ليه upsert مش create-only؟
`POST /api/contacts/new` على إيميل **موجود** = **update** (الـ total بيفضل 1، مفيش تكرار). ده متأكَّد تجريبياً في W1 + W2. يبقى آمن نناديها على نفس العميل أكتر من مرة (مثلاً سأل مرتين في يومين) من غير ما نخلق duplicates.

### Non-empty-only writes (قاعدة حرجة — متكسرهاش)
**عمرنا مانكتب قيمة فاضية فوق قيمة موجودة في Mautic.** لو عمر مابعتش `name`، الأداة مابتلمسش `firstname/lastname`. ده بيحمي الداتا اللي W1 (المشتريات) أو campaign تاني ممكن يكون كتبها قبل كده.

### Durability (نفس W1/W2)
- `retryOnFail: 3× / 2s` على الـ HTTP nodes — للأعطال اللحظية.
- خطوة الـ segment-add بتبقى `onError: continueRegularOutput` — لو الـ segment-add فشل، الـ contact + الـ tags خلاص اتحفظوا، فمفيش lead بيضيع علشان blip في الـ segment.

---

## 4. Output schema (اللي الأداة بترجّعه لعمر)

```jsonc
// نجاح
{ "status": "ok",      "mautic_contact_id": 1234, "action": "created" | "updated", "segment_added": 8 }

// اتخطّى (إيميل ناقص/غلط) — مش error، سلوك متوقّع
{ "status": "skipped", "reason": "missing_or_invalid_email" }

// فشل بعد الـ retries (نادر)
{ "status": "error",   "reason": "<short message>" }
```

**عمر بيستخدم الـ output إزاي:**
- `ok` → ممكن يخزّن `mautic_contact_id` في `omar.contacts.mautic_contact_id` (ربط بين الذاكرة المحلية و Mautic).
- `skipped` → يكمّل المحادثة عادي؛ **ميقولش للعميل حاجة** عن ده (تسجيل داخلي مش حاجة تخص العميل).
- `error` → يكمّل المحادثة عادي برضه؛ التسجيل best-effort، مش سبب يقلق العميل. (لو الموضوع محتاج بني آدم، ده شغل `escalate` مش هنا.)

> **مهم:** الأداة دي **مالهاش رسالة للعميل**. عمر مابيقولش "سجّلتك" — هو بيقفل الكلام بـ *"تحت أمرك في أي وقت 🙌"* زي ما في الـ prompt. التسجيل خلفي صامت.

---

## 5. Field mapping (input عمر → Mautic alias)

| Mautic field (alias) | المصدر من input عمر | ملاحظات |
|---|---|---|
| `email` | `email` | مفتاح الـ upsert. lowercased + trimmed. **مطلوب**. |
| `firstname` | أول كلمة من `name` | يتكتب لو غير فاضي بس. |
| `lastname` | باقي `name` | يتكتب لو غير فاضي بس. |
| `whatsapp_phone` | `phone` (digits only) | الحقل المخصص (id ضمن 44-54). **مش** حقل `phone` الافتراضي. |
| `course_interest` | `course_interest` | نص حر — اسم/slug الكورس اللي مهتم بيه. لو فيه قيمة قديمة، اكتب فوقها بالأحدث (مش append). |
| `source_channel` | ثابت `whatsapp` | ✅ **آمن هنا** — `whatsapp` قيمة موجودة في الـ option list بتاع الحقل (راجع §8 — ده فرق مهم عن W2). |
| `tags` | `whatsapp-lead` (ثابت) + `course-<slug>` (لو الكورس معروف) + أي tag زيادة من عمر | الـ tags بتتدمج، متمسحش القديم. |

> **حقول مابنلمسهاش هنا** (بتيجي من W1 أو campaigns تانية): `wc_customer_id`، `last_purchase_date`، `course_count`، `total_spent`، `cart_value`، `last_course_completed`، `telegram_chat_id`، `referrer`. الأداة دي **lead-only** — متكتبش بيانات شراء (دي شغل W1).

### Mautic API body (الشكل الفعلي اللي بيتبعت)
```jsonc
// POST /api/contacts/new   — Content-Type: application/json
{
  "email": "ahmed@example.com",
  "firstname": "أحمد",          // فقط لو name موجود
  "lastname": "عادل",            // فقط لو فيه أكتر من كلمة
  "whatsapp_phone": "201011516829", // فقط لو phone موجود
  "course_interest": "java",     // فقط لو course_interest موجود
  "source_channel": "whatsapp",  // ثابت — قيمة صحيحة في الـ select
  "tags": ["whatsapp-lead", "course-java"]
}
```
> الـ keys اللي قيمتها فاضية **مابتتضافش أصلاً** للـ body (مش بتتبعت كـ `""`). ده اللي بيحقّق قاعدة non-empty-only.

---

## 6. الكورسات — slug ↔ الكورس (للـ `course_interest` + الـ `course-<slug>` tag)

> المصدر: `../../03_KNOWLEDGE/knowledge-base.md` (هو مرجع الحقيقة لأي اسم/سعر). لو اختلف، الـ KB يكسب.

| slug (للـ tag + الحقل) | الكورس | لينك (مرجع) |
|---|---|---|
| `java` | كورس جافا للمبتدئين (+ كتاب) | learrnsimply.com/courses/java-course-level1/ |
| `oop` | البرمجة الكائنية OOP (Java) | learrnsimply.com/courses/javaoop/ |
| `data-structure` | هياكل البيانات م١ (C++) | learrnsimply.com/courses/data-structure-c/ |
| `data-structure-2` | هياكل البيانات م٢ | learrnsimply.com/courses/data_structure_level2/ |
| `python` | مشاريع بايثون (مجاني) | learrnsimply.com/courses/مشاريع-بايثون-للمبتدئين/ |
| `java-bundle` | باقة Java الكاملة (Basics + OOP + كتاب) | — |
| `data-structure-bundle` | باقة هياكل البيانات الكاملة | — |

**قاعدة الـ tag:**
- الـ tag `whatsapp-lead` بيتحط **دايماً** (موجود في taxonomy الـ 24 starter tags — متأكد).
- الـ tag `course-<slug>` بيتحط **بس لو** الكورس معروف من الجدول فوق. ⚠️ **ملاحظة drift:** الـ starter taxonomy في Mautic فيها `course-python` + `course-flutter` + `course-react` + `course-laravel` بس — **مش** متطابقة مع الكاتالوج الفعلي (java/oop/data-structure). Mautic بيقبل أي tag جديد عند الإنشاء (مش enum زي الـ select)، فـ `course-java` هيتعمل تلقائياً — بس **لازم نوحّد الـ taxonomy** (action في §9). لحد ما يتوحّد، استخدم slugs الكاتالوج الحقيقي فوق.
- `course_interest` (الحقل الحر) بياخد الـ slug أو الاسم العربي زي ما هو — مفيش قيود.

---

## 7. اختيار الـ Segment (إضافة صريحة — زي W2)

الـ segment **مابيتملاش تلقائي من الـ tag** لو الـ segment يدوي (`filters: []`). فبنضيف صراحةً بالـ API. لكن segments الـ Learn Simply الأساسية **مبنية على filters** (راجع `../../mautic/README.md`)، يعني العضوية بتتحدّث أوتوماتيك من cron (`mautic:segments:update` كل 15 دقيقة). فالإضافة الصريحة هنا **اختصار فوري** + ضمان، مش بديل.

| الحالة | الـ Segment | ID | ليه |
|---|---|---|---|
| WhatsApp lead (الافتراضي لـ A-close) | `whatsapp_contacts` | **8** | فلتره `whatsapp_phone !empty` — بس الإضافة الصريحة بتضمن العضوية فوراً حتى لو لسه مفيش phone متكتب |
| Lead مش مشتري (لو `whatsapp_phone` مش متبعت) | `non_buyers` | **6** | فلتره `email !empty AND course_count empty` — بيتملا تلقائي من cron؛ مش محتاج إضافة صريحة عادةً |

**التوصية:** أضف صراحةً لـ **segment 8 (`whatsapp_contacts`)** لكل WhatsApp lead. سيب `non_buyers` (6) للـ cron يملاه من الفلتر — متضيفش له صراحةً (تجنّب لخبطة لو العميل بقى مشتري بعدين).

```
POST /api/segments/8/contact/{contact.id}/add
```
> ⚠️ متضيفهوش لـ segment 10 (Dart Waitlist) — ده خاص بـ popup الـ Dart بس (W2)، مش leads عمر.

---

## 8. Endpoints + Auth (placeholders — مفيش secrets في الملف ده)

| الغرض | Method + Path | ملاحظات |
|---|---|---|
| Upsert contact | `POST {MAUTIC_API_BASE}/contacts/new` | dedupe على email → create-or-update |
| Add to segment | `POST {MAUTIC_API_BASE}/segments/{SEGMENT_ID}/contact/{CONTACT_ID}/add` | SEGMENT_ID=8 للـ WhatsApp leads |
| (للتشخيص بس) قراءة contact | `GET {MAUTIC_API_BASE}/contacts?search=email:{EMAIL}&limit=1` | مش لازم للـ upsert — Mautic بيـ dedupe لوحده. للـ debugging فقط |

```
MAUTIC_API_BASE = https://mautic.learrnsimply.com/api
Auth            = HTTP Basic Auth
                  user = <MAUTIC_API_USER>      ← placeholder (مثال: omar) — من brand .env §16
                  pass = <MAUTIC_API_PASSWORD>  ← placeholder — من brand .env §16 / Bitwarden
n8n credential  = "Mautic HTTP Basic — Learn Simply" (httpBasicAuth, ID HcKVugtv8k1Yr47c)
                  ← نفس الـ credential اللي W1 + W2 بيستخدموه. متعملش credential جديد.
```

> **صفر hardcoded secrets في الـ workflow.** الـ user/pass بييجوا من الـ n8n credential store عبر الـ httpBasicAuth credential فوق. الملف ده توثيق — مفيهوش قيم حقيقية.

### ليه `source_channel: whatsapp` آمن هنا (بينما `dart-popup` كسر W2)
`source_channel` حقل **select** بقائمة خيارات ثابتة:
`website / whatsapp / telegram / facebook / instagram / youtube / tiktok / direct / other`.
إرسال قيمة **مش** في القائمة بيرفض الـ create كله (ده اللي حصل في W2 مع `dart-popup` فاتخطّيناه). بس **`whatsapp` قيمة موجودة في القائمة** → آمنة تماماً، وهي الصح دلالياً لـ leads واتساب. (راجع `../../n8n/workflows/W2-dart-waitlist-popup.md` §Design decisions.)

---

## 9. كيفية بنائها في n8n (skeleton — لسه مش متبني)

تُبنى كـ **sub-workflow** يتنده من الـ AI Agent tool، أو كـ HTTP nodes جوه workflow عمر الرئيسي.

```
(tool call من عمر: {email, name?, tags?, course_interest?, phone?, country?})
  → Validate & Build (Code, defensive — نفس روح W2)
       • regex على الإيميل؛ لو غلط → emit {status:'skipped'} ومتكملش
       • نظّف phone (digits only)
       • ابنِ contactBody (اكتب الموجود بس) + tags (whatsapp-lead + course-<slug>?)
       • source_channel = 'whatsapp' (ثابت)
  → Is Valid? (IF)
       ├─ FALSE → Respond {status:'skipped'}
       └─ TRUE  → Mautic Upsert Contact (HTTP POST /contacts/new, Basic Auth, retry 3×/2s)
                    → Add to Segment 8 (HTTP POST /segments/8/contact/{{id}}/add,
                                        retry 3×/2s, onError: continueRegularOutput)
                    → Respond {status:'ok', mautic_contact_id, action, segment_added:8}
```

| Node | بيعمل إيه |
|---|---|
| **Validate & Build** | JS دفاعي مايرميش throw. regex إيميل + تنظيف phone + بناء body + tags. يتخطّى بصمت لو مفيش إيميل صح. |
| **Mautic Upsert Contact** | `POST /api/contacts/new` بـ httpBasicAuth credential `HcKVugtv8k1Yr47c`. `retryOnFail 3×/2s`. dedupe على email. |
| **Add to Segment 8** | `POST /api/segments/8/contact/{{ $json.contact.id }}/add`. `retryOnFail 3×/2s` + `onError: continueRegularOutput`. |

> **إعادة استخدام مباشرة:** الـ "Validate & Build" + "Mautic Upsert" تقدر تتنسخ شبه حرفياً من W2's `Validate & Build` + `Mautic Create Contact`، مع تبديل tag `dart-waitlist`→`whatsapp-lead`، segment 10→8، وإضافة `source_channel:'whatsapp'` (اللي W2 شالها لأن `dart-popup` مش valid — هنا valid فمضيفينها).

---

## 10. ملاحظات لازم تتعرف

- **idempotent + reversible:** الـ upsert مابيكسرش حاجة لو اتنده مرتين. حذف الـ contact (لو لزم في تنظيف) محتاج suffix `/delete`: `DELETE /api/contacts/{id}/delete` (الـ DELETE العادي no-op صامت — درس من W2).
- **متعملش double-write مع W1:** لو العميل **اشترى**، W1 بيسجّله أوتوماتيك بـ `wc-customer` tag + بيانات شراء. عمر مايستخدمش `mautic_upsert` لمشتري — ده للـ **leads** بس.
- **الـ segment أرقام ثابتة:** 1-9 = الـ strategic segments، 10 = Dart Waitlist (W2)، 11 = (لو اتعمل لاحقاً). عمر بيستخدم **8** بس. لو الأرقام اتغيّرت في Mautic، حدّث §7 هنا.
- **drift الـ taxonomy:** `course-<slug>` tags الفعلية (java/oop/data-structure) مش متطابقة مع starter taxonomy (python/flutter/react/laravel). action مفتوح: نوحّد الـ tag taxonomy في Mautic مع الكاتالوج الحقيقي قبل ما نعتمد على الـ tags دي في segmentation/campaigns. لحد ساعتها، الـ `course_interest` الحقل الحر هو المصدر الأدق للاهتمام.
- **مفيش رسالة للعميل من الأداة دي.** صامتة. أي تواصل بيتم من campaign Mautic منفصل لاحقاً (مش من عمر، مش inbound).
```
