# Learn Simply — Session Log

> تاريخ كل الـ sessions بالتفصيل، من الأحدث للأقدم. اتنقل هنا من `CLAUDE.md` عشان يفضل الـ map نضيف.
> أحدث سياق كامل + checklists → `HANDOFF.md` · الدروس المتراكمة → `lessons.md` · الحالة الحية → memory.

---

## 🚀 CURRENT STATE — 2026-06-24 (**id5 قِيس + نزيف hold-stock مؤكّد ببيانات جديدة + send.py refactor متنشر** · عمر agent LIVE)

> ⭐ **اقرا الأول:** memory `project_dart_launch_results_and_payment_diagnosis` (أحدث) + `project_omar_agent_live_state_2026-06-10` + `HANDOFF.md`.

### 🟢🆕 session 2026-06-24 (مساءً) — W4 flow استرجاع السلة: **LIVE** (بعد إصلاح بقّين)
- 🎯 **التوصية #2 (أعلى ROI) من الـ deep dive** — flow استرجاع الأوردرات الـ `pending`. قرارات Omar: **إيميل بس (Brevo، صفر خطر حظر)** · flow جاري + blast تاريخي بعدين · **تذكير + بديل المحفظة، بلا خصم**. خطة: `02_AUTOMATION/n8n/workflows/W4-W5-cart-recovery-PLAN.md` · doc: `W4-cart-recovery.md`.
- 🟢 **W4 LIVE** (`Er1W6KSwqOiEmgrd`، 6 نود): Schedule (كل 20د، 8ص–10:40م القاهرة) → WC pending → Load log → **Decide** (R1 عند 45د–10س · R2 عند ≥10س) → **Brevo SMTP** (`YTlcYxVu93s62OuL`) → **Mark** في `omar.recovery_log` (Postgres). الـ **stop** = الـ poll بيجيب pending بس. errorWorkflow=W3d.
- ✅ **اتختبر e2e على داتا حقيقية:** WC auth · Decide (R1/R2) · **بق #1 اتصلّح**: أوردرات الباقة كانت قايمة 10-أسطر مكررة → فلترة bundle children + dedupe + سقف اسمين · لينك الدفع HTTP 200 · UTM · Brevo سلّم.
- 🐞 **بق #2 (إنتاجي، اتصلّح):** أول go-live (cron مؤقت/دقيقة) — Mark Sent فشل (`undefined`) لأن **node الإرسال بيستبدل الـ item بنتيجة SMTP** فـ Decide fields بتختفي. **النتيجة: ghada (عميلة) استلمت R2 ×4 مكرر قبل ما نوقف.** الإصلاح: Mark بيقرا `={{ $('Decide Sends').item.json.markSql }}` (paired item) + SQL كامل متبني في Decide (صفر param-binding). متأكّد حي (Mark كتب 39925 · إعادة = صفر إرسال). الأوردرين (ghada+Omar) اتسجّلوا يدوي → محميين. **درس → memory.**
- **steady-state:** الأوردرين الحاليين logged → 0 إرسال لحد ما pending جديد يظهر · حجم متوقّع ~5/يوم.
- **الجاي:** مراقبة executions + `omar.recovery_log` + تحويل UTM · بعد ما يثبت → **W5 blast تاريخي** للـ313 عميل (~186K ج) عبر Mautic.

### 🟢 session 2026-06-24 — كوميت متراكم + قياس id5 + تأكيد hold-stock + نشر send.py
- ✅ **شغل 13/23 يونيو المعلّق اتكوميت + اترفع** (3 commits: UTM tooling + skills `ls-whatsapp-agent`/`ls-wp-tutor` + CLAUDE.md) — الريبو نضيف، صفر متروك.
- 📊 **قياس إيميل id5 (آخر فرصة):** 87 مبعوت · **27 فتح (31%)** · المبيعات المعزوّة لـ `utm_campaign=dart_lastchance` = **أوردر واحد (39907 Dart 350 ج) — اتلغى بالـ timeout**. (last-touch بيقلّل العدّ؛ فاتحين id5 ممكن رجعوا اشتروا عبر direct/google.)
- 🎯 **نزيف hold-stock اتأكّد ببيانات مستقلة جديدة:** كل الـ 5 أوردرات الملغية يوم 2026-06-23 (**2,400 ج**: #39866/39907/39909/39910/39914) بنفس الملاحظة بالحرف *«طلب غير مدفوع ألغي - الوقت المحدد أنتهى»*، الفجوة إنشاء→إلغاء 64-107 دقيقة. **صفر فشل بوابة.** الإعداد الحالي على الموقع: `woocommerce_hold_stock_minutes=60` · `manage_stock=yes`.
  - ✅ **الإصلاح اتطبّق 2026-06-24 (بنفسنا عبر wp-cli، مش أحمد):** `wp option update woocommerce_hold_stock_minutes 1440` → **60→1440 دقيقة (24 ساعة)**، متأكّد. Omar اختار التطويل بدل التفريغ الكامل (يدّي العميل يوم كامل يكمّل الدفع + يفضل ينضّف المهجور تلقائياً). reversible بأمر واحد.
- 🔧 **drift الـ send.py اتقفل:** النسخة المعمولها refactor اتنشرت على `/opt/dart-scheduler/send.py` (md5 = الريبو `de2c10a0…`) + `.env` اتعمل (5 مفاتيح، **mode 600**، مستخرجة server-side من الباك أب) + **dryrun متأكّد** (قرا الـ env، مفيش FATAL) + باك أب القديم `send.py.bak-2026-06-24` محفوظ mode 600. الأسرار اتشالت من الكود المنشور (لسه في git history — rotation مؤجّل بقرار Omar).
- 🔬 **Deep dive (2,936 أوردر/13 شهر، تحقق عدائي صفر اختلاف — `wf_aa060d92-7dd`، تقرير `03_KNOWLEDGE/payment-leak-deep-dive-2026-06-24.md`):** فرضية «كاشير بيرفض برّا مصر» **مفنّدة نهائياً** (أجنبي كارت 57% > مصري 48.8% · السعودية 72%). الخسارة الصافية الحقيقية = **~186K ج/13 شهر = ~14.3K ج/شهر** (مش 195K/سنة — جروس 376K بس **41% بيرجعوا يكمّلوا لوحدهم**). دليل قاطع: `kashierOrderId` على 95% من الناجح بس **0.8% من الملغي** → الملغي معرفش وصل البوابة (timeout مش رفض؛ الدافع الحقيقي بيدفع في 3.67 دقيقة). **اكتشاف جديد: صدمة السعر** (إكمال 79%→37% كل ما السعر يعلى؛ الباقات أسوأ). **الأعلى ROI الجاي = flow استرجاع السلة** (~285K جروب قابل للاسترجاع + ~22K/شهر).
- 🛒 **تقليل احتكاك الـ checkout (2026-06-24، قرار Omar):** (1) **شيل صفحة السلة** — mu-plugin `learnsimply-skip-cart.php` يخلّي add-to-cart يروح `/checkout/` مباشرة (متأكّد: 302→/checkout). (2) **5 منتجات كانت مش virtual** (منهم 33336 + 39043 من الأكثر مبيعاً) → اتعلّموا virtual عبر wp-cli → حقول الشحن اختفت (`needs_shipping=N` للـ 9). حقول الـ checkout دلوقتي: اسم+موبايل+إيميل بس. كله reversible. تفاصيل + rollback: `01_WEB/mu-plugins/skip-cart/README.md`. (بيصلّح مرحلة قبل-الأوردر، مكمّل لـ hold-stock + recovery.)
- **الجاي:** بناء **flow استرجاع السلة** (Mautic+Brevo+واتساب عمر — أعلى ROI، لسه) · مراقبة أثر شيل-السلة + تطويل hold-stock على الإكمال · (اختياري) تقليل صدمة السعر/تقسيط على الباقات · (تجميلي) إصلاح `utcnow()` DeprecationWarning.

### 🟢 session 2026-06-23 — قياس حملة Dart + آخر فرصة + تشخيص الدفع + UTM
- 📊 **حملة Dart اتقاست end-to-end:** إيميل الإطلاق (id4) اتبعت **404/404** (دفعتين، 0 spam) → 96 فتح (23.8%) · 9 كليك · **16 مبيعة Dart = 5,600 ج**. 24 محاولة (16 مكتمل · 6 ملغي · 1 فاشل · 1 pending).
- 🔬 **تشخيص نزيف الدفع (workflow 4 عدسات + متشككين عدائيين، `wf_6106b803-768`):** خرافة "Kashier مكسور/195K" **متفنّدة** (بلجن رسمي v1.3.0). السبب الحقيقي = **تخلّي شيكاوت اتسجّل "ملغي" بسبب WooCommerce hold-stock timeout (~60 دقيقة)** على منتج رقمي ملوش مخزون (6 من 8 خسائر، فيها محفظة → مش مشكلة كارت). بوابة فشلت **مرة واحدة** بس (39818). فجوة كارت-محفظة (54% vs 88%) = ضوضاء عيّنة (n=21, p=0.17). الخسارة الحقيقية **7 أوردر/2,450 ج** (39709 = أوردر أحمد نفسه). **التوصية #1: أحمد يشيل/يطوّل `hold_stock_minutes` (10 دقايق). متغيّرش البوابة.**
- 🚀 **إيميل «آخر فرصة» (id5) اتبعت لـ 87** (فتحوا الإطلاق + مكبسوش + ما اشتروش · 0 مشتري في القائمة · Brevo نضيف) — تذكير ناعم، **بلا ددلاين** (العرض ملوش تاريخ نهاية حقيقي، Omar اختار الصدق)، **بلا كوبون** (DART50 وهمي)، محفظة-أولاً. 25 فتح خلال دقائق.
- 🏷️ **ثغرة القياس اتقفلت:** لينكات الإيميل قبل كده بلا UTM → كل مبيعات الإيميل ظهرت "(direct)". دلوقتي `_tools/build_campaign_emails.py` بيعلّم UTM أوتوماتيك + اتفاقية في `02_AUTOMATION/mautic/campaigns/utm-convention.md`. لينك id5 فيه `utm_campaign=dart_lastchance`.
- ⚠️ **drift اتكشف:** الـ VPS لسه شغّال نسخة `send.py` القديمة (أسرار hardcoded، قبل refactor PR #2) — الـ refactor اتعمل merge للريبو بس ماتنشرش. (memory محدّثة.)
- **الجاي:** متابعة عزو مبيعات id5 (utm=mautic) خلال يومين · أحمد ينفّذ إصلاح hold-stock · (اختياري) flow استرجاع سلة للأوردرات pending عبر Mautic + مساعد عمر.

### 🟢🟢 session 2026-06-10 (ليلاً) — FULLY LIVE: إصلاحات عميقة + فهم صوت/صور + إطلاق عام
- 🔴→✅ **الأدوات كانت مكسورة كلها (queue mode):** `toolHttpRequest` بيرمي "supplyData but no execute" في n8n queue mode → الوكيل يألّف (لينكات غلط) ومش يصعّد. **الحل:** kb_search/order_lookup/mautic_upsert → **`toolWorkflow`** + wrappers **active** (W3t `IfIlQ2RsfsubUkHW` · W3t-order `gC9cPSiTBP3M6DZj` · W3t-mautic `R5xDtKKGSeBchue6`). متأكّد (رجّع لينك انستجرام الصح). onError كان red herring.
- 🔴→✅ **التصعيد مكنش بيوصل تيليجرام** (`can't parse entities`): HTML-escape + parse_mode HTML في W3b → متأكّد (`message_id 501` وصل الجروب). + تضييق الحارس (كان بيصطاد "سجلت" ويأكل التصعيد الحقيقي).
- 🆕 **فهم الصوت + الصور:** Evolution get-media-base64 → Gemini analyze (3-flash-preview) → Resolve Message → الوكيل. + **prompt v9 (DB active)** يستنتج النية (دخول→مساعدة · إيصال→تحقق إن الرقم رقم أحمد · خطأ→site_access).
- 🐛→✅ **بق إطلاق:** tool call ناقص الـ argument كان بيكسر التنفيذ كله (العميل ميردّش) → `$fromAI` default `''`.
- 🚀 **GONE LIVE:** نود `Test Users Only` **disabled** (مش حذف — re-enable يرجّع البوابة) → الوكيل بيرد على **كل** العملاء على `201030127228`. ✅ أحمد متضاف لجروب التيليجرام `-5163152342`. مراقبة أخطاء شغّالة. **الجاي:** مراقبة أول محادثات حقيقية + اختبار v9 بصورة حقيقية + خطر حظر Baileys (inbound-only بيخفّف).

---

### 🟢 session 2026-06-10 (مساءً) — [أقدم] الـ GO-LIVE المبدئي: QR + ترقية Evolution + أول محادثات حقيقية
- ✅ **QR كان مش بيتولّد** → السبب: واتساب رفض إصدار البروتوكول القديم. الإصلاح: `CONFIG_SESSION_PHONE_VERSION=2.3000.1035194821` في compose (+ backup) → restart → QR ظهر → **أحمد مسحه → state OPEN على رقم الدعم 201030127228** 🎉
- 🔴➡️✅ **اكتشاف LID (حرج):** واتساب بقى يبعت هوية مجهّلة (`xxx@lid`) بدل رقم العميل — و**v2.2.3 مش بيترجمها ولا يعرف يرد عليها أصلاً** (اختبرنا: إرسال لـ @lid = `exists:false`). الحل: **ترقية Evolution → v2.3.7** من الـ repo الجديد `evoapicloud/evolution-api` (القديم `atendai` ميت عند v2.2.3). backups قبلها (compose + pg_dump للـ evolution DB) · migrations نجحت · **الجلسة عاشت بدون re-QR**.
- ✅ **W3 معدّل لـ LID:** Normalize بيستخرج الرقم الحقيقي من الحقول المرافقة (senderPn/remoteJidAlt/...) · Evolution Send بيرد على `remoteJidFull` (شغال مع الصيغتين) · **نود `Test Users Only`** (فلتر بعد Is Message?) — عمر `201011516829` + أحمد `201102681074` بس، غيرهم يتجاهل **بصمت** (ولا DB write). للإطلاق العام: **Disable للنود + إعادة نشر**.
- 🟢 **W3 ACTIVE (28 نود) — أول محادثات حقيقية نجحت:** Omar اختبر live: ردود سليمة بأسعار صح من الـ KB + خطوات اشتراك. ⚠️ درس n8n: التعديلات بتروح draft — **لازم deactivate→activate بعد كل تعديل** عشان تبقى live.
- ✅ **3 إصلاحات UX من الاختبار** (prompt v4/v5 + Clean Reply): قاعدة 17 تعريف "أنا عمر من خدمة عملاء اتعلم ببساطة" بأول رسالة · قاعدة 18 ممنوع Markdown (واتساب بيكسر `[نص](لينك)` — القوس اللاصق = 404) + فلتر كود في Clean Reply يفك أي markdown · قاعدة 19 ممنوع تأليف لينكات + **لينكات شراء الباقتين inline في الـ prompt** (اكتشاف: RAG بيرتّب chunk الباقات ضعيف — الجداول بتتفهرس وحش، فالـ prompt-inline هو الضمان) · KB اتحدّثت + re-ingest (1 updated/77 skipped).
- 🆕 **(2026-06-10 ضحى) ملاحظات أحمد يوم 1 → prompt v6/v7 + KB (live فوراً — مفيش redeploy، الـ prompt من الـ DB):** قاعدة 20 «وصّلت للفريق» لازم تسبقها أداة فعلاً (**اتمسك الوكيل بيقولها والـ W3b ماشتغلش** — سؤال أحمد "الطلبات بتروح فين؟" كشفها) · لينكات المجتمع inline: قناة اليوتيوب `youtube.com/@Learn_Simply` (v7: "القناة" بدون تحديد = اليوتيوب) + تليجرام `t.me/Et3lambBsata` + Linktree `linktr.ee/ahmedadeel` · جروبات الكورسات بتتحدّث → اجمع إيميل + mautic_upsert · مسار C: **تغيير العملة في Kashier checkout** (ملاحظة Omar/فلسطين). والاطمئنان: "العملاء الحقيقيين" في سكرينات الصبح طلعوا كلهم **رقم أحمد** بيمثّل عميل — بوابة الاختبار سليمة.
- 📢 **(2026-06-10 ظهراً) قناة التصعيد بقت Telegram-only:** بدل إيميل+واتساب → **جروب تليجرام «اتعلم ببساطة — تنبيهات الدعم»** (`chat_id -5163152342`، البوت `@n8n_aimora_bot`). W3b اتبسّط 10→8 نود (نود Telegram واحد بدل Email+WhatsApp×2)، الـ DB-first فضل = الضمان. **متختبر e2e** (تصعيد angry → الرنّة وصلت الجروب). ⚠️ **أحمد لسه مش متضاف للجروب** — لازم يتضاف.
- ⏳ **المتبقي للإطلاق العام:** اختبار موسّع جولة 2 (القناة = يوتيوب · مجتمع = تليجرام · انستا = linktree · جروب كورس = جمع إيميل · عميل دولي = تغيير العملة · تصعيد **فعلي** يوصل جروب التليجرام) → **إضافة أحمد لجروب التليجرام** → Disable لـ Test Users Only → إعادة نشر. P2: voice/image عبر Gemini (pipeline rs جاهز).

### 🤖 session 2026-06-10 (laptop PUZZLE) — مساعد واتساب "عمر": من spec لتنفيذ كامل
- ✅ **System prompt v0.2** — إجابات أحمد اتدمجت (F2 خطوات دخول الكورس الحقيقية · C الدفع اليدوي فودافون `01030127228`/إنستاباي `01102681074` + اليمن/سوريا · ساعات الدعم السبت→الخميس 10-6 · F1 حل "ابدأ الآن" · الشهادات completion + bug التحميل + تغيير الاسم · قسم Dart 15 يونيو + DART50). fallback فشل الـ escalate بقى إيميل الدعم (مش واتساب الدعم — عشان عمر أصلاً بيرد منه).
- ✅ **3 n8n workflows مبنيين ومتحقق منهم:** `W3 omar-inbound` (`ESYkoJgz0e4ngMrM`، **INACTIVE** — webhook secret-path → normalize دفاعي → dedup → upsert → AI Agent Gemini Flash temp 0.3 + Postgres Chat Memory + 4 tools: kb_search/order_lookup/mautic_upsert/escalate → log → Evolution send) · `W3b omar-alert` (`ULoRfU57m5fSLD2B`، sub-workflow **مش محتاج تفعيل** — DB-first + إيميل Brevo + واتساب لأحمد `…1074` وعمر `…6829`) · `W3c omar-kb-search` (`sv6ART3GjO4JUN81`، **ACTIVE** ومتاختبر live). كل الـ IDs + الـ credentials في `.env` §20.
- ✅ **Evolution instance `omar-support`** متعمل + webhook → n8n (MESSAGES_UPSERT) + groupsIgnore. الرقم (`01030127228` رقم الدعم بموافقة أحمد) **لسه مش متوصل** — الـ QR بيتجاب من الـ API مباشرة (مش محتاج DNS — الأمر في `02_AUTOMATION/agents/deploy/README.md`).
- ✅ **أداة `inject_prompt.mjs`** (`02_AUTOMATION/agents/deploy/`) — أي تعديل على الـ prompt → أمر واحد يزامنه لنود الـ Agent (18.1K حرف + بلوك سياق العميل).
- ✅ **اختبارات:** kb-search live e2e (degraded بأمان لحد ما المفتاح ييجي) · كل SQL الـ pipeline (upsert/dedup/escalation snapshot/mark-alerted) في rollback transactions — صفر أثر · عربي + إيموجي سليمين.
- 🔄 **Refactor جودة (بعد deep-research بطلب Omar):** W3 اتعاد بناؤه 20→27 نود على نمط وكلاء rs-aios الحيين + web best practices — ack فوري · debounce 8s/تجميع رسايل · typing/read/تأخير بشري · **prompt من DB بإصدارات** (`omar.agent_prompts` v2 + أداة `push_prompt.mjs`) · hybrid memory (bucket 48h) · fallback model · فخ maxIterations · إصلاح تجاهل الصوتيات · **W3d error handler → Telegram** (`YktkjLMI12YUGWfc`). التقرير: `02_AUTOMATION/agents/workflow-quality-study-2026-06-10.md`.
- ⏳ **الـ go-live مستني 3 أكشنات:** (1) **Gemini API key** من Omar (aistudio.google.com → `.env` + n8n credential `ZeYvAf59LZGqkIbU`) · (2) **WC read-only key** (wp-admin → REST API → Read → credential `5k7PIPwao0Vkoczb`) · (3) **QR-scan مع أحمد** → بعدها RAG ingest + اختبار e2e برقم تجريبي + **تفعيل W3** (toggle واحد).

---

## 📦 Legacy — 2026-06-04 (نقل + popup · رد أحمد + الحملة + /dart LIVE + شريط + SES + ريسبونسيف موبايل — أُرشف 2026-06-10)

### 🟢 session 2026-06-04 (آخر سياق، laptop PUZZLE) — إصلاحين ريسبونسيف على الموبايل
- ✅ **إصلاح /dart offer-chip → LIVE:** شريط العرض كان `inline-flex` بلا `flex-wrap` (397px على شاشة 375px → بيتقص). الحل: `flex-wrap` + تصغير على ≤560px في `01_WEB/mu-plugins/dart-landing/page.html`. متأكّد بـ Playwright (`chipFits=true`، صفر overflow) + scp + purge wp-optimize. commit `45ae576`.
- ✅ **إصلاح الهيدر sitewide → MERGED + LIVE:** على <~466px الهيدر بيطفح (لوجو+اسم+كارت+زرّين). الحل: رفع breakpoint إخفاء اسم اللوجو من 380→480px (5 سطور CSS في الثيم). **PR #13 اتعمله merge** (أحمد، `a0dfdeb`) → نشرنا `styles.css` على السيرفر بـ scp (بعد تأكيد md5 السيرفر=الريبو) → **متأكّد LIVE بـ Playwright على /dart + الهومبيج** (`logoTextDisplay:none`، sitewide). الـ submodule اتعمله bump لـ main. دليل: `_evidence/header-mobile-FIXED-LIVE-2026-06-04.png`.

### 🟢 session 2026-06-04 (ليلاً، laptop PUZZLE) — حدود الإيميل + Amazon SES
- ✅ **آلية /dart waitlist متأكّدة 100% LIVE** — فورم + popup → `/wp-json/learnsimply/v1/dart-waitlist` → W2 → Mautic segment 10. **74 مسجّل** (وبيزيد).
- 🔴 **اكتشاف عائق:** Hostinger SMTP = **100 إيميل/يوم** (حد صلب) → يقتل بث الـ 13K (130 يوم) + نافذة إطلاق Dart (48 ساعة). Hostinger = transactional بس.
- 🟡 **القرار + التنفيذ: Amazon SES محرّك الإرسال** (Mautic عقل، SES عضلات). **provisioned بالكامل** (account `824232274089`، eu-central-1، IAM send-only، SMTP creds في `.env`). كل الطبقة التقنية اتأكّدت حيّاً: domain verified · **DKIM SUCCESS** · MAIL FROM SUCCESS · account HEALTHY. **مستني production access بس** (AWS Case `178058147100175`، `ProductionAccessEnabled=False`، الموافقة عادةً <24 ساعة).
- ❌ **DMARC مش بيتخزّن على Hostinger** (الـ panel بيقطع TXT عند `;`) → غير حاجب، الحل لاحقاً = Cloudflare DNS. ⚠️ نفس البق على rspaac.
- 🔐 **root key** (`AKIA372…`) لسه محتاجينه لمتابعة الموافقة → أحمد يمسحه بعد الربط.
- **الجاي (أول ما AWS توافق):** ربط Mautic بـ SES → Port25 → campaign drip → إيميل ترحيب للـ74. runbook: `02_AUTOMATION/mautic/ses-setup-runbook.md`.

### 🟢 session 2026-06-04 (laptop PUZZLE) — أهم الأحداث
- ✅ **نقل الموقع تمّ** لاستضافة Hostinger جديدة: `46.202.158.231:65002` · user `u791284659` · WP path `/home/u791284659/domains/learrnsimply.com/public_html` · hostname `de-fra-web1814`. SSH **key-based** شغّال (alias `learnsimply` محدّث، المفتاح اتزرع). web IP بقى `77.37.83.0`/`77.37.53.180`. (`.env` §SSH محدّث · الـ alias القديم `147.x`/`u700430280` مات.)
- ✅ **فحص ما-بعد-النقل:** الموقع سليم 100% — HTTP 200 · WordPress 6.9.4 · Meta Pixel بيـ fire (`699717432496147`) · W1 webhook (WC→Mautic, ID 7) **active** عدّى النقل · Elementor Pro رخصة رسمية · banner 39310 موجود.
- 🔴 **اكتشاف أمني (قرار Omar: نسيبه):** أحمد حدّث Tutor+Pro لـ **3.9.11** (الـ CVE 9.8 اتصلّح في الكود) عبر خدمة تفعيل رخص `wordpresslicenses.com` — اللي زرعت حساب admin `wp-licenses-95` (`support@wordpresslicenses.com`, ID 14112) + رجّعت Social Login. **قرار Omar:** خدمة موثوقة + عقد رسمي → **نسيب الحساب + الرخصة + Social Login زي ما هم** (3.9.11 آمن). تفاصيل في HANDOFF.
- 🟢 **Dart popup LIVE:** mu-plugin (`wp-content/mu-plugins/learnsimply-dart-popup.php`) · **W2 activated** (n8n id `VMVSlPEcwNr1Bd6J`) · اختبار end-to-end تم (POST→200 `{success:true}`، contact بـ tag `dart-waitlist` دخل Mautic segment 10، اتمسح). التصميم اتحسّن (الزرار اتصلّح بـ scoped CSS، حقل إيميل واحد بدل اسم+إيميل) · triggers: 12 ثانية أو **15% scroll**، dismiss **يومين** · screenshot في `01_WEB/_evidence/`. ⚠️ wp-optimize page cache كان بيخفي الـ popup عن الزوار — Omar مسحه واتأكد إنه ظهر.
- 🆕 **skill `ls-wrap`** اتعملت (`.claude/skills/ls-wrap/`) — إقفال سيشن learn-simply: تنظيف docs المضللة + git sync بـ commits منظّمة + تجهيز /compact.

### 🟢 session 2026-06-04 (مساءً، laptop PUZZLE) — رد أحمد + الحملة + صفحة /dart LIVE
- ✅ **أحمد رد على 6/7 أسئلة** (Notion "Tasks Required From Ahmed") → دُمجت في `03_KNOWLEDGE/knowledge-base.md` (دفع/دعم/شهادات/سياسات/دخول الكورس/حل "الموقع مش بيفتح") + فُكّت F2/C في تصميم مساعد واتساب + الأرقام (`…7228` مساعد/دعم، `…1074` أحمد الشخصي/تنبيهات، ساعات السبت→الخميس 10-6). **الناقص (مؤجّل):** Graph + عمق Dart.
- ✅ **سعر Dart حُسم: 700→350 (خصم 50%)** — أحمد أكّد 350 + DART50 + 15 يونيو + 48 ساعة. كل الحملة (drafts ids 2-5، unpublished) + الأداة اتحدّثوا.
- ✅ **تايمر إطلاق:** Sendtric GIF حي (`gen.sendtric.com/countdown/gf3pgkgnqk`) في إيميلات C+D. (الكابتشا منعت الأتمتة → Omar ولّده يدوياً.)
- ✅ **مقارنة الدفع الدولي** `03_KNOWLEDGE/payments-international-options.md` — 🔴 **PayPal المصري مش بيستقبل** (send-only) → بدائل: تحسين Kashier / MoR (LemonSqueezy/Paddle) / Payoneer. (قرار Omar مفتوح.)
- 🚀 **صفحة `learrnsimply.com/dart` LIVE** — mu-plugin (`01_WEB/mu-plugins/dart-landing/`): loader (rewrite + **path-match** عشان أرشيف كان بيكسب الـ route) + `page.html` (لوجو Dart + صورة أحمد الحقيقية + **عداد JS حي** + فورم). الفورم → نفس باكند الـ popup → segment 10. **متأكّد:** HTTP 200 + screenshot حي + فورم E2E (دخل Mautic + اتمسح). ⚠️ wp-optimize cache اتعمله purge بعد الـ deploy.
- ✅ **إيميلات الحملة بتوجّه لـ /dart** (بدل اللينك الميّت).
- 🎨 **/dart اتطوّرت (v2→v3):** بقت جوه **هيدر/فوتر الموقع** (صفحة WP حقيقية slug `dart` id **39320** + `get_header`/`get_footer` + CSS معزول `#ls-dart-lp`) · **إعادة تصميم تطابق الـ Homepage** (غامق navy `#0a0f1a` + أزرق `#4077f3` + خط IBM Plex Sans Arabic + كارت عرض أزرق + sparkles — سحبنا tokens الـ homepage الحقيقية) · توسيط + أنيميشن scroll-reveal · الـ **popup اتوقف على /dart**. كله live + متأكّد بصرياً ديسكتوب+موبايل.
- 📣 **شريط إعلاني sitewide** (mu-plugin جديد `01_WEB/mu-plugins/dart-announce/`) فوق كل صفحة يوجّه لـ /dart · dismissible · مستثنى من /dart والشيكاوت.
- 📋 **Notion اتحدّث** (skill `ls-notion`): Status page للعميل (نبرة بسيطة) + Tasks DB + طلب رسمي لأحمد (منتج Dart + كوبون DART50 في WC) بـ callout + mention. (الكومنت-الإشعار النظام منعه — مؤجّل لقرار Omar.)
- **الجاي:** منتج Dart في WC + كوبون DART50 (مهمة أحمد، قبل 15 يونيو) · إعادة توجيه CTA إيميلات الإطلاق (C/D) لصفحة الشراء يوم الإطلاق · نشر إيميلات الحملة (**gated على موافقة SES production** + تأكيد أحمد — مستحيل على Hostinger 100/يوم) · توحيد خط الشريط/الـ popup لـ IBM Plex (اختياري) · بناء أدوات مساعد واتساب "عمر" · rotate W2 token بعد الإطلاق (runbook §5.5).

---

## 📦 Legacy — session 2026-06-03 (مساءً: مساعد واتساب الذكي — KB + تصميم الرحلة · بداية popup · أُرشف 2026-06-04)

> ⭐ **اقرا الأول:** `HANDOFF.md` — أحدث سياق كامل + Checklist فحص ما-بعد-النقل + P0 للسيشن الجاية.
> ⭐ **خطة W1-W7 + 90-day roadmap:** `_research/2026-06-01-final-recommendations.md`.

### 🟢 session 2026-06-03 (laptop PUZZLE) — أهم الأحداث
- 🤖🆕 **مساءً — بدأنا مساعد واتساب الذكي ("عمر"):** (1) فكّرنا سوا في البنية → Evolution API + **Postgres على الـVPS (مش Supabase، وفّر ~14.4K ج/سنة)** + Gemini + شخصية "عمر من خدمة عملاء". (2) **KB كاملة من الموقع** (سحب read-only): `03_KNOWLEDGE/knowledge-base.md` — 5 كورسات/المنهج 336 درس/الأسعار/الدفع/السياسات/FAQ/سيرة أحمد (commit `69328e9`). (3) **تصميم الرحلة كاملة:** `02_AUTOMATION/agents/whatsapp-agent-design.md` — تصنيف 6 بوكسات + مسارات A/D/E/F1/F5 + أداة إنذار (commit `f6d7b37`). (4) **رسالة أحمد 7 أسئلة** (`03_KNOWLEDGE/ahmed-questions-kb-gaps.md` + Notion) بتفك المحجوز (F2/C + أرقام واتساب). ⚠️ إشعار أحمد مش اتبعت. **الجاي:** رد أحمد → F2/C + الأدوات التقنية → بناء فعلي.
- ⏳ **النقل اتأجّل لبكرة (2026-06-04).** الموقع شغّال عادي على الاستضافة الحالية (`learrnsimply.com` HTTP 200، web IP `92.113.23.79` = IP الويب الحالي، مختلف عن SSH IP `147.x` وده طبيعي). Meta Pixel بيـ fire. شغل WordPress متوقف لبكرة. VPS (Mautic + n8n) ماشي.
- 🟡 **W2 (Dart waitlist backend) DONE + tested + INACTIVE** — n8n id `VMVSlPEcwNr1Bd6J`. Webhook → Validate (honeypot + email regex) → Mautic create (tag `dart-waitlist`) → Add to Segment 10 → Respond JSON. متأكّد end-to-end (valid 200/invalid 400/honeypot skip/UTF-8/idempotent). doc: `02_AUTOMATION/n8n/workflows/W2-dart-waitlist-popup.md`. باقي: popup mu-plugin (محتاج SSH الجديد بكرة) → فعّل W2 → كوبون الإطلاق.
- 🔴 **اكتشاف خطير + إصلاح: Mautic كان مش بيبعت أصلاً** — `mailer_dsn = smtp://localhost:1025` (افتراضي) → كل إيميل يتبخّر رغم legacy keys صح + sentCount بيزيد. اتصلّح لـ Hostinger SMTP (مع `%%` escaping). **متأكد — Omar استلم seed في inbox.** صحّح rule #10 المقلوب. (`lessons.md` أعلى درس.)
- 🟢 **حملة إعادة التعريف 13K — Phase 1 شغّالة (استراتيجية A):** الإيميل (RTL + Cairo + YouTube CTA) مبني في Mautic (email id 1) + بيوصل inbox + **Ahmed وافق على النص**. خطة كاملة: `02_AUTOMATION/mautic/campaigns/01-reengagement-13k.md`. الجاي: segment `wp-import` + engaged tracking + warmup ramp + أول دفعة 200. ⚠️ TODO أمني: rotate SMTP password (ظهر جزء منه في error).
- 🟢🆕 **إيميلات سيكوينس Dart (4) اتبنوا كـ drafts في Mautic** — ids **2-5** (ترحيب waitlist · إعلان · إطلاق · آخر فرصة)، كلهم `template` + `unpublished` (صفر خطر) + نفس template إيميل 1، والقرارات المفتوحة highlight أحمر جوه الـ HTML. Tooling: `_tools/build_campaign_emails.py`. Previews في `campaigns/previews/`. باقي بعد تأكيد أحمد: ملء الـ placeholders + Mautic Campaign drip + publish. (جدول الحالة في `email-copy-drafts.md`.)

### 🔴 session 2026-06-02 (PC) — أهم الأحداث
- **⚠️ الموقع بيتنقل لاستضافة جديدة (Ahmed بينقله).** WordPress work **متوقف** لحد ما يخلص. السيشن الجاية تبدأ بـ **فحص ما-بعد-النقل** (في HANDOFF.md) + **بيانات SSH جديدة** (alias `learnsimply` القديم `147.93.73.159:65002` مش هيشتغل).
- ✅ **Meta tracking LIVE** — Pixel `699717432496147` + Ad account `1770006103570345` بيـ fire site-wide (browser + CAPI) بعد ربط بلجن Facebook for WooCommerce + مسح cache. كان متعطّل تماماً (pixel=0). (`.env` §8، commit `019fdb3`.)
- 🔴 **ثغرة Tutor LMS Pro 3.0.1 حرجة** — 4 CVEs، أخطرها CVE-2026-0953 (9.8 admin takeover عبر Social Login). ✅ خطوة عاجلة تمّت: Social Login addon اتقفل (`is_enable=0` متحقق) → الـ 9.8 اتعطّلت. ⏳ باقي: **تحديث لـ 3.9.11** لما رخصة Themeum تتفعّل (النهاردة). **مش حذف** (بيقتل الكورسات).
- ✅ **Analytics access:** GA4 (Ahmed منح لـ omarabdo385، متحقق) + Search Console (Omar أكّد من الداشبورد).
- 🟡 **Dart popup (مهمة Ahmed، إطلاق 15 يونيو):** البناء بدأ — segment `dartwaitlist` (**id=10**) + بانر (**attachment 39310**) + Mautic flow متختبر ومثبَت. باقي: n8n W2 + popup mu-plugin + كوبون (بعد النقل).
- ✅ Security fixes committed `b259154` · Notion محدّث بالكامل · **13,711 Mautic contact** متأكد (live API).

### 🆕 اللي اتعمل في session 2026-06-02 (laptop PUZZLE)
- ✅ **Setup اللابتوب:** SSH للـ VPS (`learnsimply-vps`) + n8n-MCP (v2.56.0، 23 tool)
- 🟢 **W1 LIVE** — WooCommerce → Mautic auto-sync. أي order جديد → contact في Mautic أوتوماتيك. متأكّد end-to-end (Arabic + idempotency + WC validation ping). doc: `02_AUTOMATION/n8n/workflows/W1-wc-mautic-sync.md`، WC webhook ID 7.
- 🟢 **13,709 contact مستوردين** من wp_users (wp-cli over shared-host SSH password → Mautic batch API). tag `wp-import` (+`wc-customer` لـ 89 buyer). صفر إرسال، قابل للتراجع.
- ⚠️ **اكتشاف تخطيطي:** بعد segments rebuild → `engaged_30d=1` (مش 3-5K)، `dormant90d=0`. المستوردين معندهمش تاريخ تفاعل. **الـ activation broadcast لازم يستهدف بالـ tag/recency + warmup ramp، أو re-engagement email الأول** — مش engaged_30d. (راجع `lessons.md` أعلى درس.)
- **NEXT:** Task #4 Arabic RTL email theme → Task #5/#6 broadcast (محتاج قرار استراتيجي من Omar).

### اللي اتعمل في session 2026-06-01

| Task | Real impact |
|---|---|
| **VPS bootstrap** Hostinger KVM4 (187.124.9.249، Ubuntu 24.04، 16GB RAM، 200GB NVMe) | الـ infrastructure للـ Mautic + n8n + AI agents جاهز |
| **SSH key-based auth** + UFW (22/80/443) + fail2ban + root pw rotated | Hardened production VPS |
| **Mautic LIVE** على `https://mautic.learrnsimply.com` (Let's Encrypt SSL، Traefik 3.7) | Marketing automation foundation deployed |
| **MySQL 8.0 → 8.4 LTS** | Mautic requirement satisfied |
| **Mautic SMTP configured** via legacy keys في local.php (NOT mailer_dsn — breaks Mautic 5!) | Email delivery working end-to-end (test verified) |
| **Mautic admin** + Arabic from-name + timezone Cairo | Production-ready config |
| **Research workflow** 116 agents / 8.8M tokens / 22 min | 6 markdown reports في `_research/` |
| **4 critical pivots identified** (n8n needs Postgres+Redis، WhatsApp needs Cloud API not Baileys، SPF/DKIM/DMARC before broadcast، GitHub PAT plaintext) | حفظ من mistakes كبيرة قبل ما تحصل |

### Phase progress (متراكم)
- **Phase 1 Revenue:** 2/7 (1.8 + 1.10 done)
- **Phase 2 Security:** 9/12 (75%)
- **Phase 3 DB/Perf:** 4/22 (18%)
- **🆕 VPS Infrastructure:** Mautic LIVE + **n8n LIVE** (queue mode، postgres+redis، bounded worker) — راجع `02_AUTOMATION/n8n/README.md`
- **🆕 Analytics:** Audit done، GTM container pending
- **Credentials:** 8/10 (Meta Business invitation لسه pending + bunny.net 2FA)

### Mautic Tier 1+2+3 Hardening (completed 2026-06-01 late session)
- ✅ **Redirect loop FIXED** — `trusted_proxies => ['REMOTE_ADDR']` في local.php (كانت لوب لانهائي + UI broken)
- ✅ **Worker bounds applied** — custom supervisord-bounded.conf (email 128M/160s/60msg, hit 128M/300s/200msg, failed 64M/600s/50msg) = OOM-safe
- ✅ **API enabled + Basic Auth** — `omar:<pw>` على `/api/contacts`، `/api/segments`، إلخ
- ✅ **11 Custom Fields** (IDs 44-54): wc_customer_id, course_interest, last_purchase_date, course_count, total_spent, cart_value, last_course_completed, source_channel, telegram_chat_id, whatsapp_phone, referrer
- ✅ **9 Strategic Segments** (IDs 1-9): all_contacts, engaged_30d, dormant_90d, wc_buyers, high_value, non_buyers, active_cart, whatsapp_contacts, telegram_contacts
- ✅ **24 Starter Tags**: lifecycle + behavior + channels + course-* + tier
- ✅ **Week 1 IP Warmup**: 5/min, 50/hour, 200/day (raise بعد 7 أيام clean reputation + DNS verified)
- ✅ **Omar UI Tier 1 COMPLETE** 2026-06-01: DKIM verified (Hostinger auto-publish + Custom DKIM `hostingermail1`), SPF updated (added ip4:187.124.9.249), DMARC clean single record (`p=quarantine`), bounce mailbox `bounces@learrnsimply.com` + IMAP Test=Success in Mautic
- ⏳ **Tier 2 pending** (non-blocking): Mautic UI throttle verify (2 min) + Arabic RTL email theme (15 min)
- ✅ **Auth chain proven via 2 Port25 reports**: SPF=pass, DKIM=pass, iprev=pass — DMARC=pass guaranteed by RFC 7489 (both SPF + DKIM aligned strictly)

### NEXT SESSION P0 (في `HANDOFF.md` بالكامل)
1. ✅ **n8n LIVE** — https://n8n.learrnsimply.com (n8n-MCP loaded في Claude Code، 23 tool available)
2. ✅ **Mautic hardened** — Tier 1+2+3 server-side done. UI work في `OMAR_UI_CHECKLIST.md`
3. 🟡 **Omar action**: نفذ Tier 1 UI checklist (DKIM + DNS + bounce) — يـ unblock أول broadcast
4. ⏳ Install MySQL READ-ONLY MCP (محتاج Hostinger Remote MySQL decision)، GA4 MCP (محتاج OAuth setup)
5. ⏳ Port RS brand patterns (5-stage sales agent + agent CLAUDE.md + hybrid memory + Telegram bot reuse)
6. ⏳ Build W1 n8n workflow: WC → Mautic contact sync (foundation, no AI)

---

## 📦 Legacy CURRENT STATE — 2026-05-24 (Phase 1 partially + Phase 2 67%)

> ده الـ section اللي بيتحدّث مع كل session كبير.

### اللي اتعمل في session 2026-05-24

**9 tasks مدفونين شغلتنا بنجاح، 100% verified (Procedure A + Playwright independent):**

| Task | اللي اتعمل | الـ Real impact |
|---|---|---|
| **1.8** | Sync WC From-Address → contact@learrnsimply.com | UI consistency |
| **1.10** | كوبون JAVA200 expired → draft (reversible) | UX cleanup |
| **2.3** | wp-content/.htaccess deny `.log .sql .bak .sh .dat` etc. | **Closed real exposure** — 7 SQL templates went from 200 → 403 |
| **2.4** | uploads/.htaccess deny PHP execution | **Closed real attack vector** — uploaded files can't execute as PHP |
| **2.5** | حذف debug.log + verify WP_DEBUG=false | Hygiene |
| **2.6** | Disable xmlrpc.php (.htaccess deny) | **Closed brute-force + SSRF amplification** — POST was returning method list |
| **2.9** | Comprehensive uploads audit (30K files scanned) | **0 malicious files** + corrected 4 false positives + found 236MB orphan cache |
| **2.10** | HSTS header (max-age=300; includeSubDomains) | HTTPS enforcement starter |
| **2.11** | Header unset X-Powered-By + .user.ini expose_php=Off | Removes PHP version disclosure |
| **2.12** | chmod 600 wp-config.php | DB creds + auth keys no longer world-readable |
| **3.8 + 3.9** | Disk cleanup: حذف ai1wm-backups (5.6GB) + wpvividbackups (3.8GB) | **+10.1 GB free** — disk من 16GB→5.9GB، quota 80%→29.5% |
| **3.16** | Install + apply Index WP MySQL For Speed v1.5.7 (8 tables: wp_postmeta, wp_usermeta, wp_options, wp_users, wp_comments, wp_commentmeta, wp_termmeta, wp_woocommerce_order_itemmeta) | **DB perf hardened** — compound primary keys على الـ meta tables (Ollie Jones design) = 5-10x أسرع لقرارات `WHERE post_id=X AND meta_key=Y` |
| **3.11** | Autoload cleanup — 243 orphan deletes (Jetpack, AIOSEO, PYS, BWF, DarkMode, PPCP، إلخ) + 213 inactive-plugin toggles + Tier C big-size toggles (CartFlows docs 485 KB، Astra legacy 250 KB) | **DB hot-path lightened** — autoload-positive: 1.18 MB → **0.36 MB (-70%)** + 1483 → 1023 rows (-31%). Every request now loads 70% less wp_options data. |
| **2.1+2.2** | SMTP password rotation via WPMS_SMTP_PASS + WPMS_ON constants in wp-config.php | Compromised SMTP creds no longer valid. Direct SMTP + wp_mail() both VERIFIED working post-rotation. |
| **Analytics Audit** | 4-layer read-only audit (plugins + DB options + frontend HTML + conversion flow) → `03_KNOWLEDGE/analytics-audit-2026-05-24.md` | **Critical finding**: Meta Pixel ID=0, access_token empty = COMPLETELY BROKEN despite plugin "active". TikTok = fully wired (**1.04M EGP / 2398 orders attributed via MAPI**). GA4 G-DT3Z0RSEBK fires PageView only. GTM not installed. `.env` now has 13 TikTok credentials + GA4 ID + diagnostic status fields. |

**Bonus:**
- ✅ Spec Kit formal workflow retrofitted: `/speckit-analyze` + `/speckit-checklist` applied on 001-bug-remediation-90day plan (8 fixes + 34-item security checklist)
- ✅ HANDOFF.md created for cross-device work continuity
- ✅ 3 new lessons added (re-verify active plugin list، long-term over MVP، no room for error)
- ✅ 3 new memory feedbacks (long-term-over-quick-fix، no-room-for-error-safety-first، vertical-slice + per-task-testable-url existing kept)

**Phase 2 progress:** 8/12 done (67%). الباقي: 4 tasks تحتاج Ahmed coordination.
**Phase 3 progress:** 4/22 done (18%). Phase 3d Disk cleanup = 2/3 (3.9b backups-dup-lite + 3.10 webtoffee = pending decision). Phase 3f DB indexes = 2/9 (3.16 + 3.11 done).

**ROI delivered today:**
- 1 real exposure closed (.sql templates)
- 1 brute-force vector closed (xmlrpc)
- 1 attack vector closed (PHP execution in uploads)
- DB creds + auth keys hardened
- HTTPS enforced via HSTS
- **+10.1 GB disk freed** (50.5% quota reclaim — postpones hosting upgrade need)
- **DB perf baseline upgraded** — 8 meta tables now use Ollie Jones compound PKs (5-10x faster meta lookups on critical hot path)
- **Autoload hot-path lightened by 70%** — every WP request now loads 0.36 MB instead of 1.18 MB from wp_options. 12 orphan plugins (Jetpack, AIOSEO, PYS, BWF, DarkMode، إلخ) removed from DB completely.

---

### اللي اتعمل قبل كده (2026-05-23 Wave 2 Deep Audit)

**1. Sprint 1 PR — 7 إصلاحات كود حرجة (جاهزة لـ Ahmed)**
- **PR:** https://github.com/Learrnsimply/edublink-child/pull/1
- **Branch:** `fix/audit-sprint-1`
- شامل: XSS sanitization، تعطيل REST users leak (مؤكد 404)، حذف debug files، إصلاح cart dead code، إصلاح Buy button + view-all link + footer about-me

**2. Backup system live — 3 طبقات**
- Layer 1: Hostinger daily backups (built-in)
- Layer 2: GitHub weekly snapshots — كرون على Hostinger، أول snapshot `2026-W21.sql.gz` (35 MB) في `omarabdo516/learn-simply-backups`
- Layer 3: Theme code في `Learrnsimply/edublink-child`

**3. SSH access ثابت**
- Alias: `ssh learnsimply` · Server: 147.93.73.159:65002 · user u700430280
- WP path: `/home/u700430280/domains/learrnsimply.com/public_html`
- wp-cli 2.12.0 شغّال

**4. Wave 2 Deep Audit — 71 bug جديد عبر 5 مسارات إضافية**
- استغلال الـ SSH + wp-cli + DB direct access لفتح طبقات ما كانتش متاحة في audit الأول
- موزّعين على 5 ملفات جديدة: bugs-runtime, bugs-integrity, bugs-plugins, bugs-perf, bugs-security-deep
- **الإجمالي الجديد: 134 bug** (25 Critical · 42 High · 49 Medium · 17 Low)
- ملخص موحّد + Sprint roadmap في `bugs-report.md`

### 🚨 اللي محتاج فعل فوري من Ahmed (قبل أي شغل تاني)

| البند | السبب |
|---|---|
| 🔴 **Rotate Hostinger SMTP password** (`contact@learrnsimply.com`) | أثناء الـ audit، فحص `wp_mail_smtp` كشف الـ password (base64 plain) — موجود دلوقتي في DB + dump + chat history |
| 🔴 **Merge Sprint 1 PR** | 7 إصلاحات حرجة معطّلة الـ Sprint 2 |
| 🔴 **اقرأ Top 10 في bugs-report.md** | عشان يعرف الـ priorities الـ business-impacting |

### Pending — مرتّب بالأولوية

| البند | الحالة |
|---|---|
| **Sprint 2: Kashier migration** | الـ payment gateway منزّل من GitHub master (مش official) — الـ root cause المحتمل لـ 909 فشل CC ≈ 195K EGP/سنة |
| **Sprint 2: WC From-Address** | حالياً = `ahmedadel123422@gmail.com` (شخصي) — لازم `noreply@learrnsimply.com` |
| **Sprint 2: Cart recovery** | 1645 active WC session بدون أي recovery flow ≈ 150K EGP/أسبوعين متوقع |
| **Sprint 3: .htaccess hardening** | `wp-content/.htaccess` و `wp-content/uploads/.htaccess` الاتنين فاضيين (0 bytes) |
| **Sprint 3: Disable xmlrpc + Install Limit Login + 2FA** | login مفتوح بدون أي rate-limit |
| **Sprint 4: Plugin reduction** | 61 active plugin → ~43 (حذف 4 backup plugins + 3 SVG/Elementor dups + 6 abandoned) |
| **Sprint 4: HPOS enable** | حالياً sync بيشتغل بدون HPOS فعّال = 4567 order مكرر في 2 tables |
| **Sprint 4: DB orphan tables** | 40+ table يتيمة (MailPoet, AIOSEO, BlogVault, WooFunnels) |
| **BUG-003 (duplicate `<title>`)** | المصدر مش في الـ child theme — parent theme أو plugin |

### الأرقام اللي محتاج تعرفها دايماً

| الرقم | القيمة | المصدر |
|---|---|---|
| إيراد شهري | ~67K EGP | WooCommerce API (مارس 2024 لـ مايو 2026) |
| إيراد إجمالي | 1,131,000 EGP في 27 شهر | — |
| نمو 2026 vs 2025 | **+172%** | — |
| Orders ملغية (نزيف!) | **30.2%** — معظمها فشل دفع تقني | — |
| Email subscribers | 13,140 — **صفر email marketing** | — |
| YouTube | **369K مشترك** · 18.5M مشاهدة | عداد About |
| Telegram | 24.4K · تفاعل 60-86% | — |

### أكبر فرص (من Master Action List — محدّث بعد Wave 2)

1. **🔴 Kashier gateway migration** → 909 فشل CC = ~195K EGP/سنة recovery (Wave 2 اكتشاف!)
2. **🔴 Cart recovery على 1645 active session** → ~150K EGP/أسبوعين
3. **🔴 Fix WC From-Address (deliverability)** → من ~15% لـ ~95% email reach
4. **🔴 تنشيط الـ 13K email subscriber** (FluentCRM/Mautic + cart recovery)
5. **🔴 تفعيل Meta Pixel** (الإضافة منصّبة بس مش بتـ fire)
6. **✅ سد ثغرة `/wp-json/wp/v2/users`** — Sprint 1 PR شغّال (مؤكد 404)
7. **🟡 تفعيل LiteSpeed Cache** (قرار: LiteSpeed أم WP-Optimize، مش الاتنين)
