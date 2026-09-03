---
name: ls-whatsapp-agent
description: >-
  Operational runbook للمساعد الذكي "عمر" على WhatsApp بتاع Learn Simply (Evolution API + n8n W3
  + Gemini agent، الـ system مباشر مع العملاء). استخدمها كلما الزبائن الحقيقيين قالوا "البوت مش
  بيرد" أو "المساعد بيقول أرقام/روابط غلط" أو "التصعيد ماوصلش لتليجرام" أو "الإيصال مش بيتفهم" —
  حتى لو ما اسموش n8n بالاسم. تُغطي: debug الـ W3 workflow (27 نود)، إصلاح الـ prompt من الـ DB،
  مشاكل الأدوات (toolWorkflow vs toolHttpRequest)، التصعيد لتليجرام، فهم الصوت/الصور، وقاعدة
  deactivate→activate. اقرأها قبل أي تعديل على الـ agent أو Evolution instance أو rules — فيها
  روافع الـ rollback الطارئة + مرجع الحالة الحية. (مش لاستراتيجية البراند/الحملات — دي /ls-wrap.)
---

# Learn Simply — مساعد واتساب "عمر" (Operational Runbook)

> ⚠️ **ده نظام إنتاج حي بيرد على عملاء حقيقيين 24/7.** اقرأ القواعد الحرجة تحت قبل أي تعديل.
> **المصدر الحي للحقيقة = n8n + الـ DB + `.env`** — الأرقام/IDs هنا للتوجيه السريع، تأكّد منها live قبل ما تعتمد عليها.
> **عمق أكتر:** `02_AUTOMATION/agents/whatsapp-agent-design.md` (رحلة العميل) · `workflow-quality-study-2026-06-10.md` (الـ refactor node-by-node) · `HANDOFF.md` (أحدث حالة) · `03_KNOWLEDGE/knowledge-base.md` (الـ KB) · memory `project_omar_agent_live_state_2026-06-10`.

---

## المعمارية (نظرة سريعة)

```
واتساب (عميل) → Evolution API v2.3.7 (instance: omar-support, رقم 201030127228)
   → webhook (MESSAGES_UPSERT) → n8n W3 omar-inbound
        → Normalize (LID→رقم حقيقي) → dedup → upsert → AI Agent (Gemini Flash)
             + Postgres Chat Memory (bucket 48h) + 4 tools (toolWorkflow)
        → Clean Reply (يفك markdown) → Evolution send (يرد على remoteJidFull)
```

**المكوّنات:**
- **النقل:** Evolution API **v2.3.7** (من repo `evoapicloud/evolution-api` — القديم `atendai` ميت عند v2.2.3). instance `omar-support`. **inbound فقط** (العميل بيبدأ — بيخفّف خطر حظر Baileys).
- **المخ:** Gemini Flash (temp 0.3) + fallback model + Postgres Chat Memory (hybrid، bucket 48h).
- **الـ DB:** PostgreSQL على الـ VPS (مش Supabase — قرار وفّر ~14.4K ج/سنة).
- **الهوية:** "عمر من خدمة عملاء اتعلم ببساطة" — **ما بيكشفش إنه bot**.

## الـ n8n Workflows (IDs حية)

| Workflow | ID | الدور | الحالة |
|---|---|---|---|
| **W3** omar-inbound | `ESYkoJgz0e4ngMrM` | الوكيل الرئيسي (27+ نود) | ACTIVE |
| **W3b** omar-alert | `ULoRfU57m5fSLD2B` | التصعيد (DB-first → Telegram) | sub-workflow |
| **W3c** omar-kb-search | `sv6ART3GjO4JUN81` | بحث الـ KB (RAG) | ACTIVE |
| **W3d** error handler | `YktkjLMI12YUGWfc` | أخطاء → Telegram | ACTIVE |
| **W3t** kb_search wrapper | `IfIlQ2RsfsubUkHW` | toolWorkflow | — |
| **W3t-order** | `gC9cPSiTBP3M6DZj` | order_lookup tool | — |
| **W3t-mautic** | `R5xDtKKGSeBchue6` | mautic_upsert tool | — |

الـ credentials + الـ IDs الكاملة في `.env` §20.

---

## 🚨 القواعد الحرجة (لو اتكسرت = البوت بيقع/بيألّف)

1. **التعديل في n8n بيروح draft — لازم `deactivate → activate` بعد كل تعديل** عشان يبقى live فعلاً. (أكتر فخ بيخلّي "عدّلت وملوش أثر".)
2. **الأدوات لازم `toolWorkflow` مش `toolHttpRequest`** — في queue mode الـ toolHttpRequest بيرمي "supplyData but no execute" → الوكيل يألّف لينكات غلط ومايصعّدش. (كل الأدوات متلفوفة في W3t wrappers لهذا السبب.)
3. **ممنوع Markdown في الرد** — واتساب بيكسر `[نص](لينك)` (القوس اللاصق = 404). الـ Clean Reply node بيفك أي markdown؛ والـ prompt rule 18 بيمنعه.
4. **ممنوع تأليف لينكات** (rule 19) — لينكات الباقتين **inline في الـ prompt** (الـ RAG بيفهرس الجداول وحش فمابيرجّعهاش صح). أي لينك تاني = من `knowledge_base` بس.
5. **"وصّلت للفريق" لازم يسبقها نداء أداة فعلي** (rule 20) — اتمسك الوكيل بيقولها والتصعيد ماشتغلش. لو قالها من غير ما W3b يشتغل = كذبة على العميل.
6. **الـ system prompt من الـ DB (`omar.agent_prompts`) بإصدارات — مش hardcoded في النود.** تعدّله بـ `02_AUTOMATION/agents/deploy/push_prompt.mjs` (أو `inject_prompt.mjs`). الإصدار الحي بيتقري من الـ DB فالتعديل بياخد أثر **فوراً بدون redeploy**.
7. **LID normalization:** واتساب بقى يبعت هوية مجهّلة (`xxx@lid`) — الـ Normalize node بيستخرج الرقم الحقيقي من `senderPn`/`remoteJidAlt`، والرد بيروح على `remoteJidFull`. متلمسش ده.
8. **أي tool call ناقص argument** كان بيكسر التنفيذ كله → كل الأدوات `$fromAI` default `''`.

---

## التصعيد (الـ Routing) — 6 صناديق

عمر بيصنّف بـ "بيعمل بيها إيه" مش بالموضوع:
- **A 🛒 مبيعات** → يرد من الـ KB → يقنع → لينك دفع.
- **B 🛠️ دعم** (موقع مش فاتح / دفع وملقاش الكورس / شهادة) → خطوات حل → تصعيد لو فشلت.
- **C 🌍 دفع برّه مصر** → بدائل (بطاقة دولية / تحويل فودافون كاش `01030127228` / إنستاباي `01102681074` / تغيير العملة في Kashier checkout) → تفعيل يدوي.
- **D 🎓 لأحمد شخصياً** (private/تعاون/إعلان/طلب كورس) → يجمع التفاصيل بأدب → تصعيد لأحمد.
- **E 📚 سؤال برمجي** → **مايجاوبش كود** (خطر تضليل + بياكل قيمة الكورس) → يوجّه للدرس/أحمد/المجتمع.
- **F 🚨 حساس/غاضب/استرجاع** → تعاطف + تصعيد فوري (🔴). عمر **عمره ما يوافق/يرفض استرجاع** (قرار فلوس = بشري).

**التصعيد بيروح:** جروب تليجرام «اتعلم ببساطة — تنبيهات الدعم» (`chat_id -5163152342`، بوت `@n8n_aimora_bot`). الـ W3b بيكتب DB-first (الضمان) ثم تليجرام (HTML-escaped + parse_mode HTML).

## Config (أرقام — تمييز محسوم)

| البند | القيمة |
|---|---|
| رقم الدعم/المساعد (العملاء) | **`201030127228`** (= فودافون كاش) |
| واتساب تنبيه عمر | `01011516829` |
| واتساب تنبيه أحمد (شخصي) | `01102681074` (= إنستاباي) |
| جروب تليجرام التنبيهات | `-5163152342` |
| ساعات الدعم البشري | السبت→الخميس 10ص–6م · البوت 24/7 |

> ⚠️ **متلخبطش:** رقم أحمد الشخصي `…1074` (تنبيهات) ≠ رقم الدعم `…7228` (العملاء).

---

## الصوت + الصور
Evolution `get-media-base64` → Gemini analyze (`gemini-3-flash-preview`) → Resolve Message → الوكيل. الـ prompt بيستنتج النية من الصورة (دخول→مساعدة · إيصال→تحقق إنه رقم أحمد · خطأ→site_access).

## روافع الـ Rollback
- **قفل البوت عن العملاء:** فعّل نود `Test Users Only` في W3 (مش محذوف — re-enable بيرجّع بوابة الاختبار: عمر + أحمد بس). مدّ ده + activate.
- **رجوع prompt:** فعّل إصدار أقدم في `omar.agent_prompts` (rollback-ready).
- **مراقبة:** W3d بيبعت أي خطأ على جروب التليجرام.

## قبل أي تعديل (checklist)
1. اقرأ الحالة الحية: `HANDOFF.md` (أحدث قسم) + memory `project_omar_agent_live_state_2026-06-10`.
2. عدّل في n8n → **deactivate→activate**. عدّل prompt → `push_prompt.mjs`.
3. اختبر برقم تجريبي قبل ما تسيبه للعملاء.
4. لو لمست أداة → تأكد إنها `toolWorkflow` + `$fromAI` default.
5. backup قبل أي تغيير DB/Evolution (نمط HANDOFF).
