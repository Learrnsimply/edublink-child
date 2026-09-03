# Phase 001 — Launch Unblock · Plan

> **Author:** Claude Code
> **Approval:** ⏳ awaiting Omar

---

## Summary

7 tasks في 3 sections:
- **Section A:** تأكيد قناة الإرسال (Brevo شغّال — verify بس) — T-101
- **Section B:** Dart commerce readiness — T-102 → T-104
- **Section C:** Launch execution (15 يونيو) — T-105 → T-107

> **تصحيح جوهري (2026-06-11):** Brevo هو الـ PRIMARY sender من 2026-06-06 (إنت ظبطته)، مربوط بـ Mautic ومتأكد. فقناة الإرسال **مش بلوكر** — اتشالت من 3 tasks لـ task verification واحدة. الـ SES appeal اتنقل لـ Phase 002 (سعة الـ 13K، مش الإطلاق).

---

## 1. Approach

**الإطلاق ماشي على Brevo — جاهز النهاردة.** الـ waitlist (~74-100) جوه حد Brevo free الـ 300/يوم بسهولة. فمفيش انتظار على أي قناة.

البلوكر الحقيقي الوحيد = **منتج Dart من أحمد**. كل شغلنا يدور حواليه: نجهز كل حاجة جاهزة (إيميلات + صفحة + prompt) عشان لحظة ما المنتج يبقى live، الإطلاق يبقى ضغطة زرار.

**الـ 13K (Phase 002):** Brevo free = 43 يوم، والـ warmup ramp أصلاً بطيء في البداية فالقيد مش مؤثر دلوقتي. بالتوازي نعمل SES appeal (رد أقوى: حذف framing الـ 13K-blast + تأكيد opt-in + suppression + أرقام متدرجة) عشان لو اتقبل نكسب سعة أرخص بكتير. لكن ده كله **Phase 002** — مش بيلمس الإطلاق.

## 2. Quality Gates

كل task لازم:
- ✅ دليل تحقق فعلي (inbox screenshot / API response / Playwright / أوردر test)
- ✅ أي إرسال يسبقه تأكيد SPF/DKIM بتوع Brevo سليمين (الدومين authenticated 2026-06-06 — نتأكد النقل ماكسرش حاجة)
- ✅ أي تعديل live (صفحة /dart، prompt عمر) له rollback مكتوب قبل التنفيذ
- ✅ قواعد الموقع: مفيش push على main بدون PR لأحمد · cache purge بعد أي deploy

## 3. Channel State (للرجوع)

| القناة | السعة | الحالة | الدور |
|---|---|---|---|
| **Brevo Free** | 300/يوم | ✅ PRIMARY — live من 2026-06-06، مربوط بـ Mautic ومتأكد | إطلاق Dart + كل إرسال حالي |
| SES (بعد appeal) | 50K+/يوم · $0.10/1K | ❌ رُفض 2026-06-08 — appeal في Phase 002 | قناة الـ 13K المستقبلية لو اتقبل |
| Brevo Paid | بلا حد يومي · ~$9-25/شهر | خيار ترقية لو SES فشل | بديل سعة الـ 13K |
| Hostinger SMTP | 100/يوم | 💀 ميّت (Hostinger بيقفل auth تحت الحجم) | لا شيء — اتشال |

## 4. Task Sequencing

- **A و B متوازيين** (مفيش dependency بينهم)
- T-105 (launch prep) يعتمد على T-102 (المنتج) + T-103 (اعتماد الإيميلات)
- T-106 (يوم الإطلاق) يعتمد على T-105
- T-107 (التقرير) بعد 48 ساعة من T-106

## 5. Known Risks

- **أحمد ما يعملش المنتج في الوقت** → قرار Q2 في الـ spec (تأجيل أسبوع vs دفع يدوي). التصعيد: تذكير على Notion + واتساب يوم 12 ويوم 13.
- **النقل كسر DNS بتاع Brevo** → T-101 يتأكد قبل أي إرسال. لو اتكسر، إعادة نشر records Brevo (دقايق).
- **الـ waitlist عدّى 300 فجأة** → بعيد (دلوقتي ~74)، ولو حصل نقسم على يومين — الـ waitlist مش هيفلت.

## 6. Future Enhancements (Deferred)

- SES production-access appeal → Phase 002 (سعة أرخص للـ 13K)
- Cloudflare DNS migration (يحل مشكلة DMARC semicolon نهائياً) → Phase 002
- إيميل automation كامل (welcome/cart) → Phase 002
- تتبع GA4 purchase events → Phase 004

---

## Approval

- **Plan Status:** ⏳ awaiting Omar review
