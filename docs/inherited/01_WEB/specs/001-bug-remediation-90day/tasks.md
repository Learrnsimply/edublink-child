# Tasks: Learn Simply Bug Remediation (90-Day)

**Spec**: [spec.md](spec.md) · **Plan**: [plan.md](plan.md)
**Total tasks across all phases**: ~80 (Phase 1 reordered after verification 2026-05-23)
**Format**: `[ ] X.Y — Task title (estimate) → bug refs`

> ⚠️ **Phase 1 reordered 2026-05-23:** Kashier migration removed (gateway works). Priority is now: 662 processing orders → cart recovery setup → Meta Pixel → test email campaign.

---

## Phase 0 — Foundation ✅ DONE (2026-05-23)

- [x] 0.1 — Sprint 1 PR merged (8 audit fixes) → bugs-code.md C1-4, H3-4
- [x] 0.2 — Backup system 3 layers live → ad-hoc
- [x] 0.3 — SSH access established → ad-hoc
- [x] 0.4 — Checkout polish PRs #5-#11 merged → ad-hoc
- [x] 0.5 — UI Audit tool built + first run → see `_tools/ui-audit/`
- [x] 0.6 — Brand context updated (CLAUDE.md + lessons.md) → ad-hoc

---

## Phase 1 — Growth Foundations (Week 1) — REVISED v2

> 🎯 Goal: setup growth tools (cart recovery، Meta Pixel، email) + small cleanup. **Kashier works fine، 662 processing orders settled by Ahmed manually — both off the list.**

> 🛑 **DO NOT DO (Ahmed clarifications):**
> - **Don't manually review 662 processing orders** — Ahmed كان بيـ enroll customers manually. الـ customers أخدوا كورساتهم. الـ status متعلّق فقط cosmetic. Touch = trigger duplicate emails.
> - **Don't bulk-cancel 316 zero-EGP** — same workflow. Touch = "your order was cancelled" emails للـ confused customers.
> - **Don't investigate the June 2025 anomaly** — already part of Ahmed's workflow context.

### ⏸️ PARKED — Pending VPS Purchase (Omar decision 2026-05-23)

> These tasks depend on Mautic infrastructure (decided 2026-05-23: Mautic 7.1.1 on VPS, not Hostinger shared). Execution waits for VPS day. See Phase 1.5 in plan.md.

- [ ] **1.1** ⏸️ — Setup cart recovery للـ FUTURE abandonment (4h post-VPS) → integrity.md H-5 revised
  - **Blocker:** CartFlows Free only on Hostinger (Abandonment is Pro paid). Decision: use Mautic instead.
  - Sequence (post-VPS): 1h reminder → 24h discount → 72h last chance
  - Test trigger: add to cart، abandon، verify email arrives via Mautic
- [ ] **1.6** ⏸️ — First test email campaign (2h post-VPS) → gtm-roadmap
  - **Blocker:** MailPoet uninstalled (orphan tables only). FluentCRM/Mautic deferred to VPS day.
  - Send to 72 confirmed legacy subscribers (assuming consent re-validated) OR fresh signup test list
  - Watch: open rate, click rate, spam complaints

### P1 — Critical (must-do this week)
- [ ] **1.2** — Cleanup 1673 stale WC sessions (15 min) → integrity.md H-5 revised
  - [ ] `wp db query "DELETE FROM wp_woocommerce_sessions WHERE session_expiry < UNIX_TIMESTAMP(NOW());"`
  - [ ] Enable cleanup cron (action: `woocommerce_cleanup_sessions`)
- [ ] **1.3** — Verify + Activate Meta Pixel (2 hours) → gtm-roadmap
  - [ ] Check Facebook for WC plugin settings في wp-admin (Pixel ID configured?)
  - [ ] Open browser DevTools → Network → look for `https://www.facebook.com/tr/?id=`
  - [ ] Verify in Facebook Events Manager: Pageview, AddToCart, Purchase events firing
  - [ ] Test add-to-cart flow + actual purchase flow

### P2 — Important (this week if possible)

- [ ] **1.4** — Decision on 67 trashed courses (2 hours) → integrity.md C-4
  - [ ] Export list بـ id, title, modified date, lesson count
  - [ ] Categorize: (a) good content for re-publish، (b) demo/test (delete)، (c) archive
  - [ ] Bulk action على القرار
- [ ] **1.5** — Fix Course 29368 (Python) missing WC product (1 hour) → integrity.md H-2
  - [ ] Create WC product في WC > Products
  - [ ] Link لـ Tutor course via course meta
  - [ ] Test purchase flow
- [ ] ~~**1.6**~~ — MOVED TO PARKED section above (pending Mautic on VPS)
- [ ] **1.7** — Re-consent decision للـ 13K WC users (30 min decision) → gtm-roadmap
  - [ ] **Option A:** Soft opt-in على Thank You page (low coercion، low yield)
  - [ ] **Option B:** Re-consent email campaign (legal compliant, ~5-10% yield)
  - [ ] **Option C:** No marketing لهم — work bs بـ 72 confirmed + future signups
- [x] **1.8** — Sync WC From-Address لـ `contact@learrnsimply.com` (5 min) → integrity.md C-1 revised ✅ **DONE 2026-05-24**
  - [x] WC → Settings → Emails → From address = `contact@learrnsimply.com`
  - [x] الـ SMTP plugin already overrides بـ same value، فده cleanup مش function change
- [ ] **1.9** — Change admin_email لـ `contact@learrnsimply.com` (15 min) → integrity.md C-5
  - [ ] **Constraint:** contact@ هو الـ business email الوحيد الموجود (مؤكد من Ahmed)
  - [ ] `wp option update admin_email 'contact@learrnsimply.com'`
  - [ ] Ahmed confirms في Gmail (WP بيبعت confirmation للإيميل القديم)
  - [ ] Verify Ahmed has access to contact@ inbox
- [x] **1.10** — Expired coupon cleanup (JAVA200 + audit) (30 min) → integrity.md H-6 ✅ **DONE 2026-05-24** (JAVA200 → draft، audit أكّد إنه coupon الوحيد)

### Phase 1.5 — Kashier Optimization (optional this week, P3)

- [ ] **1.11** — Enable installments على Kashier dashboard (1 hour) → integrity.md C-2 revised
  - [ ] Contact Kashier support إذا الـ option مش ظاهرة
  - [ ] Test purchase بـ installment option
- [ ] **1.12** — Enable Apple Pay / Google Pay عن طريق Kashier (1 hour) → integrity.md C-2 revised
- [ ] **1.13** — Rename Kashier folder لـ `kashier-woocommerce-plugin/` (10 min) → plugins.md C-1 revised
  - [ ] Deactivate plugin
  - [ ] Rename folder
  - [ ] Reactivate plugin
  - [ ] Verify checkout still works

### Phase 1 Exit Gate (revised — processing orders dropped)

- [ ] UI Audit `npm run audit` → 0 failures
- [ ] Cart recovery: test trigger works، first email sent + opened
- [ ] Stale WC sessions: count < 50 (down from 1673)
- [ ] Meta Pixel: Events Manager shows Pageview + AddToCart + Purchase
- [ ] Test email campaign على 72 subscribers: > 30% open، 0 spam complaints
- [ ] Course 29368 يمكن شراؤه via WC product
- [ ] WC From-Address + admin_email both = `contact@learrnsimply.com`

---

## Phase 2 — Security Hardening (Week 2)

> 🎯 Goal: plug attack surfaces before anything bad happens.

- [ ] **2.1** — Rotate SMTP password من hPanel + update WP (30 min) → security-deep.md C-1
- [ ] **2.2** — Add `define('WPMS_SMTP_PASS', ...)` في wp-config (15 min) → security-deep.md C-1
- [x] **2.3** — Write `wp-content/.htaccess`: deny sensitive file types (30 min) → security-deep.md C-2 → **Procedure A** ✅ **DONE 2026-05-24**
  - [x] Scanned wp-content extensions before deploy (502 .gz = wpo-cache MUST KEEP، 66 .zip = plugin functionality MUST KEEP، 356 .txt too common)
  - [x] Real finding: 7 litespeed-cache `.sql` templates were PUBLICLY ACCESSIBLE (HTTP 200) — now 403
  - [x] Backup `.htaccess.bak-2026-05-24` (was empty 0 bytes)
  - [x] Deployed 725-byte deny block with Apache 2.2/2.4 dual syntax
  - [x] Deny list: `log sql bak tar tar.gz sql.gz tgz bz2 7z sh swp swo orig dat`
  - [x] Excluded intentionally: `.gz` (wpo-cache)، `.zip` (plugin cache)، `.txt`، `.html`
  - [x] Verified via Playwright: SQL URL → Page Title "403 Forbidden"
  - [x] Verified .txt, .jpg, /, /courses/, /wp-json/, /wp-admin/ all unaffected
  - [x] Evidence: `_evidence/2026-05-24-task-2-3-wpcontent-deny-sensitive/`
  - **Rollback:** `ssh learnsimply 'cd /path/wp-content && cp .htaccess.bak-2026-05-24 .htaccess'`
- [x] **2.4** — Write `wp-content/uploads/.htaccess`: deny `.php` execution (15 min) → security-deep.md C-3 → **Procedure A** below ✅ **DONE 2026-05-24**
  - [x] Backup: `.htaccess.bak-2026-05-24` (was empty 0 bytes)
  - [x] Deployed: 677 bytes deny block with Apache 2.2 + 2.4 dual syntax (`Require all denied` + `Order allow,deny / Deny from all`)
  - [x] Blocked extensions: `.php .phtml .php3 .php4 .php5 .php7 .phps .phar .pl .py .jsp .asp .sh .cgi`
  - [x] Verified via Playwright: 11 uploaded images → 200، 5 PHP files in uploads → 403، site loads correctly
  - [x] Evidence: `_evidence/2026-05-24-task-2-4-uploads-deny-php/`
  - **Rollback if needed:** `ssh learnsimply 'cd /path/wp-content/uploads && cp .htaccess.bak-2026-05-24 .htaccess'`
- [x] **2.5** — `rm wp-content/debug.log` + verify WP_DEBUG=false (5 min) → runtime.md C-1 ✅ **DONE 2026-05-24**
- [x] **2.6** — Disable xmlrpc.php: .htaccess `<FilesMatch>` deny (15 min) → security-deep.md H-2 → **Procedure A** ✅ **DONE 2026-05-24**
  - [x] Pre-state was BAD: POST to /xmlrpc.php returned 200 + full method list (system.multicall + pingback.ping = real attack vectors)
  - [x] Backup `.htaccess.bak-pre-task-2.6` (POST-2.10 state)
  - [x] Added NEW "Custom Security Access Rules" block (separate from Headers block for clean separation of concerns)
  - [x] FilesMatch + Apache 2.2/2.4 dual syntax
  - [x] Verified via Playwright: /xmlrpc.php → Page Title "403 Forbidden"
  - [x] Other endpoints intact: / 200، /wp-json/ 200، /wp-admin/ 302، /courses/ 200
  - [x] Evidence: `_evidence/2026-05-24-task-2-6-xmlrpc-disabled/`
  - **Note:** Hostinger CDN cached the pre-fix 200 response; appears on first non-cache-busted POST. Cache expires naturally، and browsers don't cache POSTs by default
  - **Rollback:** `ssh learnsimply 'cd /path && cp .htaccess.bak-pre-task-2.6 .htaccess'`
- [ ] **2.7** — Install **Limit Login Attempts Reloaded** (threshold=5) (15 min) → security-deep.md H-3
  - [ ] **Pre-install:** capture Ahmed's current IP(s) from recent successful logins (wp_postmeta / login plugin logs)
  - [ ] **Whitelist Ahmed's IP** in plugin settings BEFORE saving threshold settings (CHK024)
  - [ ] Threshold: 5 attempts، lockout: 60 minutes، long lockout (after 3 lockouts): 24h
  - [ ] Document whitelist update procedure for when Ahmed travels (Add IP via hPanel File Manager → plugin options table edit)
- [ ] **2.8** — Install **Two-Factor** plugin + enable على Ahmed admin (30 min) → security-deep.md H-3
  - [ ] **2FA recovery (CHK004):** Generate 10 backup codes during enrollment
  - [ ] Save backup codes in 2 locations: (1) Ahmed's password manager، (2) Omar's Bitwarden vault as "Learn Simply 2FA Backup"
  - [ ] Document Hostinger File Manager fallback: edit `wp-content/plugins/two-factor/` to disable plugin if Ahmed totally locked out (last-resort recovery)
  - [ ] Test the whole flow on test admin account FIRST، THEN enroll Ahmed
- [x] **2.9** — Audit suspicious uploads PHP files (30 min) → security-deep.md H-1 ✅ **DONE 2026-05-24 (Deeper audit)**
  - [x] Comprehensive scope: ALL 19 PHP + 13 .htaccess + 15 .html + 540 .js + 122 .svg + 35 .zip files in uploads
  - [x] Pattern scan: eval, base64_decode, exec, shell_exec, system, fsockopen, etc. → **ZERO matches**
  - [x] Magic bytes check: 26,920 images → all real images (no disguised PHP)
  - [x] Active-plugin reconciliation: `wpsynchro/`, `redux/`, `wpforms/`, `wpo/` all belong to ACTIVE plugins (audit H-1 was partial false positive — see lessons.md)
  - [x] Truly orphan folders identified: `mailpoet/` (Phase 3.3), `al_opt_content/` (236 MB from inactive Airlift — Phase 3.2a/3.8)
  - [x] dfg-logs/plugin.log = deploy audit trail (benign, useful)
  - **Result:** No deletions needed. Audit goal (verify clean) achieved. Orphan cleanup deferred to Phase 3 organized waves.
- [x] **2.10** — Add HSTS header في .htaccess (start max-age=300, ramp up) (10 min) → security-deep.md M-3 ✅ **DONE 2026-05-24**
  - [x] Backup `.htaccess.bak-pre-task-2.10` (POST-2.11 state)
  - [x] Added `Header always set Strict-Transport-Security "max-age=300; includeSubDomains"` inside Custom Security Headers block
  - [x] No `preload` directive yet (irreversible — defer until after 1-year ramp)
  - [x] Conservative ramp documented: 300s → 1d → 30d → 1y → preload
  - [x] DNS pre-check: no subdomains active → safe to use includeSubDomains
  - [x] Verified via Playwright: header present + value correct
  - [x] Evidence: `_evidence/2026-05-24-task-2-10-hsts-header/`
  - **Rollback:** `ssh learnsimply 'cd /path && cp .htaccess.bak-pre-task-2.10 .htaccess'`
  - **Note:** browsers cache HSTS for 5 min after rollback; this is by design
- [x] **2.11** — `Header unset X-Powered-By` في .htaccess (10 min) → security-deep.md M-1, perf.md M-4 ✅ **DONE 2026-05-24**
  - [x] Backup: `.htaccess.bak-2026-05-24` (MD5 verified)
  - [x] Prepended `<IfModule mod_headers.c>` block to .htaccess (byte-precise integrity check passed)
  - [x] Created `.user.ini` with `expose_php = Off` (defense in depth)
  - [x] Verified: 4 page types (home/courses/checkout/wp-json) all return correct status + X-Powered-By GONE
  - **Rollback if needed:** `ssh learnsimply 'cd /path && cp .htaccess.bak-2026-05-24 .htaccess && rm .user.ini'`
- [x] **2.12** — `chmod 600 wp-config.php` + verify site still loads (5 min) → security-deep.md M-2 ✅ **DONE 2026-05-24**
  - [x] Confirmed Hostinger uses LiteSpeed + suEXEC (PHP runs as file owner `u700430280`) → 600 safe
  - [x] Pre-state: 644 (-rw-r--r--) world-readable
  - [x] Post-state: 600 (-rw-------) owner-only
  - [x] Immediate smoke test: / 200، /courses/ 200، /wp-json/ 200، /wp-admin/ 302
  - [x] Playwright: homepage loads with correct title
  - [x] Evidence: `_evidence/2026-05-24-task-2-12-wpconfig-chmod-600/`
  - **Rollback:** `ssh learnsimply 'cd /path && chmod 644 wp-config.php'`
  - **Why:** wp-config contains DB creds + auth keys + salts; 644 was readable by any shared-host user

### Procedure A — `.htaccess` Safe Edit (applies to 2.3, 2.4, 2.6)

> Per CHK020 (analyze checklist) — rollback procedure required before any `.htaccess` change.

1. **Backup:** `ssh learnsimply 'cp .htaccess .htaccess.bak-$(date +%Y-%m-%d)'`
2. **Apply ONE rule** (block at a time، not all at once)
3. **Test site loads:** `curl -sI https://learrnsimply.com/ | head -1` → must be `HTTP/1.1 200 OK`
4. **Test admin loads:** `curl -sI https://learrnsimply.com/wp-admin/` → 200 OK or 302 (redirect to login)
5. **If 500 error:** `ssh learnsimply 'cp .htaccess.bak-$(date +%Y-%m-%d) .htaccess'` (immediate revert)
6. **Repeat steps 2-5** for each rule
7. **End-of-task verification:** UI Audit run + manual login check

> ⚠️ NEVER apply multiple `.htaccess` blocks في commit واحد. Apache بيـ reload الفايل live، فلو حاجة كسرت، الموقع 500 فوراً.

### Phase 2 Exit Gate

- [ ] `curl -X POST` على xmlrpc.php → 403
- [ ] 6 wrong logins من نفس IP → IP blocked
- [ ] DB dump grep on `wp_mail_smtp.pass` → empty
- [ ] `curl /wp-content/debug.log` → 403
- [ ] Headers: HSTS present, no X-Powered-By
- [ ] UI Audit run → 0 new security warnings

---

## Phase 3 — Performance + Cleanup (Week 3)

> 🎯 Goal: site وأدمن أسرع. Operational debt cleared.

### 3a. HPOS migration

- [ ] **3.1** — Enable HPOS + verify sync complete (2 hours) → plugins.md H-4
  - [ ] WP Admin → WC → Settings → Advanced → Features → HPOS
  - [ ] Monitor Action Scheduler `woocommerce_custom_orders_table_background_sync` لحد ما يخلص
  - [ ] Disable sync بعد ما يكمل

### 3b. Plugin reduction wave (61 → ~43)

> 📌 Process لكل plugin: deactivate → 24h watch → delete + remove tables

- [ ] **3.2a** — Deactivate 3 inactive plugins (airlift, litespeed-cache, nextend-facebook-connect) (10 min) → plugins.md L-1, C-3
- [ ] **3.2b** — Pick one SVG plugin (safe-svg OR svg-support) → deactivate الثاني (30 min) → plugins.md H-5
- [ ] **3.2c** — Pick one backup plugin (recommend: NONE — rely on our backup) → deactivate 4 plugins (1 hour) → plugins.md C-4
- [ ] **3.2d** — Reduce Elementor extras 5 → 2 (1 day with widget audit) → plugins.md H-3
  - [ ] Audit which widgets actually used على home, archive, single-course pages
  - [ ] Deactivate one extra plugin, verify pages still render
  - [ ] Repeat
- [ ] **3.2e** — Drop wp-events-manager if Ahmed confirms not used (30 min) → plugins.md M-2
- [ ] **3.2f** — Drop tutor-lms-migration-tool (1-time use done) (15 min) → plugins.md L-2

### 3c. DB orphan tables cleanup

> ⚠️ Fresh backup قبل أي drop!

- [ ] **3.3** — Export wp_mailpoet_subscribers (privacy archive) (15 min) → plugins.md C-2
- [ ] **3.4** — Drop 20+ MailPoet tables (~5 MB) (30 min) → plugins.md C-2
- [ ] **3.5** — Drop 3 BlogVault Airlift tables (~6 MB) (15 min) → plugins.md C-3
- [ ] **3.6** — Drop 3 AIOSEO tables (~3 MB) (15 min) → plugins.md M-3
- [ ] **3.7** — Drop 15 WooFunnels/BWFAN tables after confirming CartFlows independence (30 min) → plugins.md M-4

### 3d. Disk cleanup

- [x] **3.8** — Delete `wp-content/ai1wm-backups/` (5.6 GB) (5 min) → runtime.md C-2 ✅ DONE 2026-05-24
  - Procedure A: manifest committed (cc14791) → test delete oldest file (2.97 GB) → verify site 200 → full delete
  - Deleted: 2 `.wpress` files (Dec 2025 + Feb 2026)
  - Preserved: 5 protective files (.htaccess, index.html, index.php, robots.txt, web.config)
  - Final size: 44 KB (only protective files)
- [x] **3.9** — Delete `wp-content/wpvividbackups/` (3.8 GB) (10 min) → plugins.md C-4 ✅ DONE 2026-05-24
  - Deleted: 28 .zip files + 1 partial upload (.it6d4d) + 2 log files
  - Production backup (15 files, 2026-04-20) + orphan staging backup (13 files, beta.learrnsimply.com no longer resolves)
  - Preserved: protective .htaccess, index.html, wpvivid_log/ subfolder protective files
  - Final size: 36 KB (only protective files)
  - **NOT deleted yet:** `backups-dup-lite/` (52 KB, Duplicator plugin) — separate decision pending
  - **Plugin DB metadata:** WPvivid + All-in-One UIs will show broken backup entries (cosmetic, Ahmed cleans via plugin UI later)
- [ ] **3.9b** — Delete `wp-content/backups-dup-lite/` (52 KB, Duplicator plugin cache) — needs separate decision
- [ ] **3.10** — Delete `webtoffee_*` folders (12 MB + 4.3 MB + 740 KB ≈ 17 MB) (5 min) → runtime.md L-1
  - **Note:** These are WebToffee import/export logs, may have value if Ahmed needs historical import audit trail. Surface to Ahmed first.

> **Task 3.8 + 3.9 outcome (2026-05-24):**
> - Total freed: **~10.1 GB** (server total: 16 GB → 5.9 GB, quota 80% → 29.5%)
> - Manifest commit: `cc14791` on `omarabdo516/learn-simply` main
> - Playwright verified: homepage + courses + login all 200 OK
> - Console errors observed (2) = pre-existing theme asset 404s, NOT caused by deletion

### 3e. Autoload + cron tuning

- [x] **3.11** ✅ DONE 2026-05-24 — Autoload cleanup (Mode B Hybrid: delete orphans + toggle inactive) → perf.md C-1
  - Procedure A: mysqldump 303 MB (SHA `27adcbe6...`) → audit → 3 phases of execution
  - **Phase 1 Tier A:** 94 orphan deletes (Jetpack 51, AIOSEO 8, ElementsKit 4, EAEL 6, WPins 3, Essential Blocks 4, PPCP 10, Other Elementor add-ons 6, jp_standalone 2)
  - **Phase 1 Tier B:** 211 LiteSpeed entries toggled to autoload='no' (inactive plugin)
  - **Phase 2 deletes:** 149 additional orphans (PixelYourSite 14, BuildWooFunnels 11, WP Dark Mode 99, Multi Currency 1, PPCP residue 23, ElementsKit single-underscore 1)
  - **Phase 2 toggle:** 2 Nextend FB entries (inactive plugin)
  - **Tier C toggles:** CartFlows docs cache (485 KB) + Astra legacy settings (250 KB + 3 minor) — saved 737 KB autoload payload
  - **Final:** 1,483 autoload-positive → 1,023 (-31% rows). Size: 1.18 MB → 0.36 MB (-70%)
  - **Kept critical:** `email_template_data` (49 KB) = Ahmed's Tutor LMS welcome emails في عربي
  - **Discovery:** WP 6.6+ uses expanded autoload states (`auto`, `on`, `off`) — initial audit on `autoload='yes'` only missed 565 entries. Phase 2 caught them.
  - Manifest: `01_WEB/_evidence/task-3.11-autoload-cleanup-manifest.md` + TSV audit trail
  - Playwright + curl verified: homepage + courses + login all 200 OK
- [ ] **3.12** — `define('DISABLE_WP_CRON', true)` في wp-config (15 min) → runtime.md M-4
- [ ] **3.13** — Setup hPanel system cron: `*/5 * * * * curl learrnsimply.com/wp-cron.php` (15 min) → runtime.md M-4
- [ ] **3.14** — Reduce Action Scheduler interval to every 5 min (15 min) → runtime.md M-5
- [ ] **3.15** — Reduce Facebook for WC heartbeat to hourly (or disable plugin) (15 min) → runtime.md M-6

### 3f. DB indexes + integrity

- [x] **3.16** ✅ DONE 2026-05-24 — Installed Index WP MySQL For Speed v1.5.7 (Oliver Jones, Rick James) → perf.md H-1
  - Procedure A: mysqldump (303 MB, SHA `fc7cc018...`) → dry-run preview → tiered enable (4 tiers)
  - Tier 1: wp_termmeta + wp_options + wp_users (1.05s total lock)
  - Tier 2: wp_commentmeta + wp_comments + wp_woocommerce_order_itemmeta (1.20s)
  - Tier 3: wp_usermeta 390K rows (8.47s)
  - Tier 4: wp_postmeta 258K rows / 171 MB (26.06s)
  - 8/8 standard tables now use high-performance keys (compound PKs from Ollie Jones design)
  - Deferred: `wp_posts` + `wp_wc_orders_meta` (HPOS custom keys — separate decision)
  - Manifest: `01_WEB/_evidence/task-3.16-index-mysql-manifest.md` + DB backup at `01_WEB/_evidence/pre-task-3.16-index-plugin-2026-05-24_125038.sql`
  - Playwright + curl verified: homepage + courses + login all 200 OK
- [ ] **3.17** — Schema fix: `UPDATE wp_posts SET post_status='cancelled' WHERE post_status='cancel'` (15 min) → integrity.md M-3
- [ ] **3.18** — Schema fix: `UPDATE wp_comments SET comment_approved='1' WHERE comment_approved='approved'` (15 min) → integrity.md M-4
- [ ] **3.19** — Clean 1000 orphan postmeta via WP-Optimize plugin (30 min) → integrity.md H-3
- [ ] **3.20** — Clean 659 orphan tutor_enrolled بعد trash decision في Phase 1 (30 min) → integrity.md H-4
- [ ] **3.21** — Cleanup spam users (guerrillamail + 1-2 letter logins) (30 min) → integrity.md L-1
- [ ] **3.22** — Fix 158 empty-role users → assign to subscriber (30 min) → integrity.md M-1
- [ ] **3.23** — Resolve 4 duplicate user emails (audit + merge) (1 hour) → integrity.md M-2
- [ ] **3.24** — `define('WP_POST_REVISIONS', 5)` + delete old revisions (30 min) → integrity.md M-6

### 3g. Cache + CDN

- [ ] **3.25** — Fix WP-Optimize CDN cache bypass (1 hour) → perf.md H-4
- [ ] **3.26** — Pick caching plugin once: WP-Optimize OR LiteSpeed (1 hour) → runtime.md M-7

### Phase 3 Exit Gate

- [ ] `wp plugin list --status=active --format=count` ≤ 45
- [ ] Autoload size < 500 KB
- [ ] wp-content disk < 5 GB
- [ ] HPOS enabled + sync disabled
- [ ] Lighthouse Mobile Performance > 75 (run on PageSpeed Insights)
- [ ] UI Audit run = 0 failures, ≤ 1 warning
- [ ] Admin dashboard loads < 1.5s

---

## Phase 4 — Theme Refactor (Week 4)

> 🎯 Goal: pay tech debt. Make future fixes fast (no more 7-PR loops).

### 4a. Code extraction

- [ ] **4.1** — Extract `learnsimply_enrich_course()` helper used by front-page.php + archive-courses.php (1 hour) → caught during unify-buttons commit
- [ ] **4.2** — Refactor `functions.php` (106 KB → modular includes) (2 days) → perf.md H-2
  - [ ] **4.2a** — Create `includes/twig-setup.php` (safe_html, safe_embed filters)
  - [ ] **4.2b** — Create `includes/woo-overrides.php` (cart + checkout customizations)
  - [ ] **4.2c** — Create `includes/tutor-overrides.php` (course enrichment + enrollment)
  - [ ] **4.2d** — Create `includes/security-hardening.php` (xmlrpc disable + REST users limit)
  - [ ] **4.2e** — Create `includes/checkout-cleanup.php` (the inline `<style>` + JS من current state)
  - [ ] **4.2f** — `functions.php` reduces to ~5 KB: just `require_once` calls
  - [ ] **4.2g** — UI Audit run after each module extract = 0 failures
- [ ] **4.3** — Consolidate checkout CSS: 3 sources (custom-override.css, checkout/style.css, inline `<style>`) → 1 file (4 hours) → perf.md H-2

### 4b. Theme artifacts cleanup

- [ ] **4.4** — Delete `homepage.html` artifact (330 KB) (5 min) → perf.md H-3
- [ ] **4.5** — Cleanup `wp-content/plugins/edublink-child/` (theme folder خطأ في plugins/) (5 min) → caught during deploy

### 4c. Theme + plugin updates

- [ ] **4.6** — Update parent theme `edublink` 2.0.8 → 2.0.12 (1 hour) → perf.md M-3
  - [ ] Backup first
  - [ ] Test في staging (Phase 5 dep) أو local
  - [ ] Verify 9 override files still work
- [ ] **4.7** — Fix theme dep chain: `edublink-rtl` → register `edublink-core-main-css` properly (30 min) → runtime.md H-3
- [ ] **4.8** — Fix `edublink-core` animation widget undefined key (vendor patch OR MU plugin) (1 hour) → runtime.md M-1
- [ ] **4.9** — Update OptinMonster (PHP 8.2+ deprecation) (15 min) → runtime.md M-2
- [ ] **4.10** — Update Tutor LMS + Tutor Pro (textdomain too early) (30 min) → runtime.md M-3

### 4d. Headers

- [ ] **4.11** — Add CSP header في report-only mode أولاً (1 hour) → security-deep.md M-4

### Phase 4 Exit Gate

- [ ] `functions.php` < 30 KB total (root file)، each include < 20 KB
- [ ] Grep: only ONE source styles `.woocommerce-form-login`
- [ ] Parent theme updated (admin shows no update available)
- [ ] UI Audit run = 0 failures, 0 warnings
- [ ] WP_DEBUG=true → debug.log shows no new deprecated warnings → WP_DEBUG=false

---

## Phase 5 — Future-Proofing (Week 5+)

> 🎯 Goal: العملية ما تكسرش تاني.

### 5a. CI + monitoring

- [ ] **5.1** — GitHub Action: UI Audit on PR ضد staging URL (2 hours) → ui-audit/README
  - [ ] Create `.github/workflows/ui-audit.yml` في `Learrnsimply/edublink-child`
  - [ ] Step 1: checkout PR
  - [ ] Step 2: install Playwright
  - [ ] Step 3: run audit ضد staging
  - [ ] Step 4: post comment على PR بـ report link
  - [ ] Step 5: fail on >0 failures
- [ ] **5.2** — Cron: Weekly UI Audit + Telegram alert على new failures (1 hour) → ui-audit/README
  - [ ] Add cron entry على Hostinger أو Omar's PC
  - [ ] Script: run audit، compare to last run، diff = alert
- [ ] **5.3** — Setup staging environment على Hostinger subdomain (4 hours) → Constitution V step 4
  - [ ] Create `staging.learrnsimply.com` subdomain
  - [ ] Clone live DB + theme to staging
  - [ ] Document deploy flow (live → staging → test → promote)
- [ ] **5.4** — Document Hostinger deploy workflow في CLAUDE.md (30 min) → caught: we don't know what Ahmed clicks

### 5b. Email marketing rollout

- [ ] **5.5** — FluentCRM OR Mautic full setup + import 13K subscribers (2 days) → gtm-roadmap
  - [ ] Pick: FluentCRM (WP plugin, easier) أو Mautic (standalone, more powerful)
  - [ ] Install + configure SMTP relay
  - [ ] Import subscribers من WC orders + (if any) wp_mailpoet_subscribers export
  - [ ] Setup segments: by purchase product, by activity, by date
  - [ ] Welcome sequence trigger على new signup

### 5c. Tracking + monitoring

- [ ] **5.6** — Conversion tracking dashboard (4 hours) → gtm-roadmap
  - [ ] Verify GA4 events
  - [ ] Verify Meta Pixel funnel
  - [ ] Setup WC reports auto-email weekly
- [ ] **5.7** — Site monitoring (1 hour) → infra
  - [ ] Hostinger built-in uptime
  - [ ] UptimeRobot أو similar (free tier) لـ external check
  - [ ] Telegram alert على downtime > 5 min

### 5d. Constitution + cross-brand

- [ ] **5.8** — Review UI Audit tool reusability across brands (30 min) → Constitution Principle I
  - [ ] Could dentera/voya/kitc use the same audit script؟
  - [ ] Manual decision: promote to `_tools/` at workspace root أو keep per-brand

### Phase 5 Exit Gate

- [ ] PR على Learrnsimply/edublink-child blocked when UI Audit fails
- [ ] Weekly cron يبعت Telegram report
- [ ] Staging URL functional
- [ ] FluentCRM/Mautic running with first campaign sent
- [ ] Hostinger deploy workflow documented in CLAUDE.md

---

## Tracking

### Per-Phase Status

| Phase | Status | Started | Completed | Blockers |
|---|---|---|---|---|
| 0 | ✅ Done | 2026-05-23 | 2026-05-23 | — |
| 1 | 🟡 In progress | 2026-05-24 | — | 1.8 + 1.10 done; 1.1 + 1.6 PARKED (VPS); rest pending Ahmed (1.3, 1.4, 1.5, 1.9) |
| 2 | ⏳ Pending | — | — | Ahmed 2FA setup (1 step) |
| 3 | ⏳ Pending | — | — | Phase 2 must complete first |
| 4 | ⏳ Pending | — | — | Phase 3 stable + backup verified |
| 5 | ⏳ Pending | — | — | Phase 4 stable, staging env up |

### Lessons Log

After each phase، add lessons to `brands/learn-simply/lessons.md` per Constitution Principle III (two-tier lesson flow).

### Cross-Reference

- All bug IDs reference: `brands/learn-simply/bugs-*.md`
- Spec: this folder
- Implementation logs: per-PR commit history في `Learrnsimply/edublink-child`
