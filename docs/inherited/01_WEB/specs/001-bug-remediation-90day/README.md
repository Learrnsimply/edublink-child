# Learn Simply — خطة الـ Bug Remediation (90 يوم)

> 📅 بدء: 2026-05-23 · انتهاء متوقع: ~2026-08-23
> 🎯 الهدف: حل 137 bug على 5 phases بـ priority الـ business impact

---

## فين تروح

| الملف | بيدّيك |
|---|---|
| [spec.md](spec.md) | الـ "ليه" — السيناريوهات، الـ business outcomes، الـ success criteria |
| [plan.md](plan.md) | الـ "إزاي" — 5 phases بـ goals + gates + risks |
| [tasks.md](tasks.md) | الـ "افعل ايه" — قائمة تفصيلية لكل task في كل phase |

---

## الـ TL;DR (revised 2026-05-23 بعد verification pass)

| Phase | الفترة | الهدف الأساسي | المخرج المتوقع (revised) |
|---|---|---|---|
| **0. Foundation** | ✅ تم (2026-05-23) | إعداد backup + UI Audit + Sprint 1 fixes | حماية + safety net |
| **1. Revenue Recovery** | الأسبوع 1 | 662 processing orders cleanup + cart recovery setup + Meta Pixel + test email | **~92K EGP recover + 30-60K EGP/شهر بعد setup** |
| **2. Security Hardening** | الأسبوع 2 | إقفال ثغرات أمنية + 2FA + SMTP rotation | حماية من الهاكرز |
| **3. Performance + Cleanup** | الأسبوع 3 | تنظيف plugins (61→43) + HPOS + DB | موقع وأدمن أسرع 2-5× |
| **4. Theme Refactor** | الأسبوع 4 | refactor functions.php (106KB → modules) + parent update | maintainability + future fixes أسرع |
| **5. Future-Proofing** | الأسبوع 5+ | CI integration + email marketing rollout + monitoring | recurring revenue + alerts |

---

## الإجمالي المتوقع لو الـ 5 phases خلصوا (revised)

- **+~400K-1M EGP** إيراد محتمل في السنة الجاية (cart recovery + Kashier optimization + email marketing)
- **−6 GB** disk + **−18 plugins** + **−40 orphan tables**
- **0 → 4 layers** من الحماية الأمنية
- **Auto-detection** لـ regression class اللي ضيعت 7 PRs النهاردة

> **Revision note:** الـ initial estimate كان ~600K-1M EGP/سنة لأنه كان فيه افتراض إن Kashier مكسور (~195K استرجاع) و1645 active sessions (~150K كل أسبوعين). Verification أثبت إن الاتنين overstated، فالـ realistic estimate أقل. لسه قيمة عالية بس مش غير واقعية.

---

## تحت الـ Constitution

ده مشروع brand-internal تابع لـ `brands/learn-simply/` (Two-Layer Architecture, Principle II).
بيستخدم Spec Kit format لكن مش بيمر بـ Spec Kit gates الإجبارية — لأن دي شغل tactical على brand مش feature على الـ agency نفسها.
