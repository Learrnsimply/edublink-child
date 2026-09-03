# Phase 001 — Launch Unblock · Tasks

> **Progress:** 0 / 7 tasks complete
> **Deadline:** T-106 لازم يحصل **2026-06-15**
> **Order:** Section A و B بالتوازي · Section C تسلسلي بعدهم
> **تصحيح 2026-06-11:** قناة الإرسال (Brevo) محسومة فعلاً — Section A بقى verification واحدة بدل 3 tasks. الـ SES appeal اتنقل لـ Phase 002.

---

## Section A — تأكيد قناة الإرسال (T-101)

### T-101 · تأكيد Brevo جاهز للإطلاق (DNS + test)

**Owner:** Claude
**Depends on:** —

**خلفية:** Brevo = PRIMARY sender من 2026-06-06 (راجع `.env` §22)، مربوط بـ Mautic، والدومين authenticated، ومتأكد قبل كده (Mautic→Brevo→Gmail في 6 ثواني). المهمة دي تأكيد إن النقل/الوقت ماكسرش حاجة — مش إعداد جديد.

**Steps:**
1. فحص live للـ DKIM/SPF بتوع Brevo (dig TXT/CNAME على records اللي في Brevo dashboard)
2. إيميل test من Mautic → inbox Gmail (تأكيد إنه لسه بيوصل inbox مش spam)
3. تأكيد سعة الحساب: Brevo dashboard → الـ daily quota لسه 300 + مفيش suspension

**Accept:**
- [ ] DKIM CNAMEs بتوع Brevo live (dig)
- [ ] إيميل test وصل inbox (screenshot) — landing مش spam
- [ ] Brevo daily limit مؤكد + الحساب active

---

## Section B — Dart Commerce Readiness (T-102 → T-104)

### T-102 · منتج Dart + كوبون DART50 في WooCommerce ⏰

**Owner:** **أحمد** (مهمته على Notion من 2026-06-04) — Omar يتابع
**Depends on:** — · **Deadline: قبل مساء 2026-06-14**

**Steps:**
1. تذكير أحمد (Notion mention + واتساب) يوم 2026-06-12 صباحاً
2. المطلوب منه: منتج Dart بسعر 700 (الخصم بالكوبون) · كوبون DART50 = خصم 50% · صلاحية 48 ساعة من 15 يونيو
3. لو يوم 13 مساءً مفيش منتج → تصعيد + تفعيل قرار Q2

**Accept:**
- [ ] منتج Dart منشور في WC (URL يفتح 200)
- [ ] أوردر test كامل بـ DART50 → السعر النهائي 350 (الأوردر يتلغى بعد التأكيد)
- [ ] W1 زامن الـ test order لـ Mautic (تأكيد الـ pipeline)

---

### T-103 · اعتماد أحمد لإيميلات الإطلاق + توجيه الـ CTAs

**Owner:** Claude (التجهيز) + أحمد (الاعتماد)
**Depends on:** T-102 (لينك صفحة الشراء)

**Steps:**
1. تحديث إيميلات C (إطلاق، id 4) + D (آخر فرصة، id 5): CTA → صفحة شراء Dart الفعلية + كود DART50 ظاهر + التايمر (Sendtric موجود)
2. فك الـ highlights الحمراء (placeholders) اللي استنت قرارات — كلها اتحسمت (350 / DART50 / 15 يونيو)
3. Preview links لأحمد على Notion → موافقة صريحة

**Accept:**
- [ ] كل لينكات الإيميلين تفتح 200 (فحص آلي)
- [ ] أحمد كتب موافقة (Notion comment أو واتساب screenshot)

---

### T-104 · تحديث KB + prompt "عمر" بمعلومات شراء Dart

**Owner:** Claude
**Depends on:** T-102

**Steps:**
1. تحديث `03_KNOWLEDGE/knowledge-base.md` §Dart: السعر 700→350 بكوبون DART50 · لينك الشراء · مدة العرض
2. Prompt جديد (v10) عبر `push_prompt.mjs` — القاعدة: لو حد سأل عن Dart قبل/أثناء العرض يرد باللينك والكود
3. Re-ingest للـ RAG (السكريبت الموجود)

**Accept:**
- [ ] سؤال test على واتساب: "عايز كورس Dart" → يرد بالسعر الصح + اللينك الصح (مش لينك waitlist)
- [ ] prompt v10 = active في `omar.agent_prompts`

---

## Section C — Launch Execution (T-105 → T-107)

### T-105 · Launch prep — تجهيز يوم الإطلاق

**Owner:** Claude + Omar
**Depends on:** T-101 + T-102 + T-103

**Steps:**
1. تحويل صفحة /dart من فورم waitlist → CTA شراء مباشر (نسخة v4 من الـ mu-plugin، الـ rollback = v3 الحالية محفوظة)
2. تحديث شريط الإعلان sitewide: "خصم 50% لمدة 48 ساعة"
3. جدولة إيميل C في Mautic للـ segment 10 (الـ waitlist) عبر Brevo — دفعة test 10 الأول
4. خطة بثّ القنوات المجانية يوم الإطلاق: بوست تليجرام (24.4K!) + كوميونيتي يوتيوب — drafts جاهزة لأحمد ينشرها

**Accept:**
- [ ] /dart v4 جاهزة على staging أو خلف flag (مش live قبل يوم 15)
- [ ] إيميل C scheduled + دفعة الـ test وصلت inbox
- [ ] drafts بوستات تليجرام/يوتيوب متسلمة لأحمد

---

### T-106 · يوم الإطلاق — 2026-06-15 🚀

**Owner:** Omar + Claude
**Depends on:** T-105

**Steps:**
1. صباحاً: نشر /dart v4 + الشريط الجديد + cache purge + فحص Playwright
2. إطلاق إيميل C لكل الـ waitlist (segment 10) عبر Brevo — الـ ~74 جوه حد الـ 300/يوم بسهولة
3. أحمد ينشر بوستات تليجرام + يوتيوب
4. مراقبة: orders في WC + رسايل "عمر" على واتساب + أخطاء n8n
5. بعد 24 ساعة: إيميل D (آخر فرصة) لمن لم يشترِ

**Accept:**
- [ ] إيميل C اتبعت للـ waitlist كله (Mautic sent count = حجم segment 10)
- [ ] أول أوردر حقيقي بـ DART50 اتسجل
- [ ] صفر أخطاء حرجة في n8n error handler خلال يوم الإطلاق

---

### T-107 · تقرير الإطلاق (بعد 48 ساعة)

**Owner:** Claude
**Depends on:** T-106

**Steps:**
1. سحب الأرقام: orders + revenue (WC) · opens/clicks (Mautic) · محادثات Dart على واتساب (Postgres)
2. تقرير قصير لأحمد على Notion بالأرقام + اللي اشتغل واللي لأ
3. إقفال العرض: إزالة الشريط + تحويل /dart لنسخة evergreen (سعر عادي)
4. تسجيل lessons في `lessons.md` + تحديث Phase 002 بدرس deliverability من الإطلاق

**Accept:**
- [ ] تقرير منشور على Notion
- [ ] /dart رجعت evergreen + الشريط اتشال
- [ ] Phase 002 (قناة الـ 13K) محدّثة بنتيجة الإطلاق

---

## 📍 Current task: لسه مفيش — الـ phase مستنية APPROVED من Omar
