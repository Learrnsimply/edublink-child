---
tags:
  - team
brand: learn-simply
team: web
---

# 🌐 فريق الويب — Learn Simply

> **⚠️ اقرأ `../CLAUDE.md` (brand router) + `../lessons.md` (brand-wide lessons) أول حاجة**

## دور الفولدر

`01_WEB/` بيحتوي على كل الـ technical work للموقع learrnsimply.com:
- **الكود نفسه** (`website/` submodule)
- **التقارير الفنية** (bugs + audits)
- **الأدوات** (UI Audit Playwright tool)
- **الـ Specs + Roadmap** للإصلاحات

---

## أصول الكود

| الـ folder | بيدّيك |
|---|---|
| `website/` | **Submodule** — child theme بتاع الموقع. Repo: [Learrnsimply/edublink-child](https://github.com/Learrnsimply/edublink-child). Ahmed = owner، عمر = collaborator بـ PR workflow. |

---

## تقارير الـ Bugs (137 bug total — verified)

> 📌 المرجع الموحّد = [bugs-report.md](bugs-report.md). كل الباقي drill-downs.

### Wave 1 — الـ Audit الأول (3 مسارات، بدون SSH)

| الملف | المحتوى | عدد الـ bugs |
|---|---|---|
| [bugs-code.md](bugs-code.md) | فحص الكود في `website/` | 27 |
| [bugs-functional.md](bugs-functional.md) | فحص الموقع المباشر (HTTP) | 18 |
| [bugs-data.md](bugs-data.md) | فحص بيانات WC/Tutor عبر REST | 21 |

### Wave 2 — Deep Audit (5 مسارات جديدة، عبر SSH + wp-cli + DB direct)

| الملف | المحتوى | عدد الـ bugs |
|---|---|---|
| [bugs-runtime.md](bugs-runtime.md) | PHP errors + debug.log + cron + autoload | 13 |
| [bugs-integrity.md](bugs-integrity.md) | DB orphans + WC/Tutor consistency | 19 |
| [bugs-plugins.md](bugs-plugins.md) | 61 plugin matrix + orphan tables | 17 |
| [bugs-perf.md](bugs-perf.md) | Autoload + HPOS + theme bloat | 11 |
| [bugs-security-deep.md](bugs-security-deep.md) | SMTP + .htaccess + xmlrpc + login | 11 |

### Technical drill-downs (Wave 1 originals)

| الملف | الموضوع |
|---|---|
| [audit-code-findings.md](audit-code-findings.md) | الكود نفسه — XSS، debug files، إلخ |
| [audit-commerce-deep.md](audit-commerce-deep.md) | الـ checkout failure، AOV، الـ products، الـ orders |
| [audit-technical.md](audit-technical.md) | Performance، الـ plugins، الـ security، الـ stack |

---

## الـ Specs + الـ Roadmap

| المسار | بيدّيك |
|---|---|
| [specs/001-bug-remediation-90day/](specs/001-bug-remediation-90day/) | Spec Kit-formatted plan لإصلاح الـ 137 bug على 5 phases بـ priority الـ ROI |
| [specs/001-bug-remediation-90day/README.md](specs/001-bug-remediation-90day/README.md) | TL;DR للـ plan |
| [specs/001-bug-remediation-90day/plan.md](specs/001-bug-remediation-90day/plan.md) | Phases details + gates |
| [specs/001-bug-remediation-90day/tasks.md](specs/001-bug-remediation-90day/tasks.md) | Task list مرتّبة |

---

## الأدوات

| المسار | بيدّيك |
|---|---|
| [_tools/ui-audit/](_tools/ui-audit/) | Playwright UI Audit script. `npm run setup` ثم `npm run audit`. بيـ catch class الـ checkout regressions اللي قابلناها 2026-05-23 (header overlap, form widths, JS-overridden layout). |
| [_evidence/](_evidence/) | Screenshots + artifacts من تحقيقات Playwright |

---

## ⚠️ قواعد العمل في الفولدر ده

1. **مفيش push على `Learrnsimply/edublink-child` main برّا PR review من Ahmed** — هو owner الـ repo
2. **مفيش `wp db export` على الـ production** — في CageFS بيـ fail silent. استخدم `mysqldump` بالـ credentials من `wp config get`
3. **مفيش `rm -rf` على السيرفر بدون ما تعرضه على Omar** — حتى لو الـ path واضح
4. **قبل ما تـ flag bug كـ Critical** — verify الـ EFFECTIVE state (مش بس stored value). راجع `../lessons.md` للسبب
5. **لو تعديل visual** — `npm run audit` بعد deploy + cache flush قبل ما تقول "خلّص"
