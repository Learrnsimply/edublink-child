# Quickstart — Theme Defect Remediation (runbook التنفيذ + التحقق)

> دليل تشغيلي لتنفيذ 003 بأمان. **مفيش `php` محلي** → التحقق النحوي على السيرفر.

## 0. التحضير

```bash
# داخل الـ submodule
cd brands/learn-simply/01_WEB/website
git status            # لازم clean على ffdff55
git checkout -b 003-theme-defect-remediation
```

## 1. التنفيذ (بالـ lanes — L0 الأول)

**L0 — functions.php** (لوحده، الأول): FR-002 → FR-003 → FR-001 (localize) → FR-004 (localize) → FR-011 (Cairo save).
**L1 — single-course**: FR-001 (شيل inline) → FR-004 (consume) → FR-010.
**L2 — home**: FR-009 → FR-005.
**L3 — copy**: FR-006 → FR-007 → FR-008.

> كل FR = commit منفصل: `git commit -m "fix(theme): FR-00X <وصف>"`. ده بيخلّي الـ rollback نقطي.

## 2. التحقق النحوي (إجباري — محلي عبر Docker)

```bash
# لكل ملف PHP اتغيّر (paths relative to website/):
01_WEB/_tools/php-lint.sh functions.php front-page.php
# أو الكل:
01_WEB/_tools/php-lint.sh --all
# لازم: "No syntax errors detected"
```
> مفيش php نظامي + SSH alias السيرفر مش بيتحلّ من omar-central → الـ lint المحلي (Docker `php:8.2-cli-alpine`) هو الـ gate. server `php -l` تأكيد نهائي اختياري من جهاز Omar.

```bash
# JS اتغيّر:
node -c assets/single-course/script.js
node -c assets/global/script.js
```

## 3. التحقق البصري (rendering regression)

```bash
cd ../_tools/ui-audit
npm run audit          # Playlist Playwright على الصفحات المتأثرة
# قارن screenshots قبل/بعد في _evidence/
```

الصفحات المتأثرة: `/` (home) · صفحة كورس · `/courses` (archive) · الفوتر (كل صفحة) · `/about_me/`.
المقاسات: **375 · 768 · 1440**.

## 4. اختبار FR-011 (خاص — ميزة حيّة)

1. اقرا `assets/global/script.js` countdown — أكّد إنه duration-based (`deadline - Date.now()`). لو لأ → **وقف، أجّل FR-011 لـ Audit v2**.
2. بعد الإصلاح: احفظ deadline من wp-admin، أكّد الـ epoch المخزّن (`wp option get learnsimply_promo_deadline`) بيوافق توقيت القاهرة.
3. افتح الصفحة من جهازين بـ timezones مختلفة → نفس الوقت المتبقي.

## 5. النشر (بعد merge — Omar)

```bash
# نسخة احتياطية للملف الحسّاس قبل أي scp
ssh learnsimply 'cp <wp-path>/wp-content/themes/edublink-child/functions.php <wp-path>/.../functions.php.bak-2026-06-14'
# scp الملفات المتغيّرة فقط
scp functions.php learnsimply:<wp-path>/.../edublink-child/functions.php
# ... باقي الملفات
# cache purge
ssh learnsimply 'cd <wp-path> && wp cache flush ; wp wp-optimize purge 2>/dev/null || true'
```
> الـ wp-path = `/home/u791284659/domains/learrnsimply.com/public_html`.

## 6. Rollback (لو حصل مشكلة)

```bash
# كود:
git revert <commit-sha>
# سيرفر (فوري):
ssh learnsimply 'cp <wp-path>/.../functions.php.bak-2026-06-14 <wp-path>/.../functions.php'
```

## Definition of Done (لكل FR)

- [ ] الكود اتعدّل حسب research.md
- [ ] `php -l` نظيف على السيرفر (لو PHP)
- [ ] UI audit صفر regression على الصفحة المتأثرة
- [ ] المعيار (FR acceptance) متأكَّد بصرياً
- [ ] commit منفصل واضح

## بوابة الـ merge (Omar)

- [ ] الـ diff كامل متراجَع
- [ ] صفر تغيير out-of-scope (scope guard)
- [ ] كل الـ DoD اتعملت
- [ ] Omar يـ merge + bump submodule HEAD في الـ workspace repo
