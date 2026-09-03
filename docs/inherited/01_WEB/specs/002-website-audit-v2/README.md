# Phase 002 (WEB) — Website Audit v2: Re-audit + Closeout 📋 Placeholder

> **Status:** 📋 لسه مش spec'd — بيتعمل spec كامل عند بداية الـ phase.
> **Track:** Website (مستقل عن GTM track — راجع `../../../ROADMAP.md`)
> **يخلف:** `001-bug-remediation-90day` (اتجمّد عند 20/113 task — الورق بقى stale بعد نقل الاستضافة 2026-06-04)

## ليه v2 مش استكمال 001؟

1. **الموقع اتنقل لاستضافة جديدة** (2026-06-04) — ملفات `audit-*.md` التلاتة والـ baselines كلها على السيرفر القديم
2. بنود كتير في 001 اتدحضت أو بقت obsolete (Kashier "المكسور" · الـ 662 processing orders · شراء VPS) — راجع `03_KNOWLEDGE/gap-analysis-2026-06-11.md` §3
3. الـ blockers الحقيقية اتغيرت (Mautic live فك cart recovery مثلاً)

## Scope المبدئي

- **A — Post-migration Re-audit:** sweep منهجي على البيئة الجديدة (PHP config · cron · permissions · deliverability) + أرشفة audit docs القديمة لـ `_superseded/` + تصنيف بنود 001 المعلقة (حي / obsolete / اتقفل)
- **B — Security closeout:** Limit Login Attempts · 2FA لأحمد · SMTP re-verify على الـ host الجديد
- **C — Perf & Cleanup:** HPOS · plugins 61→43 · orphan tables (MailPoet/BlogVault/AIOSEO/BWFAN) · cron tuning (DISABLE_WP_CRON + system cron) · schema fixes · orphan postmeta/enrollments · revisions limit
- **D — Theme Refactor:** functions.php (106KB) → modules · parent theme update
- **E — Guardrails:** UI audit في CI (GitHub Actions) · monitoring بسيط

## قرارات أحمد المطلوبة (تتجمع قبل الـ spec)
- الـ 67 trashed courses (استرجاع/حذف) · كورس 29368 بدون منتج WC (السعر) · webtoffee folders · wp-events-manager
