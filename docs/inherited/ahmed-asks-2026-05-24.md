# رسالة لـ Ahmed — 2026-05-24

> **هذا الملف للإستخدام كـ draft.** انسخ المحتوى وابعته على Telegram/WhatsApp/Email.
> بعد ما Ahmed يجاوب على كل بند، احذف الملف ده.

---

## 🎯 الـ Update الإجمالي

أحمد، النهارده خلّصت **9 إصلاحات أمنية مهمة على الموقع** كلها متختبرة + verified:

### اللي اتعمل:
1. ✅ شيل debug.log المتسرّب
2. ✅ سد ثغرة PHP execution في uploads (لو حد ضحى ملف خبيث، مش هيشتغل دلوقتي)
3. ✅ قفل xmlrpc اللي كان مفتوح وبيعرض methods كاملة لأي حد (brute-force vector)
4. ✅ شيل ظهور PHP version في الـ headers
5. ✅ تأمين wp-config.php من أي حد على الـ shared hosting يقرأ DB password
6. ✅ HSTS header — يجبر المتصفحات تستخدم HTTPS فقط
7. ✅ تأمين wp-content من الـ direct access للـ .log .sql .bak files
8. ✅ تنظيف WC From-Address (من Gmail شخصي → contact@learrnsimply.com)
9. ✅ Audit شامل للـ 30 ألف ملف في uploads — **مفيش حاجة خبيثة، الموقع نضيف** 💯

كل تغيير عنده **rollback command** documented + **screenshot evidence** من Chromium (مش بس curl).

الـ Phase 2 (Security) دلوقتي **67% خلصت**.

---

## 🚨 محتاج منك دلوقتي (مرتّب بالأولوية)

### 🔴 P0 — سيكيوريتي حرج (دلوقتي)

**1. غيّر باسوورد `contact@learrnsimply.com` من Hostinger hPanel**
- **السبب:** خلال الـ audit، الـ password اتطلع plain-text من DB (موجود حالياً في chat history + DB dump). أي حد عنده DB access ممكن يستخرجه.
- **اللي محتاج تعمله:**
  1. روح Hostinger hPanel → Emails → contact@learrnsimply.com → Change Password
  2. خد الباسوورد الجديد وابعتهولي (Telegram/WhatsApp)
  3. أنا هـ update الـ wp-mail-smtp في WP بنفس اليوم
- **الوقت:** 5 دقايق منك

**2. فعّل Two-Factor Authentication على حساب admin بتاعك**
- **السبب:** لو password بتاعك اتسرّب، الـ 2FA يمنع الـ login حتى بـ password
- **اللي محتاج تعمله:**
  1. أنا هـ install plugin اسمه Two-Factor (مجاني، WordPress official)
  2. أبعتلك link المتابعة
  3. تدوس enable على حسابك + تحفظ الـ backup codes في مكان آمن (password manager)
- **الوقت:** 10 دقايق منك مع مشاركتي

### 🟡 P1 — قرارات Business (هذا الأسبوع)

**3. سعر كورس Python (Course 29368)**
- **السبب:** الكورس موجود في Tutor LMS لكن مفيش له WC product مربوط — يعني **مش بيـ بيع** دلوقتي
- **اللي محتاج تعمله:** قول لي السعر بالجنيه، أنا هـ create الـ WC product + ربطه في ساعة
- **الوقت:** دقيقة منك

**4. مراجعة الـ 67 كورس في الـ trash**
- **السبب:** في 67 كورس attached للـ trash. مش واضح إذا كانوا كورسات قديمة عايز ترجعها أم demo content من شراء الـ theme
- **اللي محتاج تعمله:** هـ ابعتلك CSV file فيه: ID + اسم + تاريخ + عدد الـ lessons. اعمل label لكل واحد: `restore` أو `delete forever`
- **الوقت:** 30 دقيقة منك (راجع الـ list + label)

**5. قرار Meta Pixel verification access**
- **السبب:** الـ Pixel منزّل بس مش متأكدين الـ events بتـ fire صح. لو شغّلت إعلانات Facebook، **مش هتعرف فعلاً مين اشترى من الإعلان** = ضياع ميزانية إعلانات
- **اللي محتاج تعمله:** ضيفني (Omar) في Facebook Business Manager بـ role "Analyst" أو "Editor" لـ pixel فقط
- **الوقت:** 5 دقايق منك

### 🟢 P2 — قرار Strategic (مش مستعجل)

**6. قرار Re-Consent للـ 13 ألف email subscriber**
- **السبب:** عندك 13K زبون مسجّل بـ email بس مش متأكدين هم عملوا opt-in رسمي لـ marketing emails. قبل ما نـ بدأ campaigns، لازم نقرر:
- **الخيارات:**
  - **A — Soft opt-in:** نـ بعت email واحد "اشترك تاني عشان تستلم تحديثات الكورسات الجديدة" → متوقع 5-15% يـ confirm، الباقي يـ unsubscribe (legal compliant + clean list)
  - **B — No marketing:** نـ ignore الـ 13K ونـ build قائمة جديدة من نشطين فقط (مع cart recovery + opt-in forms)
  - **C — Aggressive:** نـ بعتلهم direct (مخاطر spam complaints + reputation damage)
- **توصيتي:** A (clean + safe)
- **الوقت:** قرار 5 دقايق منك، التنفيذ بعد ما VPS يجي (Phase 1.5 — Mautic deployment)

### 🔵 P3 — Confirmation فقط (دقيقة)

**7. تأكيد تغيير admin_email**
- **السبب:** الـ admin_email في WordPress لسه Gmail شخصي بتاعك. لو فقدت الـ Gmail ده، فقدت admin access في الـ recovery
- **اللي هيحصل:** أنا هـ change-ه لـ contact@learrnsimply.com. WordPress هيبعتلك confirmation email على Gmail القديم
- **اللي محتاج تعمله:** افتح Gmail، دور على email عنوانه "Email Change Request"، دوس confirm
- **الوقت:** دقيقة

---

## 📋 خلاصة الـ asks

| رقم | البند | المدة منك | الأولوية |
|---|---|---|---|
| 1 | SMTP password rotation | 5 دقايق | 🔴 P0 |
| 2 | 2FA setup مع Omar | 10 دقايق | 🔴 P0 |
| 3 | Course 29368 سعر | دقيقة | 🟡 P1 |
| 4 | 67 trashed courses review | 30 دقيقة | 🟡 P1 |
| 5 | FB Business Manager access | 5 دقايق | 🟡 P1 |
| 6 | Re-consent decision (decision only) | 5 دقايق | 🟢 P2 |
| 7 | admin_email confirmation | دقيقة | 🔵 P3 |

**الإجمالي:** ~57 دقيقة منك على مدار الأسبوع

لما تخلّص أي بند، قول لي، أنا هـ كمل تنفيذ. بعد ما الـ P0+P1 يخلصوا، Phase 2 الـ Security بتاع الموقع يبقى 100% done.

شكراً 🙏

---

## 📦 Bonus — اللي عملته في الـ background (مش محتاج تعمل حاجة)

- Workspace organization: brand اتقسم لـ 3 teams (Web + Automation + Knowledge) مطابقة لـ rspaac pattern
- Private GitHub repo `omarabdo516/learn-simply` (للـ workspace فقط — مش هتلمسه)
- Backup system 3 layers شغّال (Hostinger daily + GitHub weekly snapshot + theme git)
- UI Audit tool (Playwright) جاهز عشان أي تعديل CSS مستقبلاً نـ catch regressions تلقائياً
