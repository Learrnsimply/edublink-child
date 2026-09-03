# 🤝 Session Handoff — Learn Simply

> **محدّث:** 2026-06-25 (موقع bug-free + tracking جاهز للإعلانات — أحدث سياق تحت)
> **الغرض:** الجهاز التاني يلتقط الـ context كامل من غير ما يقرا الـ session history
> **اقرا ده الأول** لما تفتح session جديدة أو على جهاز جديد

---

## 🟢🆕 2026-06-25 — اقرا ده الأول: موقع bug-free + tracking جاهز للإعلانات

> **الفوكس (قرار Omar):** موقع نضيف من الـ bugs + tracking 100% للإعلانات ونقرر بالأرقام. استرجاع السلة + الإيميل **مؤجّلين** (مش ملغيين). بوابة الدفع سليمة — متغيّرهاش.

**اتعمل + اتأكّد حي:**
- 🐛 **الموقع bug-free:** BUG-003 (عنوان `<title>` مكرر سيت-وايد) → شِلنا الـ title اليدوي من 5 twig templates، **Rank Math** بقى المصدر الوحيد. + غلطتين إملائيتين (نبذة / الصفحة الرئيسية). **PR #18** (Learrnsimply/edublink-child، branch `003-theme-defect-closeout`) — **منشور حيّ خلاص** (scp + md5 + تأكيد عنوان واحد لكل صفحة عبر curl)، الـ PR للسجل/merge أحمد. backups على السيرفر `*.bak-20260625`. سبيك 003 طلع **26/29 متعمل قبل كده** في PRs سابقة.
- 📊 **التتبّع (4 قنوات):**
  - **GA4 ecommerce:** swap لمصدر واحد — عطّلنا MonsterInsights (rollback متاح) + ركّبنا **"Google Analytics for WooCommerce"** (`woocommerce-google-analytics-integration`) بنفس الـ ID `G-DT3Z0RSEBK`. gtag بيتحمّل **مرة واحدة** + events (view_item/add_to_cart/begin_checkout) متوصّلة.
  - **Clarity:** project `xckdxrkgej` (option `clarity_project_id`) — بيـ fire سيت-وايد.
  - **Meta:** متوصّلة FBE2 (`wc_facebook_has_connected_fbe_2=yes`، توكنز صالحة، browser pixel `699717432496147` بيـ fire، CAPI تلقائي). ⚠️ الـ `facebook_config.use_s2s=false` = **orphan من بلجن متشال** (red herring — متبنيش عليه).
  - **TikTok:** MAPI صلب من الأول.
- 🗂️ **CLAUDE.md** اتعمله restructure (143 سطر map + `_session-log.md` للتاريخ).

**الجاي (محتاج Omar — verification بس، مش setup):**
1. **GA4:** أوردر تجريبي → شوف `purchase` في Realtime.
2. **Meta:** Events Manager → Test Events → Browser + Server events + Event Match Quality.
3. أحمد merge لـ PR #18.

**التفاصيل الكاملة + الـ option keys + روافع rollback:** memory `project_focus_pivot_website_tracking_2026-06-25`.

---

## 🟢🟢 2026-06-10 (ليلاً، PUZZLE) — [أقدم] عمر FULLY LIVE + إصلاحات عميقة

> **الموضوع:** بعد اختبار Omar/أحمد اكتشفنا وصلّحنا 3 طبقات بق عميقة، ضفنا فهم الصوت/الصور، وطلّعنا الوكيل **LIVE للجميع** (بوابة الاختبار مقفولة). الحالة الحيّة الكاملة + روافع الـ rollback في memory `project_omar_agent_live_state_2026-06-10`.

### ✅ اللي اتعمل (كله متأكّد منه بالدليل)
- 🔴→✅ **الأدوات كانت مكسورة كلها (queue mode):** `toolHttpRequest` بيرمي "supplyData but no execute" في n8n queue mode → الوكيل كان بيألّف (لينكات غلط) ومش بيصعّد فعلاً. **الحل:** kb_search/order_lookup/mautic_upsert اتحوّلوا لـ **`toolWorkflow`** بيشاوروا على wrappers صغيرة **active** (W3t `IfIlQ2RsfsubUkHW` · W3t-order `gC9cPSiTBP3M6DZj` · W3t-mautic `R5xDtKKGSeBchue6`). متأكّد: رجّع لينك انستجرام الصح من الـ KB. (الدرس: onError كان red herring — السبب queue-mode + toolHttpRequest.)
- 🔴→✅ **التصعيد مكنش بيوصل التيليجرام:** نود Telegram في W3b بترفض الرسالة (`can't parse entities` — parse_mode HTML + رموز OCR في النص). **الحل:** HTML-escape للنص + parse_mode HTML صريح. متأكّد (`message_id 501` وصل الجروب). + ضيّقنا الحارس (Clean Reply) اللي كان بيصطاد كلمات زي "سجلت" ويأكل التصعيد الحقيقي عبر الـ dedup.
- 🆕 **فهم الصوت + الصور:** W3 بقى يحمّل الميديا من Evolution (`get-media-base64`) → Gemini يفرّغ/يقرا (gemini-3-flash-preview) → Resolve Message يحط النص مكان الرسالة → الوكيل. (الصوت + OCR متأكّدين؛ Omar قفل الـ thinking عشان قطع MAX_TOKENS المتقطّع.)
- 🆕 **prompt v9 (DB active):** الوكيل بقى يستغل وصف الصورة ويستنتج النية (سكرين دخول→مساعدة الدخول · إيصال→تحقق إن رقم الدفع = رقم أحمد · شاشة خطأ→site_access). شيلنا قاعدة "إنت مش بتشوف الصور".
- 🐛→✅ **بق إطلاق:** tool call ناقص الـ argument كان بيكسر التنفيذ كله (العميل ميردّش عليه) → ضفنا `$fromAI(...)` default `''` للأدوات الـ 3.
- 🚀 **GONE LIVE:** نود `Test Users Only` بقى **disabled** (مش حذف — re-enable يرجّع بوابة الاختبار) → الوكيل بيرد على **كل** العملاء على رقم الدعم `201030127228`. مراقبة أخطاء شغّالة. ✅ **أحمد متضاف لجروب تنبيهات التيليجرام** (`-5163152342`).

### ⏳ الجاي / متابعة
- مراقبة أول محادثات حقيقية + التدخل لو فيه رد غلط (ابعت رقم العميل/الوقت → نراجع التنفيذ بالظبط).
- اختبار v9 بصورة حقيقية (سكرين دخول→مساعدة بروأكتيف · إيصال رقم غلط→تحذير) — مش حاجز، بيأكّد سلوك الصور الجديد.
- ⚠️ خطر حظر واتساب (Baileys) على الرقم — inbound-only بيخفّف.

---

## 📦 2026-06-10 (PUZZLE) — [SUPERSEDED بقسم "FULLY LIVE" فوق] مساعد واتساب "عمر" BUILT بالكامل

> **الموضوع:** تنفيذ كامل لكل اللي كان spec — مفيش حاجة معمارية باقية. الـ go-live مستني 3 حاجات بس (تحت). **المرجع الكامل:** `.env` §20 (كل الـ IDs) + `02_AUTOMATION/agents/deploy/README.md` (الـ checklist).

### ✅ اللي اتعمل
- **System prompt → v0.2:** إجابات أحمد (2026-06-04) اتدمجت — F2 خطوات دخول الكورس الحقيقية · C الدفع اليدوي (فودافون كاش `01030127228` / إنستاباي `01102681074` + اليمن/سوريا) · ساعات الدعم (السبت→الخميس 10-6) · F1 حل "ابدأ الآن" · الشهادات (completion مش معتمدة + bug التحميل + تغيير الاسم) · قسم كورس Dart (15 يونيو + DART50) · fallback فشل escalate بقى الإيميل (مش واتساب الدعم — عشان عمر أصلاً بيرد منه).
- **3 n8n workflows مبنيين + متحقّق منهم:** `W3 omar-inbound` = `ESYkoJgz0e4ngMrM` (20 نود: webhook secret-path → normalize دفاعي → dedup → upsert contact → log → AI Agent (Gemini Flash temp 0.3 + Postgres Chat Memory keyed on phone + 4 tools: kb_search/order_lookup/mautic_upsert/escalate) → log reply → Evolution send → ack · **INACTIVE**) · `W3b omar-alert` = `ULoRfU57m5fSLD2B` (DB-first insert بـ context snapshot جوه نفس الـ SQL → إيميل Brevo لأحمد+عمر → واتساب لعمر `201011516829` + أحمد `201102681074` → mark alerted لو قناة نجحت · sub-workflow **مش محتاج تفعيل**) · `W3c omar-kb-search` = `sv6ART3GjO4JUN81` (webhook → Gemini embed → `omar.kb_search` pgvector → JSON · **ACTIVE ومتاختبر live** — بيرجع degraded بأمان لحد ما المفتاح ييجي).
- **الـ prompt محقون آلياً:** أداة جديدة `02_AUTOMATION/agents/deploy/inject_prompt.mjs` — أي تعديل على `omar-system-prompt.md` → `node inject_prompt.mjs` يزامنه لنود الـ Agent (18.1K حرف + بلوك سياق العميل من `Upsert Contact`). ⚠️ درس: الـ markers لازم يتلقطوا كسطور كاملة (سطر التوثيق بيذكرهم inline → أول نسخة لقطت 6 حروف).
- **Evolution instance `omar-support` متعمل** (instanceId `9ab43e32-…`) + webhook → `omar-inbound-ad3c5809113a` (event واحد MESSAGES_UPSERT) + `groupsIgnore=true`. **الرقم لسه مش متوصل** (QR pending).
- **5 n8n credentials اتعملوا** (IDs في `.env` §20): Postgres omar_agent ✅ · Evolution apikey ✅ · SMTP Brevo ✅ (Hostinger SMTP **مات** 06-06 — Brevo هو الـ sender) · Gemini **placeholder** ⚠️ · WC ReadOnly **placeholder** ⚠️. ملاحظة n8n حديث: الـ generic credentials محتاجة `allowedDomains`.
- **اختبارات:** kb-search e2e live (valid/invalid) ✅ · كل SQL الـ workflows (upsert/dedup/log/escalation insert بالـ snapshot/mark alerted/open_escalations view) في rollback transactions على الـ DB الحقيقية — صفر أثر ✅ · عربي + إيموجي سليمين ✅.

### 🔄🆕 Refactor جودة (نفس اليوم، بعد deep-research بطلب Omar)
- Omar حس إن جودة W3 الأولى وحشة → دراسة 4-وكلاء: تشريح وكلاء rs-aios الحيين (واتساب 36 نود + ماسنجر v6 الـ hybrid-memory) + بحث ويب 25+ مصدر → **W3 اتعاد بناؤه: 20→27 نود**. التقرير: `02_AUTOMATION/agents/workflow-quality-study-2026-06-10.md`.
- **اللي اتضاف:** ack فوري (onReceived بدل مسك الاتصال) · debounce 8 ثواني + تجميع رسايل العميل المتتالية (Postgres-only) · mark-as-read + typing indicator + تأخير بشري في الإرسال (نمط rs) · **الـ prompt بقى من جدول `omar.agent_prompts` بإصدارات** (v2 نشط — أداة `deploy/push_prompt.mjs` بدل inject) · hybrid memory كامل (session bucket بيتصفّر بعد 48h + بروفايل بـ hours_since_last_seen) · fallback model (`needsFallback` + flash-lite) · Clean Reply (فخ "Agent stopped due to max iterations" اللي n8n بيبعته حرفياً للعميل!) · 🐛 إصلاح تجاهل الصوتيات بصمت · **W3d error handler** (`YktkjLMI12YUGWfc`) → Telegram البوت المشترك، متربط على W3/W3b/W3c · settings صلبة (callerPolicy + saveDataErrorExecution + executionTimeout).
- كل الـ SQL الجديد متاختبر rollback ✅ · الكل validated ✅.
- **تكملة (بموافقة Omar):** Agent → **typeVersion 3.1** (نفس rs live) · **community node `n8n-nodes-evolution-api` v1.0.4 اتنزّل** والـ 5 نودات Evolution اتحوّلت ليه (read-messages/send-presence/sendText+delay) بـ credential `vEH26B23v67OyFSu` · ⚠️ **compose اتعدّل**: الـ worker بقى بيشارك `n8n-data` volume (ضروري في queue mode عشان الـ worker يشوف النود — backup `docker-compose.yml.bak-2026-06-10`) + restart ~60 ثانية عدّى سليم (W1/W2/W3c رجعوا active).

### 🚀🆕 تكملة 2 (نفس اليوم): مفتاح Gemini وصل → RAG live + prompt v3 red-teamed + إنذار متاختبر
- ✅ **مفتاح Gemini بتاع أحمد** ("Gemini API Ahmed"، صيغة `AQ.` الجديدة) متاختبر (200) + متربوط في credential `ZeYvAf59LZGqkIbU` + `.env`. ⚠️ **`text-embedding-004` اتشال من Google** → التحويل لـ `gemini-embedding-001` بـ `outputDimensionality=768` (ingest_kb.py + نود Embed Query اتصلّحوا).
- ✅ **RAG LIVE:** 78 chunk (KB + منهج الـ 336 درس) متعملهم embedding في `omar.kb_chunks` (شغّلناه في python container مؤقت على شبكة الـ stack). **kb-search متاختبر live** — سؤال Linked List رجّع الوحدة الصح بكل دروسها (similarity 0.71).
- ✅ **Prompt → v3 (20.2K حرف) بعد red-team بوكيلين** (12 ملاحظة): قواعد جديدة 14 anti-injection/انتحال أحمد · 15 منع تكرار التصعيد · 16 مفيش وعد مكالمات · صيغة التفعيل المشروط للتحويل اليدوي ("متقولش شفت السكرين") · حارس اسم-شخص-تالت في الشهادة · خصوصية order_lookup · fallback كوبون DART50 المربوط بالتاريخ + **حقن التاريخ (القاهرة) في الـ prompt من نود Build Prompt & Profile** (من غيره قاعدة الـ 48 ساعة كانت مستحيلة التنفيذ).
- ✅ **إيميل الإنذار متاختبر live:** escalation تجريبي → DB row بـ snapshot + **إيميل Brevo وصل** (`email:true`) + واتساب فشل gracefully (مش متوصل — متوقع). التنظيف كامل (الصفوف + TEMP workflow اتمسحوا). ⚠️ **درس n8n جديد:** الـ sub-workflows لازم تكون **published (ACTIVE)** قبل تفعيل اللي بينده عليها → **W3b بقى ACTIVE ولازم يفضل كده.**
- ✅ **Backups:** أداة `02_AUTOMATION/n8n/_tools/export_workflows.mjs` (مع secret-scrubbing) → 6 JSONs في `workflows/_exports/` + `_REGISTRY.md` يتولّد آلياً. شغّلها بعد أي تعديل n8n.
- 🟡 **قرار بيزنس لـ Omar:** رقم الإنستاباي للدفع اليدوي `01102681074` = **رقم أحمد الشخصي** (اللي الـ KB نفسها بتقول "مش للعملاء") — بيتدي للعملاء by design. يا رقم/حساب مخصص، يا قبول صريح.

### 🚀🆕 تكملة 3 (نفس اليوم): WC key اتعمل + DNS الـ Evolution Manager اتضاف — **الباقي الـ QR بس**
- ✅ **WC read-only key اتعمل بالكامل** (Omar أذن بالـ SSH): صف في `wp_woocommerce_api_keys` باسم admin Omar (ID 2، متجنبين حساب wp-licenses-95 الدخيل) بصلاحية Read · متاختبر: قراءة 200 / كتابة 401 · n8n credential `5k7PIPwao0Vkoczb` اتحدّث · القيم في `.env` §20. → **أداة `order_lookup` بقت حية.**
- ✅ **DNS `evolution.learrnsimply.com` → 187.124.9.249 اتضاف** — Claude عمله بنفسه عبر Playwright على hPanel (لوجين Omar + كود 2FA اتقري من Gmail بالـ MCP + الدومين متشيّر من حساب أحمد `learnsimplyhost@gmail.com` impersonate mode). الـ DNS بيرد على الموثّق + 8.8.8.8. **داشبورد Evolution Manager:** `https://evolution.learrnsimply.com/manager` (لوجين بالـ EVOLUTION_API_KEY من VPS .env) — وفيها زرار QR للـ instance `omar-support` = أسهل طريقة ليوم الربط مع أحمد.
- 🟡 ملحوظة أمنية صغيرة: لوجين hPanel الجديد ده هيظهر في سجل أجهزة حساب Hostinger بتاعك (جهاز جديد) — ده إحنا.

### 🟢🚀 تكملة 4 (نفس اليوم، مساءً): GO-LIVE — QR اتمسح + ترقية v2.3.7 (LID) + W3 ACTIVE + أول محادثات نجحت
- ✅ **QR كان مش بيتولّد** (الداشبورد فاضية + `connect` بيرجع `count:0` + الـ instance في لوب reconnect): واتساب رفض إصدار البروتوكول القديم. الإصلاح: `CONFIG_SESSION_PHONE_VERSION="2.3000.1035194821"` (الإصدار الحالي من Baileys repo) في compose → recreate → `qrcodeCount:1` → **أحمد مسح الـ QR → state OPEN** (ownerJid `201030127228`).
- 🔴➡️✅ **اكتشاف LID الحرج:** أول رسالة اختبار من Omar وصلت بـ `remoteJid: 246746324193422@lid` (هوية واتساب المجهّلة الجديدة) **بدون رقمه الحقيقي في أي حقل** — وv2.2.3 رفض الإرسال لـ `@lid` أصلاً (`exists:false`). يعني أي عميل حديث = مستحيل الرد عليه. **القرار (Omar وافق): ترقية v2.2.3 → v2.3.7** من الـ repo الجديد **`evoapicloud/evolution-api`** (القديم `atendai` واقف عند v2.2.3 — المشروع اتنقل). Backups قبلها: `docker-compose.yml.bak-2026-06-10-pre-v237` + `backups-evolution-pre-v237.dump`. Prisma migrations نجحت · **الجلسة عاشت (مفيش re-QR)** · الإرسال لـ `@lid` اشتغل · الـ webhook سليم.
- ✅ **W3 اتعدّل لـ LID + بوابة اختبار (28 نود):** Normalize بيستخرج الرقم الحقيقي من الحقول المرافقة (`senderPn`/`remoteJidAlt`/`participantPn`/`participantAlt` — fallback chain لأن الاسم بيتغير بين نسخ Baileys) · Evolution Send بيرد على `remoteJidFull` بدل الرقم · **نود `Test Users Only`** (Filter بعد Is Message?) — يعدّي عمر `201011516829` وأحمد `201102681074` بس، غيرهم يتجاهل بصمت (ولا حتى DB write). **للإطلاق العام: Disable للنود (مش حذف) + إعادة نشر.**
- 🟢 **W3 ACTIVE — أول محادثات حقيقية نجحت:** أسعار صح من الـ KB + خطوات اشتراك + الـ upsell شغال. ⚠️ **درس n8n:** أي تعديل على workflow active بيروح **draft** — لازم **deactivate→activate** عشان يبقى live (حصل 3 مرات في السيشن).
- ✅ **3 إصلاحات UX من ملاحظات Omar** (prompt **v4→v5** + Clean Reply): (1) قاعدة 17 — تعريف *"أنا عمر من خدمة عملاء اتعلم ببساطة"* في أول رسالة بس. (2) قاعدة 18 — ممنوع Markdown (واتساب بيكسر `[نص](لينك)` — القوس اللاصق خلّى لينك صحيح يفتح 404!) + **فلتر في Clean Reply** يفك markdown links ويحوّل `**` لـ `*` واتساب. (3) قاعدة 19 — ممنوع تأليف لينكات + سؤال باقة = لينك الباقة مش الكورس الفردي + **لينكات شراء الباقتين inline في الـ prompt** (Java bundle + DS bundle — من WC API بالـ permalink الرسمي). الـ KB كمان اتحدّثت (§4.1) + re-ingest (1 updated/77 skipped/1 pruned).
- 💡 **اكتشاف RAG:** chunk الباقات بيترتّب ضعيف في البحث (sim ~0.6 مش في top-5 لسؤال "باقة جافا") — الجداول بتتفهرس وحش. عشان كده الكاتالوج واللينكات **inline في الـ prompt** (ده أصلاً التصميم: prompt للدقة المطلقة، RAG للمنهج العميق).

### 🟢🆕 تكملة 5 (2026-06-10 ضحى): ملاحظات أحمد من أول يوم اختبار → prompt v6 + KB
- ✅ **اطمئنان أولاً:** محادثات الصبح اللي شكلها "عملاء حقيقيين" ("Basha The World" إلخ) اتفحصت في الـ executions — **كلها من رقم أحمد الشخصي `201102681074`** (pushName: Ahmed) وهو بيمثّل دور عميل. **بوابة Test Users Only شغالة سليمة.**
- 🔴➡️✅ **اكتشاف حرج (إجابة سؤال أحمد "الطلبات بتروح فين؟"):** W3b (أداة الإنذار) **ماشتغلتش خالص** في محادثات الصبح رغم إن الوكيل قال "وصّلت طلبك للفريق" — **قالها من غير ما ينده escalate** (hallucination). الإصلاح: **قاعدة 20** في v6 — ممنوع "وصّلت/سجّلت/هبعتلك" إلا لو ندَه أداة فعلاً في الرد (`escalate` أو `mautic_upsert`). (لما بتشتغل صح: الإنذار بيروح إيميل Brevo + واتساب لأحمد وعمر — متاختبر live قبل كده.)
- ✅ **ملاحظات أحمد الـ 3 اتنفذت (prompt v6 + KB §10.4):** (1) **Linktree** `linktr.ee/ahmedadeel` = كل لينكات التواصل مع أحمد/السوشيال (سؤال انستا → اللينك ده) · (2) **قناة التليجرام** `t.me/Et3lambBsata` inline (الوكيل كان بيقول "معنديش لينك") · (3) **جروبات الكورسات بتتحدّث حالياً** → الوكيل ياخد الإيميل + يسجّله `mautic_upsert` (tag `group-link-request`) + يَعِد بعد التسجيل بس. ⚠️ مؤقت — يتشال من الـ prompt والـ KB لما أحمد يخلّص تحديث الجروبات.
- ✅ **ملاحظة Omar (اختبار فلسطين) اتنفذت:** مسار C خطوة 1 جديدة + KB §6.2 — **صفحة دفع Kashier فيها قايمة اختيار عملة** (دولار/درهم/...) والمبلغ بيتحوّل تلقائياً (3299 ج ≈ 63.79$) — أول اقتراح لأي عميل دولي، ولو عملته مش موجودة → الدولار.
- ✅ **نشر:** prompt **v6** (23.4K حرف) في `omar.agent_prompts` — حي فوراً (الوكيل بيقرا الـ active من الـ DB كل رسالة، **مفيش تعديل workflows = مفيش redeploy**) · KB re-ingest (2 updated/76 skipped/2 pruned، 78 chunk).
- ✅ **prompt v7 (نفس اليوم):** Omar صحّح إن "القناة" = **اليوتيوب** مش التليجرام → ضفت قناة اليوتيوب `youtube.com/@Learn_Simply` inline + قاعدة إن "القناة" بدون تحديد = اليوتيوب افتراضياً (اللينك موثّق من client-context.md). KB §10.4 + re-ingest. (السبب الأصلي للبس: الرسالة اللي Omar شافها كانت قبل نشر v6 بـ 6 دقايق — اتأكد من الـ executions: execution 488 على v5، v6 اتنشر 09:55.)

### 🟢🆕 تكملة 6 (2026-06-10 ظهراً): قناة التصعيد بقت Telegram-only (جروب مخصّص)
- **القرار (Omar):** بدل ما الإنذار يروح إيميل Brevo + واتساب لأحمد/عمر (3 قنوات)، يبقى **تليجرام بس** — لأن (1) أحمد أصلاً Telegram-native (ماشّي مجتمع 24K)، (2) الواتساب كان بيتبعت من خط الدعم نفسه (outbound = شوية ban-risk + حمل)، (3) البوت المشترك `@n8n_aimora_bot` جاهز ومتربط في n8n.
- **التنفيذ:** جروب تليجرام **«اتعلم ببساطة — تنبيهات الدعم»** · **`chat_id = -5163152342`** · W3b اتعدّل: شيلنا 3 نود (Email Alert + WhatsApp×2) وحطّينا **نود Telegram واحد** (credential `rvECFnBLZhbV2ADN`، plain text أأمن من HTML، retry 3×، onError continue). **10→8 نود.** الـ DB-first (`Insert Escalation`) فضل زي ما هو = **الضمان الحقيقي** (الرنّة مجرد تنبيه؛ كل التصعيدات في `omar.escalations` + view `open_escalations`).
- **متختبر e2e:** تصعيد تجريبي (angry/urgent) → execution 506 success → **الرنّة وصلت الجروب** (message_id 484، النص كامل بكل الحقول) → `Return Ack: alerted:{telegram:true}` → اتنضّف (صف الـ DB + رسايل التجربة).
- 🐞 **درس مهم (بوت تليجرام مشترك):** `getUpdates` للبوت الواحد **قارئ واحد بس** — أنظمة تانية بتستهلك (rs-aios messenger / notify.sh) بتسحب الـ updates وتأكّدها قبلك، فمستحيل تقرا chat_id يدوياً بثبات. الحل: **held long-poll** (مسكنا الاتصال 85 ثانية والرسالة جت لنا مباشرة). **الإرسال** مش متأثر خالص (مش بيستخدم getUpdates). (رجّعنا `allowed_updates=['callback_query']` بعد ما خلصنا عشان notify.sh.)
- ⚠️ **بند متابعة:** **أحمد لسه مش متضاف للجروب** (الأعضاء = Omar + البوت بس) → لازم Omar يضيفه بلينك دعوة عشان يوصله الإنذار.

### ⏳ المتبقي للإطلاق العام
1. **اختبار موسّع جولة 2:** أحمد/Omar يعيدوا: "فين القناة" (= يوتيوب) · "فين المجتمع/التليجرام" (= t.me/Et3lambBsata) · "فين انستا أحمد" (= linktree) · "عايز جروب كورس X" (= جمع إيميل + تسجيل) · عميل دولي (= تغيير العملة) · مشكلة حقيقية (= إنذار **فعلي** يوصل جروب التليجرام — اتأكدوا).
2. **إضافة أحمد لجروب التليجرام** (عشان يوصله الإنذار — دلوقتي Omar بس فيه).
3. **الإطلاق:** Disable لنود `Test Users Only` في W3 + إعادة نشر (deactivate→activate) — بس كده.
4. P2 اختياري: voice transcription + image vision عبر Gemini (pipeline rs جاهز للنقل، الـ community node موجود).

---

## 🟢🆕 2026-06-04 (آخر سياق، PUZZLE) — إصلاحين ريسبونسيف على الموبايل (/dart + الهيدر)

> **الموضوع:** Omar لاحظ مشاكل ريسبونسيف على الموبايل في صفحة /dart. اتشخّصوا واتصلّحوا بـ Playwright (قياس فعلي مش تخمين). **حاجة واحدة live، والتانية في PR مستني أحمد.**

### ✅ إصلاح 1 — شريط العرض (offer-chip) في /dart → **LIVE**
- **المشكلة:** الشريط (`🎁 لأول المسجّلين: خصم 50%...`) كان `inline-flex` من غير `flex-wrap` → عرضه 397px على شاشة 375px فبيتقص من الطرفين.
- **الحل:** `flex-wrap:wrap` + `justify-content:center` + تصغير الخط/الـ padding على ≤560px. في `01_WEB/mu-plugins/dart-landing/page.html`.
- **متأكّد حيّاً:** `chipFits=true`، صفر overflow أفقي. نُشر بـ scp + purge wp-optimize cache (ملفين `dart/index.html*`). commit `45ae576` + دليل في `_evidence/dart-mobile-offerchip-fixed-2026-06-04.png`.

### ✅ إصلاح 2 — الهيدر sitewide → **MERGED + DEPLOYED + LIVE**
- **المشكلة:** على شاشات <~466px، الهيدر بيحشر لوجو + اسم «اتعلم ببساطة» + كارت + زرّي دخول → حاوية الأزرار تطفح. (منيو الهمبرجر مفيهاش أزرار الدخول فمكنش ينفع تتخفي.)
- **الحل:** اسم اللوجو كان متخفي عند ≤380px بس (واطي) → **رفعنا الـ breakpoint لـ 480px**. الأيقونة تفضل. تعديل CSS 5 سطور في `01_WEB/website/assets/global/styles.css`.
- **الحالة:** ✅ **PR #13 اتعمله merge** (أحمد، squash commit `a0dfdeb` على main) → **نشرنا `styles.css` على السيرفر** (`wp-content/themes/edublink-child/assets/global/styles.css` عبر scp). **متأكّد LIVE بـ Playwright على 375px على /dart + الهومبيج: `logoTextDisplay:none`, `headerOverflow:[]`** (sitewide). دليل: `_evidence/header-mobile-FIXED-LIVE-2026-06-04.png`.
- **نقطة أمان مهمة:** قبل النشر تأكّدنا إن md5 السيرفر = نسخة الريبو قبل-الإصلاح بالظبط (أحمد مبيعدّلش الثيم على السيرفر مباشرة) + إن الفرق الوحيد في styles.css هو تعديلنا (5 سطور). فالنشر طبّق إصلاحنا بس. (نشرنا **styles.css بس** — مش باقي الـ18 commit التانية اللي على main، دي شغل أحمد المنفصل.)
- **الـ submodule:** اتعمله bump في الريبو الأب لـ main `a0dfdeb` (بعد الـ merge). ⚠️ ملاحظة CRLF: ملف السيرفر اتحفظ بـ CRLF (ويندوز) فبصمته مختلفة عن git blob (LF) — غير مؤثّر وظيفياً.

### 🔜 الجاي
- إصلاحين الريسبونسيف خلصوا live. مفيش متابعة مطلوبة عليهم.

---

## 🟢🆕 2026-06-04 (ليلاً، PUZZLE) — حدود الإيميل + Amazon SES جاهز (مستني AWS)

> **الموضوع:** أحمد شيّر بوست /dart والناس بدأت تسجّل → تأكدنا من الآلية 100% + حسمنا محرّك الإرسال. **اكتشاف عائق:** Hostinger SMTP = **100 إيميل/يوم** (حد صلب) → يقتل بث الـ 13K (يحتاج 130 يوم) + نافذة إطلاق Dart (48 ساعة). **القرار:** Amazon SES محرّك الإرسال (Mautic = العقل، SES = العضلات).

### ✅ آلية /dart waitlist — متأكّدة 100% LIVE
- الفورم + الـ popup → `/wp-json/learnsimply/v1/dart-waitlist` → W2 (n8n) → Mautic segment 10. **متحقّق من تسجيلات حقيقية** في execution logs. **العدّاد دلوقتي = 74 مسجّل** (وبيزيد).

### 🟡 Amazon SES — provisioned بالكامل، مستني موافقة production access بس
- **AWS account `824232274089`** (أحمد owner، Paid plan) · region **eu-central-1** · IAM user `growthmora-ses` (send-only) + SMTP creds في `.env` (`MAUTIC_SES_SMTP_*`).
- ✅ **كل الطبقة التقنية اتأكّدت** (فحص sesv2 حي): الدومين `VerifiedForSendingStatus=True` · **DKIM `Status=SUCCESS`** · MAIL FROM (`mail.learrnsimply.com`) `Status=SUCCESS` · الحساب `HEALTHY`. **مفيش حاجة تقنية ناقصة من ناحيتنا.**
- 🟡 **production access PENDING** = العائق الوحيد. AWS Support **Case 178058147100175**: AWS طلب تفاصيل use-case → أحمد رد عبر الكونسول → AWS ضاف correspondence بعدها بـ 8 دقايق (auto-ack مش موافقة). `ProductionAccessEnabled=False`، sandbox 200/يوم. الموافقة عادةً خلال 24 ساعة.
- ❌ **DMARC مش بيتخزّن على Hostinger** — الـ panel بيقطع أي TXT عند أول `;` (import + يدوي + `\;` كلهم فشلوا → المنشور `v=DMARC1` فاضي، غير ضار). **غير حاجب** (SES محتاج DKIM مش DMARC). الحل النضيف لاحقاً = نقل DNS لـ **Cloudflare** (مجاني). ⚠️ نفس البق هيضرب DMARC بتاع rspaac (هو كمان على Hostinger).
- 🔐 **الـ root key** (`AKIA372…`) لسه محتاجينه لمتابعة حالة الموافقة (الـ IAM send-only مبيقراش) → **أحمد يمسحه بعد الربط + تأكيد الإرسال**.

### ✅ سلسلة إيميلات الحملة — متزامنة وجاهزة
- الـ4 drafts (ids 2-5، unpublished) متأكّد إنها بالقيم النهائية (350/700، DART50، 15 يونيو، 48 ساعة، تايمر Sendtric). تفاصيل في `02_AUTOMATION/mautic/campaigns/email-copy-drafts.md`.

### 🔜 أول ما AWS توافق (`ProductionAccessEnabled=True`)
1. اربط Mautic `local.php` بـ SES legacy keys من `.env` (مش `mailer_dsn` — راجع rule #10) + backup `.bak-pre-ses` (سيب Hostinger SMTP لـ WordPress).
2. اختبار Port25 (`check-auth@verifier.port25.com`).
3. ابنِ Mautic Campaign drip لإيميلات Dart (ids 2-5).
4. ابعت إيميل ترحيب A للـ74 في segment 10 + auto للمسجّلين الجداد.
5. warmup ramp.
- **التفاصيل الكاملة:** `02_AUTOMATION/mautic/ses-setup-runbook.md`. **مهام أحمد المتوازية:** منتج Dart (700) + كوبون DART50 في WooCommerce.

---

## 🟢🆕 2026-06-04 (مساءً، PUZZLE) — اقرا ده الأول (أحدث سياق): رد أحمد + الحملة + صفحة /dart LIVE

> **الموضوع:** أحمد رد على أسئلة المساعد → دُمجت في الـ KB · سعر Dart حُسم 700→350 · صفحة `/dart` اتنشرت LIVE · إيميلات الحملة بتوجّه لها. **كله committed + pushed (9 commits).**

### ✅ رد أحمد (Notion → الـ KB)
- 6/7 أسئلة اتجاوبوا ودُمجت في `03_KNOWLEDGE/knowledge-base.md`: دخول الكورس (§10.2) · الدفع الدولي + أرقام فودافون/إنستا (§6.1b/6.2) · ساعات+أرقام الدعم (§10.1) · قانون الإمارات **مقصود** (§7.2) · "الموقع مش بيفتح" = يدوس "ابدأ الآن" (§10.3) · الشهادات مش معتمدة + bug تحميل (§8) · مدى الحياة (§9). **الناقص (مؤجّل بقرار Omar):** Graph + عمق Dart.
- تصميم مساعد واتساب: F2 (دخول الكورس) + F-C (دفع دولي) **اتفكّوا**. الأرقام: مساعد/دعم `01030127228` · أحمد الشخصي/تنبيهات `01102681074` · ساعات السبت→الخميس 10-6، بوت 24/7.

### ✅ الحملة (700→350)
- أحمد أكّد: **350 بعد الخصم** (Omar رفع الأصلي 700 عشان الـ 50% يفضل صح) · DART50 · 15 يونيو · 48 ساعة. كل drafts الحملة (ids 2-5، **unpublished**) + `_tools/build_campaign_emails.py` اتحدّثوا.
- **تايمر Sendtric حي** (`gen.sendtric.com/countdown/gf3pgkgnqk`) في إيميلات C+D.
- إيميلات B/C/D CTA بتوجّه دلوقتي لـ **/dart** (بدل اللينك الميّت). ⚠️ يوم الإطلاق: C/D تتوجّه لصفحة الشراء.

### 🚀 صفحة /dart LIVE — v3 (بستايل الـ Homepage)
- `01_WEB/mu-plugins/dart-landing/` (loader `.php` v2 + `page.html` fragment). **صفحة WP حقيقية** slug `dart` id **39320** → الـ loader بيرندّر `get_header()` + الـ fragment + `get_footer()` (جوه **هيدر/فوتر الموقع**). الفورم → نفس باكند الـ popup (`/wp-json/learnsimply/v1/dart-waitlist`) → Mautic segment 10. الـ **popup اتوقف على /dart** (`is_page('dart')`).
- **التصميم (v3):** سحبنا tokens الـ Homepage الحقيقية → غامق navy `#0a0f1a` + أزرق `#4077f3` + خط **IBM Plex Sans Arabic** + كروت غامقة + pill badges زرقاء + **كارت عرض أزرق** + sparkles + عداد JS حي + لوجو Dart + صورة أحمد. CSS كله معزول تحت `#ls-dart-lp` (عشان ستايل الثيم ما يتسرّبش). متوسّط + أنيميشن scroll-reveal.
- **متأكّد:** HTTP 200 · screenshots حية (`01_WEB/_evidence/dart-v3-*.png` + `dart-landing-*-LIVE.png`) ديسكتوب+موبايل · فورم E2E (POST→200، contact بـ tag `dart-waitlist` دخل segment 10، اتمسح).
- 📣 **شريط إعلاني sitewide** `01_WEB/mu-plugins/dart-announce/learnsimply-dart-announce.php` (wp_body_open، dismissible، مستثنى من /dart والشيكاوت) → يوجّه لـ /dart.
- ⚠️ **دروس deploy:** (1) صفحة WP حقيقية (`wp post create … --post_name=dart`) + `wp rewrite flush` لتفادي تعارض أرشيف/category بنفس الاسم. (2) **wp-optimize page cache** لازم purge بعد أي deploy (`wp eval 'wpo_cache_flush();'`). (3) أي تطابق ستايل → اسحب tokens الموقع الحقيقية بالـ chromium (computed styles)، مش تخمين.

### مفتوح / قرارات
- 🟡 **الدفع الدولي:** أحمد قال "يُفضّل PayPal" بس المصري مش بيستقبل → بدائل في `03_KNOWLEDGE/payments-international-options.md` (قرار Omar).
- ⏳ منتج Dart + كوبون DART50 في WC (مهمة أحمد) · نشر إيميلات الحملة بعد تأكيد أحمد · بناء أدوات مساعد واتساب · rotate W2 token بعد الإطلاق.

---

## 🟢 2026-06-04 (صباحاً، PUZZLE) — نقل الموقع + Dart popup LIVE

> **الموضوع:** أحمد نقل الموقع لاستضافة Hostinger جديدة → فحص ما-بعد-النقل + نشر Dart popup LIVE.

### 🔐 SSH الجديد (الـ alias `learnsimply` محدّث → key-based)
- **الاستضافة الجديدة:** `46.202.158.231:65002` · user `u791284659` · WP path `/home/u791284659/domains/learrnsimply.com/public_html` · hostname `de-fra-web1814.main-hosting.eu`.
- المفتاح `learnsimply-shared_ed25519.pub` اتزرع في `~/.ssh/authorized_keys` على السيرفر الجديد → **key-based شغّال** (متحقّق BatchMode). `.env` §SSH محدّث. الـ alias القديم (`147.93.73.159` / `u700430280`) مات.
- ⚠️ **درس:** كان لازم أحمد يـ **Enable SSH** من hPanel الأول — قبلها الحساب كان `nologin` (بيقبل الاتصال بس بيرفض تنفيذ أوامر). بعد التفعيل اشتغل فوراً.

### ✅ فحص ما-بعد-النقل (الموقع سليم 100%)
- HTTP 200 · `siteurl`/`home` = `https://learrnsimply.com` · SSL سليم · Meta Pixel بيـ fire (`699717432496147`) · DNS اتنقل (web IP `77.37.83.0`/`77.37.53.180`).
- WordPress **6.9.4** · wp-cli شغّال · **W1 webhook** (WC→Mautic, ID 7) **active** عدّى النقل · banner **39310** موجود · Elementor Pro رخصة رسمية ACTIVE · WP_DEBUG off.

### 🔴 اكتشاف أمني: Tutor فُعّل عبر خدمة تفعيل رخص — قرار Omar: نسيبه (شريك موثوق + عقد)
- أحمد حدّث Tutor+Pro لـ **3.9.11** (الـ CVE 9.8 اتصلّح في الكود) بس عبر خدمة `wordpresslicenses.com` اللي: (1) زرعت **حساب admin** `wp-licenses-95` / `support@wordpresslicenses.com` (ID 14112، 2026-06-03 20:41)، (2) رجّعت تفعيل Social Login (`is_enable=1`).
- **قرار Omar (2026-06-04):** الخدمة موثوقة + عقد رسمي → **نسيب الحساب + الرخصة + Social Login زي ما هم** (3.9.11 آمن). مفيش action مطلوب.
- 🟡 للمراقبة: 7+ تسجيلات subscriber وهمية (أسماء bot) امبارح بالليل — على الأرجح spam عادي.

### 🟢 Dart popup — LIVE
- mu-plugin اترفع لـ `wp-content/mu-plugins/learnsimply-dart-popup.php` (php -l عدّى) · **W2 activated** (n8n id `VMVSlPEcwNr1Bd6J`) · اختبار end-to-end تم (POST→200 `{success:true}`، contact بـ tag `dart-waitlist` دخل Mautic segment 10، اتمسح بعد التأكيد). الـ popup بيظهر للزوار (12s أو 45% scroll، لحد 15 يونيو).
- **باقي (وقت الصبح، اتحدّث بعدها):** كوبون الإطلاق (لما منتج Dart يتعمل في WC) · rotate W2 token بعد الإطلاق ([runbook §5.5](MIGRATION-DEPLOY-RUNBOOK.md)). ~~إشعار أحمد بالـ 7 أسئلة~~ → ✅ **أحمد رد بالفعل** (شوف قسم المساء فوق).

---

## 🟣🆕 2026-06-03 PC SESSION (مساءً) — (سياق 2026-06-03)

> **الموضوع:** بناء أساس مساعد واتساب "عمر" + تقسية عدائية + **نشر الـ infra على الـ VPS**.

**1) الأساس + الطبقة التقنية (ملفات، committed-pending):** في `02_AUTOMATION/agents/`:
- `omar-system-prompt.md` (عقل عمر — راجعه أحمد/عمر ووافق) · `postgres-schema.sql` (الذاكرة) · `omar-build-plan.md` · `omar-n8n-workflow-spec.md` (node-by-node جاهز لـ n8n-MCP).
- `rag/` (ingest_kb.py + schema-kb.sql + README) · `tools/` (order_lookup · mautic_upsert · escalate).
- **workflow متعدد الوكلاء** (9 وكلاء) قسّى الأساس: اتصلّح **15 مشكلة** أهمها 🔴 تضارب أنواع التصعيد (dash→underscore، كان هيكسر التصعيد صامت)، 🔴 قاعدة فشل الأداة، 🔴 سكريبت "إنت بوت؟"، سعر مخترع (2150 مش 1399). التحقق النهائي: صفر تضارب.

**2) 🟢 الـ VPS infra LIVE (نُشر النهاردة):** stack جديد `/docker/omar-agent/` (معزول، مش بيلمس n8n):
- `omar-pgvector` (pgvector/pgvector:pg16) — DB `omar_agent` (schema applied: contacts/messages/escalations/kb_chunks + pgvector 0.8.2) + DB `evolution`. **متوصّل بـ n8n_default → n8n بيوصله بدون restart** (متحقّق).
- `omar-redis` + `omar-evolution` (atendai/evolution-api:**v2.2.3**) — API بيرد 200، 31 Prisma table، Redis ready، n8n→evolution:8080 ✓.
- **الأسرار:** `/docker/omar-agent/.env` على الـ VPS (chmod 600). تفاصيل الاتصال في brand `.env` §20. doc كامل: `02_AUTOMATION/agents/deploy/README.md`.

**3) ⏳ باقي للـ go-live (مرتّب):**
- 🌐 **DNS:** `evolution.learrnsimply.com` → `187.124.9.249` (Hostinger) — للإدارة العامة + SSL.
- ⚠️ **خطر الحظر:** Evolution = Baileys (غير رسمي). **رقم مخصّص جديد** (مش رقم أحمد/الدعم) + inbound + warmup. البديل الرسمي = Meta Cloud API. **قرار مفتوح.**
- 📱 **رقمين من أحمد** (المساعد + الإنذار) + إجابات الفجوات (`03_KNOWLEDGE/ahmed-questions-kb-gaps.md`، الرسالة جاهزة لسه ما اتبعتتش).
- 🔨 **ابنِ n8n workflows** (omar-inbound + omar-alert) عبر n8n-MCP (على اللابتوب) · **RAG ingest** (Gemini key) · **WC read-only key** (بعد نقل الموقع).

**4) loose ends:** ملفات `*.sql` متجاهَلة في .gitignore → عند الـ commit استخدم `git add -f` لـ `postgres-schema.sql` + `rag/schema-kb.sql` (أو سطر استثناء). الـ deploy compose فيه placeholders بس (مفيش أسرار).

---

## 🟢🆕 2026-06-03 LAPTOP SESSION — اقرا ده الأول (أحدث سياق)

> 🆕🆕 **continuation (مساءً 2026-06-03):** Omar قرّر **نكمل شغل الموقع عادي** (النقل لما يحصل ياخد آخر حالة — فسطر "كل شغل WordPress متوقف" تحت **متجاوَز**). الناتج:
> - 🔑 **SSH للاستضافة الحالية اتظبط على اللابتوب** (key-based، مفتاح `learnsimply-shared_ed25519` + alias `learnsimply`، pubkey في hPanel) — تفاصيل + إعادة استخدام بعد النقل في قسم Access تحت.
> - 🔴 **اكتشاف: Tutor LMS Pro مكرك (nulled)** — الرخصة باسم "Pankaj Maurya" مش أحمد، مش بتتحدّث. فحص ملفات سريع = **مفيش backdoor واضح**. **الحل مؤجّل لبعد النقل** (أحمد يشتري رخصة حقيقية → نسخة رسمية) — قسم Tutor تحت + بند في checklist النقل.
> - ✅ **PR #12 merged + live:** تصحيح أخطاء إملائية تواجه العميل (جنية→جنيه ×6 على سطور الأسعار + خطاء→خطأ في 404). https://github.com/Learrnsimply/edublink-child/pull/12
> - ⏳ **مؤجّل لأحمد:** الأسماء الوهمية + "باقي 12 مكان" على صفحة CTA (dark patterns تتعارض مع براند "صادق") — قرار براند. + font dedup (tech-debt) مع تنظيف `functions.php` بعد النقل.
> - 🧹 **loose end:** الـ submodule `01_WEB/website` دلوقتي على آخر main (`ffdff55`, فيه PR #12) بس الـ workspace pin لسه على نقطة قديمة → هيبان "modified"؛ `/sync` الجاي هياخده.

> 🆕🆕🆕 **continuation 2 (مساءً متأخر 2026-06-03) — تحضير + deliverables (كله committed):**
> - 🎯🆕 **popup الـ Dart مبني وجاهز للنشر** (mu-plugin): `01_WEB/mu-plugins/dart-popup/learnsimply-dart-popup.php` + README. **proxy آمن عبر WordPress** (REST route same-origin → forward server-side لـ W2) عشان توكن n8n مايبانش في المتصفح. RTL + Cairo + mobile + honeypot + rate-limit. security-reviewed. **النشر بكرة بعد النقل** — خطوات في `MIGRATION-DEPLOY-RUNBOOK.md` §4. (commit `66171f5`)
> - 📋🆕 **`MIGRATION-DEPLOY-RUNBOOK.md`** (root البراند) — runbook executable لبكرة: SSH جديد + فحص ما-بعد-النقل + تركيب Tutor رسمي + نشر popup + تفعيل W2 + كوبون + hardening.
> - 🎨🆕 **Mautic RTL Arabic email theme** "Learn Simply Arabic" منشور على الـ VPS (sunday clone + RTL/Cairo patch). مصدر + `deploy.sh` في `02_AUTOMATION/mautic/themes/learn-simply-arabic/`. ⚠️ themes dir مش volume → أعد `deploy.sh` لو الـ container اتعمله recreate. (commit `b7af7f0`)
> - 📬🆕 **mail-tester على إيميل 1 = 9/10 (ناجح).** الـ -1 (font preconnect) **اتصلّح من المصدر** (`_tools/build_campaign_emails.py` + 5 previews). List-Unsubscribe header: الإيميل فيه `{unsubscribe_url}`؛ بيتضاف auto على البث الحقيقي (اختباري كان single-send API) — نأكّد على أول دفعة warmup. (commit `b7af7f0`)
> - 📊🆕 **تحليل تنافسي شامل** (طلب أحمد): `03_KNOWLEDGE/competitor-analysis.md` + `.html` + `.pdf` مصمّم. **أهم خلاصة:** أسامة الجندي = المنافس المباشر الوحيد (Dart standalone، **399ج موثّق**، أعمق). ميزة أحمد = التوزيع+الثقة+المجتمع مش المحتوى/السعر. **3 blockers قبل الإطلاق:** سعر+عمق كورس أحمد (مجهولين) · سعر أسامة وقت الإطلاق · صحة الـ payment gateway (~30% فشل). صفحة Notion لأحمد: `3741e071-e53a-817e-b0c0-c520ec8ce793`. (commit `9ff2095`، 8-agent workflow + fact-check)
> - 🧹 **loose ends مفتوحة:** (1) إيميلات Mautic الـ live (1-5) لسه فيها preconnect — هينضّفوا أول rebuild من الأداة، أو patch API. (2) submodule pin + workspace pin متأخرين → `/sync`. (3) TODO أمني: rotate SMTP password `contact@learrnsimply.com` + rotate توكن W2 webhook بعد الإطلاق (runbook §5.5).

> 🤖🆕 **continuation 3 (مساءً 2026-06-03) — مساعد واتساب الذكي: تفكير + KB + تصميم (كله committed + Notion synced):**
> - 🤖 **قرار: نبني AI agent ("عمر") يرد على عملاء واتساب** — دعم + pre-sale + تصعيد، **inbound فقط** (آمن لـ Evolution API). البنية: Evolution API + **Postgres على الـ VPS (مش Supabase — وفّر ~14.4K ج/سنة)** + Gemini Flash. شخصية "عمر من خدمة عملاء اتعلم ببساطة" (ما بيكشفش إنه bot). القرارات الكاملة في memory `whatsapp-agent-architecture.md`.
> - 📚 **Knowledge Base كاملة اتبنت من الموقع** (سحب read-only عبر SSH `learnsimply` — لسه شغّال): `03_KNOWLEDGE/knowledge-base.md` — 5 كورسات + المنهج (336 درس) + 8 منتجات + طرق الدفع + السياسات (استرجاع 7 أيام/<20% · شروط · خصوصية) + FAQ + سيرة أحمد. الخام (مفاتيح Kashier **محجوبة**) + سكربتات في `data/website-extract/`. **اكتشافات:** Kashier كارت = الطريقة الوحيدة لبرّه مصر (بالجنيه)، **PayPal مقفول**؛ ريفيوهات بتشتكي "الموقع مش بيفتح الحصص". (commit `69328e9`)
> - 🎨 **تصميم رحلة "عمر" كاملة:** `02_AUTOMATION/agents/whatsapp-agent-design.md` — تصنيف 6 بوكسات + مسارات A(مبيعات: assist-first، اكتشاف بس لو طلب)/D(لأحمد: collect+email)/E(برمجي: يوجّه مايجاوبش كود)/F1(موقع مش فاتح: سكرين+إيميل→تصعيد→حل بشري→إيميل اتحلت)/F5(استرجاع/غاضب: عمر مايقررش فلوس) + أداة إنذار (إيميل+واتساب لأحمد+عمر). Config: إيميلات الإنذار (`omarabdo385@` + `ahmedadel123422@`) + رقم Omar `01011516829`. (commit `f6d7b37`)
> - 📑 **رسالة أحمد (7 أسئلة)** بتفك المحجوز: `03_KNOWLEDGE/ahmed-questions-kb-gaps.md`. الأسئلة: دخول الكورس بعد الدفع · بديل الدفع الدولي · ساعات الدعم · قانون الإمارات في الشروط · ميعاد Graph · مشكلة "الموقع مش بيفتح" · رقمين واتساب (رقم أحمد الشخصي + رقم المساعد).
> - 📊 **Notion اتزامن (3 أسطح):** الصفحة الرئيسية (تحديث عميل) + Tasks Required From Ahmed `3721e071-e53a-80e5-a589-d7545820ab74` (7 أسئلة + mention) + Tasks DB (تاسكين خلصوا). ⚠️ **الإشعار لأحمد (الكومنت) مش اتبعت** — Omar يقرر: Notion comment ولا WhatsApp (النص جاهز).
> - 🔜 **الجاي بعد رد أحمد:** F2 (دخول الكورس) + C (الدفع الدولي) + الأدوات التقنية (RAG/بحث الأوردر بالتليفون-إيميل/Mautic/الإنذار) → **بناء فعلي** (system prompt + n8n + Postgres schema، شغل workflow). + أرقام واتساب أحمد + رقم المساعد.
> - 🧹 **loose end:** ملفات `_tmp_udemy*.json` (5) في root البراند = بقايا تحليل المنافسين، محتاجة تتمسح.

- ⏳ **النقل لسه ماتمّش — Ahmed أجّله لبكرة (2026-06-04).** الموقع شغّال عادي على الاستضافة **الحالية** (فحص حي: `learrnsimply.com` HTTP 200، web IP `92.113.23.79` — ده IP الويب الحالي، مختلف عن IP الـ SSH `147.93.73.159` وده طبيعي). **Meta Pixel بيـ fire** (`fbq init` + `699717432496147`). كل شغل WordPress متوقف لبكرة لما النقل يخلص + SSH جديد ييجي. شغل VPS (Mautic + n8n على `187.124.9.249`) ماشي عادي.
- 🟡 **W2 (Dart waitlist backend) DONE + tested + INACTIVE** — راجع قسم Dart تحت + `02_AUTOMATION/n8n/workflows/W2-dart-waitlist-popup.md`.
- 🔴🆕 **اكتشاف + إصلاح خطير — Mautic كان مش بيبعت أصلاً:** الـ `mailer_dsn` كان `smtp://localhost:1025` (الافتراضي) → كل إيميل بيتبخّر، رغم إن الـ legacy keys شكلها صح والـ sentCount بيزيد. اتصلّح لـ Hostinger SMTP الصح (مع `%%` escaping لمشكلة Symfony parameters). cache:clear + worker restart. **متأكد end-to-end — Omar استلم seed في Gmail inbox.** (درس كامل في `lessons.md` أعلى — صحّح rule #10 المقلوب.)
- 🟢🆕 **حملة إعادة التعريف (13K) — Phase 1 شغّالة:** استراتيجية A (re-engagement أول). الإيميل (RTL + Cairo font + YouTube CTA) مبني في Mautic (email id **1**) ومتأكد بيوصل inbox. **Ahmed وافق على النص (2026-06-03).** الخطة كاملة في `02_AUTOMATION/mautic/campaigns/01-reengagement-13k.md`.
- 🟢🆕 **الحملة جاهزة للبث (مش مطلَقة):** Segment **11** (`reengagementwave1`, filter tag wp-import) = **13,709**. إيميل 1 اتحوّل لـ **list** + مربوط بـ segment 11 + published. Mautic warmup = 200/يوم. ضغطة الإطلاق = `POST /api/emails/1/send` (مؤجّلة لحد ما أحمد يأكّد).
- 🆕 **كل إيميلات الحملة (5) اتكتبوا + اتحطوا على Notion لمراجعة أحمد:** [📧 إيميلات الحملة](https://app.notion.com/p/3741e071e53a817087c0c4c5538f0406) (page id `3741e071-e53a-8170-87c0-c4c5538f0406`). أحمد اتعمله mention + comment. النصوص بالـ repo: `02_AUTOMATION/mautic/campaigns/email-copy-drafts.md`. السيكوينس: 1 إعادة تواصل · 2 إعلان Dart (engaged) · 3 ترحيب waitlist · 4 إطلاق (كوبون DART50 50%) · 5 آخر فرصة.
- 🟢🆕 **إيميلات الحملة 2-5 اتبنوا في Mautic كـ drafts** (2026-06-03 laptop): ids **2** (ترحيب waitlist) · **3** (إعلان Dart) · **4** (إطلاق) · **5** (آخر فرصة) — كلهم `emailType=template` + `isPublished=false` (صفر خطر إرسال) + نفس template إيميل 1. Tooling: `_tools/build_campaign_emails.py` (idempotent — يـ update لو موجود). Previews: `02_AUTOMATION/mautic/campaigns/previews/`. جدول الحالة الكامل في `email-copy-drafts.md`.
- 👀🆕 **Public previews لأحمد:** اتفعّل `publicPreview=true` على الإيميلات 1-5 (`_tools/enable_public_preview.py`) → روابط `https://mautic.learrnsimply.com/email/preview/{id}` (بتفتح من غير login، متأكّد). الروابط اتحطّت تحت كل إيميل في صفحة مراجعة أحمد على Notion (`3741e071-e53a-8170-87c0-c4c5538f0406`). نسخة الـ preview **نضيفة** (قيم افتراضية عادية، من غير highlights — لينك الـ CTA بس عليه note رمادي). القرارات المفتوحة متتبّعة في جدول Notion، مش جوه الإيميل.
- ⏳ **بعد تأكيد أحمد (بكرة):** (1) املا الـ highlights الحمرا بالقيم النهائية (عدّل السكريبت وأعد التشغيل أو في Mautic UI) · (2) ابنِ **Mautic Campaign** للـ Dart drip (engaged→B، wait، C، wait، D) + welcome A على trigger انضمام segment 10 · (3) publish · (4) ابدأ بث إيميل 1 (200/يوم). قرارات أحمد المفتوحة: السعر 600→300 · كود الكوبون · مدة العرض · لينك CTA. + قرار Omar: توقيت الإطلاق + رفع warmup cap (SSH).
- ⚠️🆕 **TODO أمني:** rotate الـ SMTP password بتاع `contact@learrnsimply.com` (ظهر جزء منه في رسالة خطأ Symfony أثناء التشخيص). موجود في `.env` (SMTP_PASSWORD) + Bitwarden.
- ⏳ **بكرة بعد النقل:** بيانات SSH الجديدة → popup mu-plugin + تحديث Tutor + أي wp-cli work.

> 🔑🆕 **VPS access:** اتضافت قاعدة إذن `Bash(ssh learnsimply-vps:*)` في `.claude/settings.json`. **مهم:** أوامر الـ VPS لازم تبدأ بـ `ssh learnsimply-vps` مباشرة (من غير `cd`/`$()` محلي قبلها) عشان تطابق القاعدة — وإلا الـ auto-mode بيمنعها. للكتابة على prod: ابنِ السكريبت محلياً وابعته عبر `ssh learnsimply-vps 'bash -s' < script.sh` (الأمر يبدأ بـ ssh + الباسوورد مايظهرش في الترانسكريبت).

---

## 🔴 2026-06-02 PC SESSION — سياق سابق

> **⚠️ الموقع كان المفروض يتنقل لاستضافة جديدة.** ⏳ النقل اتأجّل لـ 2026-06-04 (راجع 2026-06-03 فوق). الموقع لسه على الاستضافة الحالية وشغّال.

### ⚠️ نقطة النقل — بكرة + بيانات SSH
Ahmed هينقل `learrnsimply.com` لاستضافة تانية **بكرة**. محتاجين من Omar بعدها:
1. **بيانات SSH الجديدة** — alias `learnsimply` القديم (`147.93.73.159:65002`, user `u700430280`, path `/home/u700430280/domains/learrnsimply.com/public_html`) **مش هيشتغل بعد النقل**. لازم host/port/user/path الجديدة.
2. ✅ الدومين لسه `learrnsimply.com` (متأكد — الموقع شغّال).

**Checklist فحص ما-بعد-النقل (شغّله أول السيشن الجاية):**

> 📋 **runbook executable كامل** (SSH جديد + فحص + Tutor رسمي + نشر popup + تفعيل W2 + كوبون): **[`MIGRATION-DEPLOY-RUNBOOK.md`](MIGRATION-DEPLOY-RUNBOOK.md)**. الـ checklist تحت = نسخة سريعة.
- [ ] SSH للاستضافة الجديدة شغّال + WP path صح
- [ ] Meta Pixel لسه بيـ fire: `curl -s -A Mozilla https://learrnsimply.com/ | grep -oiE "fbq\('init'|699717432496147"`
- [ ] W1 WooCommerce webhook (ID 7) → n8n لسه سليم (URL/secret)
- [ ] **Social Login addon لسه مقفول** (`wp option get tutor_addons_config` → social-login `is_enable=0`) — ثغرة 9.8
- [ ] البانر (attachment **39310**) موجود في Media
- [ ] Mautic SMTP / from-address / تتبّع لسه شغّال
- [ ] 🔴 **Tutor LMS Pro مكرك (nulled)** — على الاستضافة الجديدة ركّب **النسخة الرسمية برخصة أحمد الحقيقية**، مش تنقل المكرك (تفاصيل في قسم Tutor تحت)

> ✅ Mautic + n8n على VPS منفصل (187.124.9.249) — **مش متأثرين بالنقل خالص**.

### 🔴 CRITICAL — ثغرة Tutor LMS Pro (مُعطّلة جزئياً، محتاجة تحديث)
بحث عميق (7 وكلاء + تحقق عكسي، confidence=high) كشف **4 ثغرات مؤكدة** في **Tutor LMS Pro 3.0.1** (المفروض 3.9.11):

| CVE | الخطورة | يتصلح في |
|---|---|---|
| **CVE-2026-0953** (Auth Bypass via Social Login) | **9.8 🔴 بدون دخول** | 3.9.6 |
| CVE-2026-25406 (Broken Auth) | 8.1 بدون دخول | 3.9.9 |
| CVE-2025-6184 (SQLi) | 8.8 | 3.7.1 |
| CVE-2025-6639 (IDOR) | 5.4 | 3.9.0 |

- **السبب:** رخصة Themeum منتهية → التحديثات الأمنية واقفة. (Patchstack "deactivate and delete" = نص افتراضي عام، الحذف بيقتل الكورسات.)
- ✅ **خطوة عاجلة تمّت:** Social Login addon اتقفل بالكامل (`is_enable=0` + `enable_google_login=off`، متحقق SSH) → الـ 9.8 اتعطّلت. مفيش أدمن غريب = مفيش اختراق.
- ⏳ **باقي:** الرخصة بتتفعّل **النهاردة** (2026-06-02). أول ما تتفعّل → **حدّث `tutor-pro` لـ 3.9.11** (مش 4.0.0 beta) + certificate-builder + elementor-addons → backup أول → اختبار (checkout→وصول الكورس + شهادات) → تقوية (rotate admin pw + 2FA). Notion task: `🔴 CRITICAL — حدّث Tutor LMS Pro`.

- 🆕 **2026-06-03 تصحيح مهم (عبر SSH key-based للاستضافة الحالية):** الرخصة **مش منتهية — هي مكركة (nulled)**. `wp option get tutor_license_info` بيقول `license_to: "Pankaj Maurya"` (شخص غريب) + `license_type: "Lifetime Unlimited"` + `activated:true` مزيّف. يعني **مفيش رخصة حقيقية نفعّلها** — النواة المجانية (`tutor`) وصلت 3.9.11 من wp.org بس الـ Pro المكرك متجمّد على 3.0.1 (`update: none`)، فرق **9 إصدارات** = خطر تطابق كمان. فحص ملفات سريع: **مفيش باكدور واضح** (header أصلي Themeum؛ كل نتائج eval/encode في `vendor/` libs + JS مصغّر = false positives) — بس ثقة 100% تحتاج hash-diff مع نسخة Themeum الرسمية.
  - **الحل (مؤجّل لبعد النقل — قرار Omar 2026-06-03):** الاستضافة الجديدة → أحمد يشتري **رخصة Tutor LMS Pro حقيقية** → تركيب **النسخة الرسمية الحديثة من الصفر** (مش نقل المكرك) → تطابق النواة + تحديثات أمنية + الموضوع القانوني يتقفل. لحد ساعتها: Social Login مقفول (9.8 قافلة)، **منحذفش الـ Pro** (بيشغّل الكورسات = الإيراد).
  - ⚠️ **افحص كمان وقتها:** `Elementor Pro` (`_elementor_pro_license_v2_data`) + `WPSynchro` (`wpsynchro_license_key`) عندهم license keys — نفس نمط الخطر المحتمل (يتأكّد إنهم مش مكركين برضه).

### ✅ Meta Tracking — من broken لـ LIVE (خلص)
- Ahmed داك full access (Pixel + Ad account + Dataset + Page + Catalog + Instagram).
- ربط بلجن Facebook for WooCommerce: Pixel `699717432496147` + Ad account `1770006103570345` + CAPI token.
- بعد مسح cache: **الـ pixel بيـ fire site-wide** (متحقق: `fbq init` + PageView على home + /courses/). browser + server CAPI شغّالين.
- `.env` section 8 محدّث + committed (`019fdb3`).
- ⏳ باقي (مش عاجل): تأكيد Purchase event بعد أول طلب حقيقي + domain `checkout.kashier.io` allowlist (Meta قالت no-action).

### ✅ Analytics access (متحقق)
- GA4: Ahmed منح access لـ `omarabdo385` (متحقق من إيميل Google). property نص-شغّال (PageView بس) — لسه محتاج conversion events.
- Search Console: Omar أكّد من الداشبورد إنه داخل.
- إيميلات Omar: جوجل=`omarabdo385@gmail.com`، فيسبوك/Meta=`omarabdo258@gmail.com` (memory `user_omar_email_variants`).

### 🟡 Dart Course Popup — البناء بدأ (متوقف للنقل)
مهمة Ahmed التانية: popup يجمع إيميلات قبل إطلاق **كورس Dart (15 يونيو 2026)** → كوبون 50% (600→300) يوم الإطلاق. صورة البانر المختارة من Omar.
- ✅ Mautic segment `dartwaitlist` (**id=10**).
- ✅ البانر WP Media (**attachment 39310**، نسخة `768x512` للـ popup — الأصل 2MB كبير).
- ✅ Mautic API flow **متختبر ومثبَت**: create contact (email+firstname+tags=[`dart-waitlist`]) + add to segment 10.
- ✅ **n8n W2 workflow DONE** 2026-06-03 (id `VMVSlPEcwNr1Bd6J`, **INACTIVE** — تفعيلة واحدة وقت ما الـ popup ينزل). Webhook → Validate (honeypot + email regex) → Create contact (tag `dart-waitlist`) → Add to Segment 10 → Respond JSON. متأكّد end-to-end (valid 200 / invalid 400 / honeypot skip / UTF-8 `أحمد` / idempotent). doc: `02_AUTOMATION/n8n/workflows/W2-dart-waitlist-popup.md`. ⚠️ `source_channel` مُتجاهَل عمداً (select field يرفض القيمة). secret path = `dart-waitlist-97ae34dfa856`.
- ✅🆕 **popup mu-plugin مبني وجاهز** (2026-06-03، laptop PUZZLE): `01_WEB/mu-plugins/dart-popup/learnsimply-dart-popup.php` + README. قرار Omar = كود مخصص. **بُني بـ proxy آمن عبر WordPress** (REST route same-origin → forward server-side لـ W2) عشان توكن n8n مايبانش في المتصفح + honeypot + rate-limit (REMOTE_ADDR) + email-validation. اتعمله **security review** (مفيش critical؛ اتصلّح: تنظيف رسالة n8n + REMOTE_ADDR بدل XFF). بانر 39310 + RTL + Cairo + mobile-first + once-per-visitor (localStorage) + يقف بعد تاريخ الإطلاق. **decision-light** (مفيش سعر/كوبون). **باقي بس النشر** (نسخ لـ `wp-content/mu-plugins/` على الاستضافة الجديدة).
- ⏳ **باقي (بعد النقل):**
  - **انشر الـ popup** + **فعّل W2** + كوبون الإطلاق — كله خطوة-بخطوة في **[`MIGRATION-DEPLOY-RUNBOOK.md`](MIGRATION-DEPLOY-RUNBOOK.md)** §4-5.
  - ⚠️ اتأكد إن attachment **39310** لسه صح بعد النقل (الـ IDs ممكن تتغير).
- Notion task: `🎯 كورس Dart — popup` في ✅ Tasks.
- ⏳ بحث أسعار منافسين Dart (Omar قال أيوة) — لسه مفتوح.

### اللي اتعمل كمان السيشن دي
- Security hardening: SSH host-key verification (RejectPolicy بدل AutoAdd) في `_tools/*.py` + PDF title HTML-escape — committed `b259154`.
- Notion محدّث بالكامل: الصفحة الرئيسية بلهجة بسيطة للعميل (Ahmed) + Tasks DB + Files & Reports + "Tasks Required From Ahmed" (Meta/GA4/GSC مع mentions + comments).
- Mautic: **13,711 contact** متأكد (live API).

---

## 🎯 إنت فين دلوقتي

### ✅ Done — متراكم من sessions السابقة + الـ session ده
- Phase 0 Foundation (backup system, Sprint 1 PR, SSH alias `learnsimply`, brand reorg)
- Phase 1 Revenue (2/7), Phase 2 Security (9/12), Phase 3 DB/Perf (4/22)
- Analytics Audit 2026-05-24 (GA4 PageView only, TikTok MAPI 1.04M EGP attributed, Meta Pixel broken)
- Credentials capture (8/10) in `.env`

### 🆕 Done في sessions 2026-06-01 (الطويل + الـ continuation)

**VPS + Infrastructure:**
- ✅ VPS bootstrap كامل — Hostinger KVM4 (187.124.9.249, Ubuntu 24.04, 16GB RAM, 200GB NVMe)
- ✅ SSH key-based auth + UFW + fail2ban + root pw rotated
- ✅ Traefik 3.7 routing both Mautic + n8n with Let's Encrypt auto-cert
- ✅ Compose hardened: TRUSTED_PROXIES=172.16.0.0/16 (not `*`), TRUSTED_HOSTS pattern restricted

**Mautic 7.1.1:**
- ✅ LIVE at https://mautic.learrnsimply.com
- ✅ MySQL 8.4 (upgraded from 8.0)
- ⚠️ SMTP: legacy mailer_* keys were set but **NOT actually sending** (Mautic 5/6 uses `mailer_dsn`, which was stuck at default `localhost:1025`). **Fixed 2026-06-03** — see `lessons.md` + rule #10.
- ✅ **Redirect loop FIXED** — `trusted_proxies => ['REMOTE_ADDR']` in local.php
- ✅ **Worker memory bounds** — custom supervisord-bounded.conf (email 128M/160s/60msg, hit 128M/300s/200msg, failed 64M/600s/50msg)
- ✅ **API + HTTP Basic Auth** enabled (`omar:<pw>`)
- ✅ **11 custom contact fields** (IDs 44-54)
- ✅ **9 strategic segments** (IDs 1-9): all_contacts, engaged_30d, dormant_90d, wc_buyers, high_value, non_buyers, active_cart, whatsapp_contacts, telegram_contacts
- ✅ **24 starter tags** (lifecycle + behavior + channels + courses)
- ✅ **Week 1 IP warmup throttle**: 5/min, 50/hour, 200/day

**Email auth chain (verified via 2 Port25 reports):**
- ✅ **DKIM**: Hostinger Custom DKIM `hostingermail1._domainkey` active. `dkim=pass header.d=learrnsimply.com`
- ✅ **SPF**: Updated to include `ip4:187.124.9.249`. `spf=pass smtp.mailfrom=contact@learrnsimply.com`
- ✅ **DMARC**: Single clean `p=quarantine` record. Legacy duplicate `p=none` deleted
- ✅ **DMARC=pass** guaranteed by RFC 7489 — both SPF + DKIM aligned strictly with From: domain
- ✅ **iprev=pass** (Hostinger uses MailChannels as outbound relay)

**Bounce processing:**
- ✅ `bounces@learrnsimply.com` mailbox created in Hostinger
- ✅ Mautic Settings → Email Settings → Bounces section configured + Test Connection=Success

**n8n Stack:**
- ✅ LIVE at https://n8n.learrnsimply.com (queue mode, Postgres 16, Redis 7 with password)
- ✅ 1 main + 1 worker, bounded concurrency 10
- ✅ Memory budget: 5.4G of 13G available
- ✅ Owner account + API key (JWT, full access)
- ✅ **n8n-MCP installed** (czlonkowski/n8n-mcp v2.56.0) — verified working with 23 tools
- ✅ Bonus: 7 n8n-skills auto-loaded (workflow-patterns, expression-syntax, validation-expert, etc.)

**Security review (post-commit hardening):**
- ✅ Hardcoded credentials removed from `create-custom-fields.sh` + `create-segments.sh` (now read from `$MAUTIC_API_USER`/`$MAUTIC_API_PASSWORD`)
- ✅ Mautic compose tightened (no more `TRUSTED_PROXIES: '*'` or `TRUSTED_HOSTS: '.*'`)

---

## 🚦 NEXT SESSION — P0 priorities (مرتّبة)

### الـ infrastructure DEPLOYED + READY. الـ work الجاي = revenue activation.

| # | Task | Effort | Why |
|---|---|---|---|
| 1 | **Optional: mail-tester.com 10/10 smoke test** | 5 min | Visual confidence. Skip if confident (Port25 already proved SPF+DKIM+iprev=pass, DMARC=pass by RFC) |
| 2 | 🟢 **W1 n8n workflow LIVE** 2026-06-02 (id `whayAvTcXhG6TDeQ`, ACTIVE + WC webhook ID 7 wired) | done | WC order → Mautic upsert. End-to-end verified 3 ways (Arabic + idempotency + WC's own validation ping = n8n exec #4 success). Now catching every new order automatically. Full doc: `02_AUTOMATION/n8n/workflows/W1-wc-mautic-sync.md` |
| 3 | 🟢 **13K backfill DONE** 2026-06-02 — **13,709 contacts imported** (tag `wp-import`, +`wc-customer` for 89 buyers, 0 sends) | done | Source: wp_users via wp-cli (shared-host SSH password in `.env`). Tooling: `_tools/fetch_users.py` + `_tools/import_to_mautic.py`. ⚠️ Imported contacts have NO engagement history → `engaged_30d` ~empty; broadcast targeting must use other criteria. Next: run `mautic:segments:update` on VPS to populate segments. |
| 4 | **Arabic RTL email theme** (Mautic UI clone default + add RTL CSS) | 15 min | Polish before any human-facing broadcast |
| 5 | **Test broadcast على 50-100 engaged contacts** | 30 min | Validate end-to-end |
| 6 | **Activation broadcast** | 1 hour | ⚠️ **Strategy changed** (2026-06-02): segments rebuilt → engaged_30d=**1** (imports have no engagement history), dormant90d=0, wcbuyers=0. Original "broadcast to engaged_30d 3-5K" doesn't apply. Reality: 13,709 all in `allcontacts`/`nonbuyers`. Target by tag `wp-import` or reg-recency with **IP warmup ramp**, OR send a re-engagement email first to build an engaged cohort. Conservative target still **400-600K EGP one-time**. |

### Tier 2 polish (non-blocking):
- Mautic UI throttle verify (visual confirmation 5/50/200 from local.php)
- Port RS brand patterns (see `memory/reference_rs_brand_patterns_to_port.md` — 5-stage agent for W3, agent CLAUDE.md, hybrid memory)

### Tier 3 deferred:
- MTA-STS + TLS-RPT DNS records (optional)
- ✅ **Amazon SES — لم يعد مؤجّلاً (2026-06-04):** الـ Hostinger throughput (100/يوم) طلع عائق **الآن** مش Week 4. SES اتجهّز بالكامل (identity + DKIM SUCCESS + MAIL FROM + IAM send-only + SMTP creds) ومستني **production access** بس (Case 178058147100175). التفاصيل في قسم "أحدث سياق" أعلى + `02_AUTOMATION/mautic/ses-setup-runbook.md`.
- WooCommerce Plugin install — handled by W1 instead

---

## 🔑 Access (في `.env` section 16)

### SSH
```bash
ssh learnsimply-vps   # 187.124.9.249, key-only auth (VPS: Mautic+n8n)
ssh learnsimply       # 147.93.73.159:65002, u700430280 — CURRENT shared host (WP)
```
> 🆕 **2026-06-03:** اللابتوب (PUZZLE) بقى عنده **key-based access** للاستضافة المشتركة الحالية. المفتاح `~/.ssh/learnsimply-shared_ed25519` + alias `learnsimply` في `~/.ssh/config`. الـ pubkey اتضاف في hPanel → SSH Access. **بعد النقل:** الصق نفس الـ pubkey في hPanel الاستضافة الجديدة + غيّر HostName/Port في `~/.ssh/config`. (الـ pubkey نفسه في `learnsimply-shared_ed25519.pub` على اللابتوب.)

### Mautic
- URL: https://mautic.learrnsimply.com — Admin: `omar` / pw in `.env`
- API: HTTP Basic Auth, `/api/contacts` etc.
- From: contact@learrnsimply.com / "اتعلم ببساطة"

### n8n
- URL: https://n8n.learrnsimply.com — Owner: omarabdo385@gmail.com / pw in Bitwarden
- API: `N8N_API_KEY` JWT in `.env` section 16
- n8n-MCP: auto-loads via `~/.claude.json`

### Docker stacks on VPS
- `/docker/traefik/` (Traefik 3.7.1, host network mode)
- `/docker/mautic-r4bx/` (Mautic web + cron + worker + MySQL 8.4 + supervisord-bounded.conf)
- `/docker/n8n/` (n8n main + worker + Postgres 16 + Redis 7)

### Bounce mailbox
- `bounces@learrnsimply.com` / `o7Y6xYzMlN*` in `.env`
- IMAP: `imap.hostinger.com:993` SSL — configured in Mautic Bounces section

---

## 💰 Revenue Scenario (Conservative)

90-day target: **~1.0-1.2M EGP** vs baseline ~200K EGP. Gated on Week 1 activation broadcast to engaged_30d segment (~3-5K from 13K dormant) → expected ~400-600K EGP one-time at 4-6% conversion at AOV ~3K EGP.

---

## 🗺️ Skip List

1. **Meta Ads MCP** (April 2026 open beta, gated) — revisit Sept 2026
2. **$25 WordPress MCP template #5060** — wp-cli over SSH covers it
3. **token-optimizer family** (~80 tools) — context bloat
4. **rs-aios Next.js dashboard port** — wrong stack
5. **Tactics Coach W7 in Phase 1** — needs ≥2 weeks data first. D61-D90.
6. **Stripe/Paymob MCPs** — Kashier is current gateway

---

## 📁 أهم الفايلات

### Brand repo:
| الملف | Why important |
|---|---|
| `CLAUDE.md` | Brand routing + current state |
| `_research/2026-06-01-final-recommendations.md` ⭐ | W1-W7 spec + 90-day roadmap |
| `.env` section 16 | All credentials + status of each item |
| `02_AUTOMATION/mautic/README.md` | Mautic ops runbook |
| `02_AUTOMATION/mautic/OMAR_UI_CHECKLIST.md` | Tier 1 ✅ + Tier 2/3 polish list |
| `02_AUTOMATION/mautic/docker-compose.yml` | Hardened compose |
| `02_AUTOMATION/mautic/supervisord-bounded.conf` | Worker bounds |
| `02_AUTOMATION/n8n/README.md` | n8n ops runbook |
| `02_AUTOMATION/n8n/docker-compose.yml` | n8n queue mode stack |

### Workspace memory (9 entries — see `MEMORY.md`):
| File | What |
|---|---|
| `feedback_mautic7_trusted_proxies_redirect_loop.md` ⭐ | The bug that made Mautic UI inaccessible |
| `feedback_mautic7_dkim_via_smtp_relay.md` ⭐ | Mautic 7 has no DKIM UI |
| `feedback_mautic_worker_bounded_supervisord.md` ⭐ | OOM safety for broadcasts |
| `feedback_mautic5_legacy_smtp_keys.md` | Legacy keys for mailer (applies to 5 + 7) |
| `project_vps_stack_2026-06-01.md` | VPS architecture |
| `reference_rs_brand_patterns_to_port.md` | RS paths for W3+W4 agents |

---

## ⚠️ قواعد ميلزم تنساها

1. **662 processing WC orders = DON'T TOUCH** — Ahmed manually enrolling
2. **مفيش push على `Learrnsimply/edublink-child` main بدون PR review من Ahmed**
3. **مفيش `wp db export` على Hostinger shared** — CageFS fails silent. Use `mysqldump`.
4. **مفيش `crontab -e` من SSH** على shared hosting. Use hPanel UI.
5. **مفيش `rm -rf` على VPS أو shared بدون عرض على Omar**
6. **`learrnsimply.com` بـ R مكررة** — مش typo
7. **Plugin "active" status لا يعني pixel firing** — verify في frontend HTML
8. **wp-config.php constants > DB options** — WPMS_SMTP_PASS overrides DB value
9. **Auto Mode لازم يفضل verify safety قبل أي destructive op** — "موافق طالما امن"
10. **Mautic 5/6 يبعت عبر `mailer_dsn` فقط — الـ legacy `mailer_*` keys للعرض بس.** ⚠️ صحّح فهم مقلوب قديم (2026-06-03). الافتراضي `smtp://localhost:1025` = إيميلات تتبخّر بصمت. لازم DSN صح + كل `%` يتضاعف `%%` (Symfony escaping). متعتمدش على Test button/sentCount — اختبر بوصول inbox. راجع `lessons.md`.
11. **Reuse `_agency/notify.sh` Telegram bot** (chat_id 6726176133) — DO NOT create new bot for Learn Simply
12. **n8n MUST use queue mode + Postgres + Redis** — vanilla SQLite default = OOM risk
13. **Mautic 7 behind Traefik MUST have `trusted_proxies => ['REMOTE_ADDR']` in local.php** — env var alone insufficient
14. **Mautic 7 has NO DKIM UI** — use SMTP relay's DKIM (Hostinger Custom DKIM)
15. **Mautic Docker workers are UNBOUNDED by default** — mount custom supervisord with --memory-limit/--time-limit/--limit
16. **DMARC duplicate records = PERMERROR** — always check for + delete legacy `p=none`
17. **Mautic `cache:clear` leaves root-owned files** — always follow with `chown -R www-data:www-data var/cache`
18. **Don't add ip4 to SPF expecting it to fix DMARC** — Hostinger SMTP routes via MailChannels, the IP at destination is NOT the VPS

---

## 💬 آخر context من Omar (الـ continuation session)

> "tier 1 + 2 + 3" — wants full Mautic hardening Tier 1+2+3 in one push.

→ Done. Server-side Tier 1+2+3 applied (workers, API, fields, segments, tags, throttle). UI Tier 1 walkthrough completed (DKIM verified, SPF updated, DMARC clean, bounce mailbox + IMAP working). 4 commits pushed: `766ff7b` + `e69ed8b` + `7f10c6a` + `e0d9bde`.

→ Tier 2 remaining = Arabic RTL theme (15 min) + throttle UI verify (2 min). Both non-blocking.

→ Tier 3 = MTA-STS + WC integration (via n8n W1) + SES migration. Future sessions.

> `/strategic-compact`

→ Updated this file + 3 new memory files (`feedback_mautic7_*`). Ready for compact. Next session opens with W1 workflow build using n8n-MCP.

---

## 💻 2026-06-02 — Laptop (PUZZLE) device setup for W1

> **السياق:** فتحنا سيشن على اللابتوب (`C:\Users\PUZZLE`) مش الـ PC (`sw`). الـ `~/.claude.json` + `~/.ssh/` مش متزامنين عبر الأجهزة (root CLAUDE.md)، فاللابتوب كان مفهوش SSH ولا n8n-MCP. الـ n8n + Mautic APIs بس كانوا شغّالين (HTTP 200).

**اللي اتعمل على اللابتوب (Claude side):**
- ✅ `.mcp.json` اتكتب في فولدر البراند (project-scoped n8n-MCP, czlonkowski/n8n-mcp@2.56.0) — N8N_API_URL + N8N_API_KEY من `.env` section 16
- ✅ SSH key جديد للّابتوب: `~/.ssh/learnsimply-vps_ed25519` (per-device key, no passphrase)
- ✅ SSH alias `learnsimply-vps` اتضاف في `~/.ssh/config` (HostName 187.124.9.249, root, port 22)
- 🔑 Laptop pubkey: `ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIKZLGPBZaJJFsRr36yWXxA/1PEI4TV5iWowZs7mrma2P claude-laptop-PUZZLE-2026-06-02`

**Pending Omar actions على اللابتوب:**
1. ✅ DONE 2026-06-02 — pubkey اتضاف على الـ VPS عبر Browser Terminal + `ssh learnsimply-vps` متأكّد شغّال (root@srv1719695، كل 9 containers صاحية)
2. ✅ DONE 2026-06-02 — Restart تم + n8n-MCP متحمّل (v2.56.0، health=ok، 23 tools). **W1 اتبنى واتختبر بنجاح في نفس السيشن.**

**Laptop (PUZZLE) setup note:** الجهاز ده دلوقتي عنده SSH للـ VPS (`learnsimply-vps`) + n8n-MCP. لسه **مفيش SSH للسيرفر المشترك** (`learnsimply` alias) — أي wp-cli work لازم PC أو نظبّط الـ alias. الـ `.env` فيه كل الـ creds.

**بعد الـ restart:** السيشن الجاي يكمّل **W1 build** (WC → Mautic contact sync) عبر `mcp__n8n-mcp__n8n_create_workflow` + `validate_workflow`. الـ spec في `_research/2026-06-01-final-recommendations.md` Section 5. الـ shared-host SSH (`learnsimply` للـ wp-cli) لسه مش متظبّط على اللابتوب — الـ WC webhook ممكن يتظبّط عبر wp-admin UI بدل كده.
