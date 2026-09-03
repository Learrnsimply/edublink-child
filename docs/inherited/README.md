# ورشة عمر — أرشيف موروث

> شغل **عمر عبدالرحمن** (GTM Engineer) على المشروع من مايو لأغسطس ٢٠٢٦.
> اتنقل هنا في ٣ سبتمبر ٢٠٢٦ بعد ما مشي، عشان المعرفة متضيعش.
> المصدر الأصلي: `omarabdo516/learn-simply` — **ريبو خاص تحت حسابه الشخصي**.

## ⚠️ الأسرار اتشالت

٩ ملفات كانت فيها بيانات دخول حقيقية واتشطبت (`[REDACTED]`).
**الشطب مش كفاية — القيم لازم تتغيّر:**

| المفتاح | المكان الأصلي |
|---|---|
| SSH · WP admin · Hostinger · DB | `.env` (مانتقلش خالص) |
| GitHub token | `_research/2026-06-01-*.md` |
| TikTok access token + secret | `03_KNOWLEDGE/analytics-audit-2026-05-24.md` |
| Mautic API password | `02_AUTOMATION/mautic/create-*.sh` |
| AWS access keys | `02_AUTOMATION/mautic/ses-setup-runbook.md` |
| Evolution API · n8n API | `02_AUTOMATION/agents/` · `02_AUTOMATION/n8n/` |

## إيه اللي هنا

| المجلد | المحتوى |
|---|---|
| `03_KNOWLEDGE/` | سياق العميل · تدقيق شامل · تحليل المنافسين · **تحليل تسريب المدفوعات (2,936 أوردر)** · تدقيق القنوات والقياس |
| `01_WEB/` | **٩ تقارير باگز (137 باگ)** · ٣ تدقيقات تقنية · specs (Spec Kit) · **mu-plugins** · أدلة Playwright |
| `02_AUTOMATION/` | Mautic (إيميل) · n8n · dart-scheduler · وكلاء |
| `_research/` | أبحاث الترافيك والجمهور والمنافسين (أغسطس ٢٠٢٦) |
| `_client-reports/` | تقارير اتبعتت لأحمد |

## أهم ٥ وثائق تبدأ بيها

1. `03_KNOWLEDGE/client-context.md` — سياق العميل الكامل
2. `03_KNOWLEDGE/payment-leak-deep-dive-2026-06-24.md` — تحليل 2,936 أوردر
3. `01_WEB/bugs-report.md` — فهرس الـ137 باگ
4. `03_KNOWLEDGE/comprehensive-audit.md` — التدقيق الشامل
5. `01_WEB/mu-plugins/` — **كود شغّال على السيرفر مش موجود في ريبو الثيم**

## ⚠️ الـ mu-plugins

`skip-cart` · `dart-landing` · `dart-popup` — دول شغالين على السيرفر في
`wp-content/mu-plugins/` و**مش جزء من الثيم**. أي شغل على الشيك أوت أو
صفحات دارت لازم ياخدهم في الحسبان.
