# 📊 دراسة جودة n8n AI-Agent Workflows + الـ Refactor — 2026-06-10

> **السبب:** Omar حس إن جودة W3 الأولى وحشة → `/deep-research`: تشريح الـ workflows الحية في `rs-aios/automation` (وكيلين production: واتساب 36 نود + ماسنجر v6 بـ 42 نود) + بحث ويب (25+ مصدر، 12 منهم deep-read) + مقارنة + **تنفيذ فوري للتحسينات**.
> **النتيجة:** W3 اتعاد بناؤه من 20 نود لـ **27 نود** بنمط production. كل الفجوات P0/P1 اتقفلت. باقي P2 (تحت).

---

## 1. أهم الدروس من rs-aios (الوكلاء الحيين)

| النمط | التفاصيل (قيم حقيقية من الـ JSON) |
|---|---|
| **Prompt من الـ DB بإصدارات** | جدول `agent_prompts` (status=active, version) بيتقري **في كل رسالة** → تعديل حي بدون redeploy. v10 نشط عندهم؛ الإصدارات القديمة مؤرشفة |
| **Hybrid Memory (v6)** | بروفايل دائم من `leads_messenger` بيتحقن كبلوك `<customer_profile>` في الـ prompt + **session bucketing**: `sessionKey = sender:YYYY-MM-DD` بيتصفّر لو آخر تواصل > 48 ساعة → الذاكرة القديمة (أسعار قديمة!) ما بتتسرّبش |
| **تحذير الأسعار البايتة** | لو العميل راجع بعد >48h → سطر ⚠️ في الـ prompt بيجبر الوكيل يتحقق قبل ما يبني على معلومات قديمة |
| **Fallback مزدوج** | `needsFallback: true` + موديل تاني، وفوقهم **Agent Retry** كامل لو الرد طلع فاضي، وفي الآخر رسالة عربي ثابتة (مفيش صمت أبداً) |
| **Clean AI Response** | code node بينضّف خرج الـ LLM: artifacts الأدوات، JSON، يقص عند 1900 حرف |
| **UX إنساني** | Mark-as-read → typing indicator → تأخير بشري `min(max(len/50*1000, 2000), 8000)` قبل الإرسال |
| **Global Error Handler** | workflow واحد بـ Error Trigger → Telegram HTML (قناة Omar `6726176133`) + كل workflow بيشاور عليه في `settings.errorWorkflow` |
| **Settings صلبة** | `callerPolicy: workflowsFromSameOwner` على كل حاجة |
| **Post-reply analytics** | تصنيف صامت لمرحلة المحادثة (Gemini temp 0.3 + structured parser) → `stage_snapshots` (قمع التحويل) |
| **الميديا** | صوت → Gemini transcription، صورة → Gemini vision + تخزين evidence — النص بيتحقن للوكيل بصيغة "العميل بعت ريكورد ودي محتوياته…" |

**فجوات عندهم (ما قلدناهاش):** مفيش dedup حقيقي للرسائل، مفيش debounce، مفيش فلترة جروبات في الواتساب — إحنا أحسن منهم في التلاتة دول.

## 2. أهم خلاصات بحث الويب (مصادر مقتبسة في تقرير الوكيل)

1. **Ack فوري للـ webhook + الرد عبر sendText** — Evolution بيتجاهل جسم الرد أصلاً؛ مسك الاتصال = مخاطرة timeout بدون أي فايدة (n8n docs + community + Evolution docs).
2. **فخ maxIterations:** n8n بيبعت النص الحرفي *"Agent stopped due to max iterations"* **للعميل** — لازم فلتر بعد الوكيل (n8n community #220526).
3. **Debounce 5-10 ثواني** للرسائل المتتالية (نمط "آخر رسالة تكسب") — العملاء بيكتبوا أفكارهم مجزأة (n8n templates + community).
4. **Inbound-only على Baileys = نسبة حظر <2% سنوياً** vs 15-30% للـ outbound — قرارنا المعماري سليم (Kraya). الـ delay/presence humanization بيقلل الـ detection كمان.
5. **Temperature 0.2-0.3** للدعم الـ factual (دراسة arXiv + Vapi) — الدفء يجي من الـ persona مش الحرارة. (rs بيستخدموا 0.6 لأنهم sales-creative.)
6. **"error handling is the product"** — error workflow مركزي + retry على كل HTTP خارجي + `saveDataErrorExecution=all`.
7. `EXECUTIONS_TIMEOUT` الافتراضي = بلا حدود — لازم timeout (حطّينا 240s على W3).

## 3. اللي اتنفّذ فعلاً (الـ refactor — W3 من 20 → 27 نود)

| # | التحسين | التنفيذ |
|---|---|---|
| 1 | **Ack فوري** | `responseMode: onReceived` — شالنا نودات الـ Respond، الرد بيروح عبر sendText بس |
| 2 | **Debounce + تجميع** | Typing→Wait 8s→`Newer Msg Check` (لو فيه أحدث = انسحب بصمت)→`Collect Batch` (كل رسايل العميل من آخر رد لعمر بـ `string_agg`) — Postgres-only، من غير Redis جديد |
| 3 | **Mark-as-read + typing + تأخير بشري** | `/chat/markMessageAsRead` + `/chat/sendPresence` (composing 12s) + `delay` في sendText بمعادلة rs (1.5-6s حسب طول الرد) |
| 4 | **Prompt من الـ DB** | جدول `omar.agent_prompts` (v2 نشط، 17,644 حرف) + نود `Fetch Active Prompt` كل رسالة + أداة `deploy/push_prompt.mjs` (بترفع إصدار جديد وتأرشف القديم — بدل inject_prompt.mjs اللي اتشال) |
| 5 | **Hybrid Memory كامل** | `Upsert Contact` بقى بيرجّع `hours_since_last_seen` + `session_bucket` (CTE على last_seen القديم) → `sessionKey = phone:bucket` (تصفير بعد 48h) + بلوك بروفايل في `Build Prompt & Profile` مع تحذير الراجع-بعد-فترة |
| 6 | **Fallback model** | `needsFallback: true` + نود `Gemini Fallback` (gemini-2.5-flash-lite) كموديل تاني |
| 7 | **Clean Reply** | فلتر "Agent stopped due to max iterations" + الرد الفاضي + قص 1900 حرف + تنظيف code fences. نصوص الـ fallback **صادقة** (البوت على رقم الدعم اللي أحمد بيتابعه فعلاً) |
| 8 | **🐛 إصلاح الصوتيات** | كانت بتتجاهل **بصمت** — دلوقتي `[رسالة صوتية من العميل]` + قاعدة 13 في الـ prompt (يطلب نص بأدب). فيديو/صورة-بدون-نص كذلك |
| 9 | **W3d Global Error Handler** | `YktkjLMI12YUGWfc`: Error Trigger → Telegram HTML للبوت المشترك (`_agency/notify.sh`، credential `rvECFnBLZhbV2ADN`) — متربط في `settings.errorWorkflow` على W3/W3b/W3c |
| 10 | **Settings صلبة** | `callerPolicy: workflowsFromSameOwner` + `saveDataErrorExecution: all` + `executionTimeout` (240/120/60s) + timezone Cairo على الكل |
| 11 | **order_lookup أوفر** | `optimizeResponse: true` + حقول مختارة (id, status, total, billing, line_items) — أوردر WC الخام ~10KB+ |
| 12 | **قواعد prompt جديدة** | 12 (الرسايل المجمعة = رد واحد) + 13 (الصوتيات) — مرفوعة كـ v2 في الـ DB |

**اختبارات:** كل الـ SQL الجديد (bucket جديد/راجع-72h/تجميع 3 رسايل/newer-check) في rollback transactions ✅ · validate نضيف على W3 وW3d (والـ warnings الباقية false-positives موثقة) ✅ · kb-search لسه شغال بعد التغييرات ✅.

### تكملة (نفس اليوم — بطلب Omar): محاذاة كاملة مع اختيارات nodes بتاعة rs
- **AI Agent → typeVersion 3.1** (نفس الشغال live عندهم — كان 2.3 من الـ spec القديم؛ `needsFallback` مضمون على 3.1).
- **Community node `n8n-nodes-evolution-api` v1.0.4 اتنزّل** على n8n بتاعنا، والـ 5 نودات Evolution اتحوّلت من HTTP خام ليه بنفس configs بتاعة rs الحرفية: `chat-api/read-messages` + `chat-api/send-presence` (composing, delay 12000) + `messages-api` sendText بـ `options_message.delay` ديناميكي. credential جديد type `evolutionApi` (`vEH26B23v67OyFSu`).
- **⚠️ تغيير بنية (بموافقة Omar الصريحة):** الـ n8n worker كان مش شايف فولدر الـ nodes (queue mode!) — اتضاف `n8n-data:/home/node/.n8n` لخدمة الـ worker في compose (backup: `docker-compose.yml.bak-2026-06-10`) + restart (~60 ثانية). من غيرها كل تنفيذ كان هيفشل صامت رغم إن الواجهة شكلها سليم.
- الفرق الوحيد المتبقي عن rs في اختيار النودات = أدوات الوكيل (langchain toolHttpRequest عندنا vs base httpRequestTool عندهم) — متكافئين وظيفياً، والـ escalate بتاعنا (toolWorkflow) أحسن من نمطهم.

## 4. Backlog — P2 (بعد الـ go-live)

| البند | ليه مش دلوقتي |
|---|---|
| 🎙️ **Transcription حقيقي للصوتيات** (Evolution get-media-base64 → Gemini) — نمط rs جاهز للنسخ | محتاج Gemini key شغال + اختبار حي؛ أعلى أولوية في P2 لأن جمهور أحمد voice-first |
| 🖼️ وصف الصور بـ Gemini vision (بدل الماركر) | نفس السبب |
| 🔁 Agent Retry ثاني على الرد الفاضي (نمط rs الكامل) | عندنا fallback model + safe text — كفاية v1 |
| 📊 تصنيف صامت بعد الرد (intent → `omar.messages.intent` + `contacts.last_intent`) — نمط stage_snapshots | analytics مش blocking؛ يتبني مع أول داتا حقيقية |
| 🧪 Eval set (~50 محادثة حقيقية) لقياس جودة الردود قبل أي تعديل prompt | محتاج داتا حقيقية الأول (n8n نفسهم عملوا كده) |
| ✂️ تقسيم الردود الطويلة لرسايل متعددة بدل القص | نادر مع maxOutputTokens 1024 + قاعدة الردود القصيرة |
| 🚫 Blocklist/kill-switch per-user | n8n active toggle كافي دلوقتي |

## 5. الخريطة النهائية (4 workflows)

| ID | الاسم | الحالة | ملاحظة |
|---|---|---|---|
| `ESYkoJgz0e4ngMrM` | W3 omar-inbound (27 نود) | INACTIVE | يتفعّل يوم الـ go-live |
| `ULoRfU57m5fSLD2B` | W3b omar-alert | inactive (طبيعي) | sub-workflow بيشتغل من غير تفعيل |
| `sv6ART3GjO4JUN81` | W3c omar-kb-search | **ACTIVE** | secret path، read-only |
| `YktkjLMI12YUGWfc` | W3d omar-error-handler | inactive (طبيعي) | error workflows بتشتغل من غير تفعيل |

> **مرجع الدراسة الخام:** تقارير الوكلاء الأربعة في الـ session transcript (تشريح واتساب rs + ماسنجر v6 + بنية الأخطاء + بحث الويب بالمصادر). أهم المصادر: n8n docs (error handling, AI Agent, Postgres Chat Memory, queue mode) · n8n blog (15 best practices, AI assistant rebuild) · n8n community (#220526 maxIterations, debounce patterns) · Evolution API docs (sendText delay, sendPresence) · Kraya (ban-risk data).
