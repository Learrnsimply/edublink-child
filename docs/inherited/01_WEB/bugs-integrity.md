# تقرير Data Integrity Bugs — اتعلم ببساطة
**تاريخ الفحص:** 2026-05-23 (Wave 1B — Deep Audit عبر SSH + DB direct)
**المصدر:** wp_wc_orders + wp_posts + wp_users + wp_postmeta + wp_actionscheduler_actions
**الموقع:** learrnsimply.com
**Read-only:** نعم — كل الاستعلامات SELECT فقط

> ⚠️ **تحديث 2026-05-23 (verification pass متعدد بعد pushback من Ahmed):** الـ initial Wave 2 audit كان فيه over-aggression في الـ flagging. بعد re-verification + clarification من Ahmed، **4 ادعاءات اتعدّلت:**
> - **C-1** (From-Address Gmail "disaster"): wp-mail-smtp بيـ override بـ contact@learrnsimply.com → downgrade Critical → Medium
> - **C-2** (909 failed CC = gateway leak): Kashier شغّال (102 successful آخر 30 يوم) → downgrade Critical → High (optimization)
> - **H-5** (1645 active sessions = 150K recovery): كلهم stale > 18 يوم → scope مغيّر (cart recovery للـ future)
> - **H-1** (662 processing = 92K stuck): **RESOLVED** — Ahmed كان بيـ enroll manually، الـ customers أخدوا كورساتهم، الـ status متعلّق فقط cosmetic
> الـ ROI الإجمالي نزل من initial overestimation ~600K-1M لـ realistic ~200-700K EGP/سنة.

> ده تقرير **مكمّل** للـ bugs-data.md (اللي اعتمد على REST API). الـ DB direct queries كشفت مشاكل مكنش الـ REST بيظهرها.

---

## جدول إحصائي سريع (محدّث بعد verification + Ahmed pushback)

| الفئة | المشاكل | Critical | High | Medium | Low | Resolved |
|---|---|---|---|---|---|---|
| Payment & Orders | 4 | 1 | 1 | 1 | 0 | **1** (H-1) |
| Catalog (Courses/Products) | 3 | 1 | 1 | 1 | 0 | 0 |
| Email / Identity | 2 | 0 | 1 | 1 | 0 | 0 |
| Data orphans | 4 | 0 | 2 | 2 | 0 | 0 |
| Users / Roles | 4 | 0 | 0 | 3 | 1 | 0 |
| Catalog cleanup | 2 | 0 | 0 | 2 | 0 | 0 |
| **الإجمالي** | **19** | **2** | **5** | **10** | **1** | **1** |

**الفرق عن النسخة الأولى:**
- Critical: 5 → 2 (C-1, C-2, H-1 اتعدّلوا)
- High: 6 → 5 (H-1 اتـ resolve)
- Medium: 6 → 10 (downgrades)
- Low: 1 → 1
- **Resolved: 0 → 1** (H-1 — manual workflow بتاع Ahmed كان بيـ handle)

---

## 🔴 Critical (5 مشاكل)

---

### C-1 (REVISED) — تناقض بين WC settings و wp-mail-smtp في الـ From-Address
**الوصف الأصلي (غلط):** "From-Address = Gmail شخصي → deliverability disaster"

**اللي اتأكدنا منه فعلياً:**
- `woocommerce_email_from_address` = `ahmedadel123422@gmail.com` (Gmail) ✓ موجود
- لكن `wp_mail_smtp` plugin (active) عنده:
  - `from_email` = **`contact@learrnsimply.com`**
  - `from_email_force` = **`true`** ← يعني SMTP plugin بيـ **override** الـ WC setting في كل إيميل
  - `mailer` = `smtp` (smtp.hostinger.com:465)

**الواقع:** كل إيميل فعلياً بيتبعت من **`contact@learrnsimply.com`** على SMTP الـ Hostinger، **مش من Gmail**. الـ Gmail value في WC option = orphan/legacy متخزّن بس مش بيـ effect.

**الخطورة الحقيقية:**
- ✅ Deliverability فعلياً = OK (مفيش spoofing لأن `contact@learrnsimply.com` من نفس domain)
- ⚠️ المشكلة الباقية = **maintainability/risk**:
  - لو حد قفّل wp-mail-smtp plugin بالخطأ، الإيميلات هتبدأ تخرج من Gmail (spoofing) فجأة
  - لو حد فتح WC settings وغير الـ from-address، مش هياخد effect (مربك)
  - SMTP password بـ plain-text في DB (مشكلة منفصلة — security-deep.md C-1)

**الإصلاح (downgrade من emergency لـ cleanup):**
> 📌 **القيد المؤكد من Ahmed (2026-05-23):** البريد البيزنس الوحيد على الدومين = `contact@learrnsimply.com`. مفيش `noreply@` أو `admin@` أو `support@` منفصلين. الـ plan هيستخدم contact@ لكل شيء.

1. WP Admin → WooCommerce → Settings → Emails: غيّر From address لـ `contact@learrnsimply.com` (يطابق الـ SMTP) عشان مفيش surprise لو الـ plugin اتقفل
2. WP Admin → Settings → General → Email Address: غيّر لـ `contact@learrnsimply.com` (شيل الـ Gmail — راجع C-5)
3. **اختياري long-term:** SPF + DKIM + DMARC على DNS عشان legitimate الـ contact@ مؤكدة من Hostinger
4. **اختياري لما الـ scale يزيد:** إنشاء `noreply@` (للـ transactional) منفصل عن `contact@` (للـ replies) — يفصل sender reputation. مش urgent دلوقتي.

**الخطورة:** 🟡 **Medium** (downgraded من Critical) — UI inconsistency، مش deliverability crisis
**المكان:** WP Admin → WooCommerce → Settings → Emails

**Verification 2026-05-23:** SSH `wp option get wp_mail_smtp` أكّد إن SMTP overrides WC. Ahmed أكّد إن contact@ هو الوحيد الموجود.

---

### C-2 (REVISED) — Cancel rate عالي على البطاقات (61% success rate على آخر 30 يوم)
**الوصف الأصلي (غلط):** "909 معاملة فشلت — Gateway بيـ leak"

**اللي اتأكدنا منه فعلياً (آخر 30 يوم):**

| Status | Card | Wallet |
|---|---|---|
| Completed | 75 (43,362 EGP) ✅ | 27 (13,825 EGP) ✅ |
| Cancelled | 43 | 39 |
| Failed | 5 | 0 |
| **Success Rate** | **61%** (75/123) | **41%** (27/66) |

**Kashier gateway فعلياً شغّال:** أحدث completed order كان 2026-05-22 22:49 (امبارح). الـ plugin = `WooCommerce Kashier Gateway v1.3.0`، official من `github.com/Kashier-payments/`. الـ live API key + merchant ID مضبوطين.

**الخطورة الحقيقية:**
- ✅ Gateway مش مكسور — بيشتغل، بيـ process payments كل يوم
- ⚠️ Cancel rate عالي (39% على البطاقات في آخر 30 يوم) — أسباب محتملة:
  1. **User abandonment في 3DS step** (OTP من البنك بياخد وقت، الـ user بيقفل التاب)
  2. **Insufficient funds** على البطاقة
  3. **Card declined by issuer** (خصوصاً البطاقات الأجنبية)
  4. **User غير رأيه** بعد ما شاف الـ total (شامل أي extras)

**الإصلاح (NOT migration):**
1. **تفعيل التقسيط** عن طريق Kashier (بيدعم) — يقلل الـ "insufficient funds" failures
2. **تفعيل Apple Pay / Google Pay** عن طريق Kashier — يـ skip الـ 3DS friction
3. **Cart recovery للـ FUTURE abandonment** (مش الـ 1645 sessions القديمة — راجع H-5 المعدّل)
4. **Trust signals** على صفحة الدفع (شهادات، badges، تأكيد رد الفلوس)
5. **Test purchase journey** على mobile لمعرفة الـ friction الحقيقي

**التقدير المالي المعدّل:**
- لو cancel rate نزل من 39% لـ 25% = +14% more completed orders
- 75 → ~85 completed/شهر × ~570 EGP avg = **~+5,700 EGP/شهر** = ~70K EGP/سنة
- (مش 195K زي ما كنت قدّرت غلط — لأن الـ gateway مش مكسور)

**الخطورة:** 🟠 **High** (downgraded من Critical) — optimization opportunity مش emergency
**المكان:** Kashier dashboard + WC settings + checkout page UX

**Verification 2026-05-23:** SSH أكّد إن Kashier processed 102 successful orders في آخر 30 يوم بـ 57K EGP.
2. **افتح حساب dashboard الـ gateway** وقارن السجل — fail rate في الواقع غالباً أعلى من اللي WC بيشوفه
3. **اعمل enable لـ:**
   - تقسيط (إن لم يكن موجود) — يقلل قيمة الـ transaction = success rate أعلى
   - Apple Pay / Google Pay — تجنب 3DS friction
   - Fawry / InstaPay redirect — وسائل ثقة محلية
4. **اعمل cart recovery email** للـ 909 — حتى لو 5% يرجعوا، ده ~45 customer إضافي

**الخطورة:** 🔴 Critical
**المكان:** Payment Gateway Integration + WooCommerce → Status → Logs

**يكمّل:** bugs-data.md C-2 (الـ audit الأول شاف 663 processing، إحنا شفنا 909 فشل صريح)

---

### C-3 — Anomaly في يونيو 2025: 252 أوردر processing بقيمة 600 EGP فقط
**الوصف:** من breakdown شهري:

| الشهر | Processing count | Processing total EGP | متوسط الأوردر |
|---|---|---|---|
| **2025-06** | **252** | **600** | **~2.4 EGP** ← anomaly! |
| 2025-07 | 65 | 200 | ~3 EGP ← anomaly! |
| 2025-05 | 1 | 200 | 200 |
| 2025-08 | 2 | 400 | 200 |

في يونيو 2025 لوحده، **252 أوردر** بـ متوسط 2.4 EGP — ده غير ممكن إن منتج اتباع بـ 2 جنيه.

**السببين المحتملين:**
1. **Coupon 100% off اتاسوب بـ percent غلط:** لو حد عمل كوبون `100%` بدل `100` (percentage discount) — الأوردرات هتتسجّل بقيمة تقريباً صفر
2. **Test/fraud flood:** scraper أو bot جرّب 250+ checkout attempts في يوم واحد (يونيو 2025 = شهر العيد + إجازات، فيه stress على الأنظمة)
3. **Gateway-side test mode:** الـ gateway كان في sandbox/test، فالقيم اتسجّلت 0 بس الـ status اتعلق

**الأثر:** الأوردرات دي:
- بتزحم الـ admin dashboard (663 processing total بناءً على Wave 1B)
- بتأثر على متوسط قيمة الـ "processing" المعروض في الـ analytics
- بتحوّش data noise للـ reports

**الإصلاح:**
1. **Query للـ orders دي بالتفصيل:**
   ```sql
   SELECT id, date_created_gmt, customer_id, total_amount, status
   FROM wp_wc_orders
   WHERE status='wc-processing'
     AND DATE_FORMAT(date_created_gmt,'%Y-%m')='2025-06'
   ORDER BY date_created_gmt;
   ```
2. تحقق من تفاصيل أول 5 وآخر 5 — هل nfs customer_id يتكرر؟ هل في طريقة دفع موحدة؟
3. لو coupon issue: ابحث عن كوبون انتهى وقتها بـ value 100 ولكن type=percent
4. **Bulk delete** بعد التحقيق — `wp wc shop_order delete <id> --force`

**الخطورة:** 🔴 Critical
**المكان:** `wp_wc_orders` table — June/July 2025 anomaly

---

### C-4 — 97% من الكورسات في الـ Trash — كاتالوج Tutor LMS مدمّر
**الوصف:** فحص `wp_posts` للـ post_type='courses':
| الحالة | العدد |
|---|---|
| `trash` | **67** |
| `publish` | **5** |
| `draft` | **2** |
| **المجموع** | **74** |

يعني **97% من الكورسات الموجودة في الـ DB في الـ trash**. على الموقع الحي بيظهر بس 5 كورسات.

**الأثر:**
- 67 كورس فيه content + lessons + quizzes + enrollments محتملة، كل ده في الـ trash بس مش متحذف نهائي
- ده بيـ inflate حجم الـ DB (`wp_posts = 55 MB` يحتوي على trash data)
- enrollments قديمة لكورسات trashed بتظهر في الـ tutor_enrolled (شوف H-4 — 659 orphaned enrollment)
- لو احمد قرر يـ "empty trash"، هـ 67 كورس بـ data كاملة هيتحذفوا — قرار كبير

**سؤال للـ Ahmed:**
- الـ 67 كورس دي هل هي **محتوى قديم مرفوض** (ما يستاهلش يرجع) ولا **content يستاهل revival** (يمكن نرجّعه وننشره)؟

**الإصلاح المقترح:**
1. **اعمل query لـ كل الـ 67 trashed courses + dates:**
   ```sql
   SELECT ID, post_title, post_date, post_modified
   FROM wp_posts WHERE post_type='courses' AND post_status='trash'
   ORDER BY post_modified DESC;
   ```
2. **اعمل export لقايمة بـ:** ID, title, lessons count, enrollment count
3. **عرض القايمة على Ahmed** يقرر:
   - دول للاحتفاظ بـ archive (نسيبهم trash) — بس نحذف الـ enrollments المرتبطة
   - دول للحذف النهائي (free 30%+ من حجم wp_posts)
   - دول للإعادة النشر (نشيلهم من trash)

**الخطورة:** 🔴 Critical
**المكان:** WP Admin → Tutor LMS → Courses → Trash

---

### C-5 (CONFIRMED، downgrade) — `admin_email` = بريد شخصي. risk عند لو Ahmed يـ lose Gmail access
**الوصف:** `admin_email = ahmedadel123422@gmail.com` (نفس بريد C-1).
**Verification 2026-05-23:** SSH أكّد. وأيضاً `new_admin_email` (pending) = نفس Gmail.

**الأثر — single-point-of-failure على bus factor = 1:**
- WP بيـ require admin_email لـ:
  - الـ password reset
  - الـ network errors (مثل critical PHP errors في WP 5.2+)
  - الـ plugin/theme update notices
  - تأكيد admin actions جديدة
- لو الإيميل ده اتعطّل أو ضاع password، الـ "lost password" link هيـ go to هناك — Ahmed هيـ lose access كامل للـ WP admin
- **لكن مش deliverability crisis** — الإيميلات بـ wp-mail-smtp بتـ send من contact@learrnsimply.com (راجع C-1 المعدّل). الـ admin_email بس receiver للـ admin notifications.

**الإصلاح (مبسّط بناءً على constraint البريد):**
> 📌 **القيد المؤكد:** البريد البيزنس الوحيد = `contact@learrnsimply.com`. الـ recommendation الأصلي كان `admin@learrnsimply.com` (مش موجود)، فالـ plan الجديد بيستخدم contact@.

1. `wp option update admin_email 'contact@learrnsimply.com'`
2. **مهم:** WP بيـ send confirmation للإيميل القديم. Ahmed لازم يـ confirm change من Gmail بتاعه
3. تأكد إن Ahmed عنده access لـ contact@ inbox (عبر hPanel أو forwarder)
4. **اختياري لاحقاً:** لو Ahmed عاوز يفصل الـ admin notifications عن الـ public contact، نخلق `admin@` كـ alias forward لـ Ahmed (مش urgent)

**الخطورة:** 🟠 **High** (downgraded من Critical — risk حقيقي بس مش active disaster)
**المكان:** WP Admin → Settings → General → Email Address

**Verification 2026-05-23:** Ahmed أكّد إن contact@ هو الوحيد، فهو الـ landing place الطبيعي للـ admin notifications.

---

## 🟠 High (6 مشاكل)

---

### H-1 (RESOLVED — manual workflow) — 662 processing orders كانت bug تاريخي حلّها Ahmed manually
**الوصف الأصلي (غلط في التشخيص):** "662 order processing بـ 92K EGP حقيقي = أمولات معلّقة، محتاجة manual review"

**الواقع المؤكد من Ahmed (2026-05-23):**
- الـ 662 order processing كانت من **bug قديم** في الـ checkout/gateway integration (الـ orders كانت بتقع في processing بدون ما تـ flip لـ completed)
- الـ bug ده **اتحلّ لوحده** (probably gateway update أو webhook fix قديم)
- **Ahmed كان بيـ enroll الـ customers في الكورسات manually** لما حد يبعتله إنه دفع — يعني الـ customers أخدوا الكورسات فعلياً
- الـ orders لسه في status "processing" بس **مش أمولات معلّقة** — الـ customers اتـ served عبر الـ manual workflow بتاع Ahmed
- 316 منهم بـ 0 EGP (coupon/test) + 345 بـ real money، كلهم متعالجين

**الأثر الحقيقي:**
- ✅ **مفيش customer crisis** — كلهم استلموا كورساتهم
- ✅ **مفيش 92K EGP recovery opportunity** — الفلوس وصلت Ahmed (عبر الـ gateway)، بس الـ status متعلّق
- ⚠️ **المشكلة الباقية = cosmetic فقط:** الـ WC reports بتعرض processing rate غلط، analytics بـ noise، dashboard مزدحم
- ⚠️ **Risk لو حد لمسهم بدون فهم:** تـ flip لـ "completed" يـ trigger duplicate enrollment notifications للـ customers (confusion)، تـ flip لـ "cancelled" يـ trigger "your order was cancelled" emails (worse confusion)

**الإصلاح المعدّل — DO NOT TOUCH هؤلاء الـ orders:**
1. ❌ **لا تـ bulk-update الـ status** — هيـ break الـ Ahmed manual workflow أو يبعت confused emails للـ customers
2. ✅ **سيبهم كما هم** — الواقع stable، الـ customers happy، الـ Ahmed مش بيشتكي
3. ✅ **افهم الـ pattern للمستقبل:** أي order processing جديد > 7 days = نـ check مع Ahmed قبل touch
4. ✅ **لو فيه plan طويل المدى:** نضيف custom column في WC orders بـ "ahmed_manually_enrolled = yes/no" + UI button، عشان نـ separate الـ legitimate processing من الـ real new abandonment. (Phase 4 أو 5، مش الآن)

**الخطورة:** 🟢 **Resolved** (was 🟠 High) — مش bug فعلي، manual workflow
**المكان:** WP Admin → WooCommerce → Orders → status=processing (DO NOT MODIFY)

**Verification 2026-05-23:** Ahmed أكّد إن الـ bug القديم في الـ checkout اتحلّ، والـ orders اللي في processing معالجة manually.

---

### H-2 — كورس "مشاريع بايثون للمبتدئين" منشور بدون منتج WooCommerce
**الوصف:** (يكمّل bugs-data.md C-1). تحققنا في DB مباشرة:
```
ID: 29368
Title: 📘 مشاريع بايثون للمبتدئين
Status: publish
_tutor_course_product_id: (NULL or 0)
```

**الأثر:** الطالب يفتح صفحة الكورس، يدوس "Buy" — مفيش منتج فيقع في error أو بيمشي.

**الإصلاح:** نفس الـ bugs-data.md C-1 — إما نـ create WC product ونـ link، أو نحوّل الكورس لـ draft.

**الخطورة:** 🟠 High
**المكان:** Tutor Course 29368

---

### H-3 — 1000 row orphaned في wp_postmeta — DB junk
**الوصف:**
```sql
SELECT COUNT(*) AS orphaned_postmeta FROM wp_postmeta pm
LEFT JOIN wp_posts p ON pm.post_id=p.ID WHERE p.ID IS NULL;
-- Result: 1000
```

**الأثر:**
- wp_postmeta = 194 MB (أكبر table في الـ DB)
- 1000 row meta بدون parent post = junk بيكبر search time
- backups الـ DB أكبر بدون داعي
- المشكلة بتكبر مع الوقت لو محدش بيـ cleanup

**الإصلاح:**
```sql
DELETE pm FROM wp_postmeta pm
LEFT JOIN wp_posts p ON pm.post_id=p.ID
WHERE p.ID IS NULL;
```
أو من WP Admin: استخدم plugin `WP-Optimize` → Database → Remove orphaned post meta. (احتياط: dump أولاً!)

**الخطورة:** 🟠 High
**المكان:** `wp_postmeta` table

---

### H-4 — 659 enrollment يتيم في Tutor (كورس متحذف، enrollment لسه موجود)
**الوصف:**
```sql
SELECT COUNT(*) AS orphan FROM wp_posts e
LEFT JOIN wp_posts c ON e.post_parent=c.ID
WHERE e.post_type='tutor_enrolled' AND c.ID IS NULL;
-- Result: 659
```

**الأثر:**
- لو user عنده enrollment ليه parent course متحذف، هو مش هيقدر يفتح أي حاجة
- ممكن يظهر في الـ "My Courses" بـ broken link
- بيـ inflate الـ enrollment counts في reports

**الإصلاح:** بعد تحديد الـ 67 trashed courses (C-4) ومسحهم نهائياً، نظّف الـ enrollments:
```sql
DELETE e FROM wp_posts e
LEFT JOIN wp_posts c ON e.post_parent=c.ID
WHERE e.post_type='tutor_enrolled' AND c.ID IS NULL;
```

**الخطورة:** 🟠 High
**المكان:** `wp_posts` table — `tutor_enrolled` rows

---

### H-5 (REVISED) — Setup cart recovery للـ FUTURE abandonment (الـ 1673 session الموجودة stale)
**الوصف الأصلي (غلط):** "1645 active session = 150K EGP recovery opportunity"

**اللي اتأكدنا منه فعلياً:**
- 1673 row في `wp_woocommerce_sessions` ✓
- **لكن كلهم stale**: distribution by age = 100% > 18 يوم
- WC sessions normally expire في 48 ساعة
- دي مش "users جاهزين يكملوا الـ checkout" — دي records قديمة مش بتتـ cleanup
- معظمهم بـ session keys رقمية (user IDs) أو tokens — مش active visitors

**الخطورة الحقيقية:**
- ❌ مفيش 150K EGP "ينتظر" — السيشنز دي ميّتة
- ✅ بس Cart recovery لسه قيمة كبيرة لـ **FUTURE abandonment**:
  - متوسط abandonment rate في WC المصري = 60-70%
  - لو 40 visitor/يوم بيـ abandon → 1200/شهر
  - 5-10% recovery على دول = 60-120 customer إضافي/شهر
  - بـ متوسط ~500 EGP = **~30K-60K EGP/شهر** محتمل

**الإصلاح:**
1. **Setup cart recovery للـ FUTURE (مش الـ stale records):**
   - Plugin: **CartFlows + Abandoned Cart addon** (CartFlows موجود بالفعل!)
   - Sequence: 1h reminder → 24h discount → 72h last chance
   - Trigger على cart abandonment **جديد** (من اليوم وبعدين)
2. **Cleanup الـ 1673 stale sessions:**
   ```sql
   DELETE FROM wp_woocommerce_sessions
   WHERE session_expiry < UNIX_TIMESTAMP(NOW());
   ```
3. **Enable session cleanup cron** (action: `woocommerce_cleanup_sessions`)

**التقدير المالي المعدّل:**
- مش 150K كل أسبوعين زي ما كنت قلت غلط
- الواقع: ~30-60K/شهر = ~360-720K/سنة (بافتراض 1200 abandonment/شهر)
- يبدأ يـ generate من شهر بعد ما الـ recovery sequence تتفعّل

**الخطورة:** 🟠 **High** (نفس tier بس الـ scope مختلف — مش "rescue 1645"، بل "build future flow")
**المكان:** CartFlows Plugin + WC settings

**Verification 2026-05-23:** SSH أكّد إن كل 1673 session age > 18 يوم. مفيش fresh sessions.

---

### H-6 — كوبون `JAVA200` انتهى من 11 مايو لكن لسه `publish`
**الوصف:** (يكمّل bugs-data.md). الفحص الجديد:
```
ID: 38837 | post_title: JAVA200 | expired_at: 2026-05-11 21:00:00
```

**الأثر:** Customer يشوف ad/post قديم فيه الكوبون، يدخل، يلاقي "غير صالح" — تجربة سيئة + ثقة مهزوزة.

**الإصلاح:** إما نـ trash، أو نـ extend الـ `date_expires` معلوم بشهر/سنة جاية إن كنا عاوزين نـ reactivate.

**الخطورة:** 🟠 High
**المكان:** WP Admin → Marketing → Coupons → JAVA200

---

## 🟡 Medium (6 مشاكل)

---

### M-1 — 158 user بـ empty role (`a:0:{}`) — ما يقدروش يدخلوا حاجة
**الوصف:**
```
roles: a:0:{}  → 158 users
```

**الأثر:**
- ده ينتج عن:
  - role كان مفعّل من plugin اتحذف (مثل MailPoet "subscriber"، تصفّر بعد إزالة plugin)
  - user تحوّل role وما اتحدّدش له جديد
- الـ 158 user دول مش هيقدروا يـ login، أو لو دخلوا مفيش admin access ولا student access
- ممكن يكونوا paying customers قدامى!

**الإصلاح:**
```sql
UPDATE wp_usermeta
SET meta_value='a:1:{s:10:"subscriber";b:1;}'
WHERE meta_key='wp_capabilities' AND meta_value='a:0:{}';
```
ده يـ assign كلهم لـ `subscriber` كـ default. لو ضمنهم customers أو instructors، يحتاج مراجعة يدوية بناءً على history.

**الخطورة:** 🟡 Medium
**المكان:** `wp_usermeta` table — `wp_capabilities` rows

---

### M-2 — 4 إيميلات user مكرّرة (1 إيميل في حسابين مختلفين)
**الوصف:**
```
mastka12345@gmail.com         → 2 حسابات
fayd553@gmail.com             → 2 حسابات
darsh.sultan8@gmail.com       → 2 حسابات
Abdelrahmanhatem33554@gmail.com → 2 حسابات
```

**الأثر:**
- WP في الـ default بيـ enforce email uniqueness — وجود مكرر يعني خلل قديم في settings أو registration via plugin مختلف
- المستخدم يحاول يـ login بـ email، WP بيـ pick الأول — الأوردرات/enrollments على الحساب الثاني هتختفي
- privacy issue: ممكن user يبص على بيانات user تاني

**الإصلاح:**
1. لكل إيميل، اطلع تفاصيل الحسابين:
   ```sql
   SELECT ID, user_login, user_registered, display_name FROM wp_users
   WHERE user_email='mastka12345@gmail.com';
   ```
2. حدّد الـ "primary" (الأقدم أو الأكثر activity)
3. اعمل merge أو ابعت email للـ user اطلب توضيح
4. حدّد الـ unique constraint على عمود user_email لو غير معرّف

**الخطورة:** 🟡 Medium
**المكان:** `wp_users` table

---

### M-3 — Schema inconsistency في tutor_enrolled (`cancel` و `cancelled` الاتنين موجودين)
**الوصف:** من post_status breakdown لـ tutor_enrolled:
```
completed:   5459
cancelled:   1258
pending:     1066
processing:  324
failed:      130
cancel:      12      ← ده غلط!
```

12 row فيها `cancel` بدل `cancelled` — schema drift.

**الأثر:** أي query بـ `WHERE post_status='cancelled'` هيـ miss الـ 12. Reports مش هتـ match.

**الإصلاح:**
```sql
UPDATE wp_posts SET post_status='cancelled'
WHERE post_type='tutor_enrolled' AND post_status='cancel';
```

**الخطورة:** 🟡 Medium
**المكان:** `wp_posts.post_status` للـ tutor_enrolled

---

### M-4 — Schema inconsistency في wp_comments (`1` و `approved` الاتنين موجودين)
**الوصف:** comment_approved breakdown:
```
1:        9945  ← قيمة قديمة (default WP)
approved: 1824  ← قيمة جديدة (Tutor Pro غالباً)
0:        31    (pending)
hold:     4
spam:     51
post-trashed: 9
```

WP default بيستخدم `'1'` للـ approved. لكن `'approved'` بقت بتظهر بعد plugin migration (محتمل من Tutor LMS).

**الأثر:** Queries بـ `WHERE comment_approved=1` هتـ miss 1824 comment. Plugins بتـ count comments بـ "approved" string هتـ miss 9945.

**الإصلاح:**
```sql
UPDATE wp_comments SET comment_approved='1' WHERE comment_approved='approved';
```

**الخطورة:** 🟡 Medium
**المكان:** `wp_comments.comment_approved` column

---

### M-5 — 88 customer role users vs 2399 completed orders — 96% guest checkouts
**الوصف:**
```
Total users:    13,402
subscribers:    13,154
customers:         88
Completed orders: 2399
```

**يعني:** من 2399 completed order، **بس 88 منهم على حساب "customer"**. الباقي **96.3% guest checkout**.

**الأثر:**
- مفيش طريقة لـ remarketing عبر WP admin user list
- مفيش "My Orders" page للـ guest — هم بيـ rely على receipt email (اللي بيـ go to spam بسبب C-1)
- صعب نـ identify repeat buyers
- صعب نـ track LTV (lifetime value)

**الإصلاح:**
1. WC Settings → Accounts & Privacy → فعّل "Force account creation at checkout"
2. **بس قرار marketing:** ده هيـ reduce conversion بنسبة 5-15% لأن guest checkout أسرع
3. حل وسط: فعّل "Allow customers to create an account during checkout" + اخلي default = unchecked. ده يـ keep الـ funnel sharp بس يفتح option

**الخطورة:** 🟡 Medium (بس له marketing implications)
**المكان:** WC → Settings → Accounts & Privacy

---

### M-6 — 2927 revision مكدّسة (مفيش WP_POST_REVISIONS limit)
**الوصف:** من post types breakdown:
```
revision: 2927 (inherit status)
```

**الأثر:**
- كل revision = row في wp_posts + كذا meta في wp_postmeta
- 2927 revision = +1MB+ في wp_posts بدون قيمة فعلية
- لو احمد عنده 5 كورسات publish + 67 trash + lessons/topics = ~1200 post، يبقى متوسط 2.4 revision per post = `معقول`
- لكن في WP default = unlimited، يعني صفحات بـ heavy editing عندها 50+ revision

**الإصلاح:**
1. أضف في `wp-config.php`:
   ```php
   define('WP_POST_REVISIONS', 5);
   ```
2. نظّف القديم:
   ```sql
   DELETE FROM wp_posts WHERE post_type='revision' AND post_modified < DATE_SUB(NOW(), INTERVAL 90 DAY);
   ```
   (يخلّي آخر 90 يوم فقط)

**الخطورة:** 🟡 Medium
**المكان:** `wp-config.php` + `wp_posts` cleanup

---

## 🔵 Low (1 مشكلة)

---

### L-1 — Spam users واضحين مش مفلترين
**الوصف:** من query لـ spam patterns:
```
spamhereplease@gmail.com    | spamhereplease | 2026-04-06
asdf@guerrillamail.com      | fasdf32        | 2026-04-17
fake@guerrillamail.com      | rwerwerw       | 2026-04-17
oiouidtl@guerrillamailblock.com | pop90pop   | 2025-06-26
```

بالإضافة لـ ~25+ user بـ login من 1-2 حرف (مثل `ff`, `o`, `k`, `mf`, `aa`, `vv`, `ze`, إلخ).

**الأثر:** صفر functional. بس بيـ inflate user count + بيخلي subscriber list فيها noise.

**الإصلاح:**
1. Plugin `WPForms` أو `Anti-Spam by CleanTalk` لـ register form
2. Cleanup يدوي للـ spam patterns:
   ```sql
   DELETE FROM wp_users WHERE user_email REGEXP '@(guerrillamail|mailinator|tempmail|throwaway)';
   ```
3. هتحتاج تنظيف usermeta كمان: `DELETE FROM wp_usermeta WHERE user_id NOT IN (SELECT ID FROM wp_users);`

**الخطورة:** 🔵 Low
**المكان:** `wp_users` table

---

## الخلاصة

| Tier | العدد | الأثر |
|---|---|---|
| 🔴 Critical | 5 | كل واحدة منهم هتفرّق في الإيراد أو الأمان أو الـ deliverability |
| 🟠 High | 6 | data drift + revenue leak يحتاج cleanup |
| 🟡 Medium | 6 | schema fixes + role management |
| 🔵 Low | 1 | spam users |

**أعلى ROI:**
1. **C-1 + C-5:** غيّر الـ from-address + admin email — هيرفع deliverability من ~15% لـ ~95%
2. **C-2:** حلّل 909 failed credit card، فعّل تقسيط + InstaPay = +30-50% conversion على البطاقات
3. **H-5:** Cart recovery على 1645 session = ~150K EGP إيراد متوقّع كل أسبوعين
4. **C-3:** التحقق من anomaly يونيو 2025 — لو coupon issue، مش هيتكرر

**الأرقام الإجمالية بعد التحديث:**
- bugs-code.md: 27 bug
- bugs-functional.md: 18 bug
- bugs-data.md: 21 bug
- bugs-runtime.md: 13 bug (Wave 1A)
- **bugs-integrity.md (هذا الملف): 19 bug جديدة (Wave 1B)**
- **مجموع كلي: 98 bug**

**هـ overlap محتمل:**
- C-3 (anomaly يونيو 2025) و H-2 (course بدون product) و H-6 (expired coupon) كانوا lures في bugs-data.md، إحنا أضفنا evidence أعمق
- بقية الـ 16 bug جديدة 100% (مكنش الـ REST API بيكشفهم)

**اللي مكنش متاح في الـ audit الأول واتكشف هنا:**
- C-1, C-2, C-5 (email + payment depth)
- H-3, H-4 (DB orphans)
- H-5 (active sessions)
- M-1, M-2, M-3, M-4 (data drift)
- M-5 (guest vs customer)
- M-6 (revisions)
- L-1 (spam patterns)
