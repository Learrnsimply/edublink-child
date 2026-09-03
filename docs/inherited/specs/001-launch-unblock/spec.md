# Phase 001 — Launch Unblock · Spec

> **Phase:** 001 (فك بلوكرز الإطلاق: قناة الإرسال + منتج Dart + إطلاق 15 يونيو)
> **Duration:** أيام — deadline صلب **2026-06-15** (إطلاق Dart)
> **Depends on:** كل الـ infrastructure الجاهزة (راجع ROADMAP §credit)
> **Blocks:** Phase 002 (Email Engine) — مفيش broadcasts من غير قناة إرسال محسومة

---

## 1. User Story

**As** Omar (GTM Engineer)،
**I want** قناة إرسال إيميل شغالة + منتج Dart قابل للشراء + حملة الإطلاق منشورة في معادها،
**so that** الـ 74+ waitlist يتحولوا لمبيعات يوم 15 يونيو، والـ 13K contact يبقوا قابلين للوصول بعدها.

---

## 2. Problem Statement

كل البنية جاهزة (Mautic + قناة إرسال + إيميلات مكتوبة + landing page + waitlist). **قناة الإرسال محسومة فعلاً:** Brevo هو الـ PRIMARY sender من 2026-06-06، مربوط بـ Mautic ومتأكد (Mautic→Brevo→Gmail inbox في 6 ثواني)، free 300/يوم — يغطي الـ waitlist (~74-100) بسهولة. (التصحيح: SES مش قناتنا الأساسية ورفض الـ production access 2026-06-08 **مش بيمنع الإطلاق** — بقى تحسين سعة مؤجل لـ Phase 002 للـ 13K.)

اللي فعلاً واقف الإطلاق **بلوكرين بس**:

1. **منتج Dart مش موجود في WooCommerce** — مفيش حاجة تتباع حتى لو الإيميلات اتبعتت. (أحمد)
2. **إيميلات الحملة (ids 2-5) drafts** مستنية موافقة أحمد + توجيه CTA لصفحة الشراء.

كل يوم تأخير بعد 15 يونيو = حرق momentum الـ waitlist (≈5-10K EGP/يوم متوقع ضايع).

---

## 3. Success Criteria

- [ ] قناة الإرسال (Brevo) متأكدة جاهزة للإطلاق: DNS سليم بعد النقل + إيميل test يوصل inbox (مش spam)
- [ ] منتج Dart live في WC بسعر 350 + كوبون DART50 شغال (اختبار شراء فعلي)
- [ ] إيميلات الإطلاق (C+D) معتمدة من أحمد + CTA بيوجه لصفحة الشراء الصح
- [ ] إيميل الإطلاق وصل لكل الـ waitlist يوم 15 يونيو (داخل حدود القناة المتاحة)
- [ ] مساعد واتساب "عمر" عارف إن Dart بقى متاح للشراء (prompt محدّث)
- [ ] صفحة /dart اتحولت من "سجّل اهتمامك" لـ "اشتري دلوقتي" يوم الإطلاق

---

## 4. User Journeys

### Journey A: واحد من الـ waitlist يوم الإطلاق
1. يستلم إيميل "كورس Dart اتفتح — خصم 50% لمدة 48 ساعة" (كود DART50)
2. يضغط CTA → صفحة شراء Dart في WC
3. يدفع بـ Kashier (أو يدوي فودافون/إنستاباي) → يتسجل في الكورس
4. W1 يزامنه لـ Mautic كـ buyer أوتوماتيك

### Journey B: زائر جديد بعد الإطلاق
1. يدخل /dart → الصفحة بقت بتعرض الشراء المباشر بدل الفورم
2. نفس مسار الدفع

### Journey C: عميل يسأل "عمر" على واتساب عن Dart
1. يسأل عن الكورس → "عمر" يرد بالسعر الصح + لينك الشراء (مش لينك الـ waitlist القديم)

---

## 5. Scope

### IN scope:
- ✅ تأكيد جاهزية Brevo للإطلاق (DNS + test) — مش بناء، بس verify
- ✅ متابعة أحمد لإنشاء منتج Dart + الكوبون
- ✅ تجهيز ونشر إيميلات الإطلاق للـ waitlist
- ✅ تحديث /dart + شريط الإعلان + prompt "عمر" يوم الإطلاق
- ✅ قياس الإطلاق (orders / revenue / opens)

### OUT of scope (deferred):
- ❌ broadcast الـ 13K (إعادة التعريف) → **Phase 002** (Brevo free = 43 يوم؛ والـ SES appeal بالتوازي لسعة أرخص)
- ❌ SES production-access appeal → **Phase 002** (تحسين سعة، مش بلوكر إطلاق)
- ❌ welcome/cart-abandonment sequences → Phase 002
- ❌ يوتيوب CTAs → Phase 003
- ❌ rotate W2 token → Phase 002 (بعد الإطلاق زي ما اتقرر)

---

## 6. Functional Requirements

| FR-ID | Requirement | Acceptance |
|-------|-------------|------------|
| FR-01 | قناة الإرسال (Brevo، 300/يوم) تغطي الـ waitlist في يوم واحد | إيميل test يوصل inbox (مش spam) من Brevo عبر Mautic |
| FR-02 | منتج Dart قابل للشراء بـ 350 EGP | أوردر test كامل ينجح |
| FR-03 | كوبون DART50 يطبق الخصم الصح | تطبيق الكوبون في checkout يظهر السعر النهائي الصح |
| FR-04 | إيميلات C+D فيها لينك الشراء + التايمر + كود الكوبون | مراجعة بصرية + كل اللينكات تفتح 200 |
| FR-05 | "عمر" يرد بمعلومات Dart المحدثة | سؤال test على واتساب يرجع السعر واللينك الصح |
| FR-06 | تتبع مبيعات الإطلاق | تقرير بعد 48 ساعة: orders + revenue من utm/coupon |

---

## 7. Non-Functional Requirements

- **Deliverability:** SPF/DKIM بتوع Brevo سليمين قبل أي إرسال (الدومين authenticated في Brevo 2026-06-06 — re-verify إن النقل ماكسرش حاجة)
- **Reversibility:** أي تعديل على /dart أو الـ prompt له rollback موثق
- **No-spam:** احترام unsubscribe فوري + مفيش إرسال لحد ما سحب موافقته

---

## 8. Open Questions (قرارات Omar)

- [x] **Q1 — قناة الإرسال:** ✅ محسوم 2026-06-11 — Brevo free هو القناة (شغّال PRIMARY من 2026-06-06)، والـ SES appeal بالتوازي لسعة الـ 13K في Phase 002.
- [ ] **Q2 — لو أحمد ما عملش المنتج قبل 14 يونيو مساءً:** نأجل الإطلاق لـ 22 يونيو ولا نطلق بالدفع اليدوي بس (فودافون/إنستاباي في الإيميل)؟

---

## 9. Dependencies

- **Depends on:** موافقة/تنفيذ أحمد (المنتج + الكوبون + اعتماد الإيميلات)
- **Blocks:** Phase 002 (الـ broadcast محتاج الإطلاق يخلص الأول + درس الـ deliverability منه)
