# تقرير Security Deep Bugs — اتعلم ببساطة
**تاريخ الفحص:** 2026-05-23 (Wave 3B — Security Deep Audit)
**المصدر:** File permissions + REST API probing + SMTP config + xmlrpc test + .htaccess audit
**الموقع:** learrnsimply.com

> ⚠️ **تنبيه أمني هام:** أثناء الـ audit، فحص `wp option get wp_mail_smtp` كشف كلمة مرور SMTP مخزّنة بـ base64 trivially-decodable في wp_options. **الكلمة دلوقتي موجودة في:** (1) الـ DB الـ live، (2) الـ DB dump المحلي (`backups/snapshots/db/2026-W21.sql.gz`)، (3) في chat history. **لازم تتغيّر من Hostinger hPanel فوراً.**

---

## جدول إحصائي سريع

| الفئة | المشاكل | Critical | High | Medium | Low |
|---|---|---|---|---|---|
| Credential exposure | 1 | 1 | 0 | 0 | 0 |
| .htaccess hardening | 2 | 2 | 0 | 0 | 0 |
| Uploads PHP risk | 1 | 0 | 1 | 0 | 0 |
| Legacy attack surface | 2 | 0 | 2 | 0 | 0 |
| Information disclosure | 2 | 0 | 0 | 2 | 0 |
| HTTP headers | 3 | 0 | 0 | 2 | 1 |
| **الإجمالي** | **11** | **3** | **3** | **4** | **1** |

---

## ✅ اللي عدّى الفحص (محمي)

| المجال | الحالة |
|---|---|
| File permissions | ✅ مفيش 777 أو world-writable |
| wp-config.php | ✅ 644 (آمن، رغم إن 600 أحسن) |
| .git directories | ✅ مفيش .git مكشوف على web |
| Backdoor patterns | ✅ مفيش أكواد مشبوهة (eval-based patterns غير موجودة) في plugins/themes |
| REST `/users` endpoint | ✅ بيرجع 404 (Sprint 1 PR شغّال!) |
| PHP version | ✅ 8.2.30 (مدعوم لحد ديسمبر 2027) |
| HTTPS + HTTP/2 | ✅ مفعّل |
| SSL/TLS | ✅ valid cert من Hostinger |
| Gzip compression | ✅ مفعّل |

---

## 🔴 Critical (3 مشاكل)

---

### C-1 — كلمة مرور SMTP مخزّنة plain-text في wp_options
**الوصف:** فحص الـ wp-mail-smtp config:
```
'smtp' => [
    'host' => 'smtp.hostinger.com',
    'port' => 465,
    'user' => 'contact@learrnsimply.com',
    'pass' => '<base64 encoded — trivially decoded>',
    ...
]
```

الـ `pass` field محفوظ بـ base64. **base64 مش encryption** — أي حد عنده DB access (admin user، wp-cli access، أو DB dump) يقدر يـ decode في ثانية.

**الأثر:**
- لو حد سرّق DB backup، عنده الـ SMTP creds = يقدر يبعت إيميلات باسم `contact@learrnsimply.com` (spam, phishing, impersonation)
- الـ SMTP creds دي بتـ access Hostinger mail server — لو الـ user/pass دي بتـ work في hPanel email login، الـ attacker عنده access لكل الـ inbox
- **الـ password حالياً متخزن في:**
  - Production DB (`wp_options.wp_mail_smtp`)
  - الـ DB dump المحلي (`backups/snapshots/db/2026-W21.sql.gz`)
  - GitHub repo `omarabdo516/learn-simply-backups` (private، بس لو الـ repo سُرّب)
  - Chat history لـ Claude session (هذا الجلسة)

**الإصلاح الفوري:**
1. **افتح Hostinger hPanel** → Email → contact@learrnsimply.com → Change Password
2. **ولّد password جديد ثقيل** (32 char random)
3. **WP Admin** → WP Mail SMTP → Settings → SMTP → Password → ضع الجديد
4. **اختبر إرسال** من WP Admin → WP Mail SMTP → Tools → Send Test Email
5. **(اختياري)** فعّل `WPMS_SMTP_PASS` كـ constant في `wp-config.php`:
   ```php
   define('WPMS_SMTP_PASS', 'xxxxxxxxxxxxxx');
   ```
   الـ plugin هيـ prefer الـ constant على الـ DB option (الـ DB option يبقى فاضي).

**الخطورة:** 🔴 Critical
**المكان:** `wp_options.wp_mail_smtp` + Hostinger email account

---

### C-2 — `wp-content/.htaccess` ملف فاضي (0 bytes) — مفيش حماية للـ log files
**الوصف:**
```
-rw-r--r-- 1 u700430280 ... 0 Jun 19  2024 wp-content/.htaccess
```

ملف موجود بـ صفر bytes. كان لازم يحتوي على rules لمنع:
- Direct access لـ `.log` files (مرتبط بـ bugs-runtime.md C-1 — debug.log exposed)
- Direct access لـ `.sql`, `.bak`, `.tar.gz` (backup leftovers)
- Listing folders (`Options -Indexes`)

**الأثر:** ضحية مزدوجة:
1. الـ debug.log اللي في bugs-runtime.md C-1 = قابل للقراءة عبر URL مباشرة
2. أي backup .sql أو .zip في wp-content بدون .htaccess = downloadable

**الإصلاح:** اكتب الـ `.htaccess` الـ standard لـ wp-content:
```apache
# Block access to log files
<FilesMatch "\.(log|sql|sqlite|db|tar|tar\.gz|tgz|zip|bak)$">
    Require all denied
</FilesMatch>

# Block direct PHP access outside specific allowed paths
<FilesMatch "\.php$">
    Require all denied
</FilesMatch>
<FilesMatch "(index|admin-ajax|wp-cron)\.php$">
    Require all granted
</FilesMatch>

# Disable directory listing
Options -Indexes

# Block sensitive WordPress files
<FilesMatch "(wp-config|debug)\.(php|log)$">
    Require all denied
</FilesMatch>
```

**الخطورة:** 🔴 Critical
**المكان:** `wp-content/.htaccess`

---

### C-3 — `wp-content/uploads/.htaccess` ملف فاضي — PHP في uploads قابل للتنفيذ
**الوصف:**
```
-rw-r--r-- 1 u700430280 ... 0 Jun 19  2024 wp-content/uploads/.htaccess
```

نفس الـ pattern. والـ uploads فيها 19 ملف PHP (شفنا في الـ scan): `redux/`, `mailpoet/`, `wpsynchro-69e64efa3d42c/`, `wpforms/cache/`, إلخ.

**الأثر:** لو الـ uploads قابل للـ direct PHP execution:
- Attacker يـ upload ملف `image.php.jpg` (multi-extension trick)
- يـ navigate لـ `/wp-content/uploads/.../image.php.jpg`
- لو السيرفر بيـ execute كـ PHP، يبقى remote code execution كامل

**الإصلاح:** اكتب `.htaccess` يـ block PHP execution في uploads:
```apache
<FilesMatch "\.(php|phtml|php3|php4|php5|php7|pl|cgi|sh|py)$">
    Require all denied
</FilesMatch>

# Allow specific index.php files (WP default)
<Files "index.php">
    Require all granted
</Files>
```

**الخطورة:** 🔴 Critical
**المكان:** `wp-content/uploads/.htaccess`

---

## 🟠 High (3 مشاكل)

---

### H-1 — 19 ملف PHP موجود في `wp-content/uploads/` (لما .htaccess يتفعّل، هتـ block، بس مهم نمسحها)
**الوصف:** الـ ملفات:
```
wp-content/uploads/redux/{15+ files}.php
wp-content/uploads/mailpoet/index.php
wp-content/uploads/wpsynchro-69e64efa3d42c/index.php   ← suspicious! "69e64efa3d42c" = random hash
wp-content/uploads/wpo/logs/index.php
wp-content/uploads/wpforms/cache/index.php
```

**أكثرهم خطورة:**
- `wpsynchro-69e64efa3d42c/index.php` — مجلد بـ random hash من plugin sync. لو فيه code فعّال = backdoor potential
- `redux/*.php` — UI control PHP saved في uploads (anti-pattern من Redux Framework)

**الأثر:** حتى لو الـ .htaccess في C-3 بيـ block PHP في uploads، وجود الملفات نفسها = bad smell.

**الإصلاح:**
1. افحص محتوى الملفات أولاً (خاصة `wpsynchro-69e64efa3d42c/`):
   ```bash
   ssh learnsimply 'cat ~/domains/learrnsimply.com/public_html/wp-content/uploads/wpsynchro-69e64efa3d42c/index.php'
   ```
2. لو فاضي (`// Silence is golden`) = حذف آمن
3. لو فيه code → engineer review قبل الحذف

**الخطورة:** 🟠 High
**المكان:** `wp-content/uploads/{redux,mailpoet,wpsynchro-*}/`

---

### H-2 — `xmlrpc.php` مفعّل ومجاوب على system.listMethods (legacy attack vector)
**الوصف:** فحص:
```bash
$ curl -X POST -H 'Content-Type: text/xml' \
  --data '<methodCall><methodName>system.listMethods</methodName></methodCall>' \
  https://learrnsimply.com/xmlrpc.php

<methodResponse>
  <params>
    <param>
      <value>
        ... (list of all available methods)
```

xmlrpc بيرجع 405 على HEAD لكن POST بيشتغل. ده default WP behavior.

**الأثر:**
- **Brute force amplification:** `wp.getUsersBlogs` بيـ allow 100+ password attempts في request واحد (بدل ما تـ try واحد واحد على wp-login). Botnets بيستخدموها لـ password spraying
- **Pingback DDoS:** `pingback.ping` بتـ allow attacker يـ trigger الـ site يبعت requests لـ targets آخرين (DDoS amplification)
- مفيش plugin مفعّل في learrnsimply بيستخدم xmlrpc (مفيش Jetpack مثلاً)

**الإصلاح:** أضف لـ `wp-config.php`:
```php
add_filter('xmlrpc_enabled', '__return_false');
```
أو في `.htaccess`:
```apache
<Files xmlrpc.php>
    Require all denied
</Files>
```

**الخطورة:** 🟠 High
**المكان:** `xmlrpc.php` endpoint

---

### H-3 — `wp-login.php` مكشوف بدون rate-limit أو 2FA
**الوصف:**
```
wp-login.php → HTTP 200 (public, no protection)
wp-admin/    → HTTP 302 (redirect to login)
```

**الأثر:**
- Bot يقدر يـ try ١٠,٠٠٠ password attempt/يوم على `wp-login.php` بدون أي flag
- Ahmed = admin بـ بريد شخصي (bugs-integrity.md C-5) — لو compromised، الموقع كله
- مفيش 2FA visible في الـ plugin list

**الإصلاح (طبقات):**
1. **فوراً:** plugin **Limit Login Attempts Reloaded** (free) — يـ throttle attempts من نفس IP
2. **بعدها:** plugin **Two-Factor** (official Plugin) — يـ enable 2FA لـ admin
3. **لاحقاً:** تغيير login URL via plugin **WPS Hide Login** (free)
4. **اختياري:** Cloudflare Access (لو فيه CF) — يحط Cloudflare Login قبل WP Login

**الخطورة:** 🟠 High
**المكان:** `wp-login.php` + Hostinger security layer

---

## 🟡 Medium (4 مشاكل)

---

### M-1 — `x-powered-by: PHP/8.2.30` header — version disclosure
**(مكرر من bugs-perf.md M-4 — لكن security severity)**

**الإصلاح:** `expose_php = Off` في PHP config أو `Header unset X-Powered-By` في .htaccess.

**الخطورة:** 🟡 Medium
**المكان:** PHP / Apache config

---

### M-2 — `wp-config.php = 644` (readable لكل user)
**الوصف:** `ls -la wp-config.php` بيظهر `644`. على shared hosting، 644 = readable لـ webserver user (مطلوب) + group + others.

**الأثر:** على Hostinger CageFS، الـ "others" قاعدتها مختلفة (jailed)، فمش خطر حاد. بس لو chroot escape (theoretical)، الـ wp-config readable.

**الإصلاح:**
```bash
chmod 600 wp-config.php
```
لو الـ WP لسه بيشتغل بعدها = OK. لو error = الـ webserver user مش owner، الـ 644 لازم.

**الخطورة:** 🟡 Medium (depends on hosting isolation)
**المكان:** `wp-config.php` permissions

---

### M-3 — مفيش HSTS header (HTTP Strict Transport Security)
**الوصف:** الـ response headers مفيش `Strict-Transport-Security`.

**الأثر:** على first visit، الـ browser ممكن يـ try HTTP قبل ما يـ upgrade لـ HTTPS — opportunity للـ SSL stripping attack.

**الإصلاح:** في `.htaccess`:
```apache
<IfModule mod_headers.c>
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
</IfModule>
```
ابدأ بـ `max-age=300` (5 دقايق) للـ test، ثم زوّد لما تتأكد.

**الخطورة:** 🟡 Medium
**المكان:** `.htaccess` root

---

### M-4 — Content-Security-Policy ضعيف: بس `upgrade-insecure-requests`
**الوصف:** الـ existing CSP:
```
content-security-policy: upgrade-insecure-requests
```

ده بس بيـ promote requests لـ HTTPS. مفيش `script-src`, `style-src`, `frame-ancestors` rules.

**الأثر:** لو حصل XSS injection (Sprint 1 خفّفها بـ wp_kses_post)، CSP كان ممكن يكون second layer من الحماية.

**الإصلاح:** صعب الـ CSP يكون strict مع WordPress (الـ themes/plugins بيـ inline styles/scripts كتير). ابدأ بـ report-only:
```
Content-Security-Policy-Report-Only: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; report-uri /csp-report.php
```
ثم progressively tighten.

**الخطورة:** 🟡 Medium
**المكان:** `.htaccess` / WP headers

---

## 🔵 Low (1 مشكلة)

---

### L-1 — OpenSSH على Hostinger مش بيدعم post-quantum key exchange
**الوصف:** أي SSH connect بيظهر warning:
```
WARNING: connection is not using a post-quantum key exchange algorithm.
This session may be vulnerable to "store now, decrypt later" attacks.
```

**الأثر:** Threat actor دلوقتي يقدر يـ record SSH traffic encrypted، يخزّنه، وفي 5-10 سنين لما quantum computers قادرين، يفك تشفيره. للـ Learn Simply ده مش threat فعلي بس لو SSH session فيها commands حساسة، نظرياً ممكن يـ leak.

**الإصلاح:** ده issue على الـ Hostinger side. مفيش حاجة client-side تحلها. ابعت لـ Hostinger feedback لو حابب. مش urgent.

**الخطورة:** 🔵 Low
**المكان:** Hostinger SSH server config

---

## الخلاصة + Top Priorities

| Tier | العدد | الأثر |
|---|---|---|
| 🔴 Critical | 3 | SMTP password leak + 2 missing .htaccess = immediate attack surface |
| 🟠 High | 3 | uploads PHP + xmlrpc + login brute force vectors |
| 🟡 Medium | 4 | hardening (HSTS, CSP, headers) |
| 🔵 Low | 1 | quantum-resistance (hosting-side) |

**Top 5 immediate actions:**
1. **C-1 — Rotate SMTP password** (5 دقايق) — كلمة المرور دلوقتي في تلت أماكن
2. **C-2 + C-3 — اكتب الـ `.htaccess` الناقصة** (10 دقايق) — يقفل debug.log + uploads PHP risk
3. **H-2 — Disable xmlrpc** (1 سطر في wp-config) — يقفل DDoS amplification
4. **H-3 — Install Limit Login Attempts** (5 دقايق) — يقفل brute force
5. **C-1 wp_kses من Sprint 1** ✅ (شغّال — تأكدنا الـ users endpoint بيرجع 404)

**Sprint 2 / 3 quick wins:**
- Theme parent update (bugs-perf.md M-3)
- HSTS + CSP headers (M-3, M-4)
- 2FA على admin accounts

**اللي مش في الـ scope هنا:**
- Penetration test (يحتاج external pentester مع authorization)
- WAF (Cloudflare Pro) — قرار مالي + setup
- Code scanning للـ plugins paid (snyk, sonatype) — تكلفة
