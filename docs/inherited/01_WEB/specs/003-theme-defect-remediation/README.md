# Phase 003 (WEB) — Theme Defect Remediation 🟡 Tasked, ready to implement

> **Status:** ✅ spec + clarify + plan + plannotator + **tasks** done (2026-06-14) → ⏳ `/speckit-implement` (29 task / 11 FR)
> **النطاق بعد الـ clarify:** 8 آمنة (FR-001..008) + 3 محسومة (FR-009 واتساب الدعم · FR-010 شيل "3 أيام" · FR-011 توقيت العداد القاهرة) = **11 FR**. DD-1/4/6/7/8 مؤجّلة برّا 003.
> **Track:** Website (مستقل عن GTM track — راجع `../../../ROADMAP.md`)
> **يكمّل:** الـ verification pass (2026-06-14) اللي أعاد فحص الـ 134 bug ضد الكود الحالي.

## TL;DR

بعد إعادة التحقق من audit 2026-05-23 ضد الكود الحقيقي (HEAD `ffdff55`):
- **13 already-fixed** (كل الـ Critical: XSS×2، cart dead code، debug files، REST users…) → مايتعملش.
- **7 stale/not-found** → مايتعملش.
- **8 إصلاحات آمنة** متحقَّق منها → نطاق الـ spec (FR-001..008).
- **8 بنود قرار/عناية** (DD-1..DD-8) → تتحسم في `/speckit-clarify`.
- **2 بنود فحص حي** (`/blog` 404 + تكرار `<title>`) → Audit v2 (002).

## الملفات

| ملف | بيدّيك |
|---|---|
| [spec.md](spec.md) | الـ WHAT/WHY + FR-001..008 + Deferred Decisions + Success Criteria |
| [checklists/requirements.md](checklists/requirements.md) | فحص جودة الـ spec (PASS) |

## المسار

1. ✅ `/speckit-specify` → spec.md (done 2026-06-14)
2. ✅ `/speckit-clarify` → 4 قرارات اتحسمت + اترمّزت (FR-009/010/011)، DD-1/4/6/7/8 اتأجّلت (done 2026-06-14)
3. ✅ `/speckit-plan` → [plan.md](plan.md) + [research.md](research.md) + [quickstart.md](quickstart.md) (done 2026-06-14) — اكتشاف مهم: تشخيص FR-011 اتصحّح (العداد آمن TZ؛ البق admin-side + فخ DST)
4. ✅ plannotator على plan.md (ملاحظة "php محلي" اتنفذت → docker php-lint + helper) + `/speckit-tasks` → [tasks.md](tasks.md) (29 task، done 2026-06-14)
5. ⏳ `/speckit-implement` → تنفيذ على code branch في الـ submodule (L0 الأول) → diff → Omar merges

## القيود

- Ahmed owner، Omar merge authority. مفيش local php → `php -l` على السيرفر.
- إطلاق Dart 15 يونيو → مسار شراء الباقة في الهومبيج مجمّد (DD-6).
