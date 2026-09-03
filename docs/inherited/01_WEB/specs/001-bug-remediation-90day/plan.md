# Implementation Plan: Learn Simply Bug Remediation (90-Day)

**Branch**: `001-bug-remediation-90day`
**Spec**: [spec.md](spec.md)
**Tasks**: [tasks.md](tasks.md)
**Created**: 2026-05-23
**Last revised**: 2026-05-23 (after verification pass — Phase 1 priorities reordered)

> ⚠️ **Phase 1 reordered after verification:** Original plan led with "Kashier migration" (~195K EGP). Verification showed Kashier works fine, so Phase 1 leads instead with "manual review of 662 processing orders" (~92K EGP actual stuck money). See revised Phase 1 below.

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────┐
│  Phase 0: Foundation (✅ Done)                          │
│  ─────────────────────────                              │
│  • Backup system 3 layers                               │
│  • Sprint 1 PR merged (8 audit fixes)                   │
│  • Checkout polish (PR #5-#11)                          │
│  • UI Audit tool (Playwright)                           │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  Phase 1: Revenue Recovery (Week 1)                     │
│  Largest financial impact first.                        │
│  Goal: stop the bleeding.                               │
│  Exit: first new gateway payment + first cart email.    │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  Phase 2: Security Hardening (Week 2)                   │
│  Plug attack surfaces before anything bad happens.      │
│  Exit: scan shows 0 critical security findings.         │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  Phase 3: Performance + Cleanup (Week 3)                │
│  Speed up site + clean operational debt.                │
│  Exit: Lighthouse > 75, autoload < 500KB, 43 plugins.   │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  Phase 4: Theme Refactor (Week 4)                       │
│  Pay technical debt for sustainable future fixes.       │
│  Exit: functions.php < 30KB per module, parent updated. │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  Phase 5: Future-Proofing (Week 5+)                     │
│  Prevent regression class permanently.                  │
│  Exit: CI integrated, monitoring live, email rolling.   │
└─────────────────────────────────────────────────────────┘
```

---

## Phase 1 — Revenue Recovery (Week 1) — REVISED 2026-05-23

### Goal

Setup الـ growth foundations: cart recovery للـ future + Meta Pixel + first email campaign + cleanup صغير. **Kashier shipping perfectly، الـ 662 processing orders settled (Ahmed manually enrolling)، فالـ focus shifted لـ growth tools.**

### Tasks (revised priorities — Ahmed clarifications + 2026-05-23 verification pass + VPS decision)

> 🛑 **REMOVED من الـ list (Ahmed confirmed منعهم):**
> - ~~Manual review 662 processing orders~~ → orders كانت legacy bug + Ahmed enrolled customers manually. مفيش 92K EGP "stuck" — الـ customers أخدوا كورساتهم.
> - ~~Bulk-cancel 316 zero-EGP~~ → كلهم already مع Ahmed's manual workflow، تـ touch-هم = trigger duplicate/cancel emails للـ customers.
>
> ⏸️ **PARKED — pending VPS purchase (Omar decision 2026-05-23):**
> - **1.1 Cart recovery setup** → blocked. SSH verification أكد: CartFlows Free only (مفيش Pro/Abandonment addon). Mautic chosen as long-term tool. Mautic مش هيشتغل صح على Hostinger shared (CageFS + cron limits + memory). **يوصل دور Mautic install + cart recovery لما Omar يشتري VPS.**
> - **1.6 First test email campaign** → blocked لنفس السبب. MailPoet uninstalled (orphan tables فقط)، "72 confirmed subscribers" مش قائمة قابلة للاستخدام دلوقتي.
> - **1.7 Re-consent decision** → decision لسه actionable الآن (Omar/Ahmed يفكروا)، بس الـ execution waits for Mautic on VPS.

| # | Task | Bug Refs | Estimated | Owner | Status | Why Priority |
|---|---|---|---|---|---|---|
| 1.1 | ~~Setup cart recovery~~ | integrity.md H-5 | 4 hours | Omar | ⏸️ PARKED (VPS) | Moved to post-VPS phase |
| 1.2 | **Cleanup 1673 stale WC sessions** (DELETE expired) | integrity.md H-5 (revised) | 15 min | Omar | ✅ Ready | DB hygiene |
| 1.3 | **Verify + Activate Meta Pixel** (verify events in Events Manager) | gtm-roadmap, audit-tracking | 2 hours | Omar + Ahmed (FB access) | ✅ Ready | Ad ROI tracking |
| 1.4 | **Decision على 67 trashed courses** — restore good / delete demo | integrity.md C-4 | 2 hours | Ahmed | ✅ Ready | Catalog cleanup |
| 1.5 | **Fix Course 29368 (Python) missing WC product** | integrity.md H-2 | 1 hour | Ahmed (price) + Omar (impl) | ✅ Ready | يمكن شراؤه |
| 1.6 | ~~First test email campaign~~ | gtm-roadmap | 2 hours | Omar | ⏸️ PARKED (VPS) | Needs email tool |
| 1.7 | **Re-consent decision للـ 13K WC users** (GDPR/CAN-SPAM) | gtm-roadmap | 30 min decision | Omar + Ahmed | 🟡 Decision-only ready | Execution waits for VPS |
| 1.8 | **Sync WC settings**: update From-Address لـ `contact@learrnsimply.com` (يطابق الـ SMTP) | integrity.md C-1 (revised) | 5 min | Omar | ✅ Ready | UI consistency |
| 1.9 | **Change admin_email** لـ `contact@learrnsimply.com` (الـ business email الوحيد المؤكد) | integrity.md C-5 | 15 min | Omar + Ahmed confirms | ✅ Ready | Single-point-of-failure prevention |
| 1.10 | **Expired coupon cleanup** (JAVA200 + audit) | integrity.md H-6 | 30 min | Omar | ✅ Ready | UX |

### Phase 1.5 — Post-VPS (NEW — added 2026-05-23)

Once Omar purchases VPS، do these in order:

| # | Task | Estimated | Owner |
|---|---|---|---|
| 1.5.1 | Setup Mautic 7.1.1 على VPS (Docker + Traefik + MariaDB + Redis) | 8 hours | Omar |
| 1.5.2 | DNS: `mautic.learrnsimply.com` → VPS IP + DKIM/SPF | 30 min + propagation | Ahmed (DNS) + Omar |
| 1.5.3 | Mautic SMTP config (Hostinger SMTP أو Amazon SES) | 1 hour | Omar |
| 1.5.4 | WP integration: install Mautic plugin + form tracking | 2 hours | Omar |
| 1.5.5 | Build cart recovery automation (3-email sequence) | 3 hours | Omar |
| 1.5.6 | Re-consent campaign للـ 13K (based on Task 1.7 decision) | 4 hours | Omar |
| 1.5.7 | First test email campaign | 2 hours | Omar + Ahmed approval |

> See parking notes in `bugs-integrity.md H-5` و `bugs-plugins.md C-1` لتاريخ التحقق.

### Optional / Phase 1.5 (Kashier optimization — if bandwidth)

| # | Task | Bug Refs | Estimated | Owner | Why |
|---|---|---|---|---|---|
| 1.14 | **Enable installments على Kashier** (يقلل insufficient funds failures) | integrity.md C-2 (revised) | 1 hour | Omar + Kashier support | ~70K EGP/سنة optimization |
| 1.15 | **Enable Apple Pay / Google Pay** عن طريق Kashier | integrity.md C-2 (revised) | 1 hour | Omar | Less 3DS friction |
| 1.16 | **Rename Kashier folder** to `kashier-woocommerce-plugin/` (drop -master) | plugins.md C-1 (revised) | 10 min | Omar | Avoid future audit confusion |

### Exit Gate (revised — processing orders dropped)

- ✅ Run `npm run audit` في `_tools/ui-audit/`: 0 failures
- ✅ Cart recovery: test trigger works، first recovery email sent + opened
- ✅ Meta Pixel: Events Manager في Facebook بيشوف `Purchase` events
- ✅ Test email campaign: > 30% open rate على الـ 72 confirmed subscribers
- ✅ Stale WC sessions deleted (count < 50)
- ✅ Course 29368 يمكن شراؤه via WC product
- ✅ WC From-Address + admin_email both = `contact@learrnsimply.com`

### Risks + Mitigation (revised)

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Cart recovery emails go to spam (deliverability test) | Medium | Medium | First test = 72 confirmed only. Watch open rate. Then scale. |
| Test campaign على 72 = spam complaints | Low | Medium | Subject line + content tested. Unsubscribe link required. |
| Meta Pixel events misconfigured | Medium | Low | Verify في Facebook Events Manager قبل ads. Test purchase. |
| Re-consent campaign للـ 13K = low conversion + costs goodwill | Medium | Low | Frame كـ "stay subscribed to learn about new courses". Soft opt-in. |
| **حد يلمس الـ 662 processing orders بالخطأ** | Low (already documented) | High | bugs-integrity.md H-1 موصّف بـ "DO NOT TOUCH" + Ahmed's manual workflow noted في lessons.md |

### What's NOT in Phase 1 anymore (moved out)

- ~~Migrate Kashier gateway~~ → Disproved. Kashier works (102 orders/30 days).
- ~~"Rescue" 1645 active sessions~~ → All stale. Cart recovery is for FUTURE only.
- ~~Manual review 662 processing orders~~ → Ahmed handles manually. Don't touch.
- ~~Bulk-cancel 316 zero-EGP processing~~ → Same as above. Don't touch.
- ~~Investigate 316 zero-EGP anomaly~~ → Already part of Ahmed's manual workflow. Document but don't act.
- ~~Setup full FluentCRM/Mautic for 13K~~ → Moved to Phase 5 (needs re-consent decision first).

---

## Phase 2 — Security Hardening (Week 2)

### Goal

إقفال الـ attack surfaces المكشوفة قبل ما حد يستغلها. التركيز: credentials، uploads، login، xmlrpc.

### Tasks

| # | Task | Bug Refs | Estimated | Owner |
|---|---|---|---|---|
| 2.1 | **Rotate SMTP password** من Hostinger hPanel + update WP | security-deep.md C-1 | 30 min | Omar (Ahmed approves) |
| 2.2 | **Add `define('WPMS_SMTP_PASS', ...)`** في wp-config (مش في DB) | security-deep.md C-1 | 15 min | Omar |
| 2.3 | **Write `wp-content/.htaccess`** بـ rules لمنع .log/.sql/.bak | security-deep.md C-2 | 30 min | Omar |
| 2.4 | **Write `wp-content/uploads/.htaccess`** يمنع PHP execution | security-deep.md C-3 | 15 min | Omar |
| 2.5 | **Delete `debug.log`** + verify WP_DEBUG=false | runtime.md C-1 | 5 min | Omar |
| 2.6 | **Disable xmlrpc.php** عبر filter + .htaccess | security-deep.md H-2 | 15 min | Omar |
| 2.7 | **Install Limit Login Attempts Reloaded** (threshold = 5) | security-deep.md H-3 | 15 min | Omar |
| 2.8 | **Install Two-Factor plugin** + setup على admin | security-deep.md H-3 | 30 min | Ahmed |
| 2.9 | **Audit suspicious uploads PHP** files (wpsynchro-* + redux/) | security-deep.md H-1 | 30 min | Omar |
| 2.10 | **Add HSTS header** في .htaccess (start بـ max-age=300) | security-deep.md M-3 | 10 min | Omar |
| 2.11 | **Hide `x-powered-by` header** | perf.md M-4 / security-deep.md M-1 | 10 min | Omar |
| 2.12 | **chmod 600 wp-config.php** (test الـ site لسه يـ run) | security-deep.md M-2 | 5 min | Omar |

### Exit Gate

- ✅ `curl -X POST` على xmlrpc.php returns 403
- ✅ Failed login attempt #6 من نفس IP = blocked
- ✅ DB dump `grep wp_mail_smtp.pass` = empty (constant in wp-config instead)
- ✅ `curl /wp-content/debug.log` returns 403 (مش 200)
- ✅ Header check: HSTS + no X-Powered-By
- ✅ UI Audit run = 0 NEW security warnings

### Risks + Mitigation

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| 2FA on admin locks Ahmed out | Low | High | Backup codes saved + Hostinger File Manager fallback documented |
| chmod 600 breaks WP (rare on Hostinger) | Low | Medium | Test على staging أو revert quickly |
| Limit Login Attempts blocks legitimate Ahmed login | Medium | Low | Whitelist Ahmed's IP في plugin settings |
| .htaccess rules break a plugin's expected behavior | Low | Medium | Apply 1 rule at a time، test الموقع بعد كل واحدة |

---

## Phase 3 — Performance + Cleanup (Week 3)

### Goal

موقع وأدمن أسرع. تنظيف operational debt (orphan plugins, autoload bloat, DB junk).

### Tasks

| # | Task | Bug Refs | Estimated | Owner |
|---|---|---|---|---|
| 3.1 | **Enable HPOS + verify sync complete** | plugins.md H-4 | 2 hours | Omar |
| 3.2 | **Plugin reduction wave (61→43)** | plugins.md all C/H | 1 day across wave | Omar |
| 3.2a | → Deactivate 3 inactive (airlift, litespeed, nextend-fb) | plugins.md L-1, C-3 | 10 min | Omar |
| 3.2b | → Pick one SVG (safe-svg أو svg-support) | plugins.md H-5 | 30 min | Omar |
| 3.2c | → Reduce 3 backup plugins → 0 (rely on our backup) | plugins.md C-4 | 1 hour | Omar |
| 3.2d | → Reduce Elementor extras 5 → 2 (after widget audit) | plugins.md H-3 | 2 hours | Omar + Ahmed |
| 3.2e | → Drop wp-events-manager (if no active events) | plugins.md M-2 | 30 min | Ahmed decision |
| 3.3 | **Drop orphan tables (MailPoet × 20)** بعد export subscribers | plugins.md C-2 | 1 hour | Omar |
| 3.4 | **Drop orphan tables (BlogVault Airlift × 3)** | plugins.md C-3 | 15 min | Omar |
| 3.5 | **Drop orphan tables (AIOSEO × 3)** | plugins.md M-3 | 15 min | Omar |
| 3.6 | **Drop orphan tables (WooFunnels × 15)** بعد التأكد CartFlows مش بيحتاج | plugins.md M-4 | 30 min | Omar |
| 3.7 | **Delete `wp-content/ai1wm-backups/` (5.6 GB)** | runtime.md C-2 | 5 min | Omar |
| 3.8 | **Autoload cleanup** (cartflows_docs + astra-settings + إلخ) | perf.md C-1 | 1 hour | Omar |
| 3.9 | **Setup system cron** + `DISABLE_WP_CRON = true` | runtime.md M-4 | 30 min | Omar |
| 3.10 | **Install Index WP MySQL For Speed** | perf.md H-1 | 30 min | Omar |
| 3.11 | **Schema fix:** `cancel` → `cancelled` في wp_posts | integrity.md M-3 | 15 min | Omar |
| 3.12 | **Schema fix:** comment_approved unify | integrity.md M-4 | 15 min | Omar |
| 3.13 | **Clean 1000 orphan postmeta** (via WP-Optimize plugin) | integrity.md H-3 | 30 min | Omar |
| 3.14 | **Clean 659 orphan tutor_enrolled** بعد الـ trash decision | integrity.md H-4 | 30 min | Omar |
| 3.15 | **Cleanup spam users** (guerrillamail + 1-2 letter logins) | integrity.md L-1 | 30 min | Omar |
| 3.16 | **Fix 158 empty-role users** (assign to subscriber) | integrity.md M-1 | 30 min | Omar |
| 3.17 | **Resolve 4 duplicate user emails** (audit + merge) | integrity.md M-2 | 1 hour | Omar |
| 3.18 | **Add `define('WP_POST_REVISIONS', 5)`** + delete old | integrity.md M-6 | 30 min | Omar |
| 3.19 | **Fix CDN cache bypass** (WP-Optimize config) | perf.md H-4 | 1 hour | Omar |
| 3.20 | **Tune Action Scheduler** to 5 min interval | runtime.md M-5 | 15 min | Omar |
| 3.21 | **Reduce Facebook for WC heartbeat** to hourly | runtime.md M-6 | 15 min | Omar |
| 3.22 | **Pick caching plugin once** — WP-Optimize OR LiteSpeed | runtime.md M-7 | 1 hour | Omar |

### Exit Gate

- ✅ `wp plugin list --status=active --format=count` ≤ 45
- ✅ Autoload size < 500 KB (`wp db query "SELECT ROUND(SUM(LENGTH(option_value))/1024,0) FROM wp_options WHERE autoload='yes'"`)
- ✅ wp-content size < 5 GB
- ✅ HPOS verified (`woocommerce_custom_orders_table_enabled = yes`)
- ✅ Lighthouse Mobile Performance > 75 (run from PageSpeed Insights)
- ✅ UI Audit run = 0 failures, ≤ 1 warning
- ✅ Admin dashboard loads < 1.5s

### Risks + Mitigation

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Plugin deletion breaks a feature we didn't notice | Medium | Medium | Deactivate first، test 24h، then delete |
| DB drop deletes data still in use | Low | High | Fresh backup before, table snapshot per drop |
| HPOS enable breaks reporting | Low | Medium | Sync بيكمل، watch logs، rollback ممكن |
| Index changes slow specific queries | Low | Low | Index WP MySQL plugin بيـ revert safely |

---

## Phase 4 — Theme Refactor (Week 4)

### Goal

Pay tech debt: الـ `functions.php` 106KB + 3 ملفات CSS متنافسين على checkout = كل bug فيه سلسلة من 7 PRs (زي اللي عشناه). Refactor واحد = كل fix جاي بقى أسرع.

### Tasks

| # | Task | Bug Refs | Estimated | Owner |
|---|---|---|---|---|
| 4.1 | **Extract `learnsimply_enrich_course()`** helper (front-page + archive-courses) | code.md (button unify) | 1 hour | Omar |
| 4.2 | **Refactor `functions.php`** → `includes/` modules | perf.md H-2 | 2 days | Omar |
| 4.2a | → `includes/twig-setup.php` (filters: safe_html, safe_embed) | | | |
| 4.2b | → `includes/woo-overrides.php` (cart, checkout customizations) | | | |
| 4.2c | → `includes/tutor-overrides.php` (course enrichment, enrollment logic) | | | |
| 4.2d | → `includes/security-hardening.php` (xmlrpc disable, REST limits) | | | |
| 4.2e | → `includes/checkout-cleanup.php` (the inline `<style>` + JS من المرحلة الحالية) | | | |
| 4.3 | **Consolidate checkout CSS** (3 sources → 1) | perf.md H-2 | 4 hours | Omar |
| 4.4 | **Delete `homepage.html`** artifact (330 KB) | perf.md H-3 | 5 min | Omar |
| 4.5 | **Update parent theme `edublink`** 2.0.8 → 2.0.12 | perf.md M-3 | 1 hour | Ahmed |
| 4.6 | **Fix theme dep chain** (`edublink-rtl` missing `edublink-core-main-css`) | runtime.md H-3 | 30 min | Omar |
| 4.7 | **Fix `edublink-core` animation widget** undefined key | runtime.md M-1 | 1 hour | (vendor patch OR MU plugin) |
| 4.8 | **Update OptinMonster** (PHP 8.2+ deprecation) | runtime.md M-2 | 15 min | Omar |
| 4.9 | **Update Tutor LMS + Tutor Pro** (textdomain too early) | runtime.md M-3 | 30 min | Omar |
| 4.10 | **Add CSP header** (report-only mode أولاً) | security-deep.md M-4 | 1 hour | Omar |
| 4.11 | **Cleanup `wp-content/plugins/edublink-child/`** (theme folder خطأ في plugins/) | (caught during deploy) | 5 min | Omar |

### Exit Gate

- ✅ `functions.php` < 30 KB (each include file < 20 KB)
- ✅ Only ONE source styles `.woocommerce-form-login` (verified via grep)
- ✅ Parent theme updated (admin shows no update available)
- ✅ UI Audit run = 0 failures, 0 warnings
- ✅ No deprecated warnings في `debug.log` بعد ما نـ flip WP_DEBUG temporarily ON-OFF

### Risks + Mitigation

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Theme update breaks 9 override files | Medium | High | Backup كامل، test على staging، review كل override بعد update |
| Refactor introduces new bug | Medium | Medium | UI Audit run بعد كل module extract |
| Vendor (ThemeGrill) discontinued edublink | Low | High | Pin current version، document fork plan |

---

## Phase 5 — Future-Proofing (Week 5+)

### Goal

العملية ما تتكسرش تاني. مَن يـ commit تعديل يلاقي الـ regression قبل ما الزبون.

### Tasks

| # | Task | Bug Refs | Estimated | Owner |
|---|---|---|---|---|
| 5.1 | **GitHub Action: UI Audit on PR** ضد staging URL | ui-audit/README "CI integration" | 2 hours | Omar |
| 5.2 | **Cron: Weekly UI Audit + Telegram alert** على new failures | ui-audit/README | 1 hour | Omar |
| 5.3 | **Setup staging environment** على Hostinger subdomain | (Constitution V step 4) | 4 hours | Ahmed |
| 5.4 | **FluentCRM/Mautic full rollout** + import 13K subscribers | gtm-roadmap | 2 days | Omar |
| 5.5 | **Conversion tracking dashboard** (GA4 + Meta Pixel + WC) | gtm-roadmap | 4 hours | Omar |
| 5.6 | **Monitoring: site uptime + page speed** (Hostinger built-in + ping) | infra | 1 hour | Omar |
| 5.7 | **Document Hostinger deploy workflow** في brand CLAUDE.md | (caught: we don't know what Ahmed clicks) | 30 min | Ahmed + Omar |
| 5.8 | **Cross-brand promotion review** for UI Audit tool (Constitution Principle I) | constitution | 30 min | Omar |

### Exit Gate

- ✅ PR على Learrnsimply/edublink-child بيـ block on UI Audit failure
- ✅ Weekly cron يبعت Telegram report
- ✅ Email campaign list segmented + first مail sent
- ✅ Staging URL exists + documented
- ✅ Site monitoring dashboard accessible

---

## Cross-Phase Risks

| Risk | Mitigation |
|---|---|
| Ahmed unavailable for 2+ weeks | Phases 2, 3, 4 mostly Omar-only — skip Ahmed-blockers، complete what's possible |
| Hostinger account issue (billing, downtime) | الـ backup system بياخد snapshots أسبوعياً — migration ممكن |
| Major WordPress update breaks plugins | WP 6.9 → 7.0 hypothetical — test على staging أولاً (Phase 5.3) |
| Constitutional principles change mid-project | لو Constitution v3.0 صدر، revisit gates و alignment |

---

## Success Metrics Per Phase

| Phase | Metric | Target |
|---|---|---|
| 1 | Cancel rate, email deliverability | < 15% cancel, > 90% inbox |
| 2 | Security findings | 0 critical |
| 3 | Plugin count, autoload, wp-content size | 43, < 500KB, < 5GB |
| 4 | functions.php size, regression PRs | < 30KB/module, > 95% UI Audit pass |
| 5 | CI integration, monitoring uptime | 100%, > 99.5% |

---

## Constitution Compliance Check

| Principle | Compliance |
|---|---|
| I — Agency Thinking (manual cross-brand) | ✅ UI Audit tool's potential reuse for dentera/voya/kitc = explicit Phase 5.8 decision |
| II — Two-Layer Architecture | ✅ All work in `brands/learn-simply/` |
| III — Per-Brand Teams + Lesson Flow | ✅ Lessons go to `brands/learn-simply/lessons.md`، manual promotion |
| V — Spec-Driven Execution | ⚠️ Spec Kit format used; mandatory gate (speckit-specify) skipped for brand-tactical work |
| VI — Manual Capture | ✅ Stop hook not used |
| VII — Lean Root | ✅ Brand work stays in brand folder |
