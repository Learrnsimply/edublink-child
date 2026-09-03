# Runbook — نشر ما بعد النقل (2026-06-04+)

> **شغّله أول السيشن الجاية بعد ما أحمد ينقل الموقع للاستضافة الجديدة.**
> مرتّب بالأولوية. كل خطوة فيها الأمر + ليه. سياق كامل في [HANDOFF.md](HANDOFF.md).
> **الـ VPS (Mautic + n8n على 187.124.9.249) مش متأثر بالنقل خالص** — ده كله شغل الاستضافة (WordPress) بس.

---

## 0) محتاج من أحمد قبل ما تبدأ

| البند | ليه |
|---|---|
| 🔑 **بيانات SSH الجديدة** — host / port / user / WP path | الـ alias القديم `learnsimply` (`147.93.73.159:65002`) **مات بعد النقل**. |
| 🧾 **رخصة Tutor LMS Pro حقيقية** (مشتراة من Themeum) | عشان نشيل المكرك ونركّب الرسمي. لو لسه ماشتراهاش → الخطوة 3 تتأجّل، الباقي يمشي. |

---

## 1) SSH للاستضافة الجديدة (نعيد استخدام نفس المفتاح)

> **الفكرة بالبلدي:** المفتاح اللي عملناه امبارح (نص عام + نص خاص) لسه صالح. بس بنوجّهه للسيرفر الجديد. أحمد بيحط النص العام في لوحة تحكم الاستضافة الجديدة، وإحنا بنغيّر العنوان في إعدادات SSH عندنا.

**النص العام اللي أحمد بيضيفه في hPanel الجديد → SSH Access → Import SSH Key:**
```
ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIEKIBKZFdwj5iEw5N3gahowpBfQh6eX8/uuVdArW/KmM claude-laptop-PUZZLE-shared-host
```

**بعد كده عدّل `~/.ssh/config`** (الـ block بتاع `Host learnsimply`) بالعنوان الجديد:
```sshconfig
Host learnsimply
    HostName <NEW_HOST>        # العنوان الجديد من أحمد
    User <NEW_USER>            # اليوزر الجديد
    Port <NEW_PORT>            # البورت الجديد
    IdentityFile ~/.ssh/learnsimply-shared_ed25519
    IdentitiesOnly yes
```

**اختبر الاتصال:**
```bash
ssh learnsimply "wp core version --path=<NEW_WP_PATH> && pwd && hostname"
```
- المتوقع: رقم نسخة WordPress + الـ path + اسم السيرفر الجديد. لو طلب باسوورد → النص العام مش متحط صح في hPanel.

> ⚠️ **حدّث `.env`** (§ shared-host) بالقيم الجديدة بعد ما يشتغل: `SSH_HOST` / `SSH_PORT` / `SSH_USER` / `WP_PATH`. (الباسوورد القديم بقى بلا قيمة — key-based دلوقتي.)

---

## 2) فحص ما-بعد-النقل (تأكّد إن مفيش حاجة اتكسرت)

> النقل بينقل الملفات + الداتابيز. بنتأكد إن اللي ظبّطناه قبل كده لسه شغّال على البيت الجديد.

```bash
# 2.1 الموقع شغّال + نفس الدومين
curl -s -o /dev/null -w "%{http_code}\n" https://learrnsimply.com/      # المتوقع 200

# 2.2 Meta Pixel لسه بيـ fire (كان متعطّل تماماً قبل ما نصلّحه)
curl -s -A Mozilla https://learrnsimply.com/ | grep -oiE "fbq\('init'|699717432496147" | head

# 2.3 SSL سليم (شهادة مش منتهية / مش self-signed)
curl -sI https://learrnsimply.com/ | grep -i "HTTP/"                    # المتوقع 200/301

# 2.4 Social Login addon لسه مقفول (ثغرة Tutor 9.8 — لازم is_enable=0)
ssh learnsimply "wp option get tutor_addons_config --path=<NEW_WP_PATH> --format=json" | grep -o '"social-login":[^}]*'

# 2.5 البانر بتاع الـ popup لسه موجود (الـ ID ممكن يتغير بعد النقل!)
ssh learnsimply "wp post get 39310 --field=post_type --path=<NEW_WP_PATH>"   # المتوقع: attachment
```

**فحوصات يدوية (UI):**
- [ ] **W1 WooCommerce webhook (ID 7)** → n8n: من wp-admin → WooCommerce → Settings → Advanced → Webhooks → اتأكد إن الـ Delivery URL + Secret لسه مظبوطين (الـ n8n على VPS منفصل، فالـ URL مفروض ثابت — بس اتأكد إنه Active).
- [ ] **Mautic SMTP + from-address + تتبّع** — ابعت test email من Mautic، اتأكد إنه وصل inbox (مش spam). (الـ VPS منفصل، فالمفروض سليم — تأكيد بس.)
- [ ] لو الـ Meta pixel وقف (2.2 رجعت فاضي): امسح أي cache على الاستضافة الجديدة + اتأكد بلجن Facebook for WooCommerce متفعّل.

---

## 3) Tutor LMS Pro الرسمي (بديل المكرك) — 🔴 أولوية أمنية

> **الموقف:** النسخة الحالية مكركة (`license_to: "Pankaj Maurya"`) ومتجمّدة على 3.0.1 فيها 4 ثغرات (أخطرها 9.8). الحل = رخصة حقيقية + نسخة رسمية حديثة. **منحذفش الـ Pro قبل ما نركّب البديل** — بيشغّل الكورسات = الإيراد.

```bash
# 3.1 backup أول (إجباري) — DB + الكورسات. استخدم mysqldump مش "wp db export" (بيفشل صامت في CageFS).
ssh learnsimply "cd <NEW_WP_PATH> && wp config get DB_NAME --path=<NEW_WP_PATH>"
# خد الـ creds من wp config get (DB_NAME/DB_USER/DB_PASSWORD/DB_HOST) واعمل:
#   mysqldump -u<USER> -p<PASS> -h<HOST> <DB_NAME> | gzip > ~/pre-tutor-update-2026-06-04.sql.gz
# (أو ادفع snapshot يدوي عبر backup system في 02_AUTOMATION/backups)

# 3.2 سجّل النسخة الحالية قبل أي تغيير (للرجوع لو لزم)
ssh learnsimply "wp plugin get tutor-pro --field=version --path=<NEW_WP_PATH>"      # المتوقع 3.0.1 (المكرك)
ssh learnsimply "wp option get tutor_license_info --path=<NEW_WP_PATH> --format=json"
```

**الخطوات (UI + رخصة أحمد):**
1. من حساب أحمد على **Themeum** → نزّل أحدث `tutor-pro.zip` رسمي (يطابق النواة `tutor` الحالية — اتأكد من نسخة النواة: `wp plugin get tutor --field=version`).
2. wp-admin → Plugins → **Deactivate** الـ tutor-pro المكرك (مش Delete) → **Delete** → ارفع الرسمي (Add New → Upload) → **Activate**.
3. فعّل الرخصة الحقيقية من Tutor → Settings → License.
4. حدّث الإضافات المرتبطة لو موجودة: `tutor-pro/addons` (certificate-builder, elementor-addons) — كلها من النسخة الرسمية.
5. **اختبار حرج:** اعمل test order على كورس → اتأكد إن الطالب **وصله الكورس** + الشهادة بتتولّد. (ده اللي بيأكد إن استبدال الـ Pro ماكسرش الـ enrollment.)

```bash
# 3.3 تأكيد بعد التركيب
ssh learnsimply "wp plugin get tutor-pro --field=version --path=<NEW_WP_PATH>"      # المتوقع نسخة حديثة (≥3.9.11)
ssh learnsimply "wp option get tutor_license_info --path=<NEW_WP_PATH> --format=json"   # license_to = أحمد
```

**تقوية بعد التحديث:** rotate admin password + فعّل 2FA. **افحص كمان:** `Elementor Pro` (`_elementor_pro_license_v2_data`) + `WPSynchro` (`wpsynchro_license_key`) — اتأكد إنهم مش مكركين برضه.

---

## 4) نشر الـ Dart popup + تفعيل W2

> الكود جاهز في [`01_WEB/mu-plugins/dart-popup/`](01_WEB/mu-plugins/dart-popup/). تفاصيل كاملة في [README بتاعه](01_WEB/mu-plugins/dart-popup/README.md).

```bash
# 4.1 lint (متأكد مفيش syntax error قبل ما يلمس السيرفر)
#     على الجهاز لو فيه PHP، أو على السيرفر:
ssh learnsimply "php -l <NEW_WP_PATH>/wp-content/mu-plugins/learnsimply-dart-popup.php"  # بعد الرفع
#     المتوقع: No syntax errors detected

# 4.2 ارفع الملف لـ mu-plugins (بيتحمّل تلقائياً — مفيش "تفعيل")
#     عبر SFTP أو:
scp -P <NEW_PORT> 01_WEB/mu-plugins/dart-popup/learnsimply-dart-popup.php \
    <NEW_USER>@<NEW_HOST>:<NEW_WP_PATH>/wp-content/mu-plugins/
#     (لو مجلد mu-plugins مش موجود: ssh learnsimply "mkdir -p <NEW_WP_PATH>/wp-content/mu-plugins")

# 4.3 (أنضف) شيل التوكن من الملف → wp-config.php
#     ضيف في wp-config.php:
#        define('LS_DART_WEBHOOK_URL', 'https://n8n.learrnsimply.com/webhook/dart-waitlist-97ae34dfa856');
#     وامسح الـ inline fallback من الـ mu-plugin.
```

**فعّل W2 في n8n** (toggle واحد — الـ backend جاهز ومتختبر):
```
mcp__n8n-mcp__n8n_update_partial_workflow  (id: VMVSlPEcwNr1Bd6J, activateWorkflow: true)
```

**اختبار end-to-end:**
- [ ] افتح الموقع incognito → استنى 12 ث أو اسكرول 45% → الـ popup يظهر.
- [ ] اتأكد إن البانر (39310) بيظهر؛ لو لأ راجع الـ `LS_DART_BANNER_ID`.
- [ ] سجّل إيميل تجريبي → "تم تسجيلك 🎉".
- [ ] في Mautic: الإيميل دخل **segment 10** + tag `dart-waitlist` → **امسح التجريبي بعدها**.
- [ ] جرّب إيميل غلط → رسالة خطأ inline (مش تسجيل).

---

## 5) كوبون الإطلاق (لما منتج Dart يتعمل في WooCommerce)

> مش بكرة بالضرورة — ده وقت ما أحمد يجهّز منتج الكورس. القيم لسه **مستنية تأكيد أحمد** (راجع `02_AUTOMATION/mautic/campaigns/email-copy-drafts.md`).

- كوبون WooCommerce: كود `DART50` (مقترح) · 50% · مرتبط بمنتج Dart بس · صلاحية = مدة العرض (48س مقترح).
- بعد إنشاء المنتج: حدّث لينكات الـ CTA + الكوبون في إيميلات Mautic (ids 2-5) → ابنِ Mautic Campaign drip → publish.

---

## 5.5) 🔒 Hardening بعد الإطلاق (مش عاجل — بعد ما الـ popup يستقر)

> قرار Omar 2026-06-03: التوكن inline دلوقتي للنشر السريع (ريبو خاص + التوكن بوضعه محدود الأثر). بعد الإطلاق نقفلها للآخر:

- [ ] **rotate توكن W2 webhook** — في n8n غيّر الـ webhook path (التوكن الحالي `dart-waitlist-97ae34dfa856`) لتوكن جديد.
- [ ] **حطّه في `wp-config.php` بس** — `define('LS_DART_WEBHOOK_URL', ...)` + امسح الـ inline fallback من الـ mu-plugin (يبقى placeholder فاشل بصوت عالي).
- [ ] **نضّف التوكن القديم** من الـ docs (W2 doc + HANDOFF + الـ runbook ده) بعد الـ rotate.
- [ ] (اختياري) فعّل **double opt-in** في Mautic على segment 10 → يقفل أي abuse عبر الـ endpoint نهائياً.

---

## 6) بعد ما يخلص — حدّث التوثيق

- [ ] `HANDOFF.md` — علّم بنود الـ checklist اللي خلصت + سجّل بيانات SSH الجديدة.
- [ ] `.env` — قيم shared-host الجديدة.
- [ ] `01_WEB/mu-plugins/dart-popup/README.md` — غيّر الحالة لـ 🟢 LIVE + أكّد البانر.
- [ ] `02_AUTOMATION/n8n/workflows/W2-dart-waitlist-popup.md` — غيّر الحالة لـ ACTIVE.
- [ ] `/sync` لحفظ كل ده + bump الـ submodule pin لو لزم.
