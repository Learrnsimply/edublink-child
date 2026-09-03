# 🔧 n8n Workflow Spec — "omar-inbound" (+ "omar-alert")

> **الغرض:** المخطط node-by-node اللي يتسلّم لـ **n8n-MCP** عشان يبني الـ workflows الفعلية. ده **spec** (مش live JSON) — بس دقيق كفاية إن أي حد (أو n8n-MCP) يبني منه على طول.
> **الحالة:** ✅ **مبني فعلاً في n8n (2026-06-10):** `omar-inbound` = `ESYkoJgz0e4ngMrM` (INACTIVE) · `omar-alert` = `ULoRfU57m5fSLD2B` (sub-workflow، مش محتاج تفعيل) · `omar-kb-search` = `sv6ART3GjO4JUN81` (ACTIVE). كل الـ IDs + الـ credentials في brand `.env` §20، وخطوات الـ go-live في `deploy/README.md`. الـ spec ده بقى مرجع تصميمي — **الـ instance الحي يكسب على المكتوب هنا لو اختلفوا.** (فروق التنفيذ عن الـ spec: رقم أحمد للإنذار اتفعّل `201102681074` · الـ snapshot بيتبني جوه INSERT واحد بدل نود منفصل · kb_search اتعمل كـ workflow webhook منفصل W3c · إيميل الإنذار عبر Brevo SMTP مش Hostinger.)
> **الملفات الأخوات:** `omar-build-plan.md` (المعمارية) · `omar-system-prompt.md` (عقل الـ AI Agent) · `postgres-schema.sql` (ذاكرة + escalations) · `rag/schema-kb.sql` (kb_search store) · `whatsapp-agent-design.md` (الرحلة الكاملة).
> **النمط المرجعي:** `../n8n/workflows/W1-wc-mautic-sync.md` + `W2-dart-waitlist-popup.md` + `../n8n/README.md` — نفس الـ conventions (secret-path webhook · `retryOnFail 3×/2s` · `onError: continueRegularOutput` للخطوات غير الحرجة · inactive-by-default · upsert idempotency · جدول credentials بالـ IDs).

---

## 0. مصدر أرقام النودات (node types + typeVersions)

كل `typeVersion` تحت **متأكّد من سورس n8n نفسه** (`n8n-io/n8n@master`, يونيو 2026) مش متخمّن. الجدول ده هو المرجع — أي نود في الـ spec بيستخدم منه:

| Node type | typeVersion | المصدر (ملف في `n8n-io/n8n`) |
|---|---|---|
| `n8n-nodes-base.webhook` | **2.1** | `packages/nodes-base/nodes/Webhook/Webhook.node.ts` → `version: [1,1.1,2,2.1]`, `defaultVersion: 2.1` |
| `n8n-nodes-base.code` | **2** | code node ثابت على 2 (نفس اللي بنته W1/W2) |
| `n8n-nodes-base.if` | **2.3** | نفس اللي W2 استخدمه (`Is Valid?` IF v2.3) |
| `n8n-nodes-base.postgres` | **2.6** | `packages/nodes-base/nodes/Postgres/Postgres.node.ts` → `defaultVersion: 2.6` |
| `n8n-nodes-base.httpRequest` | **4.2** | نفس النمط اللي W1/W2 بنوا بيه الـ Mautic HTTP node |
| `n8n-nodes-base.respondToWebhook` | **1.5** | بيرجّع رد للـ Evolution webhook لو احتجنا response sync |
| `n8n-nodes-base.executeWorkflow` | **1.2** | ينده sub-workflow `omar-alert` (لو الـ escalate اتعمل كـ sub-workflow بدل tool HTTP) |
| `@n8n/n8n-nodes-langchain.agent` | **2.3** (default 3.1) | `.../nodes/agents/Agent/Agent.node.ts` → AgentV2 يوصل 2.3؛ default 3.1. **نستخدم 2.3** (الـ family المستقر اللي بيربط tools كـ sub-nodes + `options.systemMessage`). يتـ bump لـ 3.1 لو n8n-MCP أكّد إنه نفس الـ ports. |
| `@n8n/n8n-nodes-langchain.lmChatGoogleGemini` | **1.1** | `.../nodes/llms/LmChatGoogleGemini/...` → `version: [1,1.1]`. credential `googlePalmApi`. |
| `@n8n/n8n-nodes-langchain.memoryPostgresChat` | **1.4** | `.../nodes/memory/MemoryPostgresChat/...` → `version: [1,1.1,1.2,1.3,1.4]`. params: `sessionIdType`, `sessionKey`, `tableName`, `contextWindowLength`. |
| `@n8n/n8n-nodes-langchain.toolHttpRequest` | **1.1** | `.../nodes/tools/ToolHttpRequest/...` → `version: [1,1.1]`. (v≥1.1 = N8nTool structured schema + `{placeholder}` params.) |
| `@n8n/n8n-nodes-langchain.toolWorkflow` | **2.2** | `.../nodes/tools/ToolWorkflow/...` → `defaultVersion: 2.2`. (لو tool اتعمل كـ sub-workflow بدل HTTP.) |

> **ملاحظة للبناء عبر n8n-MCP:** قبل ما تبني، شغّل `get_node_essentials` على كل type من دول للتأكد إن الـ typeVersion المثبّت هنا لسه هو الأحدث على نسخة الـ instance (n8n بيتحدّث؛ لو الـ instance أقدم/أحدث، استخدم اللي عليه فعلاً وحدّث الجدول ده). **القاعدة:** الـ instance يكسب على الجدول، والجدول يكسب على التخمين.

---

## 1. نظرة عامة — pipeline الـ `omar-inbound`

```
Evolution Webhook (POST, secret path)
  → Normalize & Guard (Code v2)            // يطلّع phone/body/wa_message_id/media؛ يتجاهل الـ non-message events
  → Is Message? (IF v2.3)
       ├─ FALSE → Respond 200 (ack، مفيش شغل)        // status updates / presence / receipts
       └─ TRUE  → Dedup Check (Postgres: SELECT wa_message_id)
                   → Already Seen? (IF v2.3)
                        ├─ TRUE  → Respond 200 (ack، اتعالج قبل كده)
                        └─ FALSE → Upsert Contact (Postgres)            // omar.contacts ON CONFLICT(phone)
                                    → Log Customer Msg (Postgres)        // omar.messages role=customer
                                    → عمر (AI Agent v2.3)
                                         ├─ Model:  Google Gemini Chat (temp 0.2–0.3)
                                         ├─ Memory: Postgres Chat Memory (key=phone، window 10)
                                         └─ Tools:  kb_search · order_lookup · mautic_upsert · escalate
                                    → Log Omar Reply (Postgres)          // omar.messages role=omar
                                    → Evolution Send (HTTP POST)          // يبعت رد عمر للعميل
                                    → Respond 200 (ack)
```

**ذاكرة هجينة:** الـ Postgres Chat Memory بيدير نافذة الـ 10 رسايل اللي الموديل بيقراها (جدول خاص بيه). **بالتوازي** بنكتب كل رسالة في `omar.messages` (full audit) + بروفايل دائم في `omar.contacts`. الاتنين منفصلين بنيّة — الـ chat-memory للـ LLM context، والـ `omar.messages`/`omar.contacts` للـ QA + analytics + الـ escalation snapshot.

---

## 2. node-by-node — `omar-inbound`

> الترتيب = ترتيب التنفيذ. كل نود: **type · typeVersion · params رئيسية · credentials · error policy**. القيم الحساسة كلها `<PLACEHOLDER>`.

### N1 — Evolution Webhook
- **type:** `n8n-nodes-base.webhook` · **typeVersion:** 2.1
- **params:**
  - `httpMethod`: `POST`
  - `path`: `omar-inbound-<SECRET_PATH_TOKEN>` ← **shared secret** زي W1/W2 (`a7f3c19e4b82`, `97ae34dfa856`). ولّد token عشوائي جديد، عامله كـ credential، متحطّوش في public. الـ URL الكامل هيبقى `https://n8n.learrnsimply.com/webhook/omar-inbound-<SECRET_PATH_TOKEN>`.
  - `responseMode`: `responseNode` ← عشان نرد `200` يدوي من نود Respond في الآخر (Evolution مستني ack؛ ونتحكّم في توقيت الرد بعد ما الشغل يخلص).
  - `options`: `{ rawBody: false }` (الـ payload JSON).
- **credentials:** مفيش (الـ secret path + HTTPS هما البوابة، نفس W1/W2). **hardening لاحق:** Evolution ممكن يبعت header (`apikey`/Authorization) — أضف `authentication: headerAuth` لما نثبّت الـ Evolution webhook config (راجع §Hardening).
- **error policy:** default. (الـ Respond node بيضمن الرد.)
- **ملاحظة Evolution payload:** Evolution API (`messages.upsert` event) بيبعت JSON فيه `data.key.id` (= wa_message_id), `data.key.remoteJid` (= phone@s.whatsapp.net), `data.key.fromMe` (لازم `false` للـ inbound), `data.message.conversation` أو `data.message.extendedTextMessage.text` (= نص الرسالة), وللميديا `data.message.imageMessage`/`base64`. الـ shape بيختلف شوية حسب نسخة Evolution — نود **N2** بيكون defensive ويتعامل مع الاحتمالات.

### N2 — Normalize & Guard (Code)
- **type:** `n8n-nodes-base.code` · **typeVersion:** 2 · **mode:** `runOnceForEachItem`
- **بيعمل إيه:** JS دفاعي **لا يرمي خطأ أبداً** (زي `Map WC → Mautic` في W1 و`Validate & Build` في W2). يطلّع object موحّد:
  ```
  {
    isMessage,        // boolean: true بس لو inbound text/media فعلي من عميل
    phone,            // E.164 digits only — من remoteJid: '201011516829' (يشيل @s.whatsapp.net و '+')
    waMessageId,      // data.key.id — مفتاح الـ dedup + idempotency
    body,             // نص الرسالة (conversation | extendedTextMessage.text | caption) أو ''
    mediaUrl,         // لو صورة/ملف (F1 screenshot) — URL أو data-ref؛ غير كده null
    pushName,         // اسم العميل من واتساب (للـ contacts.name لو فاضي)
    fromMe            // لو true → isMessage=false (تجاهل رسائلنا الراجعة)
  }
  ```
- **قواعد الحراسة (تخلّي `isMessage=false`):** `fromMe === true` · مفيش `waMessageId` · مفيش `body` ولا `mediaUrl` (presence/receipt/status event) · الـ event مش `messages.upsert`.
- **تطبيع التليفون:** `remoteJid.split('@')[0].replace(/\D/g,'')` → digits only (E.164 بدون `+`)، يطابق `omar.contacts.phone PK`. **groups** (`@g.us`) → `isMessage=false` (عمر inbound 1:1 بس).
- **error policy:** الكود نفسه try/catch داخلي يرجّع `isMessage=false` عند أي شكل غير متوقع (never throw).

### N3 — Is Message? (IF)
- **type:** `n8n-nodes-base.if` · **typeVersion:** 2.3
- **condition:** `{{ $json.isMessage }}` === `true` (boolean).
- **TRUE →** N4 (Dedup) · **FALSE →** N12 (Respond 200 — ack صامت لأحداث non-message).

### N4 — Dedup Check (Postgres SELECT)
- **type:** `n8n-nodes-base.postgres` · **typeVersion:** 2.6
- **operation:** `executeQuery`
- **query:**
  ```sql
  SELECT 1 AS seen FROM omar.messages
  WHERE wa_message_id = $1 LIMIT 1;
  ```
  `queryReplacement` = `{{ $json.waMessageId }}`. (الـ unique index `uq_messages_wa_id` بيخلّي ده سريع.)
- **credentials:** `Postgres — omar_agent` (`<PG_CRED_ID>`) — راجع §Credentials.
- **error policy:** `retryOnFail: true, maxTries: 3, waitBetween: 2000` (نفس نمط W1). لو الـ DB وقع، الـ retry بيمسك blip؛ persistent failure → الـ workflow يفشل → Evolution بيعيد (idempotency بتحمينا من التكرار).
- **ملاحظة:** بنمرّر `isMessage` item الأصلي لقدام كمان (الـ Postgres node بيرجّع صفوف الـ query بس) — استخدم **"Always Output Data"** + اقرأ `N2`'s json في النودات اللي بعده عبر `{{ $('Normalize & Guard').item.json.* }}` بدل الاعتماد على output الـ SELECT.

### N5 — Already Seen? (IF)
- **type:** `n8n-nodes-base.if` · **typeVersion:** 2.3
- **condition:** عدد صفوف N4 > 0 → اتشاف قبل كده. عملياً: `{{ $('Dedup Check').all().length > 0 }}` === `true`.
- **TRUE →** N12 (Respond 200 — ack، اتعالج قبل كده، مفيش تكرار رد).
- **FALSE →** N6.

### N6 — Upsert Contact (Postgres)
- **type:** `n8n-nodes-base.postgres` · **typeVersion:** 2.6
- **operation:** `executeQuery`
- **query (idempotent upsert على PK = phone):**
  ```sql
  INSERT INTO omar.contacts (phone, name, total_messages, last_seen)
  VALUES ($1, NULLIF($2,''), 1, now())
  ON CONFLICT (phone) DO UPDATE SET
    name           = COALESCE(omar.contacts.name, EXCLUDED.name),  -- ما نمسحش اسم موجود
    total_messages = omar.contacts.total_messages + 1,
    last_seen      = now()
  RETURNING phone, name, email, wc_customer_id, mautic_contact_id,
            course_interest, lead_status, country, total_messages;
  ```
  `queryReplacement`: `[{{ phone }}, {{ pushName }}]` من N2.
- **ليه:** ده "الذاكرة طويلة المدى" — مين العميل + حالته. الـ `RETURNING` بيدّي الـ AI Agent بروفايل العميل (lead_status, email, course_interest…) كـ context يحقنه في الـ prompt.
- **error policy:** `retryOnFail 3× / 2000ms`. (الـ FK في `omar.messages.phone` بيتطلّب الـ contact موجود الأول — فالترتيب N6 قبل N7 إجباري.)

### N7 — Log Customer Msg (Postgres)
- **type:** `n8n-nodes-base.postgres` · **typeVersion:** 2.6
- **operation:** `executeQuery`
- **query:**
  ```sql
  INSERT INTO omar.messages (phone, role, body, media_url, wa_message_id)
  VALUES ($1, 'customer', $2, $3, $4)
  ON CONFLICT (wa_message_id) WHERE wa_message_id IS NOT NULL DO NOTHING
  RETURNING id;
  ```
  `queryReplacement`: `[{{ phone }}, {{ body }}, {{ mediaUrl }}, {{ waMessageId }}]` من N2.
- **ليه `ON CONFLICT … DO NOTHING`:** طبقة idempotency تانية — لو رسالة جت مرتين بسرعة (سباق قبل ما N4 يشوفها)، الـ unique index يمنع تكرار الصف. (`intent` بنسيبه NULL هنا؛ ممكن نحدّثه لاحقاً من خرج الـ Agent لو فعّلنا triage tagging.)
- **error policy:** `retryOnFail 3× / 2000ms`.

### N8 — عمر (AI Agent)
- **type:** `@n8n/n8n-nodes-langchain.agent` · **typeVersion:** 2.3
- **params:**
  - `promptType`: `define`
  - `text`: `{{ $('Normalize & Guard').item.json.body }}` ← رسالة العميل الحالية.
  - `options.systemMessage`: **المحتوى الكامل بين `---- BEGIN SYSTEM PROMPT ----` و `---- END SYSTEM PROMPT ----` في `omar-system-prompt.md`** (يتنسخ حرفياً). ضيف سطر context في الأول/الآخر يحقن بروفايل العميل من N6:
    ```
    [سياق العميل الحالي] الاسم: {{ contact.name }} · الإيميل: {{ contact.email }} · الحالة: {{ contact.lead_status }} · اهتمامات: {{ contact.course_interest }} · الدولة: {{ contact.country }}. (لو فاضي = عميل جديد.)
    ```
  - `options.maxIterations`: 5 (سقف على دورات الـ tool-calling — يمنع loops).
  - `needsFallback`: false (ابدأ بسيط).
- **sub-nodes (ai connections):** Model (N8a) · Memory (N8b) · Tools (N8c–N8f). راجع §Connections.
- **error policy:** `onError: continueErrorOutput` → لو الـ Agent فشل (Gemini timeout/quota)، الـ error branch يروح لنود يرد على العميل برسالة آمنة (*"معلش حصل تأخير بسيط، ثانية وأرجعلك"*) + يكتب system message في `omar.messages` (role=`system`) للـ QA. **متبعتش رد فاضي.**

#### N8a — Google Gemini Chat Model
- **type:** `@n8n/n8n-nodes-langchain.lmChatGoogleGemini` · **typeVersion:** 1.1
- **params:**
  - `modelName`: `models/gemini-2.5-flash` (Gemini Flash — القرار المعماري المثبّت. لو الـ instance بيقترح أحدث flash، استخدمه.)
  - `options.temperature`: **0.3** (في النطاق 0.2–0.3 المطلوب — التزام بالحقائق، صفر اختراع).
  - `options.maxOutputTokens`: 512 (ردود واتساب قصيرة — القاعدة الذهبية #6: جملة أو اتنين).
- **credentials:** `googlePalmApi` → `Google Gemini — Learn Simply` (`<GEMINI_CRED_ID>`).
- **connection:** output `ai_languageModel` → N8 (Agent).

#### N8b — Postgres Chat Memory
- **type:** `@n8n/n8n-nodes-langchain.memoryPostgresChat` · **typeVersion:** 1.4
- **params:**
  - `sessionIdType`: `customKey`
  - `sessionKey`: `{{ $('Normalize & Guard').item.json.phone }}` ← **keyed on phone** (E.164 digits).
  - `tableName`: `omar_chat_memory` ← جدول منفصل بيديره الـ node نفسه (مش `omar.messages`؛ ده window store للـ LLM). يتعمل تلقائياً لو مش موجود؛ في DB `omar_agent`.
  - `contextWindowLength`: **10** ← آخر ~10 رسايل (نافذة قصيرة).
- **credentials:** `postgres` → نفس `Postgres — omar_agent` (`<PG_CRED_ID>`).
- **connection:** output `ai_memory` → N8 (Agent).

#### N8c — Tool: kb_search
- **type:** `@n8n/n8n-nodes-langchain.toolHttpRequest` · **typeVersion:** 1.1
- **اسم النود:** `kb_search` (لازم alphanumeric/underscore — الـ node بيتحقق من ده).
- **toolDescription:** "ابحث في منهج الكورسات العميق (الدروس والوحدات) لما العميل يسأل عن تفصيلة مش موجودة في الكاتالوج. مدخل: نص السؤال بالعربي. مخرج: أقرب مقاطع من قاعدة المعرفة."
- **method:** `POST` · **url:** `http://<KB_SEARCH_ENDPOINT>` — راجع §"kb_search implementation".
- **sendBody:** true · **specifyBody:** `json` · **jsonBody:** `{ "query": "{searchQuery}", "k": 5 }` حيث `{searchQuery}` placeholder بيملاه الموديل.
- **placeholderDefinitions:** `searchQuery` = "سؤال العميل عن المنهج، نص حر بالعربي".
- **error policy:** `onError: continueRegularOutput` (فشل البحث ما يكسرش الرد — الموديل يكمّل بـ "هأكّدلك من الفريق").
- **connection:** output `ai_tool` → N8.
- > **kb_search implementation (buildable، sub-workflow صغير):** الـ tool بيـ POST لـ sub-workflow/endpoint بيعمل: (1) يحوّل `query` لـ embedding عبر Gemini `text-embedding-004` (768-dim)، (2) ينده `omar.kb_search(q vector(768), k)` (الـ SQL function في `rag/schema-kb.sql`)، (3) يرجّع `content` + `heading_path` للـ top-k. **مستني فقط:** تشغيل `rag/schema-kb.sql` + ingestion (`ingest_kb.py`) على prod (موافقة Omar). لحد ساعتها الـ tool يـ return رسالة "KB مش جاهز" والموديل يقع على سلوكه الآمن.

#### N8d — Tool: order_lookup
- **type:** `@n8n/n8n-nodes-langchain.toolHttpRequest` · **typeVersion:** 1.1
- **اسم النود:** `order_lookup`
- **toolDescription:** "ابحث عن حالة أوردر/دفع لعميل بالتليفون أو الإيميل. استخدمه في مشاكل الدخول/الدفع (B). مدخل: تليفون أو إيميل. مخرج: حالة الأوردر لو موجود."
- **method:** `GET` · **url:** `https://learrnsimply.com/wp-json/wc/v3/orders?search={searchTerm}&per_page=5`
- **authentication:** `genericCredentialType` → `httpBasicAuth` → `WooCommerce ReadOnly — Learn Simply` (consumer key/secret كـ Basic). الـ key = `<WC_READONLY_KEY>` / `<WC_READONLY_SECRET>` (placeholder — read-only فقط).
- **placeholderDefinitions:** `searchTerm` = "تليفون العميل أو إيميله".
- **optimizeResponse:** فعّل تقليم الرد لأهم الحقول (`id, status, total, billing.email, line_items[].name, date_created`) عشان نوفّر tokens.
- **error policy:** `onError: continueRegularOutput`.
- **connection:** `ai_tool` → N8.
- > **buildable now ماعدا:** محتاج WooCommerce read-only API key. لحد ساعتها الـ tool معطّل/يرجّع "مش متاح" والموديل يجمع التفاصيل ويصعّد بدون lookup.

#### N8e — Tool: mautic_upsert
- **type:** `@n8n/n8n-nodes-langchain.toolHttpRequest` · **typeVersion:** 1.1
- **اسم النود:** `mautic_upsert`
- **toolDescription:** "سجّل/حدّث lead متردد في Mautic للمتابعة بإيميل لاحقاً (مسار A-close). مدخل: الإيميل + الاسم + اهتمام الكورس. مخرج: تأكيد التسجيل."
- **method:** `POST` · **url:** `https://mautic.learrnsimply.com/api/contacts/new` ← **نفس endpoint W1/W2 بالظبط**.
- **authentication:** `genericCredentialType` → `httpBasicAuth` → **`Mautic HTTP Basic — Learn Simply` (`HcKVugtv8k1Yr47c`)** ← الـ credential الموجود اللي W1+W2 بيستخدموه (إعادة استخدام، مش جديد).
- **sendBody:** true · **specifyBody:** `json` · **jsonBody:**
  ```json
  { "email": "{leadEmail}", "firstname": "{leadName}", "tags": ["whatsapp-lead", "course-{courseInterest}"], "source_channel": "whatsapp" }
  ```
- **placeholderDefinitions:** `leadEmail` (مطلوب)، `leadName` (اختياري)، `courseInterest` (`java`/`oop`/`data-structure`/…).
- **field-mapping (نفس درس W1):** upsert على email (Mautic بيـ dedupe) · non-empty-only · **`source_channel`: `whatsapp` بيتبعت** (قيمة صحيحة في الـ select — متحقّقة من `mautic/create-custom-fields.sh`؛ فرق عن W2 اللي شالتها لأن `dart-popup` مش valid). الـ `whatsapp-lead` tag علامة إضافية. راجع `tools/mautic_upsert.md` §8.
- **error policy:** `retryOnFail 3× / 2000ms` + `onError: continueRegularOutput`.
- **connection:** `ai_tool` → N8.
- > **buildable now بالكامل** — النمط + الـ credential موجودين من W1.

#### N8f — Tool: escalate
- **type:** `@n8n/n8n-nodes-langchain.toolWorkflow` · **typeVersion:** 2.2 (ينده sub-workflow `omar-alert`)
  - *(بديل أبسط: `toolHttpRequest` 1.1 يـ POST لـ Execute-Workflow webhook. نفضّل `toolWorkflow` عشان type-safe schema + بيمرّر الـ context object نضيف.)*
- **اسم النود:** `escalate`
- **toolDescription:** "صعّد مشكلة حقيقية لفريق بشري (B/C/D/F). استخدمه فوراً لأي: access/شهادة/فشل دفع/دفع دولي/مشكلة موقع/استرجاع/عميل غاضب/طلب خاص لأحمد. بيبعت إيميل + واتساب لأحمد وعمر ويسجّل في DB."
- **workflowId:** `<OMAR_ALERT_WORKFLOW_ID>` (sub-workflow `omar-alert` — N9 section).
- **workflowInputs (schema — الموديل بيملاها):**
  ```
  type         (enum: access|certificate|payment|payment_intl|site_access|refund|angry|for_ahmed)  // = omar.escalation_type
  priority     (enum: normal|high|urgent)   // غاضب=urgent/high                                    // = omar.priority
  summary      (string)  // ملخص عمر للمشكلة
  customerEmail(string?) · orderId(string?) · screenshotUrl(string?)
  ```
  الـ `phone` ما بيتطلبش من الموديل — الـ sub-workflow بياخده من الـ session/context (`{{ $('Normalize & Guard').item.json.phone }}`) عشان ما يتزوّرش.
- **error policy:** `retryOnFail 3× / 2000ms`. **مهم:** فشل الإنذار = خطر حقيقي (مشكلة عميل اتـ"وصّلت للفريق" بس فعلياً اتبهدلت). لو فشل نهائياً → الـ Agent error branch يكتب system msg + الـ workflow يفشل عشان Evolution يعيد (ما نطمنش العميل كذب). الـ DB write جوه الـ sub-workflow أول خطوة عشان حتى لو الإيميل/واتساب فشل، الـ escalation متسجّل ومرئي في `omar.open_escalations`.
- **connection:** `ai_tool` → N8.
- > **buildable now ماعدا:** **رقم واتساب أحمد** للإنذار (`<AHMED_WHATSAPP>` — لسه ما اداهوش). الإيميل + DB + واتساب عمر (`01011516829`) كلهم جاهزين. لحد ما ييجي رقم أحمد، الـ sub-workflow يبعت لعمر بس + إيميل للاتنين، ويسجّل ملاحظة "Ahmed WA pending".

### N9 — Log Omar Reply (Postgres)
- **type:** `n8n-nodes-base.postgres` · **typeVersion:** 2.6
- **operation:** `executeQuery`
- **query:**
  ```sql
  INSERT INTO omar.messages (phone, role, body)
  VALUES ($1, 'omar', $2) RETURNING id;
  ```
  `queryReplacement`: `[{{ phone }}, {{ $('عمر').item.json.output }}]` (رد الـ Agent من `.output`).
- **error policy:** `retryOnFail 3× / 2000ms` + `onError: continueRegularOutput` (لو فشل اللوج، لسه ابعت الرد للعميل — مش هنحجب الرد عشان audit row).

### N10 — Evolution Send (HTTP Request)
- **type:** `n8n-nodes-base.httpRequest` · **typeVersion:** 4.2
- **params:**
  - `method`: `POST`
  - `url`: `https://<EVOLUTION_HOST>/message/sendText/<EVOLUTION_INSTANCE>` ← Evolution send-text endpoint. `<EVOLUTION_HOST>` على الـ VPS، `<EVOLUTION_INSTANCE>` = اسم instance رقم المساعد.
  - `sendBody`: true · `bodyContentType`: `json` · `jsonBody`:
    ```json
    { "number": "{{ $('Normalize & Guard').item.json.phone }}", "text": "{{ $('عمر').item.json.output }}" }
    ```
    *(شكل الـ body بيختلف بنسخة Evolution — بعض النسخ: `{ number, textMessage: { text } }` أو `{ number, options:{}, text }`. تأكّد من نسخة الـ instance وقت البناء.)*
  - `options`: `{ timeout: 15000 }`
- **authentication:** `genericCredentialType` → `httpHeaderAuth` → header `apikey: <EVOLUTION_API_KEY>` → credential `Evolution API — Learn Simply` (`<EVOLUTION_CRED_ID>`).
- **error policy:** `retryOnFail 3× / 2000ms`. لو فشل الإرسال نهائياً → الـ workflow يفشل → Evolution webhook retry (idempotency بتمنع تكرار اللوج بفضل dedup).
- > **inbound-only تذكير:** النود ده بيرد على رسالة جاية **فقط** — مفيش بث/outbound مبادر. ده القرار المعماري (راجع `omar-build-plan.md` §تنبيهات).

### N11 — Respond 200 (success ack)
- **type:** `n8n-nodes-base.respondToWebhook` · **typeVersion:** 1.5
- **params:** `respondWith`: `text`، `responseBody`: `OK`، `responseCode`: 200.
- **ليه:** Evolution مستني `2xx` يعتبر التسليم نجح. بنرد بعد ما الشغل كله يخلص.

### N12 — Respond 200 (silent ack — non-message / duplicate)
- **type:** `n8n-nodes-base.respondToWebhook` · **typeVersion:** 1.5
- **params:** نفس N11 (`200 OK`). الفرع ده بيمسك: non-message events (من N3-FALSE) + duplicates (من N5-TRUE) — نرد `200` من غير أي شغل، عشان Evolution ما يعيدش.

---

## 3. Connections — `omar-inbound`

**main flow (data):**
```
N1 → N2 → N3
N3 (true)  → N4 → N5
N3 (false) → N12
N5 (false) → N6 → N7 → N8 → N9 → N10 → N11
N5 (true)  → N12
N8 (error) → [Safe-Reply node → N9] (راجع N8 error policy)
```

**ai sub-node connections (نوع الـ connection مش `main`):**
```
N8a (Gemini)        --ai_languageModel--> N8
N8b (Chat Memory)   --ai_memory-------->  N8
N8c (kb_search)     --ai_tool---------->  N8
N8d (order_lookup)  --ai_tool---------->  N8
N8e (mautic_upsert) --ai_tool---------->  N8
N8f (escalate)      --ai_tool---------->  N8
```

> **للبناء عبر n8n-MCP:** الـ AI sub-nodes بتتوصّل للـ Agent عبر ports غير الـ `main` (`ai_languageModel`, `ai_memory`, `ai_tool`). ده موثّق في الـ Agent builderHint (SDK `subnodes: { model, memory, ... }`). تأكد إن كل tool node اسمه alphanumeric (`kb_search` ✅، `order_lookup` ✅، إلخ — الـ ToolHttpRequest بيرمي لو الاسم مش valid).

---

## 4. Sub-workflow — `omar-alert` (الإنذار)

> **تفصيل كامل مؤجّل لـ `tools/escalate.md`** (companion spec — هيتكتب في سيشن منفصل). هنا الـ skeleton اللي `escalate` tool بينده عليه. النمط: write-DB-first عشان لا يضيع escalation حتى لو الإشعار فشل.

**workflow name:** `omar-alert` · **trigger:** `n8n-nodes-base.executeWorkflowTrigger` (typeVersion 1.1) — بيستقبل الـ inputs من tool `escalate` (N8f).

| # | Node | type · typeVersion | بيعمل إيه |
|---|---|---|---|
| A1 | Execute Workflow Trigger | `n8n-nodes-base.executeWorkflowTrigger` · 1.1 | يستقبل `{type, priority, summary, phone, customerEmail?, orderId?, screenshotUrl?}` |
| A2 | Build Context Snapshot | `n8n-nodes-base.postgres` · 2.6 | `SELECT` آخر ~10 رسايل من `omar.messages WHERE phone=$1` (LIMIT 10، متطابق مع `tools/escalate.md` Node 3) → JSONB للـ `context_snapshot` |
| A3 | Write Escalation (DB FIRST) | `n8n-nodes-base.postgres` · 2.6 | `INSERT INTO omar.escalations (phone,type,priority,status,summary,customer_email,order_id,screenshot_url,context_snapshot) VALUES (...,'open',...) RETURNING id;` — **`alerted_at` بيتسيب NULL هنا** ويتحدّث في node "Mark Alerted" بعد الإرسال (بس لو قناة نجحت — متطابق مع `tools/escalate.md` Node 9). أول خطوة بعد الـ snapshot عشان الـ escalation يبقى مرئي في `omar.open_escalations` مهما حصل. `retryOnFail 3×/2s`. |
| A3b | Mark Alerted | `n8n-nodes-base.postgres` · 2.6 | `UPDATE omar.escalations SET alerted_at=now() WHERE id=$1` — بس بعد A4/A5، ولو قناة واحدة على الأقل نجحت. |
| A4 | Email Alert | `n8n-nodes-base.emailSend` (SMTP) · 2.1 **أو** Mautic SMTP HTTP | لأحمد `ahmedadel123422@gmail.com` + عمر `omarabdo385@gmail.com`. عنوان: `🚨 [{priority}] {type} — {customer name/phone}`. الجسم: الملخص + الإيميل/الأوردر + سكرين + الـ context snapshot. `onError: continueRegularOutput`. |
| A5 | WhatsApp Alert | `n8n-nodes-base.httpRequest` · 4.2 (Evolution sendText) | لعمر `01011516829` ✅ + أحمد `<AHMED_WHATSAPP>` ⏳ (لو فاضي → خطوة أحمد تتخطّى مع log "pending"). نفس Evolution credential. `onError: continueRegularOutput`. |
| A6 | Return ack | (آخر نود) | يرجّع `{ escalated: true, escalation_id }` للـ Agent عشان عمر يطمّن العميل بصدق ("وصّلت الفريق"). |

**enum alignment (لازم يطابق `postgres-schema.sql` حرفياً):**
- `type` → `omar.escalation_type`: `access · certificate · payment · payment_intl · site_access · refund · angry · for_ahmed`.
- `priority` → `omar.priority`: `normal · high · urgent` (عميل غاضب = `urgent`).
- `status` يبدأ `open` (الـ partial index `idx_escalations_open` بيخلّيه يظهر في `omar.open_escalations` view لحد ما حد يقفله).

**alert recipients (من الـ canonical contract):**
| | إيميل | واتساب |
|---|---|---|
| أحمد | `ahmedadel123422@gmail.com` ✅ | `<AHMED_WHATSAPP>` ⏳ (لسه ما اداهوش) |
| عمر | `omarabdo385@gmail.com` ✅ | `01011516829` ✅ |

---

## 5. Credentials — جدول مرجعي (كله placeholder ماعدا الموجود فعلاً)

| الاسم | type | ID | بيستخدمه | الحالة |
|---|---|---|---|---|
| `Postgres — omar_agent` | `postgres` | `<PG_CRED_ID>` | N4, N6, N7, N8b, A2, A3 | ⏳ يتعمل بعد provision DB `omar_agent` |
| `Google Gemini — Learn Simply` | `googlePalmApi` | `<GEMINI_CRED_ID>` | N8a | ⏳ محتاج Gemini API key |
| `Evolution API — Learn Simply` | `httpHeaderAuth` (`apikey`) | `<EVOLUTION_CRED_ID>` | N10, A5 | ⏳ محتاج Evolution host+instance+apikey |
| `WooCommerce ReadOnly — Learn Simply` | `httpBasicAuth` | `<WC_CRED_ID>` | N8d | ⏳ محتاج WC read-only consumer key/secret |
| **`Mautic HTTP Basic — Learn Simply`** | `httpBasicAuth` | **`HcKVugtv8k1Yr47c`** | N8e | ✅ **موجود** (إعادة استخدام من W1/W2) |

> كل القيم الحساسة (secret path token, Gemini key, Evolution apikey, WC key) → brand `.env` (cross-device sync) + Bitwarden، **مش في الـ spec**.

---

## 6. Idempotency & durability (نفس فلسفة W1/W2)

| طبقة | الآلية |
|---|---|
| **Dedup webhook deliveries** | N4 SELECT + N5 IF على `wa_message_id` → duplicate = ack صامت (N12)، مفيش رد متكرر. |
| **DB-level guard** | `uq_messages_wa_id` unique index + `ON CONFLICT DO NOTHING` في N7 → حتى لو سباق عدّى N4، الصف ما يتكررش. |
| **Contact upsert** | `ON CONFLICT (phone) DO UPDATE` في N6 → run-safe، ما يكررش contacts، ما يمسحش data موجودة (`COALESCE`). |
| **Transient retry** | كل Postgres/HTTP node حرج: `retryOnFail 3× / 2000ms` (نمط W1). |
| **Persistent failure → re-delivery** | فشل نهائي → الـ workflow يفشل → Evolution webhook retry. الـ dedup فوق بتمنع تكرار الأثر. |
| **Non-critical never blocks reply** | tools (kb/order/mautic) + logging + alerts الفرعية: `onError: continueRegularOutput` → فشلها ما يمنعش رد العميل. |
| **Escalation never lost** | `omar-alert` يكتب الـ DB row **قبل** الإيميل/واتساب → مرئي في `omar.open_escalations` حتى لو الإشعار فشل. |

---

## 7. Inactive-by-default rule (زي W2)

- **الـ workflow يتبني INACTIVE** ويفضل كده لحد ما:
  1. الـ Postgres schema (`postgres-schema.sql`) اتشغّل على `omar_agent` (موافقة Omar).
  2. الـ credentials الـ 4 الناقصة اتعملت.
  3. Evolution مربوط برقم المساعد + الـ webhook مظبوط يبعت لـ N1 secret path.
  4. اختبار end-to-end برقم تجريبي عدّى (A سعر · F2 access · F5 استرجاع · E كود · dedup · escalate).
- **التفعيل** = toggle واحد (`n8n_update_partial_workflow activateWorkflow`) — نفس ما W2 مكتوب.
- لحد التفعيل، الـ webhook URL بيرجّع **404** (زي W2 و هي INACTIVE) — آمن.

---

## 8. Buildable now ✅ vs Blocked ⛔ (go-live callout)

### ✅ نقدر نبنيه دلوقتي (بأرقام placeholder، INACTIVE)
- **كل هيكل `omar-inbound`** (N1–N12) + الـ connections + الـ AI Agent مع 4 tools موصّلين.
- **كل هيكل `omar-alert`** (A1–A6) بالـ DB-first pattern.
- **tool `mautic_upsert`** — جاهز 100% (credential `HcKVugtv8k1Yr47c` + نمط W1 موجودين).
- **الـ dedup + idempotency + logging** — كلها معتمدة على `postgres-schema.sql` اللي خلص (مستني نشر بس).
- **system prompt** للـ Agent — جاهز (`omar-system-prompt.md`).

### ⛔ Blocked على نشر/مفاتيح (Omar action، مش أحمد)
| البند | محتاج |
|---|---|
| تشغيل الـ workflow فعلياً | provision DB `omar_agent` + run `postgres-schema.sql` (+ `rag/schema-kb.sql` لـ kb_search) — موافقة Omar |
| `Postgres` credential | بعد provision DB |
| `Google Gemini` credential | Gemini API key |
| `Evolution` credential + N10/A5 | Evolution host + instance + apikey + ربط رقم المساعد |
| `kb_search` يرجّع نتائج حقيقية | run `rag/schema-kb.sql` + `ingest_kb.py` (RAG ingestion) |
| `order_lookup` يشتغل | WooCommerce read-only API key (`<WC_READONLY_KEY>`) |

### ⛔ Blocked على أحمد (go-live بثقة 100%)
| البند | محتاج من أحمد |
|---|---|
| **رقم واتساب المساعد** | الرقم اللي عمر هيشتغل عليه (≠ رقم أحمد الشخصي) — **بدونه مفيش inbound أصلاً** |
| **رقم واتساب أحمد للإنذار** (`<AHMED_WHATSAPP>`) | A5 يبعتله؛ لحد ساعتها عمر بس + إيميل للاتنين |
| **مسار F2** (دخول الكورس) | الخطوات الحرفية — لحد ساعتها الـ Agent على سلوكه الآمن (راجع system prompt §B-F2) |
| **مسار C** (بديل دفع دولي) | قرار PayPal/بديل — السلوك الآمن في الـ prompt شغّال |
| **ساعات الدعم + SLA** | يأثّر على رسائل الطمأنة (دلوقتي "بأسرع وقت") |

> **الخلاصة:** نقدر نبني ونختبر **كل حاجة** دلوقتي بأرقام تجريبية. اللي بيمنع الـ **go-live الحقيقي** = (1) موافقة Omar على نشر الـ Postgres + المفاتيح، و(2) رقمين واتساب من أحمد. الفجوات الباقية (F2/C/SLA) **ما بتمنعش الإطلاق** — الـ system prompt فيه سلوك آمن لكل واحدة، بس بتمنع "الثقة 100%".

---

## 9. خطوات البناء المقترحة (للسيشن اللي هيبني فعلياً عبر n8n-MCP)
1. `get_node_essentials` على الـ 11 node type في §0 — أكّد الـ typeVersions على الـ instance الحالي، حدّث الجدول لو اختلف.
2. ابني `omar-alert` الأول (sub-workflow) → خد `<OMAR_ALERT_WORKFLOW_ID>`.
3. ابني `omar-inbound` (N1–N12) + الـ AI sub-nodes + الـ connections، واربط `escalate` بالـ ID من خطوة 2.
4. اربط الـ credentials الموجودة (Mautic `HcKVugtv8k1Yr47c`) + placeholders للباقي.
5. سيبه **INACTIVE**. اعرض على Omar للنشر + اطلب رقمين أحمد.
6. بعد النشر + المفاتيح + الأرقام: اختبار برقم تجريبي → فعّل (toggle).

---

## 10. ملاحظات (gotchas من W1/W2 + Evolution)
- **Evolution payload shape بيختلف بالنسخة** — N2 defensive عشان كده. أكّد الشكل الفعلي من أول رسالة تجريبية قبل ما تثبّت الـ field paths.
- **send-text body shape** برضه بيختلف بالنسخة (N10) — `{number, text}` أو `{number, textMessage:{text}}`. اتأكد وقت البناء.
- **UTF-8 عربي:** الـ Postgres + Mautic + Evolution كلهم بيتعاملوا مع العربي صح (W2 أثبت `أحمد` بيتخزن سليم؛ الـ `?????` كان artifact من Windows-Bash curl مش من الـ workflow). متستخدمش curl من Windows-Bash للاختبار اليدوي للعربي — استخدم ملف UTF-8.
- **الـ phone كـ E.164 digits only** هو الـ PK في `omar.contacts` والـ `sessionKey` للـ memory — لازم التطبيع في N2 يكون متطابق في كل مكان (نفس الرقم بالظبط) وإلا الذاكرة هتتقسم.
- **`source_channel`: `whatsapp` بيتبعت** من `mautic_upsert` (قيمة valid في الـ select — متحقّقة من `create-custom-fields.sh`؛ بعكس `dart-popup` في W2 اللي اتشالت). الـ `whatsapp-lead` tag علامة إضافية.
- **Mautic contact delete** (لو احتجت تنضّف بعد اختبار): لازم `/delete` suffix — `DELETE /api/contacts/{id}/delete` (W2 §Notes).
```
