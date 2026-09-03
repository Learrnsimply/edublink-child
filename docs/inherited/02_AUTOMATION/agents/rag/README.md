# RAG Ingestion — `kb_search` for "عمر" WhatsApp Agent

> **الحالة:** السكربت **مكتوب، مش متشغّل** (authored, NOT run). مفيش embedding اتحسب، مفيش DB اتكتب، مفيش حاجة لمست الـ prod. التشغيل محتاج موافقة Omar صريحة (زي باقي ملفات `agents/` — راجع `omar-build-plan.md` §Deploy).
>
> **الغرض:** ده الـ pipeline اللي بيحوّل المنهج العميق من الـ Knowledge Base لـ vectors، عشان أداة `kb_search` تقدر تجيب المقطع المظبوط لما العميل يسأل سؤال تفصيلي عن درس/وحدة مش موجود inline في الـ system prompt.
>
> **مبدأ أساسي:** الأسعار + الكاتالوج + السياسات **مش بتتقدّم من هنا** — هي inline في `omar-system-prompt.md` §المعلومات عشان الدقة المطلقة في السعر. الـ RAG بيخدم **المنهج/الدروس/التفاصيل العميقة بس**. (لو حصل تعارض بين السعر هنا والسعر في الـ prompt، الـ prompt يكسب.)

---

## 1. ليه RAG أصلاً؟ (الفكرة قبل المصطلح)

تخيّل إن عندك كتالوج ضخم (336 درس عبر 5 كورسات). لو حطّيت الكتالوج كله جوه دماغ الموديل في كل رسالة = غالي + بيشتّت الموديل + بيوصل لحدود السياق. بدل كده، بنخزّن كل قطعة معرفة كـ "بصمة رقمية" (embedding) في قاعدة بيانات، ولما العميل يسأل، بنجيب **أقرب 3–5 قطع** للسؤال بس ونحطّهم قدام الموديل. ده اسمه **RAG** (Retrieval-Augmented Generation) — الموديل بيجاوب من معرفة "اتسحبت" وقت الحاجة، مش محفوظة جواه.

الـ "بصمة الرقمية" = **embedding**: ليستة أرقام (768 رقم هنا) بتمثّل معنى النص. النصوص المتشابهة في المعنى = بصماتهم قريبة من بعض رياضياً (cosine similarity). pgvector بيخزّن البصمات دي في Postgres وبيلاقي الأقرب بسرعة.

---

## 2. استراتيجية التقطيع (Chunking)

الـ KB **متصمّمة أصلاً عشان تتقطّع بسهولة**: كل قسم/كورس/وحدة عنوان مستقل بسياقه الكامل (ده مكتوب حرفياً في مقدمة `knowledge-base.md`). فاستراتيجيتنا بسيطة ومحترمة للبنية الموجودة:

**قاعدة: chunk واحد لكل عنوان (heading)، مع الاحتفاظ بسلسلة العناوين الأب كاملة.**

| القرار | التفصيل | ليه |
|---|---|---|
| **التقطيع بالعنوان** | كل `#`..`######` (ATX heading) بيبدأ chunk جديد. جسم الـ chunk = سطر العنوان + كل النص تحته لحد العنوان اللي بعده على نفس العمق أو أقل. | بيطابق بنية الـ KB المقصودة (chunk-per-section). |
| **سلسلة الأب محفوظة** | `heading_path` = كل العناوين الأب مفصولة بـ ` > `. مثال: `الكورسات > هياكل البيانات م١ > و2 — Linked List`. | الـ chunk لوحده ("و2 — Linked List") مبهم؛ مع السلسلة بيبقى واضح إنه تابع لأي كورس. كمان بيتـ embed مع الجسم فالـ vector بيحمل الموضوع. |
| **اتفاقية الوحدة في المنهج** | في `_generated-curriculum.md` الوحدات مكتوبة كـ `**الوحدة: ...**` (سطر bold مش `#` heading). الـ chunker بيتعرّف عليها كـ sub-heading **تحت أقرب ATX heading** — فكل وحدات الكورس بيبقوا **أشقّاء** تحت عنوان الكورس، مش سلسلة متداخلة. | بدون ده، الوحدات كانت بتتعشّش جوه بعض (bug اتصلّح + اتأكّد بالـ dry-run). |
| **العنوان متبقّى مع الجسم** | بنعمل embed لـ `{title}\n{body}` مع بعض. | الـ vector بيحمل التسمية (الموضوع) مش بس التفاصيل → استرجاع أدق. |
| **حدود الحجم (safety)** | عنوان جسمه أكبر من `MAX_CHUNK_CHARS` (4000 حرف) بيتقسّم على حدود الفقرات/القوائم، ومع كل قطعة بيتعاد العنوان (`(تابع)`). | يمنع chunk عملاق نادر. الـ KB الحالية مفيهاش قطعة بتعدّي الحد، فده وقاية مش إعادة تشكيل. |
| **المقدمة مش بتتفقد** | أي نص قبل أول عنوان بيتسجّل تحت مسار `(preamble)`. | صفر فقدان للمعلومة. |

**النتيجة الفعلية (من الـ dry-run):**
- `knowledge-base.md` لوحده = **26 chunk** (~5,375 token).
- `knowledge-base.md` + `_generated-curriculum.md` = **73 chunk** (~8,610 token).

---

## 3. اختيار موديل الـ Embedding

**الافتراضي: Gemini `text-embedding-004`** (768 بُعد) — بيتماشى مع قرار الموديل (Gemini Flash) ومع مفتاح Gemini الموجود أصلاً في الـ stack.

- **قابل للتبديل عبر env** — مفيش مفتاح متحطّ في الكود (hardcoded). الـ provider بيتحدد بـ `EMBED_PROVIDER`:
  - `gemini` (افتراضي) → `text-embedding-004`، مفتاح من `GEMINI_API_KEY`.
  - `openai` → `text-embedding-3-small`، مفتاح من `OPENAI_API_KEY`، بُعد قابل للضبط بـ `dimensions`.
  - `fake` → embedding وهمي حتمي (deterministic) للاختبار/الـ dry-run بدون أي API call.
- **البُعد لازم يتطابق مع الـ schema.** `EMBED_DIM` (افتراضي 768) لازم = `vector(N)` في `schema-kb.sql`. لو غيّرت الموديل لبُعد تاني، **غيّر الاتنين + اعمل re-ingest من الأول** (السكربت بيتأكد وبيرمي error لو البُعد مش متطابق).
- **ليه cosine؟** بصمات Gemini مصمّمة للمقارنة بالـ cosine similarity → فالـ index بيستخدم `vector_cosine_ops`.

---

## 4. تصميم جدول pgvector

الجدول `omar.kb_chunks` في نفس DB الذاكرة (`omar_agent`) ونفس الـ schema (`omar`). الـ DDL الكامل في **`schema-kb.sql`**. ملخص الأعمدة:

| العمود | النوع | بيمثّل |
|---|---|---|
| `id` | `BIGINT` identity PK | مفتاح الصف |
| `source` | `TEXT` | اسم الملف المصدر (`knowledge-base.md` / `_generated-curriculum.md`) |
| `heading_path` | `TEXT` | سلسلة العناوين الأب كاملة (السياق) |
| `content` | `TEXT` | جسم الـ chunk (العنوان + نصّه) — ده اللي بيترجّع للموديل |
| `content_hash` | `TEXT` | `sha256(source\|heading_path\|content)` — مفتاح الـ idempotency / كشف التغيير |
| `token_count` | `INTEGER` | تقدير تقريبي للـ tokens (لميزانية سياق الموديل) |
| `embedding` | `vector(768)` | بصمة الـ Gemini (NULL بس في dry-run) |
| `chunk_index` | `INTEGER` | ترتيب الـ chunk داخل الملف (قراءة ثابتة) |
| `created_at` / `updated_at` | `TIMESTAMPTZ` | توقيتات (trigger بيحدّث `updated_at`) |

**الفهارس:**
- `UNIQUE (source, content_hash)` → مفتاح الـ upsert (نفس النص = صف واحد، إعادة تشغيل آمنة).
- **HNSW** على `embedding` بـ `vector_cosine_ops` → أفضل recall/latency لكوربوس صغير ثابت زي ده. (fallback لـ IVFFlat معلّق في الـ SQL لو نسخة pgvector قديمة.)

> ⚠️ **pgvector لازم يكون متفعّل** (`CREATE EXTENSION vector;`) قبل الجدول — صورة `postgres:16` الستوك **مفيهاش** pgvector. استخدم صورة `pgvector/pgvector:pg16` أو ثبّت `postgresql-16-pgvector`. التفاصيل + التحذير في آخر `schema-kb.sql`. **نسّق مع Omar قبل أي تغيير على صورة الـ prod.**

---

## 5. عقد أداة `kb_search` (للـ n8n spec)

ده العقد اللي الـ AI Agent node في n8n هيربط عليه أداة `kb_search`:

**الاسم:** `kb_search` (من القائمة الرسمية للأدوات — متغيّرش الاسم).

**المدخل (input):**
```json
{ "query": "string — نص سؤال العميل (عربي)", "k": 5 }
```
- `query` (مطلوب): نص السؤال زي ما العميل كتبه.
- `k` (اختياري، افتراضي 5): عدد المقاطع المطلوب رجوعها.

**المخرج (output):** أعلى-k مقاطع متشابهة، مرتّبة من الأقرب:
```json
{
  "results": [
    {
      "id": 12,
      "source": "_generated-curriculum.md",
      "heading_path": "هياكل البيانات م١ > الوحدة: الوحدة الثانية : Linked List",
      "content": "... نص المقطع ...",
      "token_count": 158,
      "similarity": 0.83
    }
  ]
}
```
- `similarity` في المدى `0..1` (1 = أقرب). الموديل بيستخدم الـ `content` + `heading_path` كسياق، **مش** بيقتبس الـ score للعميل.

**الربط في n8n (نمطان، كلاهما يقرأ نفس الجدول):**
1. **عبر الدالة المخزّنة** (موصى به): الـ tool بيـ embed الـ `query` (نفس الموديل المستخدم في الـ ingest) → بينده `SELECT * FROM omar.kb_search($queryVec, $k);` (الدالة معرّفة في `schema-kb.sql`). الـ embed وقت الاستعلام لازم يكون **بنفس** `EMBED_PROVIDER`/`EMBED_MODEL`/`EMBED_DIM` بتاع الـ ingest، وإلا المقارنة غلط.
2. **SELECT inline** (لو مش عايز الدالة): نفس منطق الدالة كـ query مباشر في Postgres node:
   ```sql
   SELECT id, source, heading_path, content, token_count,
          (1 - (embedding <=> $1::vector)) AS similarity
   FROM omar.kb_chunks
   WHERE embedding IS NOT NULL
   ORDER BY embedding <=> $1::vector
   LIMIT $2;
   ```

> **ملاحظة معمارية:** الـ query embedding بيتعمل بـ `task_type=retrieval_query` (مقابل `retrieval_document` للـ ingest) لو الـ provider بيدعم التمييز — Gemini بيدعمه. ده بيحسّن دقة الاسترجاع. (الـ ingest بيستخدم `retrieval_document`.)

---

## 6. إزاي تشغّله (لما Omar يوافق)

> كل ده **بعد** موافقة صريحة. مفيش حاجة منهم اتنفّذت.

**0. متطلبات (في venv منفصل، مش على الـ prod على عمياني):**
```bash
pip install "psycopg[binary]" google-generativeai   # + openai لو هتستخدم OpenAI
```

**1. dry-run (آمن 100% — بيقرأ ملفات بس، صفر DB، صفر API):**
```bash
python ingest_kb.py --dry-run                 # الـ KB بس
python ingest_kb.py --dry-run --with-curriculum   # + المنهج الـ 336 درس
```
بيطبّع عدد الـ chunks + سلاسل العناوين + تقدير الـ tokens. استخدمه دايماً قبل أي ingest حقيقي عشان تشوف هيتكتب إيه.

**2. تفعيل pgvector + إنشاء الجدول (مرة واحدة، محتاج superuser):**
```bash
psql "$OMAR_DB_DSN" -c "CREATE EXTENSION IF NOT EXISTS vector;"
psql "$OMAR_DB_DSN" -f schema-kb.sql
```

**3. ingest حقيقي (محتاج env كامل):**
```bash
export OMAR_DB_DSN="postgresql://omar_agent:<pw>@127.0.0.1:5432/omar_agent"
export EMBED_PROVIDER=gemini
export GEMINI_API_KEY="<from .env / Bitwarden>"
python ingest_kb.py --with-curriculum --verbose
```
المخرج بيقول: `inserted/updated`, `skipped(unchanged)`, `pruned`.

**أعلام (flags) مفيدة:**
| العلم | بيعمل إيه |
|---|---|
| `--dry-run` | تحليل + تقطيع + تقرير. صفر DB، صفر embedding. |
| `--with-curriculum` | يضمّ كمان `_generated-curriculum.md` (تفاصيل الـ 336 درس). |
| `--prune-only` | يحذف بس الصفوف اللي chunk-اتها اختفت (تنظيف، بدون insert). |
| `--verbose` | لوج لكل chunk على stderr. |

**Env المتغيّرات (كلها من البيئة — مفيش secret في الكود):**
`OMAR_DB_DSN` · `EMBED_PROVIDER` · `EMBED_MODEL` · `EMBED_DIM` · `GEMINI_API_KEY` / `OPENAI_API_KEY` · `KB_FILE` / `CURRICULUM_FILE` (override للمسارات، اختياري).

---

## 7. الـ Idempotency (إعادة التشغيل آمنة)

السكربت **idempotent** بالتصميم:
- مفتاح التفرّد = `(source, content_hash)`. نفس النص = نفس الـ hash = صف واحد. (متأكّد: الـ hashes ثابتة عبر التشغيلات + فريدة لكل chunk.)
- chunk **اتغيّر** نصّه → hash جديد → `INSERT ... ON CONFLICT DO UPDATE` يحدّث الصف + يعيد الـ embedding.
- chunk **اتشال** من المصدر → الصف القديم بيتـ **prune** (يتحذف) في نفس التشغيلة.
- chunk **متغيّرش** → بيتـ skip بدون إعادة embedding (توفير API calls + استقرار).

يعني تقدر تشغّله بعد أي تعديل على الـ KB من غير ما يعمل تكرار أو يهدر embeddings.

---

## 8. إعادة الـ ingestion بعد نقل الاستضافة ⚠️

الموقع بيتنقل لاستضافة جديدة (راجع `HANDOFF.md`). تأثير ده على الـ RAG:

- **الـ chunking + الـ DB مش متأثّرين** — الـ KB ملف محلي في الريبو، والـ `omar_agent` DB على الـ **VPS** (مش على استضافة الموقع المشتركة)، فمفيش حاجة تتكسر من النقل نفسه.
- **بس** لو اتسحبت داتا جديدة من الموقع بعد النقل (curriculum محدّث، أسعار، إلخ): حدّث `knowledge-base.md` (+ `_generated-curriculum.md` لو لزم) → بعدين **شغّل `python ingest_kb.py --with-curriculum` تاني**. الـ idempotency هيتكفّل: المتغيّر يتحدّث، المتشال يتـ prune، الباقي يتـ skip.
- خطوات إعادة السحب نفسها (تحديث الـ SSH alias `learnsimply` + سكربتات السحب + `assemble-curriculum.js` + `strip-pages.py`) موثّقة في آخر `knowledge-base.md` §الصيانة.

> القاعدة: **أي تعديل على الـ KB → re-ingest.** السكربت رخيص ويعيد بناء الجزء المتغيّر بس.

---

## الملفات في الفولدر ده

| الملف | بيدّيك |
|---|---|
| `README.md` | (ده) الاستراتيجية + عقد `kb_search` + التشغيل. |
| `ingest_kb.py` | سكربت التقطيع + الـ embedding + الـ upsert (idempotent، dry-run flag). **مكتوب مش متشغّل.** |
| `schema-kb.sql` | DDL لـ `omar.kb_chunks` + index (HNSW) + دالة `omar.kb_search` + ملاحظة pgvector. |
