# 🚀 Deploy — "Omar" agent VPS stack

> **الحالة:** 🟢 **BUILT** (آخر تحديث 2026-06-10). الـ 3 services شغّالين + **instance `omar-support` متعمل + webhook متظبط على n8n + الـ 3 workflows مبنيين والـ prompt محقون**. الباقي للـ go-live: Gemini key (Omar) + QR-scan رقم الدعم `01030127228` + WC read-only key + RAG ingest + تفعيل W3.
> **المكان على الـ VPS:** `/docker/omar-agent/` (187.124.9.249). **الـ IDs كلها في brand `.env` §20.**
>
> **🆕 Refactor 2026-06-10 (بعد deep-research على rs-aios + الويب):** W3 بقى 27 نود — ack فوري (onReceived) + debounce 8 ثواني/تجميع رسايل + mark-as-read/typing + تأخير بشري في الإرسال + **prompt من `omar.agent_prompts` (DB، إصدارات)** + hybrid memory (session bucket 48h + بروفايل) + fallback model + clean reply (فخ maxIterations) + إصلاح تجاهل الصوتيات + **W3d error handler → Telegram** متربط على الكل. التقرير الكامل: `../workflow-quality-study-2026-06-10.md`.
>
> **أداة الـ prompt:** أي تعديل على `../omar-system-prompt.md` → `node push_prompt.mjs "ملاحظة التغيير"` — بيرفع **إصدار جديد** في `omar.agent_prompts` ويأرشف القديم، والوكيل بياخده من أول رسالة جاية (مفيش redeploy). (حلّ محل inject_prompt.mjs القديم.)

---

## اللي اتنشر

| Service | Image | الدور | الحالة |
|---|---|---|---|
| `omar-pgvector` | `pgvector/pgvector:pg16` | ذاكرة العميل + RAG (DB `omar_agent`) + DB `evolution` | 🟢 healthy، schema applied، pgvector 0.8.2 |
| `omar-redis` | `redis:7-alpine` | كاش Evolution (معزول عن redis بتاع n8n) | 🟢 healthy |
| `omar-evolution` | `atendai/evolution-api:v2.2.3` | بوابة واتساب (Evolution API) | 🟢 running، API 200، 31 Prisma table، Redis ready |

**الموارد:** ~130MB إجمالي (pgvector 44MB · redis 3MB · evolution 83MB). الـ VPS عنده 13Gi فاضية.

---

## المعمارية (الشبكات — الحيلة المهمة)

```
n8n_default (شبكة n8n الموجودة)            omar_default (الشبكة الجديدة)
├── n8n-n8n-1, worker, postgres, redis     ├── omar-redis
└── omar-pgvector  ◄──┐                     ├── omar-pgvector  (على الشبكتين)
    omar-evolution ◄──┘ (الاتنين على        └── omar-evolution (على الشبكتين)
                         الشبكتين)
```

- **`omar-pgvector` + `omar-evolution` متوصّلين بـ `n8n_default` (external)** → n8n بيكلّمهم بالاسم **بدون أي تعديل/restart على n8n**. (متحقّق: n8n→pgvector:5432 ✓ · n8n→evolution:8080 ✓.)
- **درس:** استخدم **container names الفريدة** (`omar-redis`, `omar-pgvector`, `omar-evolution`) في أي URI داخلي — لأن `redis`/`postgres` كأسماء services **بتتعارض** مع stack الـ n8n على الشبكة المشتركة. (ده كان سبب لوب أخطاء Redis ساعة النشر، واتصلّح.)

---

## تفاصيل الاتصال (للـ n8n credentials لاحقاً)

| الوجهة | من جوه n8n | تفاصيل |
|---|---|---|
| **Postgres (agent)** | host `omar-pgvector` (أو `pgvector`)، port `5432` | db `omar_agent` · user `omar_agent` · pw في VPS `.env` (`OMAR_PG_PASSWORD`) |
| **Evolution (API)** | `http://omar-evolution:8080` | header `apikey: <EVOLUTION_API_KEY>` (في VPS `.env`) |
| **Evolution (عام/إدارة)** | `https://evolution.learrnsimply.com/manager` | ⏳ محتاج DNS (تحت) |
| **Redis** | `omar-redis:6379` | لـ Evolution بس — n8n عنده redis خاص |

### 🔑 الأسرار
كلها في **`/docker/omar-agent/.env` على الـ VPS** (chmod 600، **مش في git**): `OMAR_PG_PASSWORD` · `OMAR_REDIS_PASSWORD` · `EVOLUTION_API_KEY`.
للاسترجاع وقت الحاجة: `ssh learnsimply-vps "cat /docker/omar-agent/.env"`.

---

## ⏳ متطلبات قبل الـ go-live

1. **🌐 DNS:** ضيف A record **`evolution.learrnsimply.com` → `187.124.9.249`** (نفس IP الـ VPS بتاع n8n/Mautic) في Hostinger DNS. من غيره: واجهة الإدارة العامة + شهادة Let's Encrypt مش هيشتغلوا (التكامل الداخلي n8n↔evolution شغّال من غيره).
2. **📱 رقمين واتساب من أحمد:** رقم المساعد (اللي Evolution هيربط بيه) + رقم أحمد للإنذار.
3. **🔒 قرار الرقم + خطر الحظر** (تحت).

---

## ⚠️ خطر الحظر (Evolution = Baileys)

Evolution API بيستخدم **Baileys** (بروتوكول واتساب غير الرسمي). **الرقم المتربط معرّض للحظر** من واتساب. توصية:
- **رقم مخصّص جديد** (مش رقم أحمد الشخصي ولا رقم الدعم الحالي `wa.link/hdomyx`).
- **inbound فقط** (القرار المعماري) — مفيش بث/outbound.
- **warmup** تدريجي + متابعة.

البديل الرسمي (مفيش خطر حظر، بس setup أعقد + رقم منفصل) = **Meta WhatsApp Cloud API**. القرار لأحمد/عمر.

---

## الخطوات الجاية (go-live checklist — محدّث 2026-06-10)

> ✅ **اتعمل بالفعل (2026-06-10):** instance `omar-support` + webhook → n8n + groupsIgnore · الـ 3 workflows (W3/W3b/W3c) مبنيين + متحقّق منهم · الـ prompt v0.2 محقون · كل الـ SQL متاختبر في rollback transactions · kb-search متاختبر live.

1. **Gemini API key (Omar):** aistudio.google.com → Get API key → حطه في brand `.env` (`GEMINI_API_KEY`) + حدّث n8n credential `ZeYvAf59LZGqkIbU`.
2. **RAG ingest:** شغّل `../rag/ingest_kb.py --with-curriculum` (محتاج المفتاح + SSH tunnel للـ DB أو تشغيل على الـ VPS) → يملأ `omar.kb_chunks`.
3. **WooCommerce read-only key:** wp-admin → WooCommerce → Settings → Advanced → REST API → Add key (**Read**) → حدّث n8n credential `5k7PIPwao0Vkoczb` + `.env`.
4. **اربط الرقم (مع أحمد — الموبايل اللي عليه `01030127228`):** الـ QR يتجاب من الـ API (مش محتاج DNS):
   ```bash
   ssh learnsimply-vps 'KEY=$(grep ^EVOLUTION_API_KEY= [REDACTED] -d= -f2); docker exec omar-evolution wget -qO- --header="apikey: $KEY" http://localhost:8080/instance/connect/omar-support'
   ```
   بيرجّع `base64` (صورة QR) — احفظها وامسحها بالموبايل خلال ~40 ثانية (أعد الأمر لو انتهت). بديل: DNS `evolution.learrnsimply.com` → `187.124.9.249` وافتح `/manager`.
5. **اختبار e2e برقم تجريبي** (A سعر · F2 access · F5 استرجاع · E كود · dedup · escalate) → نضّف صفوف الاختبار.
6. **فعّل W3 `omar-inbound`** (toggle واحد — `ESYkoJgz0e4ngMrM`). W3b مش محتاج تفعيل (sub-workflow) و W3c مفعّل بالفعل.

---

## أوامر تشغيلية

```bash
# الحالة + اللوجز
ssh learnsimply-vps "cd /docker/omar-agent && docker compose ps"
ssh learnsimply-vps "docker logs --tail 50 omar-evolution"

# restart / تحديث
ssh learnsimply-vps "cd /docker/omar-agent && docker compose up -d"

# اختبار Postgres
ssh learnsimply-vps "docker exec omar-pgvector psql -U omar_agent -d omar_agent -c '\\dt omar.*'"

# اختبار Evolution API
ssh learnsimply-vps 'KEY=$(grep ^EVOLUTION_API_KEY= [REDACTED] -d= -f2); docker exec omar-evolution wget -qO- --header="apikey: $KEY" http://localhost:8080/'
```

> **متعملش `docker compose down -v`** — الـ `-v` بيمسح الـ volumes (الذاكرة + instances). للإيقاف المؤقت: `docker compose stop`.
