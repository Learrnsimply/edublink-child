# HANDOFF — 01_WEB Lane 2 (+ Cart prep) — 2026-06-23

> Resume reference for the solo web session. Full bug triage = `_triage-2026-06-23.json` (81KB, 133 bugs classified).
> Brand router: `../CLAUDE.md`. Bug master: `bugs-report.md`.

## Session scope (Omar's brief)
Solo-only web work (no waiting on Ahmed). Order: **Lane 2 (bugs) → Cart Recovery → Lane 3 (perf/DB)**.
Omar has merge rights on `Learrnsimply/edublink-child`. Rule: **merge ≠ deploy** — theme deploys via scp + verify LIVE.

## Access / environment (THIS machine = central VPS, Linux, user `omar`)
- **WP host SSH:** alias `learnsimply` (added this session) → `46.202.158.231:65002`, user `u791284659`. Key `~/.ssh/id_ed25519` **seeded into host authorized_keys** → key-based login works. Password fallback in brand `.env` §SSH (`Oo0164795891@`); use SSH_ASKPASS method if key ever fails.
- **WP path:** `/home/u791284659/domains/learrnsimply.com/public_html`. wp-cli 2.12.0. WP 7.0. host `de-fra-web1814`.
- **Theme on server = NOT git** → deploy by scp. `THEME_SRV=.../wp-content/themes/edublink-child`.
- **Submodule:** `01_WEB/website` synced to `origin/main`. Current main HEAD after this session = **f9a8775**. Parent brand-repo gitlink bump (`M 01_WEB/website`) still UNcommitted — do at session wrap (ls-wrap).

## DEPLOY PROTOCOL (proven this session)
1. branch off main → edit → PR → `gh pr merge <n> --repo Learrnsimply/edublink-child --squash --delete-branch` → `git checkout main && git pull`.
2. Per changed file: server md5 MUST == `git show <base>:<file>` (no drift) before scp. (Zero drift confirmed: all 552 tracked files matched origin/main.)
3. `scp <file> learnsimply:$THEME_SRV/<file>` → re-verify server md5 == new local.
4. PHP files: `php -l` on server first.
5. Purge cache: `wp eval 'wpo_cache_flush();'` (WP-Optimize is the LIVE cache).
6. Verify LIVE: cache-busted curl (`?v=$RANDOM`) bypasses wpo; for visuals `01_WEB/_tools/ui-audit` (Playwright installed, chromium-1228).

## DONE this session ✅
- **Server fixes (direct SSH):** wp-config.php `644→600` (migration regression); HSTS `max-age=300→31536000`. Both LIVE-verified.
- **PR #15** (merged+LIVE): HIGH-05 instructor_title, BUG-006 contact CTA→wa.me/201030127228, BUG-011 footer typos, BUG-014 trailing slash, LOW-03 duration `40 ساعة→غير محدد`, perf-H-3 removed orphan homepage.html.
- **PR #16** (merged+LIVE, zero visual change): HIGH-07+HIGH-08 — homepage bundle card pinned to Java bundle via `$context['featured_bundle']` (slug `java-basics-oop-bundle`), price/id/link/avatar now from live WC. **Java bundle = product 33336** (849/2150/61%). ⚠️ The audit's naive `bundles[0].id` fix would've added WRONG product (Data Structures 39043) to cart — caught pre-deploy.

## NEXT — remaining Lane 2 theme work
- **A2 — ✅ DONE + LIVE-verified (PR #17, squash-merged, main HEAD = f9a8775):**
  - HIGH-02: removed duplicate `edublink_child_enqueue_fonts` (exact dup of `learnsimply_enqueue_ibm_plex_font`); moved homepage Inter out of render-blocking `@import` → non-blocking `wp_enqueue_style` (homepage-scoped, new fn `learnsimply_enqueue_inter_font`); dropped dead Readex Pro. Verified: no `@import`/Readex on homepage, `inter-font` link present, features section renders identically (`_evidence/features-section-{desktop,mobile}-2026-06-23.png`, title font = IBM Plex Sans Arabic).
  - HIGH-09: inline `<script>window.ajaxurl` removed from `single-course.twig`; `wp_localize_script('edublink-single-course-script','lsCourseAjax',…)` added in `edublink_child_load_page_assets`; `script.js` reads `lsCourseAjax.url`. Verified live on `/courses/data-structure-c/`: `lsCourseAjax` present, no inline ajaxurl.
  - BUG-017: `template_redirect` @ priority 0 redirects `is_author()`/`?author=N` → home 301. Verified: `?author=1` and `/author/admin/` both 301 → home (no `/author/{slug}` leak).
  - Deploy: functions.php via temp `.new` + `php -l` (clean) + atomic mv + backup `functions.php.bak-a2` on server; all 4 files md5-verified; wpo flushed; site 200.
- **C (LEFT AS-IS per Omar — DO NOT TOUCH):** MED-01 fake countdown, MED-04 "3 أيام", MED-07 fake buyer-name toast, LOW-01 hero stats, LOW-02 "12 مكان" fake scarcity. (`cta-section.twig`, `single-product*.twig`, `assets/global/script.js`, `hero-section.twig`.)
- **D (deferred refactors):** MED-02 page-type dedup (functions.php ~130 lines), LOW-04/LOW-05 extend base.twig, perf-H-2 split functions.php 111KB.

## soloServer — ✅ DONE (2026-06-23, Omar-greenlit) + much was already obsolete
Read-only diagnosis flipped the premise — most of the batch was stale:
- **Already done / no-op:** disk (server now 21T/53%/9.9T-free; ai1wm=44K, wpvivid=36K — the 5.6GB was freed 2026-05-24); WC From-Address already `contact@learrnsimply.com`; optinmonster already current (2.16.24, no update).
- **Executed (Omar approved):**
  - Deactivated `duplicator`, `wpvivid-backuprestore`, `tutor-lms-migration-tool` (kept `all-in-one-wp-migration` active as in-WP backup). Reversible.
  - `WP_POST_REVISIONS=5` (wp-config constant); pruned **2,954 → 0** revisions (post_type=revision, --force).
  - Action Scheduler: cleaned **20,855** complete+canceled; **kept failed=1702 + pending=32**.
  - Safety: mysqldump of wp_posts + AS tables → `~/ls-soloserver-backup-2026-06-23/affected-tables-pre-cleanup.sql.gz` (3.6M, verified). wp-config stayed `600`. Site 200 throughout.
- **Still pending (need Omar / not done):**
  - DISABLE_WP_CRON + real cron → needs **hPanel UI** (can't crontab from SSH).
  - `svg-support` vs `safe-svg` dedup → deferred (svg-support may render inline SVGs in content — needs a content check before deactivating).
  - AS **1702 failed actions since 2024-03-08** → investigate the recurring failing job (separate from cleanup).
  - Optional: `OPTIMIZE TABLE` to reclaim freed space (skipped — locks tables on live site).
  - plugins-H-4 (HPOS enable) — commerce-sensitive, coordinate with Ahmed.

## Lane 2.5 — CART RECOVERY (verified read-only, NOT built yet)
Live snapshot 2026-06-23 (`wp_woocommerce_sessions`): 496 total / 426 active / 337 w-email / **57 active carts w/ email+value = ~36,032 EGP** (avg ~632). It's a CONTINUOUS flow (catch future abandonment), not a one-shot of 57. Build: n8n flow (sessions → Mautic segment → Brevo drip) + Arabic RTL copy. **Sending GATED on Omar approval** (Brevo cap 300/day → newest/highest-value first). Exclude anyone who later completed an order. Don't email mid-checkout (wait for real abandonment gap).

## Lane 3 decisions
- **Cache: switch to LiteSpeed Cache (Omar APPROVED)** — server is LiteSpeed (Hostinger), litespeed-cache plugin installed-but-inactive, wp-optimize active. Execute in Lane 3 (careful, verify). Until then deploys purge via wpo.
- Orphan tables: **231 tables** (~150 extra from MailPoet/BlogVault/AIOSEO/WooFunnels) → cleanup with backup first.
- Autoload healthy (~150KB/604), wp-content 3.3GB — not pressing.
