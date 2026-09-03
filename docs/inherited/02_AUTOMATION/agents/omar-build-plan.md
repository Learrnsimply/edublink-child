# 🏗️ Build Plan — "عمر" WhatsApp Agent

> **الحالة (2026-06-10):** ✅ **المعمارية دي اتبنت بالكامل** — prompt v0.2 (بإجابات أحمد) محقون في n8n · 3 workflows مبنيين (IDs في `.env` §20) · Evolution instance `omar-support` + webhook · credentials متظبطة. الباقي للـ go-live: Gemini key + QR + WC key + RAG ingest + تفعيل (checklist في `deploy/README.md`). الملف ده بقى مرجع معماري.
> **القرارات المعمارية المثبّتة:** inbound فقط · Evolution API · Postgres على الـ VPS (مش Supabase، وفّر ~14.4K ج/سنة) · Gemini Flash · ابدأ ضيّق.
> **الملفات الأخوات:** `omar-system-prompt.md` (العقل) · `postgres-schema.sql` (الذاكرة) · `whatsapp-agent-design.md` (التصميم الكامل) · `../../03_KNOWLEDGE/knowledge-base.md` (المعرفة).

---

## 1. المعمارية (نظرة عامة)

```
عميل واتساب
   │ (inbound message)
   ▼
Evolution API (على الـ VPS)
   │ webhook
   ▼
n8n workflow "omar-inbound"
   ├─ 1. Webhook (Evolution) ──► dedup على wa_message_id
   ├─ 2. Upsert omar.contacts (phone) + log رسالة العميل في omar.messages
   ├─ 3. Load context: آخر ~10 رسائل (Postgres Chat Memory keyed on phone)
   ├─ 4. AI Agent node (Gemini Flash)
   │      ├─ system message = omar-system-prompt.md
   │      ├─ tools:
   │      │     • kb_search     → RAG على knowledge-base (pgvector)
   │      │     • order_lookup  → WooCommerce API بالتليفون/الإيميل
   │      │     • mautic_upsert → Mautic API (leads + tags)
   │      │     • escalate      → يكتب omar.escalations + يطلق الإنذار
   │      └─ يطلّع: رد العميل + (tool calls)
   ├─ 5. log رد عمر في omar.messages
   └─ 6. Evolution send ──► العميل
                │
   (لو escalate اتنده) ──► sub-workflow "omar-alert"
                              ├─ إيميل لأحمد + عمر (Mautic SMTP أو SMTP مباشر)
                              └─ واتساب لأحمد + عمر (Evolution)
```

**ذاكرة هجينة (RS port):** بروفايل طويل المدى في `omar.contacts` (مين العميل، اهتماماته، حالته) + نافذة قصيرة من `omar.messages` للـ context الفوري.

---

## 2. هيكل الـ n8n workflows (skeleton)

### Workflow A — `omar-inbound` (الرئيسي)
| # | Node | النوع | بيعمل إيه |
|---|---|---|---|
| 1 | Evolution Webhook | Webhook | يستقبل رسائل واتساب الجاية |
| 2 | Dedup | Code/IF | يتجاهل لو `wa_message_id` اتسجّل قبل كده |
| 3 | Upsert Contact | Postgres | `INSERT ... ON CONFLICT (phone) DO UPDATE` + log الرسالة. **بيملأ الحقول الحيّة:** `last_intent` (من تصنيف عمر) · `country` (لو العميل ذكره في مسار C) · `lead_status`: `engaged` عند تفاعل/A-close، `purchased` لو `order_lookup` لقى أوردر مدفوع (عشان الأعمدة دي مش تفضل فاضية) |
| 4 | Chat Memory | Postgres Chat Memory | آخر 10 رسائل keyed على phone |
| 5 | عمر (AI Agent) | LangChain Agent + Gemini | system prompt + 4 tools |
| 6 | Log + Send | Postgres + Evolution | يسجّل الرد + يبعته للعميل |

**خريطة التصنيف (prompt A-F → `omar.intent_box`):** A→`sales` · B→`support` · C→`payment_intl` · D→`for_ahmed` · E→`code` · F→`sensitive`. الـ n8n layer بيترجم حرف التصنيف للـ enum قبل ما يكتب `messages.intent` / `contacts.last_intent` (المابينج ده عقد صريح، مش مدفون في تعليقات الـ SQL).

### Workflow B — `omar-alert` (التصعيد — sub-workflow)
| # | Node | بيعمل إيه |
|---|---|---|
| 1 | Trigger | يتنده من tool `escalate` |
| 2 | Write escalation | `INSERT omar.escalations` |
| 3 | Email | لأحمد (`ahmedadel123422@gmail.com`) + عمر (`omarabdo385@gmail.com`) |
| 4 | WhatsApp | لأحمد (⏳ رقمه) + عمر (`01011516829`) عبر Evolution |

### Tools (sub-workflows أو HTTP nodes)
- `kb_search` → pgvector similarity على chunks الـ KB (embeddings).
- `order_lookup` → `GET /wp-json/wc/v3/orders?search=<phone|email>` (WooCommerce REST، read-only key).
- `mautic_upsert` → `POST /api/contacts/new` (نفس نمط W1، + `source_channel:whatsapp`).
- `escalate` → يكتب `omar.escalations` (type/priority/summary/customer_email/**order_id**/screenshot + country في الـ context) + ينده Workflow B (إيميل + واتساب).

---

## 3. اللي نقدر نبنيه دلوقتي ✅ vs اللي مستني أحمد ⛔

### ✅ Buildable الآن (صفر اعتماد على أحمد)
| البند | الحالة |
|---|---|
| **system prompt** (عقل عمر) | ✅ خلص — `omar-system-prompt.md` |
| **Postgres schema** | ✅ خلص — `postgres-schema.sql` (مستني موافقة Omar على النشر) |
| **مسارات A / D / E / F1 / F5** | ✅ متحددة بالكامل في الـ prompt |
| **RAG ingestion** (تحويل الـ KB لـ embeddings) | ⏳ جاهز نبنيه — المصدر `knowledge-base.md` + `data/website-extract/` |
| **tool `order_lookup`** | ⏳ جاهز — محتاج WooCommerce read-only API key بس |
| **tool `mautic_upsert`** | ✅ النمط موجود (W1) — إعادة استخدام |
| **tool `escalate` + Workflow B** | ⏳ جاهز ماعدا رقم واتساب أحمد للإنذار |
| **n8n workflow skeleton** | ⏳ جاهز نبنيه بـ n8n-MCP (placeholder Evolution credentials) |

### ⛔ Blocked على رد أحمد (`ahmed-questions-kb-gaps.md`)
| البند | محتاج إيه من أحمد |
|---|---|
| **مسار F2** (دخول الكورس بعد الدفع) | الخطوات الحرفية (لينك/باسورد/مكان الكورسات) |
| **مسار C** (بديل دفع دولي دائم) | قرار: نفعّل PayPal؟ البديل إيه؟ |
| **ساعات الدعم + SLA** | مين بيرد + إمتى |
| **وحدة Graph** | ميعاد النزول |
| **قانون الإمارات في الشروط** | مقصود؟ (يأثّر على رد الاسترجاع) |
| **🔌 تشغيل Evolution فعلياً** | **رقمين واتساب**: رقم المساعد + رقم أحمد للإنذار |

> **ملاحظة مهمة:** الفجوات دي **مش بتمنع البناء** — الـ prompt فيه سلوك آمن لكل واحدة. بتمنع بس **الإطلاق الكامل بثقة 100%**. نقدر نبني ونختبر كل حاجة بأرقام تجريبية، وبمجرد ما أحمد يرد نقفل الفجوات ونوصّل الرقم الحقيقي.

---

## 4. خطوات النشر (Deploy) — بعد موافقة Omar

> ⚠️ **مفيش حاجة من دول اتنفّذت.** كله محتاج موافقة صريحة (تعديل على prod VPS).

1. **Postgres:** SSH للـ VPS → provision database `omar_agent` + user → run `postgres-schema.sql`. (آمن: DB جديدة منفصلة، مش بتلمس n8n/Mautic.)
2. **Evolution API:** تأكيد إنه منصّب على الـ VPS (مذكور في القرارات بس مش متأكد إنه live) → ربط رقم واتساب المساعد (محتاج رقم أحمد).
3. **n8n:** بناء Workflow A + B عبر n8n-MCP + ربط credentials (Gemini key, Postgres, Evolution, WooCommerce, Mautic).
4. **RAG:** سكربت ingestion يقطّع `knowledge-base.md` → embeddings → pgvector في `omar_agent`.
5. **اختبار:** رقم تجريبي → سيناريوهات (A سعر · F2 access · F5 استرجاع · E كود) → تأكيد الردود + التصعيد + عدم الاختراع.
6. **Go-live:** بعد رد أحمد + الرقم الحقيقي + اختبار نظيف.

---

## 5. الترتيب المقترح للسيشن الجاية
1. **(الآن)** عرض الـ foundation على Omar + قرار نشر الـ Postgres schema.
2. ابني RAG ingestion + tools (`order_lookup`, `mautic_upsert`, `escalate`) — كلها buildable.
3. ابني n8n Workflow A + B skeleton بأرقام placeholder.
4. **بالتوازي:** Omar يبعت رسالة أحمد (الـ 7 أسئلة) — أول ما ترد، نقفل الفجوات.
5. اختبار end-to-end برقم تجريبي → go-live.

---

## ⚠️ تنبيهات
- **Evolution = inbound فقط** (القرار المعماري) — آمن. مفيش بث/outbound من عمر للعملاء غير الرد على رسايلهم + التصعيد.
- **عمر مايكشفش إنه bot** (قرار البراند) — بس مايكدبش كدبة صريحة لو اتسأل، ومايخترعش معلومة (راجع القواعد الذهبية في الـ prompt).
- **Telegram bot reuse** — لو ضفنا تنبيه تليجرام لاحقاً، استخدم `_agency/notify.sh` (مفيش bot جديد).
- **drift الأسعار:** لو السعر اتغيّر على الموقع، حدّث `omar-system-prompt.md` §المعلومات **و** الـ KB. السعر inline في الـ prompt عشان الدقة.
