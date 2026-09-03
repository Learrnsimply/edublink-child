# Phase 002 — Email Engine Completion 📋 Placeholder

> **Status:** 📋 لسه مش spec'd — بيتعمل spec كامل (spec.md + plan.md + tasks.md) عند بداية الـ phase.
> **Depends on:** Phase 001 (قناة إرسال محسومة بسعة > 100/يوم).

## Scope المبدئي (من gap-analysis-2026-06-11)

- **broadcast إعادة التعريف للـ 13,711** (email id 1 جاهز) عبر **Brevo free** (300/يوم ≈ 43 يوم) — بـ warmup ramp + قرار re-consent (مع أحمد)
- **SES appeal بالتوازي** (Case 178058147100175 رُفض 2026-06-08، باب reevaluation مفتوح): رد أقوى = حذف framing الـ 13K-blast + تأكيد opt-in + SNS suppression مفعّل + أرقام متدرجة. لو اتقبل → سعة 50K+/يوم بـ $0.10/1K. لو فشل → قرار ترقية Brevo مدفوع.
- **Welcome sequence** (3-5 إيميلات أوتوماتيك عند التسجيل)
- **Cart abandonment flow** (n8n W1 جاهز — الإيميلات نفسها + الـ campaign logic)
- **Lead magnets ×3** (روودماب المبرمج PDF · cheat sheet · interview Q&A) + فورم capture عام على الموقع
- **Cloudflare DNS migration** (يحل DMARC semicolon نهائياً)
- **rotate W2 token** (مؤجل من الإطلاق — runbook §5.5)

## Deferred من Phase 001
- broadcast الـ 13K · sequences · SES appeal · أي حاجة فوق إطلاق الـ waitlist
