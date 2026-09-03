---
name: ls-notion
description: >-
  Update the Learn Simply (اتعلم ببساطة) Notion workspace — status page, Tasks DB,
  "Tasks Required From Ahmed", Files & Reports, Assets Requested. Trigger here whenever
  Omar says anything like: "حدّث نوشن" / "حدّث التراكر" / "update Notion" / "اكتب عند أحمد" /
  "وضّح لأحمد في الترّاكر" / "بلّغ أحمد" / "سجّل في الترّاكر" / "ضيف تاسك" / "ابعت طلب لأحمد" /
  "سجّل الإنجاز" / يشغّل /ls-notion — حتى لو ما ذكرش Notion بالاسم. Bakes in every
  page/DB/data-source ID, schemas, Ahmed/Omar user IDs, and hard rules (verify-before-claim,
  client-facing tone, mention+comment for Ahmed). Omar should never re-explain the Notion
  structure. NOT here: updating the WhatsApp agent prompt DB, n8n workflow notes, or any
  Notion workspace outside learn-simply.
---

# Learn Simply — Notion Sync

الغرض: تحديث Notion بتاع **اتعلم ببساطة** بأمر واحد، من غير ما Omar يعيد شرح البنية كل مرة.
كل الـ IDs والقواعد محفوظة هنا. لو حاجة في Notion اتنقلت/اتغيّر اسمها → حدّث الملف ده.

> **تحديث Notion — 3 طرق (الأحدث 2026-06-06):**
> 1. **سيرفر `notion` الدائم بالتوكن** (✅ المفضّل) — أدوات `mcp__notion__*`، بدون تسجيل دخول، user scope (متاح في كل البراندات). متخزّن في `~/.claude.json` (env `NOTION_TOKEN`، محلي على الجهاز — **مش** على GitHub). أول مرة بعد التركيب: السيشن لازم تتعمل restart عشان الأدوات تتحمّل.
> 2. **REST API مباشرة بالتوكن** عبر Bash/PowerShell (`Authorization: Bearer <NOTION_TOKEN>` · `Notion-Version: 2022-06-28`) — بيشتغل في أي سياق حتى لو أدوات الـ MCP لسه مش محمّلة (مفيد في نفس سيشن التركيب). query DB: `POST /v1/databases/{id}/query` · قراءة صفحة: `GET /v1/blocks/{id}/children` · بحث: `POST /v1/search`.
> 3. **سيرفر `claude.ai Notion` القديم** (`mcp__claude_ai_Notion__*`, OAuth interactive) — fallback بس لو التوكن اتسحب/اتلغى.
>
> السيرفر الجديد non-interactive، فـ background/workflow agents ممكن توصله (على عكس OAuth اللي بيختفي في الـ headless runs). لكن تحديثات Notion من السيشن الرئيسية تظل الأبسط.

---

## 🗺️ خريطة Notion (IDs — لا تخمّن، استخدم دي)

**الصفحة الأم:** `🗂️ Projects` → `3711e071-e53a-815c-8b20-f05cdf7ed0e9`

| السطح | نوع | Page ID | Data source ID (للـ create) |
|---|---|---|---|
| **اتعلم ببساطة** (الصفحة الرئيسية / Status) | page | `3711e071-e53a-81d1-a0ee-c3e116ae086e` | — |
| **✅ Tasks** | database | `c67e359d-7686-4511-ac6f-4d67018017fd` | `7b3337f3-381c-4317-b2b0-db64cc527b32` |
| **📁 Files & Reports** | database | `14c5e69a-a546-4c2c-94b5-79634516b98c` | `da1fe05c-03df-44f8-b058-439fb4b96caf` |
| **📦 Assets Requested** | database | `4d52db33-25e0-439a-9250-c92ee2341da1` | `a8df3d65-24d4-4a97-8303-6ca80bc11478` |
| **📊 Projects** | database | `8a1cb599-fc15-4e04-8712-559bb8e6c62d` | `da1b57f3-a96b-495a-94b0-ca4a951f7b49` |
| **📑 Tasks Required From Ahmed** | page | `3721e071-e53a-80e5-a589-d7545820ab74` | — |
| **problems and Things i want** (صندوق طلبات أحمد الحر — هو بيكتب فيه مشاكله/طلباته + يرفع screenshots) | page | `3761e071-e53a-80ec-b01f-daeaa908c8c6` | — |
| **Competitors** | page | `3721e071-e53a-80dc-8b03-dcc713f9df90` | — |
| **🎬 Dart Course Montage** (tracker مونتاج داخلي لعمر) | page | `3791e071-e53a-81d1-9b0e-d5627c929bf6` | — |
| **دروس Dart — تتبّع المونتاج** (الجدول جوّه الصفحة دي · أعمدة: الدرس/الوحدة/ترتيب/المدة/دقائق/مونتاج/مترفع · 35 درس) | database | `3791e071-e53a-8172-ab59-c531748134c6` | — |

**Users (للـ @mention):**
- **Mr: Ahmed** (العميل، guest) → `0d9da346-52cf-43e9-aebd-f16aa6014954` · email `20812018100588@eng.zu.edu.eg`
- **Omar Abdelrahman** → `8c427cdf-83e0-4514-8b34-f287183493df`
- صيغة الـ mention في الـ markdown: `<mention-user url="user://0d9da346-52cf-43e9-aebd-f16aa6014954"></mention-user>`

---

## 🧩 Schemas (للـ create-pages / update_properties)

### ✅ Tasks (`7b3337f3-…`)
- `Name` (title) · `Done` (checkbox → `__YES__` / `__NO__`) · `Notes` (text)
- `Priority` (select: `Low` / `Medium` / `High`)
- `Project` (relation → `da1b57f3-…`) · `Task Creator` (person)

### 📁 Files & Reports (`da1fe05c-…`)
- `Name` (title) · `Type` (select: `Report` / `File` / `Doc` / `Link`)
- `date:Date:start` (ISO date) + `date:Date:is_datetime` (0/1) · `File` (file) · `userDefined:URL` (url)

### 📦 Assets Requested (`a8df3d65-…`)
- `Name` (title) · `Type` (select: `VPS`/`Image`/`Data`/`Invoice`/`Credential`/`Other`)
- `Status` (select: `Requested`/`In Progress`/`Done`) · `Amount` (number) · `Details` (text)

> Date properties: استخدم الصيغة الموسّعة `date:<prop>:start` / `:is_datetime`. Checkbox: `__YES__`/`__NO__`. أي property اسمه `id`/`url` → prefix بـ `userDefined:`.

---

## 📋 بروتوكول التحديث لكل سطح

### 1. الصفحة الرئيسية "اتعلم ببساطة" (Status) — **بتكلّم Ahmed (العميل)**
- **نبرة بسيطة بلغة بيزنس، صفر مصطلحات تقنية** (مفيش CVE/workflow IDs/RejectPolicy على الصفحة دي).
- التحديثات جوّه toggles `<details>` مؤرّخة (الأحدث فوق) أو callout للملاحظة العاجلة.
- ركّز على: إيه اللي خلص + إيه اللي بيعنيه لبيزنسه + الخطوة الجاية + الأرقام.
- استخدم `insert_content` بـ `position: start` لإضافة بلوك جديد فوق (آمن، مبيكسرش الموجود).

### 2. ✅ Tasks DB — لوحة شغلنا (تقني، داخلي)
- مهمة جديدة: `create-pages` بـ `data_source_id: 7b3337f3-…`، `Priority: High` للعاجل، `Notes` بالملخص، والتفاصيل في `content`.
- مهمة خلصت: `update_properties` → `Done: __YES__` + حدّث `Notes` بالنتيجة المتحقّقة.

### 3. 📑 Tasks Required From Ahmed — طلبات من العميل
- أضف callout بالطلب + **mention حقيقي لأحمد** + **كومنت** (أداة create-comment بنفس الـ mention) عشان يوصله **إشعار** (الـ mention في المحتوى لوحده مش مضمون يبعت إشعار لـ guest).
- ⚠️ **آلية الإشعار-بالكومنت لسه متأكّدش منها نهائياً** (مرجع: `HANDOFF.md` 2026-06-04 — اتوثّق إن الإشعار لأحمد مش اتبعت وفضل قرار Omar: Notion comment ولا WhatsApp). فالكومنت **مفروض** يضمن الإشعار — لو مش متأكد إن التوكن بيسمح بإنشاء comments، **وثّق المحاولة وأبلغ Omar بالنتيجة** بدل ما تفترض إنه وصل.
- وضّح: الإيميل الصح (Google=`omarabdo385`, Meta/FB=`omarabdo258`) + ليه محتاجينه.

### 4. 📁 Files & Reports — تقارير وملفات العميل
- `create-pages` بـ `data_source_id: da1fe05c-…`, `Type: Report`/`Doc`, `date:Date:start`. الملف نفسه Omar يرفعه (مش ممكن رفع screenshot من الشات).

### 5. 📦 Assets Requested — أصول/أكسيس مطلوبة (VPS/Image/Credential…)
- أصل/أكسيس جديد مطلوب: `create-pages` بـ `data_source_id: a8df3d65-…` + الحقول: `Name` (title) · `Type` (`VPS`/`Image`/`Data`/`Invoice`/`Credential`/`Other`) · `Status: Requested` · `Amount` (لو فيه مبلغ، number) · `Details` (text) — والتفاصيل الطويلة في `content`.
- لما الأصل يوصل/يتسلّم: `update_properties` → `Status: In Progress` أو `Done`.
- مثال: عمر محتاج مفتاح Gemini API من أحمد → سطر `Name: Gemini API key` · `Type: Credential` · `Status: Requested` · `Details: من aistudio.google.com — للـ WhatsApp agent`.

---

## 🚨 قواعد حرجة (من دروس اتكلّفت)

1. **VERIFY before claiming** — في auto-mode classifier بيمنع أي "ادعاء نجاح" مش متحقّق على صفحة client-facing. **اتأكد بنفسك الأول** (curl / API / SSH / Mautic API)، وبعدين اكتب. لو منقول من commit/مصدر تاني → اكتبه كـ "منقول، محتاج تأكيد" مش كحقيقة.
2. **الصفحة الرئيسية = نبرة عميل بسيطة.** المصطلحات التقنية تبقى في Tasks DB أو الـ session log.
3. **Ahmed guest مش بيظهر في `get-users` العادي** — استخدم `notion-search` بـ `query_type: user`. الـ ID فوق ثابت.
4. **mention أحمد = mention في المحتوى + كومنت** (الكومنت **مفروض** يضمن الإشعار — لكن الآلية لسه متأكّدش منها نهائياً، مرجع `HANDOFF.md` 2026-06-04؛ لو مش متأكد إن التوكن بيسمح بـ comments وثّق المحاولة وأبلغ Omar).
5. **اتعلم ببساطة بـ R مكررة:** `learrnsimply.com` — مش typo.

---

## ▶️ الـ Workflow لما الـ skill يتنده (`/ls-notion` أو "حدّث نوشن")

> **أسماء الأدوات هنا shorthand** (`create-pages` · `update_properties` · `insert_content` · `create-comment` · `notion-search`) — الأدوات الفعلية جوّه namespace `mcp__notion__*`. لو الـ exact function signatures مش واضحة (خصوصاً agent جديد أو بعد restart) → اعمل list للأدوات المتاحة من سيرفر `notion` أول السيشن واربط كل shorthand بأقرب tool. لو السيرفر مش محمّل خالص → استخدم REST API بـ curl (الطرق الـ3 فوق): create في DB = `POST /v1/pages` بـ `parent.database_id` + `properties` · قراءة page = `GET /v1/blocks/{id}/children` · query DB = `POST /v1/databases/{id}/query` · comment = `POST /v1/comments`.

1. **اعرف اللي اتغيّر:** استنتج من شغل الـ session الحالية + آخر `git log` (`git log --oneline -10`) + الـ `.env` status fields. اسأل Omar بس لو فيه غموض حقيقي (مثلاً حصل أكتر من حاجة وفيه تعارض في الأولوية) — الهدف إن Omar ما يعيدش الشرح كل مرة.
2. **افحص/اتأكد** من أي ادعاء قبل ما تكتبه (القاعدة #1).
3. **حدّث الأسطح المناسبة** حسب البروتوكول فوق (الرئيسية للعميل، Tasks DB للتقني، Tasks Required From Ahmed للطلبات + mention).
4. **أكّد لـ Omar** بإيه اتحدّث + الروابط.

> لو فيه ID جديد (page/DB اتعمل جديد) → ضيفه للخريطة فوق في الملف ده.

---

## 🔮 تحسين مستقبلي (اختياري)
لو حابين أتمتة كاملة بدون session: اعمل **Notion internal integration token** (notion.so/my-integrations) + شاركه مع الصفحات دي، وبعدين سكربت Python/n8n يحدّث عبر Notion REST API مباشرة. دلوقتي الـ MCP-driven (الـ skill ده) كفاية وأبسط.
