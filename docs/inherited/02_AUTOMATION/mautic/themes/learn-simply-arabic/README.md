# Mautic Email Theme — Learn Simply Arabic (RTL)

> **الحالة:** 🟢 **منشور على Mautic** (2026-06-03). theme إيميل RTL + خط Cairo، يظهر في theme picker باسم **"Learn Simply Arabic"**.

## إيه ده ليه

قوالب Mautic الافتراضية كلها إنجليزي LTR. جمهور أحمد كله عربي → أي إيميل جديد لازم يبدأ **RTL بخط عربي** عشان يطلع شكله احترافي مش مكسور. ده القالب الافتراضي العربي.

## إزاي اتعمل (بدون مخاطرة)

clone من theme `sunday` المدمج (معروف إنه شغّال مع الـ builder) + **patch جراحي** في `<head>` بتاع الإيميل:
- `<html dir="rtl" lang="ar">`
- import خط **Cairo** + CSS يقلب الاتجاه `direction:rtl; text-align:right` ويغيّر الخط.

ده **additive بالكامل** — مفيش تعديل على أي theme قائم، مفيش recreate، مفيش restart للـ container.

## الملفات

| ملف | إيه |
|---|---|
| `deploy.sh` | السكربت اللي بيبني وينشر الـ theme على الـ VPS. **idempotent** — أعد تشغيله أي وقت. |
| `config.json` | ميتاداتا الـ theme (نسخة مرجعية؛ `deploy.sh` بيكتب نفسها). |

## ⚠️ ملاحظة استمرارية (مهمة)

مجلد `themes/` جوه الـ Mautic container **مش volume** — يعني لو الـ container اتعمله **recreate** (`docker compose up` بعد تحديث image مثلاً) الـ theme **هيضيع**. الحل: أعد تشغيل `deploy.sh` (ثواني). 

```bash
# على الـ VPS:
bash /path/to/deploy.sh
# أو من اللابتوب:
ssh learnsimply-vps "tr -d '\r' | bash -s" < deploy.sh
```

**الحل الدائم (لاحقاً، وقت أول تحديث Mautic مخطّط له):** ضيف bind-mount في `docker-compose.yml`:
```yaml
    volumes:
      - /docker/mautic-r4bx/themes/learn-simply-arabic:/var/www/html/docroot/themes/learn-simply-arabic:ro
```
(يحتاج `docker compose up -d` = recreate → نأجّله لوقت تحديث مخطّط مش دلوقتي.)

## الاستخدام

Mautic → New Email → اختار template/theme → **"Learn Simply Arabic"**. لو ماظهرش فوراً: امسح الـ cache بأمان (كـ web user مش root):
```bash
ssh learnsimply-vps "docker exec -u www-data mautic-r4bx-mautic-1 php /var/www/html/bin/console cache:clear"
```
