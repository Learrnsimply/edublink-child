---
name: ls-wrap
description: >-
  إقفال سيشن Learn Simply (اتعلم ببساطة / learrnsimply.com) بأمر واحد — تنظيف المعلومات المضللة من الـ docs،
  ثم git sync لريبو learn-simply بس (omarabdo516/learn-simply)، ثم تجهيز السياق واقتراح /compact.
  شغّلها لما Omar يقول أي من ده —
  صريح: "اقفل السيشن" · "wrap" · "اعمل sync ونظّف" · "جهّزني للـ compact" · /ls-wrap;
  مستتر: "خلصنا النهاردة" · "كفاية" · "ننهي" · "جهّزني أروح" · "حفظ الشغل" · "commit وخلّص".
  NOT هنا — ارفع لـ /sync (global) لو Omar طلب sync للـ workspace كله أو لبراند تاني (rspaac / kitc / dentera / voya).
  بتطبّق قواعد Omar: مفيش حذف بدون إذن per-item، git learning mode، أرشفة (مش مسح) للسياق التاريخي، sync للبراند ده بس.
---

# Learn Simply — Session Wrap (نظّف + sync + جهّز للـ compact)

الغرض: إنهاء سيشن learn-simply نضيف بأمر واحد، من غير ما Omar يعيد شرح الترتيب أو القواعد.
**3 مراحل بالترتيب: 1) نظّف المضلِّل → 2) sync (learn-simply بس) → 3) جهّز للـ compact.**

> ## 🔒 القواعد الذهبية (مش قابلة للتفاوض)
> - **مفيش حذف/تعديل بدون عرض على Omar الأول** — per-item، مش batch. (memory: `feedback_never_delete_without_explicit_permission`)
> - **Sync لبراند learn-simply بس** — الريبو `omarabdo516/learn-simply`. مش الـ workspace كله.
> - **اشرح كل أمر git قبل ما تشغّله** بالعربي المصري (Omar في git learning mode — راجع root CLAUDE.md). للأوامر المدمّرة (`reset --hard`, `clean -fd`, force push) — أوقف واسأل صراحةً.
> - **أرشف، متمسحش** السياق اللي له قيمة تاريخية. شيل/صحّح بس اللي بقى **مضلِّل** (بيقول حاجة غلط النهاردة).

---

## المرحلة 1 — Audit & Clean (شيل المعلومات المضللة)

الهدف: الـ docs تعكس الواقع الحالي بس — من غير لخبطة من حالات قديمة بقت غلط.

### الملفات اللي تفحصها
| الملف | دوّر على |
|---|---|
| `HANDOFF.md` | حالات "pending/blocked/بيتنقل/متوقف" **اتحلّت** · أرقام/مسارات/SSH **قديمة** · تنبيهات **بطلت** · أقسام "أحدث سياق" متعددة (الأقدم يتأرشف) |
| `CLAUDE.md` (brand router) | قسم **CURRENT STATE** قديم · تنبيهات (alias/IP/paths) اتغيّرت · جدول Pending بنود **خلصت** · الأرقام (إيراد/مشتركين) لو اتحدّثت |
| `02_AUTOMATION/agents/` | **الأكتر تغيّراً دلوقتي** — `whatsapp-agent-design.md` · `workflow-quality-study-*.md` · إصدارات prompt الوكيل · workflow IDs / node counts **قديمة** بعد أي تعديل n8n |
| الـ docs اللي اتلمست في السيشن | تناقضات مع الواقع الجديد (statuses، IDs، روابط) |
| جذر البراند | ملفات مؤقتة مش مقصود إبقاؤها (`_tmp_*`, `*.staging`, `*.bak`) |

### الخطوات
1. اقرا الملفات، اعمل **قائمة واحدة** بكل عنصر مضلِّل، بالصيغة دي:
   `[الملف:السطر] — بيقول "X" · الواقع بقى "Y" · الاقتراح: (حدّث / أرشف / احذف)`
2. **اعرض القائمة على Omar. متعملش أي تعديل قبل تأكيده.**
3. لكل عنصر بعد التأكيد:
   - **له قيمة تاريخية** (قرار + سببه، رقم مرجعي قديم) → **انقله لقسم `## 📦 Legacy / أرشيف`** في نفس الملف (مش تمسحه).
   - **مضلِّل بحت** (بيقول حاجة غلط دلوقتي + مفيش قيمة في إبقائه) → صحّحه أو احذفه.
   - **ملف مؤقت** → اعرض المسح، نفّذ بعد التأكيد.
4. **بعد ما Omar يأكّد كل البنود اللي في القائمة** (مش قبل): حدّث **CURRENT STATE** في `CLAUDE.md` + قسم "أحدث سياق" في `HANDOFF.md` بالواقع الحالي. ده تعديل كتابة — يخضع لنفس قاعدة التأكيد per-item بتاعت خطوة 2/3.

> ⚠️ الخطر الأساسي = مسح سياق صح بالغلط. لو شاكك → **أرشف، متمسحش**، واسأل.

---

## المرحلة 2 — Sync (git — learn-simply repo بس · commits منظّمة)

> ⚠️ براند learn-simply **بس**. الريبو: `omarabdo516/learn-simply` (Omar owner → push مباشر مقبول). مش الـ workspace.

### 🎯 المبدأ: commits منظّمة (atomic) — مش commit واحد بكل حاجة
كل commit = **تغيير واحد متماسك** (concern واحد). كده تاريخ الريبو يبقى مقروء، وتقدر تتراجع عن أي جزء لوحده من غير ما تلخبط الباقي.

1. `git status` + `git diff --stat` — افهم كل التغييرات (معدّلة + untracked). **لو الشجرة نضيفة (مفيش تغييرات):** أخبر Omar إن مفيش حاجة للـ commit وامشي على طول للمرحلة 3 — متعملش commits فاضية.
2. **جمّع التغييرات في commits منطقية حسب الـ scope/concern.** التجميع المعتاد في learn-simply:
   | scope | بيشمل | نوع الـ commit الغالب |
   |---|---|---|
   | `01_WEB` | popup, website, mu-plugins, _evidence, bugs/audits | `feat` / `fix` / `docs` |
   | `02_AUTOMATION` | n8n workflows, mautic, agents, backups | `feat` / `fix` / `docs` |
   | `03_KNOWLEDGE` | KB, client-context, audits, GTM | `docs` |
   | جذر البراند | `.env`, `HANDOFF.md`, `CLAUDE.md`, `lessons.md` | `chore` / `docs` |
   - **متخلطش concerns مختلفة في commit واحد** (مثلاً: كود الـ popup ≠ تحديث `.env` ≠ تنظيف docs = 3 commits منفصلة).
   - **رتّب منطقياً:** الكود/الميزة الأول → التوثيق بعده → الـ config/cleanup في الآخر.
3. **اعرض خطة الـ commits على Omar قبل أي تنفيذ** — قائمة مرقّمة: لكل commit (الرسالة + الملفات اللي جواه). استنى تأكيده.
4. **نبّه على `.env`:** فيها secrets، بس الريبو **private** + cross-device sync → مقبول هنا (root CLAUDE.md). الـ `.env` بيتعمله commit كل سيشن بالتصميم — فمتطلعش تنبيه rotate لمجرد وجوده. التنبيه الحقيقي بس **لو اتولّد secret جديد في السيشن دي** (API key / webhook token / SMTP password) **أو ظهر secret في الـ chat** → **ذكّر Omar يـ rotate** كبند متابعة.
5. نفّذ الـ commits **واحد-واحد بالترتيب**: لكل واحد → `git add <ملفات الـ commit ده بالظبط>` ثم `git commit -m "<type>(<scope>): <وصف>"`.
   - conventional commits: `feat / fix / docs / chore / refactor / perf`. الوصف بالعربي مقبول.
   - **اشرح كل أمر git بالعربي قبل ما تشغّله** (learning mode). للمدمّر (`reset --hard`/force) أوقف واسأل.
6. `git push` **مرة واحدة** بعد ما كل الـ commits تخلص.
7. أكّد النجاح + اعرض قائمة الـ commit hashes (commit لكل concern).

> لو أي submodule اتغيّر pointer-ه — **اعرض على Omar قبل** ما تـ bump:
> - `01_WEB/website` (theme repo `ahmedlearnSimply/edublink-child` — owner Ahmed بـ PR workflow).
> - `02_AUTOMATION/backups` (`omarabdo516/learn-simply-backups` — ريبو منفصل، متضمّنش bump-ه في commit عادي بصمت).

---

## المرحلة 3 — Context boundary (تجهيز للـ /compact)

1. اتأكد `HANDOFF.md` فيه قسم "أحدث سياق" **نضيف** يلخّص السيشن — ده اللي بيـ **survive** الـ compact ويبقى نقطة الدخول للسيشن/الجهاز الجاي.
2. اقترح على Omar `/compact` برسالة summary جاهزة من واقع السيشن:
   > `/compact حالة learn-simply: <أهم إنجاز اتعمل>. الجاي: <أهم 2-3 خطوات>.`
3. **الـ skill مش بتعمل `/compact` بنفسها** — ده أمر Omar بيعمله بنفسه. إنت بتجهّز وتقترح بس.

---

## ليه الترتيب ده (للفهم)
- **نظّف قبل sync** → الـ commit يبقى نضيف، مش بيحفظ معلومات مضللة في تاريخ Git.
- **sync قبل compact** → الشغل يتحفظ على GitHub، وده بيـ survive الـ compact (المحادثة نفسها بتتلخّص).
- **HANDOFF محدّث قبل compact** → السيشن الجاية تبدأ من سياق صح، مش من تلخيص ناقص.

## مصادر مرتبطة
- root `CLAUDE.md` → cross-device sync + git learning mode + secrets policy
- `HANDOFF.md` → نقطة الدخول الأساسية للسياق
- `/sync` (global command) → النسخة الـ workspace-wide (ده البديل المحلي المحصور في learn-simply)
