---
tags:
  - team
brand: learn-simply
team: automation
---

# ⚙️ فريق الأتوميشن — Learn Simply

> **⚠️ اقرأ `../CLAUDE.md` (brand router) + `../lessons.md` أول حاجة**

## دور الفولدر

`02_AUTOMATION/` بيحتوي على الـ scheduled tasks + infrastructure الـ automated:
- **DB backups** (cron-driven، GitHub-pushed)
- **(مستقبلاً)** cart recovery sequences، email automations، Meta Pixel server-side تتبع

---

## المحتوى الحالي

### `backups/` — DB Snapshot System (3 طبقات)

**Submodule** — Repo: [omarabdo516/learn-simply-backups](https://github.com/omarabdo516/learn-simply-backups) (private).

| المسار | بيدّيك |
|---|---|
| `backups/scripts/backup.sh` | DB dump via mysqldump + plugin list + manifest. Idempotent per ISO week، rotation 12 weeks |
| `backups/scripts/cron-wrapper.sh` | Hostinger-side cron wrapper: git pull → backup.sh → commit → push → Telegram alert |
| `backups/scripts/RESTORE.md` | 7-step recovery playbook |
| `backups/snapshots/db/YYYY-Www.sql.gz` | DB dumps |
| `backups/snapshots/plugins/YYYY-Www.json` | Plugin list snapshots |
| `backups/snapshots/manifests/YYYY-Www.json` | Per-snapshot metadata |

**Schedule:** أحد الساعة 02:00 Cairo (hPanel cron job)
**Layer 1:** Hostinger built-in daily backups (محتاج action manual للـ restore)
**Layer 2:** This system (offsite redundancy عبر GitHub)
**Layer 3:** Theme code في `01_WEB/website/` (real-time as Ahmed commits)

---

## محتوى محتمل في الـ Future (Phase 1+)

| Sub-folder | الغرض |
|---|---|
| `cart-recovery/` | CartFlows abandonment sequence configs + email templates |
| `email-sequences/` | Welcome، nurture، announcement templates لـ FluentCRM/Mautic |
| `meta-pixel-server-side/` | Conversions API setup لو قررنا نوصل لـ server-side tracking |
| `n8n-workflows/` | لو قرّرنا نعمل n8n integration (مفيش instance حالياً) |

---

## ⚠️ قواعد العمل في الفولدر ده

1. **مفيش edit مباشر على الـ scripts على السيرفر** — كل تعديل في الـ `backups/` repo + commit، السيرفر بيـ pull تلقائياً قبل كل run
2. **مفيش disable للـ cron-wrapper.sh** بدون تأكيد من Omar — الـ backup الأسبوعي = layer 2 critical
3. **الـ Telegram bot creds** في `02_AUTOMATION/backups/.env` على السيرفر (مش في repo) — راجع cron-wrapper.sh للـ pattern
4. **لو ضفت automation جديد** — وثّقه هنا أولاً + ضيف entry في brand `CLAUDE.md` تحت "Pending"
