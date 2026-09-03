# SES Setup Runbook — Mautic Sending Engine for Learn Simply

> **الغرض:** تحويل محرّك إرسال Mautic من **Hostinger SMTP (100/يوم — سقف قاتل)** إلى **Amazon SES** (50K+/يوم، $0.10/1000)، عشان نقدر نبعت كوبون إطلاق Dart للمسجّلين + ننشّط قايمة الـ13K.
> **القرار:** اتعرض على Omar 2026-06-04 → SES (الأرخص + الأقوى + سمعة تخصّنا + تكامل مثالي مع Mautic). راجع [[project_hostinger_smtp_100day_cap]].
> **الحالة (آخر تحديث 2026-06-04 ~15:30):**
> - ✅ حساب AWS **824232274089** (Paid plan) · SES identity + DKIM + MAIL FROM متعملين · IAM user `growthmora-ses` + SMTP creds في `.env`.
> - ✅ **DNS اتلصق في hPanel** (عبر zone import + إصلاحات يدوية) — SPF (مع amazonses) ✓ · 3 DKIM CNAME ✓ · MAIL FROM (MX+TXT) ✓.
> - 🟡 **Production access:** اتطلب → AWS سألت عن الـ use-case → **رددنا في Case `178058147100175`** (عبر الكونسول، أحمد) → ⏳ **بنستنى قرار AWS** (`ProductionAccessEnabled=False` لسه · quota 200/يوم sandbox). الرد على الـ case محفوظ في `_research` لو احتجناه (أو في Gmail thread).
> - ⏳ **DKIM = PENDING** — الـ CNAMEs بتتشاف عالمياً (8.8.8.8)، فهيـ verify لوحده. تابع: `get_email_identity`.
> - ❌ **DMARC مكسور على Hostinger:** لوحتهم بتقطع TXT عند أول `;` (جرّبنا import + يدوي + escaping `\;` — كلهم فشلوا). المنشور حالياً `v=DMARC1` (غير ضار، بيتعامل كأنه مش موجود). **non-blocking** (SES محتاج DKIM مش DMARC). **الحل النضيف لاحقاً = نقل DNS لـ Cloudflare** (مجاني).
>
> **🔜 الـ session الجاية — أول ما `ProductionAccessEnabled=True`:**
> 1. اربط Mautic بـ SES: عدّل `config/local.php` بالـ legacy keys من `.env` (`MAUTIC_SES_SMTP_*`) — مش mailer_dsn. backup الأول (`.bak-pre-ses`). سيب Hostinger SMTP للموقع.
> 2. **Port25 test** (`check-auth@verifier.port25.com`) → أكّد SPF/DKIM=pass (DMARC هيفضل ناقص لحد Cloudflare).
> 3. ابنِ **Mautic Campaign drip** للـ Dart (إيميلات ids 2-5 جاهزة + متزامنة) — توقيت في `campaigns/email-copy-drafts.md`.
> 4. ابعت **إيميل ترحيب (A)** للـ **74+ مسجّل** في segment 10 (tag `dart-waitlist`) + أوتوماتيك لأي جديد.
> 5. warmup ramp. + ذكّر أحمد: **منتج Dart (700) + كوبون DART50** في WooCommerce (blocker لينك الشراء في C/D) + **حذف root key** `[REDACTED]` (أمان).
> **الإطلاق:** 15 يونيو 2026 — فيه buffer كفاية لموافقة AWS (1-3 أيام).

---

## ✅ القيم الحيّة (provisioned 2026-06-04) — دفعة DNS كاملة للّصق في hPanel

> ⚠️ **SPF و DMARC = عدّل الموجود** (مش تضيف تاني — سجلين SPF/DMARC = invalid). الباقي = إضافة جديدة.

| # | Type | Name / Host | Value / Points to | Priority |
|---|---|---|---|---|
| 1 (edit) | TXT | `@` | `v=spf1 include:_spf.mail.hostinger.com include:_spf.reach.hostinger.com include:amazonses.com ~all` | — |
| 2 (edit) | TXT | `_dmarc` | `v=DMARC1; p=quarantine; rua=mailto:dmarc@learrnsimply.com; ruf=mailto:dmarc@learrnsimply.com; fo=1; adkim=s; aspf=s; pct=100` | — |
| 3 (new) | CNAME | `pa3igftkhnammrn3txk5e3ylgttqe3ph._domainkey` | `pa3igftkhnammrn3txk5e3ylgttqe3ph.dkim.amazonses.com` | — |
| 4 (new) | CNAME | `zwp2y76jhlz67cda4ga5ndsg5kkqbj4n._domainkey` | `zwp2y76jhlz67cda4ga5ndsg5kkqbj4n.dkim.amazonses.com` | — |
| 5 (new) | CNAME | `ejvb3la3klj3htnuyfuytdad2klhddiu._domainkey` | `ejvb3la3klj3htnuyfuytdad2klhddiu.dkim.amazonses.com` | — |
| 6 (new) | MX | `mail` | `feedback-smtp.eu-central-1.amazonses.com` | 10 |
| 7 (new) | TXT | `mail` | `v=spf1 include:amazonses.com ~all` | — |

بعد اللصق: `aws sesv2 get-email-identity` لتأكيد DKIM=SUCCESS + MAIL FROM=SUCCESS → ربط Mautic (§2.6) → Port25 test → warmup.

**🔐 أمان:** root access key `[REDACTED]` اتشير في الشات — بعد ما نتأكد الـ IAM user شغّال، أحمد يحذفه (IAM → root user → access keys → delete). الـ IAM user `growthmora-ses` بيغطّي الإرسال.

---

## 0. الحالة الحالية للـ DNS — متأكّدة live (2026-06-04, عبر Google DNS 8.8.8.8)

⚠️ **اكتشاف:** نقل الموقع يوم 4 يونيو **رجّع SPF + DMARC لإعدادات Hostinger الافتراضية** ومسح الـ hardening بتاع 1 يونيو.

| السجل | القيمة الحالية الحيّة | ملاحظة |
|---|---|---|
| **SPF** | `v=spf1 include:_spf.mail.hostinger.com include:_spf.reach.hostinger.com ~all` | ❌ فقد `ip4:187.124.9.249` (VPS) — اترجع للافتراضي بعد النقل |
| **DMARC** | `v=DMARC1; p=none` | ❌ كان `p=quarantine` متشدّد — اترجع للافتراضي |
| **DKIM (Hostinger)** | `hostingermail-a._domainkey` CNAME → `hostingermail-a.dkim.mail.hostinger.com` | ✅ **سليم** — نجا من النقل، بيغطّي إيميلات الموقع |
| **MX** | `mx1.hostinger.com` (5) · `mx2.hostinger.com` (10) | استقبال البريد على Hostinger — متغيّرش |
| **amazonses** | غير موجود | متوقّع — SES لسه |

> **المعنى:** لو بعتنا الـ13K من Mautic دلوقتي على Hostinger SMTP، الوصول هيبقى ضعيف (SPF fail لمصدر الـ VPS + DMARC مش مفعّل). كويس إننا بنتحوّل لـ SES — هنعيد بناء الـ auth صح كجزء من ده. **مفيش حملة تتبعت قبل ما SES يخلص + auth يترجع.**

---

## 1. دور أحمد (مرة واحدة — هو بس اللي يقدر يعمله)

أحمد يفتح حساب AWS ويدّينا وصول. خطوات مبسّطة:

1. يدخل **aws.amazon.com** → **Create an AWS Account** (إيميل + باسوورد + اسم الحساب).
2. يدخل **كارت بنكي** للتفعيل (AWS بتاخد ~$1 رسم تحقّق بيترجع · مفيش اشتراك شهري — بندفع بس على اللي نبعته).
3. تأكيد التليفون (OTP) + اختيار **Basic Support (Free)**.
4. **يدّينا وصول** — أفضل طريقة (آمنة):
   - IAM → Users → **Create user** باسم `omar-growthmora`
   - صلاحية: `AmazonSESFullAccess` (+ console access لو سهّل علينا التفعيل)
   - Create access key → يبعتلنا **Access Key ID + Secret** (في رسالة آمنة).
   - **بديل أبسط لو معقّد عليه:** يدّينا console access مؤقت ونعمل إحنا الـ IAM user بنفسنا.

> دور أحمد بيخلص هنا. كل اللي تحت ده **علينا**.

---

## 2. دورنا — Runbook تقني كامل (نعمله أول ما يوصل الوصول)

### 2.1 اختيار المنطقة (Region)
**`eu-central-1` (Frankfurt)** — قريبة من الـ VPS (EU) + لاتنسي كويسة للجمهور العربي + SES متوفّر فيها. (بدائل: `me-central-1` UAE / `me-south-1` Bahrain لو حبينا أقرب للجمهور.)

### 2.2 تفعيل الدومين في SES (Easy DKIM)
- SES Console → **Verified identities** → Create identity → **Domain** = `learrnsimply.com`
- فعّل **Easy DKIM** (RSA 2048) → SES بيولّد **3 سجلات CNAME** بتوكنز فريدة.
- **(مهم)** فعّل **Custom MAIL FROM** = `mail.learrnsimply.com` → عشان SPF يتوافق (alignment) كمان مش DKIM بس.

### 2.3 سجلات DNS اللي هتتضاف في Hostinger (أنا أجهّزها، Omar أو أحمد يلصقها)

| النوع | الاسم | القيمة | الغرض |
|---|---|---|---|
| **TXT (SPF — تعديل الموجود)** | `@` | `v=spf1 include:_spf.mail.hostinger.com include:_spf.reach.hostinger.com include:amazonses.com ~all` | يضيف SES لمصادر الإرسال المصرّح بيها |
| **CNAME ×3 (SES DKIM)** | `<token1>._domainkey` … | `<token>.dkim.amazonses.com` | تجي من SES بعد إنشاء الـ identity |
| **TXT (DMARC — استرجاع الـ hardening)** | `_dmarc` | `v=DMARC1; p=quarantine; rua=mailto:dmarc@learrnsimply.com; ruf=mailto:dmarc@learrnsimply.com; fo=1; adkim=s; aspf=s; pct=100` | يرجّع الحماية اللي النقل مسحها |
| **MX + SPF للـ MAIL FROM** | `mail` | `feedback-smtp.eu-central-1.amazonses.com` (MX 10) + `v=spf1 include:amazonses.com ~all` (TXT) | للـ Custom MAIL FROM |

> الـ Hostinger DKIM الحالي (`hostingermail-a`) **يفضل زي ما هو** — بيغطّي إيميلات الموقع المعاملاتية.

### 2.4 طلب الخروج من الـ Sandbox (Production Access)
SES بيبدأ في sandbox (200/يوم + إيميلات متحقّقة بس). نطلب production access — النص جاهز في §3 تحت. AWS بترد في **1-3 أيام شغل**.

### 2.5 إنشاء SMTP Credentials
SES Console → **SMTP settings** → Create SMTP credentials → بنحفظ:
- Host: `email-smtp.eu-central-1.amazonaws.com` · Port: `587` (STARTTLS)
- Username + Password (مختلفين عن الـ IAM access key).

### 2.6 ربط SES بـ Mautic (نفس نمط legacy keys — مش mailer_dsn!)
في `config/local.php` جوّه كونتينر `mautic-r4bx-mautic-1` (راجع [[feedback_mautic5_legacy_smtp_keys]] — `mailer_dsn` بيكسر Mautic 5):
```php
'mailer_transport'  => 'smtp',
'mailer_host'       => 'email-smtp.eu-central-1.amazonaws.com',
'mailer_port'       => 587,
'mailer_user'       => '<SES SMTP username>',
'mailer_password'   => '<SES SMTP password>',
'mailer_encryption' => 'tls',   // STARTTLS
'mailer_from_email' => 'contact@learrnsimply.com',
'mailer_from_name'  => 'اتعلم ببساطة',
```
- نعمل backup للـ local.php الأول (`.bak-pre-ses`).
- **Hostinger SMTP يفضل للموقع (WordPress) للإيميلات المعاملاتية** — مش بنلمسه.

### 2.7 التحقّق + الإحماء (Warmup)
1. **Seed test** 5-10 إيميلات بتاعتنا → نتأكد inbox مش spam.
2. **Port25 verifier** (`check-auth@verifier.port25.com`) → نتأكد SPF=pass · DKIM=pass · DMARC=pass.
3. **Warmup ramp** (دومين/IP جديد على SES): 50 → 200 → 500 → 1000+/يوم على مدار أيام، بوابات bounce <3% / complaints <0.1%.
4. نوصّل segment 10 (قايمة Dart) + نجهّز broadcast الكوبون.

---

## 3. نص طلب الـ Production Access (جاهز للّصق)

> **Use case:** Transactional and opt-in marketing email for an online education business (learrnsimply.com). We send: (1) launch/announcement emails to users who explicitly joined a waitlist on our website, and (2) re-engagement emails to existing customers and registered users who created accounts on our platform.
>
> **How recipients signed up:** All recipients are first-party — they registered accounts, purchased courses, or submitted our on-site waitlist form. No purchased or third-party lists.
>
> **Bounce/complaint handling:** We use Mautic (self-hosted) with an IMAP bounce mailbox (bounces@learrnsimply.com) and SES SNS notifications. Bounces and complaints are automatically suppressed; unsubscribe links are in every marketing email (one-click).
>
> **Volume:** Ramping from ~200/day up to ~2,000/day over 2-3 weeks; peak ~13,000 over a launch window. Sending domain authenticated with SPF + DKIM + DMARC (p=quarantine, strict alignment).
>
> **Sending region:** eu-central-1. We will configure a dedicated MAIL FROM subdomain (mail.learrnsimply.com).

---

## 4. خلاصة التقسيم

| المرحلة | مين | الحالة |
|---|---|---|
| فتح حساب AWS + كارت + access key | **أحمد** | ⏳ مطلوب |
| SES identity + DKIM + MAIL FROM | Omar/Claude | جاهز نبدأ فور الوصول |
| سجلات DNS (SPF/DKIM/DMARC) | Omar يلصق في hPanel | محضّرة فوق |
| Production access request | Omar/Claude | النص جاهز §3 |
| ربط Mautic + اختبار + warmup | Omar/Claude | runbook §2.6-2.7 |

---

## ملاحظات
- **التكلفة الفعلية:** 13K إيميل = ~$1.3. مفيش اشتراك شهري. (IP مخصص $25/شهر — لاحقاً لو الحجم استحق، مش دلوقتي.)
- **النقل مسح الـ auth:** أي نقل استضافة مستقبلاً ممكن يرجّع DNS للافتراضي — نراجع SPF/DMARC بعد أي migration. (`.env` §DNS محتاج تحديث للقيم الحقيقية الحالية.)
- بعد ما SES يشتغل: نحدّث `.env` + `01-reengagement-13k.md` (الـ200/يوم القديمة بقت غير صحيحة).
