# Learn Simply — Lessons Learned

> Brand-wide accumulated lessons. Read at the start of every meaningful session — these are the "I wish I knew this earlier" facts.
> Format: short rule + **Why** (root cause) + **How to apply**. Newest at top.

---

## 2026-06-11 — Mautic segments filtering on un-imported custom fields silently return 0 (or wrongly include everyone) — filter on TAGS instead

### `wcbuyers` segment showed 0 contacts (real buyers = 118) and `nonbuyers` wrongly included the 118 buyers. Root cause: the 13.9K wp_users import populated `wc_customer_id` but left `course_count` and `total_spent` as `None`.

**Why:** The segment filters were defined on commerce fields that the import never filled:
- `wcbuyers` filtered `course_count > 0` → `course_count` is `None` for everyone → **0 matches** (should be 118).
- `nonbuyers` filtered `course_count empty` → matches ALL 13.9K → it **included buyers** (should exclude them).
- A field is only as good as its population. The import tagged buyers reliably (`wc-customer` tag = 118, tag id 1) but didn't compute per-buyer course counts or spend. So the **tag is the trustworthy signal, not the field.**
- Other field-based segments are empty *by data, not by bug* and will fill as live data syncs: `highvalue` (total_spent>5000), `activecart` (cart_value>0), `whatsappcontacts`, `telegramcontacts`. `dormant90d` (last_active<-90d) correctly = 0 because the import is only ~10 days old — nobody has been dormant 90 days yet. **Don't "fix" these by forcing them; they're behaviorally correct.**

**How to apply:**
- Prefer **tag-based segment filters** over custom-field filters when the field isn't reliably populated at import time. Tags are set deterministically during import; computed fields often aren't.
- Mautic API tag filter (verified working): `{"glue":"and","field":"tags","object":"lead","type":"tags","operator":"in","properties":{"filter":["1"]}}`. Exclude with operator **`!in`** (NOT `notIn` — that returns HTTP 400 "selected choice is invalid").
- After `PATCH /api/segments/{id}/edit`, membership does NOT update until a rebuild: `docker exec mautic-r4bx-mautic-1 php bin/console mautic:segments:update -i {id}`.
- Verify count via API: `GET /api/contacts?search=segment:{alias}&limit=1` → `total`.
- **Rollback (old filter defs, 2026-06-11):** wcbuyers(4) was `course_count gt 0`; nonbuyers(6) was `email !empty AND course_count empty`.
- **Phase 002 TODO:** backfill `course_count` + `total_spent` per buyer from WooCommerce so `highvalue` and field-based commerce segments work as designed.

---

## 2026-06-03 — Mautic 5/6 sends via `mailer_dsn` ONLY; legacy `mailer_*` keys are display-only; `%` needs `%%`

### Emails silently vanished because `mailer_dsn` was the default `smtp://localhost:1025` while the (unused) legacy SMTP keys looked correct. This REVERSES the old "use legacy keys, mailer_dsn breaks it" rule — that earlier conclusion was WRONG.

**Why:** A seed test "sent" (`sentCount=1`) but never arrived. Diagnosis on the VPS showed:
- `messenger:stats` email queue = 0 (drained), `failed` queue = 0, no SMTP error in `mautic_prod` log → Mautic handed it to the mailer "successfully."
- BUT `grep mailer_dsn config/local.php` → **`'mailer_dsn' => 'smtp://localhost:1025'`** (Mautic's default placeholder). `localhost:1025` is CLOSED in prod (confirmed via `fsockopen`) → every email went into the void.
- The legacy keys (`mailer_host => smtp.hostinger.com`, `mailer_port => 465`, etc.) looked perfectly configured — **but Mautic 5/6 uses Symfony Mailer, which reads `mailer_dsn`, NOT the legacy keys.** Those keys are remnants shown in the UI; they do not drive sending.
- The 2026-06-01 "SMTP test verified" was a **false positive**: the UI "Test Connection / Send Test Email" button tests the legacy SMTP fields, not the real `mailer_dsn` send path. It will go green while real sends vanish.

**Then a second trap:** setting `mailer_dsn` to the URL-encoded DSN (`smtp://contact%40learrnsimply.com:<pass>@…`) broke `cache:clear` with `non-existent parameter "40learrnsimply.com:…"`. Symfony treats `%…%` in container parameters as a **parameter reference**. A `%` from URL-encoding (the `%40` for `@`, or `%XX` in the password) is read as a placeholder delimiter.

**How to apply:**
- **Trust delivery only when a seed test lands in a real inbox.** Never trust `sentCount`, the green Test-Connection button, or "no error in log." All three lied here.
- The real mailer config is `mailer_dsn` in `config/local.php`. To fix SMTP, set:
  `'mailer_dsn' => 'smtp://<urlenc-user>:<urlenc-pass>@smtp.hostinger.com:465'`
  with the username's `@` encoded as `%40`, then **double every `%` to `%%`** (Symfony container-param escaping) → stored value looks like `smtp://contact%%40learrnsimply.com:…@…`.
- After editing `local.php`: `cache:clear` **as www-data** (`docker exec -u www-data …`) or chown `var/cache` after, then **restart the worker container** so the `messenger:consume email` consumer reloads the new DSN. Verify Mautic still serves (`curl /s/login` → 301/200).
- Diagnostic one-liners: `grep mailer_dsn config/local.php` · `php bin/console messenger:stats` · `SELECT COUNT(*) FROM messenger_messages WHERE queue_name='failed'` · `fsockopen('localhost',1025)`.
- ⚠️ This corrects [[feedback_mautic5_legacy_smtp_keys]] in workspace memory — the legacy-keys approach does NOT send on Mautic 5/6.

---

## 2026-06-02 — cold-list import → activity segments are empty; Mautic segments filter FIELDS, not tags

### When you import a never-emailed list into Mautic, the engagement segments (engaged_30d / dormant_90d) come back ~empty, and tag-based identity does NOT populate field-based segments

**Why:** Imported 13,709 WP registered users into Mautic, then ran `mautic:segments:update`:
- `engaged30d = 1`, `dormant90d = 0` — imports have **zero prior engagement history** in Mautic (they were never emailed before this), so "active in last 30d" matches almost nobody, and "last active >90d ago" doesn't match a NULL last-active either. Imported contacts sit in a "never engaged" limbo, in neither bucket.
- `wcbuyers = 0`, `highvalue = 0` even though 89 contacts carry the `wc-customer` **tag** — because those segments filter on **fields** (`wc_customer_id`, `total_spent`), and the import set only the tag, not those fields.
- `allcontacts = 13,709`, `nonbuyers = 13,709` — everyone.

The original GTM plan ("activation broadcast to engaged_30d ~3-5K") silently assumed engagement data that a cold import simply doesn't have.

**How to apply:**
- For any cold-list activation: **don't target activity-based segments** (empty for imports). Target by tag (`wp-import`), registration recency, or send a **re-engagement email first** and let openers become the engaged cohort you build on.
- Mautic segment membership is field/activity-driven. To get a contact into `wcbuyers`/`highvalue`, populate the **fields** (`wc_customer_id`, `total_spent`) — a tag alone won't do it unless a segment explicitly filters on that tag.
- Mautic `/contacts/new` and `/contacts/batch/new` **upsert on email** natively (verified) — re-running an import is idempotent, no duplicates. Batch endpoint (100/req) imported 13.7K in ~5 min.
- After any bulk import, run `mautic:segments:update` (cron also does it on schedule) and read the **real** segment counts BEFORE planning a broadcast — size assumptions are usually wrong.
- Reusable tooling lives in `_tools/fetch_users.py` (wp-cli pull over SSH) + `_tools/import_to_mautic.py` (batch upsert). Shared-host SSH is password-auth via paramiko (laptop has no key on that host).

---

## 2026-05-24 — "Plugin active" status lies — verify pixel firing in browser, not in admin UI

### When auditing analytics/tracking integrations, the plugin admin's green checkmark means almost nothing — always inspect the actual frontend HTML output

**Why:** Today's Analytics Audit found `facebook-for-woocommerce` plugin showed:
- Status: **active** (green in admin)
- `facebook_capi_integration_status = 1` (active integration!)
- `wc_facebook_external_business_id` populated (catalog handshake succeeded)
- `facebook_for_woocommerce_is_active = 1`

…and yet the actual `facebook_config.pixel_id = 0` (literally zero) and `access_token = ""` (empty string). **Browser HTML shows ZERO `fbq()` calls, ZERO `connect.facebook.net` references.** The pixel is completely uninstalled — yet every admin-side signal said "active."

The trap: the FB plugin has TWO separate concerns:
1. **Catalog sync** (product feed to Meta Commerce) — uses `external_business_id` + `feed_url_secret`. Can be "active" with zero Pixel.
2. **Pixel + CAPI** (conversion tracking) — needs `pixel_id` + `access_token`. These were deleted/never set.

The plugin reports OVERALL status based on catalog, not on Pixel. So Ahmed sees "Facebook plugin: Connected ✓" in admin and has been running Meta ads for months/years thinking it works.

**How to apply (mandatory checklist when auditing analytics):**
1. **Plugin layer (admin):** What plugins claim to track? List from `wp plugin list --status=active`.
2. **DB options layer:** Extract the actual IDs and tokens. Are they non-empty AND non-zero?
   - `wp_options.facebook_config` → check `pixel_id` AND `access_token` separately
   - `tt4b_pixel_code`, `monsterinsights_settings.v4_id`, etc.
3. **Frontend HTML layer (curl as user):** Does the actual page output contain the script tags?
   - `grep -c 'connect.facebook.net' homepage.html`
   - `grep -c 'fbq(' homepage.html`
   - `grep -c 'analytics.tiktok.com' homepage.html`
4. **Network layer (Playwright):** Do the events ACTUALLY fire (not just script loaded)?
5. **Conversion layer (thank-you page):** Does the `Purchase` / `purchase` / `CompletePayment` event fire on order completion?

Never trust an admin UI checkmark alone. **The pixel exists IF AND ONLY IF the browser console can see it fire.**

**Bonus rule for CAPI:** "CAPI integration active" sometimes means only catalog/Microdata events, NOT conversion events. Always check the `events_filter` setting — for purchase tracking, you need `Purchase` explicitly in the filter list.

---

## 2026-05-24 — WordPress 6.6+ uses expanded autoload states beyond `yes`/`no`

### When auditing `wp_options.autoload`, query for ALL autoload-positive values, not just `'yes'`

**Why:** Task 3.11 initial audit ran `SELECT ... WHERE autoload='yes'` and counted 918 rows / 0.96 MB. After Phase 1 cleanup (94 deletes + 211 toggles), I discovered the full picture:

| autoload state | meaning | rows in DB | WP loads on every request? |
|---|---|---|---|
| `yes` (legacy) | autoload-on | 612 | YES |
| `on` (WP 6.6+) | explicit autoload-on | 107 | YES |
| `auto` (WP 6.6+) | new default autoload-on | 458 | YES |
| `auto-on` | variant | rare | YES |
| `no` (legacy) | autoload-off | 452 | NO |
| `off` (WP 6.6+) | explicit autoload-off | 2416 | NO |

**True autoload-positive baseline was 1,483 rows — not 918.** I missed 565 entries by only querying `autoload='yes'`. WordPress 6.6 (Aug 2024) introduced the new schema for better autoload management; rows can have ANY of `yes`/`on`/`auto`/`auto-on` and WP loads all of them on every request.

The miss meant I almost left orphan options behind from plugins like PixelYourSite (14 entries), BuildWooFunnels (11), WP Dark Mode (99 entries!), Multi Currency (1), and PPCP residue (23) — most of which had `autoload='auto'` not `'yes'`. Phase 2 caught them only because I noticed `nsl_google` returned 0 from a query that expected to find it.

**Final cleanup ratio:** 1,483 → 1,023 autoload-positive rows (-31%), 1.18 MB → 0.36 MB (-70%) — but it took TWO phases because Phase 1 was blind to the new states.

**How to apply:**
- For ANY `wp_options.autoload` audit on WP 6.6+, use: `WHERE autoload IN ('yes', 'on', 'auto', 'auto-on')`
- To see all states + counts: `SELECT autoload, COUNT(*), SUM(LENGTH(option_value)) FROM wp_options GROUP BY autoload`
- The "fast drawer" in WP analogy = ALL of these states, not just `yes`
- When a plugin is uninstalled, its options often keep their original autoload state (which might be `on` or `auto` for newer plugins)
- Disabling autoload: use `UPDATE ... SET autoload='off'` (not `'no'`) on WP 6.6+ for consistency with new schema
- Even safer: use the `wp_set_option_autoload_values` filter rather than raw SQL when WP-based plugins are involved

---

## 2026-05-24 — "DB index plugin" is misleading: this plugin restructures PRIMARY KEYS, not just adds keys

### When applying performance plugins that modify schema, always read the dry-run SQL — the operation may be deeper than the name implies

**Why:** Task 3.16 = "Install Index WP MySQL For Speed". The name + description implies adding indexes. The dry-run showed the plugin actually:
1. DROPs the existing PRIMARY KEY on most meta tables (wp_postmeta, wp_usermeta, wp_commentmeta, wp_termmeta, wp_woocommerce_order_itemmeta)
2. ADDs a compound PRIMARY KEY like `(post_id, meta_key, meta_id)`
3. DROPs the existing secondary keys
4. ADDs new keys that include `meta_value(32)` prefix indexes

This is Ollie Jones's well-known design (he writes for Percona) — compound primary keys on meta tables make `WHERE post_id=X AND meta_key=Y` queries (the bread-and-butter of WP) 5-10x faster. But it ISN'T a simple "add indexes" op.

**Risk that would have been missed if we trusted the name:**
- ALTER TABLE on wp_postmeta (171 MB, 258K rows) takes ~26 seconds of table lock — the site queues requests during that window
- During the alter, MySQL is rebuilding the entire table on disk (file_per_table copy approach)
- Index storage roughly doubles (wp_postmeta: 23 MB → 44 MB; wp_usermeta: 34 MB → 64 MB)
- If we'd run `wp index-mysql enable --all` blindly without tiering, a heavy table alter would have blocked the whole site for 30-60s

**Outcomes from doing tiered:**
- Tier 1 (small tables): 1s total lock
- Tier 2 (medium): 1.2s total lock
- Tier 3 (wp_usermeta 390K rows): 8.47s lock
- Tier 4 (wp_postmeta 258K rows): 26.06s lock
- Verifications between tiers caught zero issues — we could continue with confidence

**How to apply:**
- For ANY "performance plugin" that touches DB schema: ALWAYS run `--dry-run` first and read the SQL
- Tier execution by table size (rows × data MB) — smallest first, biggest last
- mysqldump before, scp local copy + SHA verify (defense in depth)
- Quick HTTP check between tiers (curl -sI, not full Playwright every time)
- Note that information_schema.table_rows is APPROXIMATE for InnoDB — direct `SELECT COUNT(*)` for true counts
- Background processes (Action Scheduler, WC sessions, transient cleanup) cause row drift between baseline and post-alter measurements — don't panic at -16K rows if it correlates with elapsed time

---

## 2026-05-24 — re-inspect filesystem before disk cleanup, not just audit numbers

### Cleanup tasks should fresh-inspect the filesystem; audit numbers from weeks ago can be incomplete

**Why:** Task 3.8/3.9 plan said "delete ai1wm-backups (5.6 GB)". Fresh inspection on the day of execution found `wpvividbackups/` (3.8 GB!) — a folder the original Wave 2 audit didn't quantify. It also found:
- An `.it6d4d` partial upload file (failed upload, taking 3.2 MB)
- Orphan staging backups from `beta.learrnsimply.com` (subdomain no longer resolves)
- Pre-existing protective files (`.htaccess`, `index.html`, `web.config`) that must NOT be deleted

The actual deletion scope was **68% larger** than planned (5.6 GB → 9.4 GB) and **3x more files** (2 → 33). If we'd executed blindly on the audit numbers, we would have:
- Missed 3.8 GB of free space (left WPvivid sitting)
- Possibly deleted protective files thinking they were backup metadata

The actual disk reclaim ended up at **10.1 GB** (additional savings from fs metadata cleanup after large file removal).

**How to apply:**
- Before any disk cleanup task: `du -sh wp-content/*/` to see CURRENT top folders (don't trust the audit)
- Cross-reference each candidate folder with its owner plugin via `wp plugin list` — even "trash" folders have owners
- Look for staging/beta prefixes in backup filenames — they're often orphan from removed subdomains
- Always grep for protective files (`.htaccess`, `index.html`, `index.php`, `web.config`, `robots.txt`) BEFORE rm — those preserve directory structure + security
- Save a manifest (filenames + sizes + dates) to `_evidence/` + commit BEFORE deletion. Audit trail for "what was there"
- Test-delete oldest single file first → verify site 200 → then batch delete remainder

---

## 2026-05-24 — re-verify active plugin list before flagging folders as "suspicious"

### Verify the active plugin list before flagging uploads/ folders as orphan/suspicious

**Why:** Wave 2 audit flagged `wpsynchro-69e64efa3d42c/` as "suspicious uploads PHP files" because the folder name has a random-looking hash suffix. That looks like a backup/temp/malware pattern at first glance. But re-verification via `wp plugin list` showed **wpsynchro is ACTIVE v1.14.0** — the hash is the plugin's install/session identifier, not a malware marker. Same for `redux/` (Redux Framework active), `wpforms/` (WPForms active), `wpo/` (WP-Optimize active).

If we'd acted on the audit without verification, we would have deleted folders that active plugins depend on → silent breakage at the next sync run + customer-facing failures.

The audit's `bugs-security-deep.md H-1` (suspicious uploads PHP files) was therefore partially a false positive. The truly orphan folders are: `mailpoet/` (plugin uninstalled), `al_opt_content/` (Airlift inactive — 236 MB cache).

**How to apply:**
- For any "this folder/file looks suspicious" finding in uploads/:
  1. Run `wp plugin list` and check if a plugin matches the folder name OR could legitimately create it
  2. `grep -rE "folder_name" wp-content/plugins/ wp-content/themes/` to see if any active code references it
  3. Only AFTER both come back negative, treat as orphan/suspicious
- Hash-suffixed folder names are NORMAL for plugins like wpsynchro (install ID), WPForms (session), CartFlows (campaign ID). Hash != malicious.
- Random-looking filenames are common in plugin internals — verify before assuming malicious intent
- Cross-reference: this connects to [[verify-effective-state-not-stored]] — same principle, different domain

---

## 2026-05-23 — infrastructure-blocked task = park, don't half-build

### Defer infrastructure-dependent tasks until infrastructure exists. Don't half-build on shared hosting and plan to migrate.

**Why:** Phase 1 Task 1.1 (cart recovery) needed an email automation tool. The "thinnest slice" instinct said: install something on Hostinger shared now, migrate to VPS later when ROI proven. Math looked tempting: 8 hours now → 4-5 hours migration later = 13 hours total vs 10 hours direct VPS.

**Reality:** Hostinger shared has known killers for Mautic (CageFS, 5-min cron minimum vs 1-min recommended, 1.5 GB memory limit, 300s exec time, no Docker). Setup would have been fighting the constraints, and migration "later" tends to slip because the half-broken thing keeps technically running.

Plus Omar stated explicit preference: "بفضل دايما نبدأ صح Long term حتى لو هتاخد وقت أكبر" — see [[long-term-over-quick-fix]] in memory.

**What we did instead (2026-05-23):** Parked Task 1.1 + 1.6 in `specs/001-bug-remediation-90day/plan.md` under "PARKED — pending VPS purchase". Created Phase 1.5 (post-VPS) with full Mautic deployment plan. Continued Phase 1 with tasks that don't depend on email infrastructure (1.2, 1.5, 1.8, 1.9, 1.10).

**How to apply:**
- If a task depends on infrastructure that doesn't exist yet AND that infrastructure is a known purchase/decision the user will make → park the task explicitly with a status marker (⏸️ PARKED), document the blocker, and continue with parallel tasks that aren't blocked
- Don't suggest "temporary shared hosting setup → migrate later" for tools that fight shared hosting constraints (Mautic, n8n, Plausible, anything with workers/queues/precise cron). Use shared hosting only for things designed for it (WordPress, static sites)
- The "right tool on wrong infrastructure" produces worse results than "wait + right tool on right infrastructure"
- Parallel work principle: parked task ≠ blocked project. Phase 1 has 7 ready-to-execute tasks that don't need VPS.

---

## 2026-05-23 — second verification pass (deep audit refinement)

### Ask the operator before treating "weird data state" as a bug — Ahmed's manual workflow is invisible to the DB

**Why:** Wave 1B flagged 662 processing orders as "92K EGP stuck money — needs urgent recovery". The data looked perfect for that diagnosis: 345 orders with real money (100-499 EGP each), some up to 7+ months old, no completion. Classic "gateway didn't fire webhook" pattern.

**Reality:** Ahmed was manually enrolling those customers into courses outside the WC flow. The original checkout bug (whatever it was) got fixed silently long ago, but Ahmed kept doing the manual enrollment for the affected orders. From the customer's perspective: they paid, they got the course, life is good. From the DB's perspective: the orders are stuck in `processing` forever, but that's just cosmetic — nothing actually broken.

**If we had executed the plan:**
- "Bulk-cancel zero-EGP orders" → would have sent 316 "your order was cancelled" emails to customers who already have their courses → confusion + support tickets
- "Manually flip real-money to completed" → would have triggered 345 "your order is now complete!" emails to customers who got that email 6 months ago via the original manual path → confusion + duplicate enrollment notifications

**How to apply:** Before treating any "weird historical data" as actionable:
- Ask the operator: "I see X orders in state Y for Z months. Was this normal workflow or an unresolved bug?" — one Slack/Telegram message saves a rollback.
- The DB shows the system state. The operator's manual workflow is the **off-the-books** state. The two can diverge and that's not always wrong.
- Same applies to: orphan records (maybe used by a custom script), "abandoned" enrollments (maybe gifted), 0-EGP orders (maybe internal test/QA/employee), etc.

**Cost of the lesson:** Almost zero — Ahmed caught this in a one-line correction. But the rollback cost if we'd executed would have been 600+ angry-customer emails.

---

### Verify EFFECTIVE state, not stored value — config options can be overridden by plugins/constants

**Why:** Wave 2 audit flagged 4 Critical findings that turned out to be wrong or overstated after re-verification with Ahmed's pushback ("Kashier is working"):

1. **`woocommerce_email_from_address` = Gmail** → flagged as deliverability disaster. Reality: `wp-mail-smtp` plugin has `from_email_force = true` with `contact@learrnsimply.com`. The WC option is dead — every actual email uses contact@. **Reading one option in isolation gave a misleading picture.**
2. **Kashier plugin folder `-master`** → flagged as "unofficial GitHub download = root cause of 909 failed CC". Reality: that IS the official distribution channel for Kashier (their repo is `Kashier-payments/Kashier-WooCommerce-Plugin`, distributed via GitHub not WordPress.org). Plugin processed 102 successful orders in last 30 days. The `-master` suffix is just GitHub's ZIP naming.
3. **1645 "active" WC sessions** → claimed 150K EGP cart recovery opportunity. Reality: 100% of them are >18 days stale (WC sessions expire in 48 hours normally). They're abandoned records, not live shoppers. Cart recovery would yield zero on these.
4. **909 failed CC = gateway leak** → assumed Kashier broken. Reality: cancel rate is real (39% on cards last 30 days) but it's natural abandonment + 3DS friction, not a broken integration. Optimization needed (installments, Apple Pay), not migration.

**How to apply:** Before tagging anything Critical:
- For config options: also check if a plugin or `wp-config.php` constant overrides them. `wp-mail-smtp`, `Jetpack`, security plugins all override silently.
- For "X is broken because of Y" inferences: **find a recent successful transaction first**. If 102 orders went through Kashier yesterday, the gateway isn't broken — the cancel rate has another cause.
- For "X exists in N quantity" claims: check the **age distribution** of those records. 1645 sessions sounds active but if they're all >18 days old, they're not.
- For audit findings driven by naming conventions (`-master`, `temp`, `backup`): check the actual file contents + the vendor's distribution model.
- Add a "Verification" footnote to every Critical finding documenting what SSH/wp-cli command established the claim. This makes the next reviewer (or future you) able to re-check fast.

**Cost of the lesson:** ~2 hours of plan-rewriting + nearly executing a Kashier migration that would have broken a working payment flow. Caught only because Ahmed pushed back.

---

## 2026-05-23 — first deep session

### `wp db export` silently fails on Hostinger CageFS

**Why:** Hostinger's CageFS restricts the subprocess wp-cli spawns for `db export`. The command returns exit 0, produces zero bytes, no error message. We discovered this when the first backup snapshot was 20 bytes.

**How to apply:** For any DB dump on Hostinger, skip `wp db export` entirely. Use `mysqldump` directly with credentials sourced from `wp config get`. The backup script in `backups/scripts/backup.sh` already does this — copy that pattern for any new tooling.

---

### `crontab -e` is blocked on Hostinger shared plans

**Why:** CageFS removes the `crontab` binary from PATH on shared hosting. Cron jobs **must** be created via hPanel → Advanced → Cron Jobs UI.

**How to apply:** When automating anything on Hostinger, plan for hPanel UI as the only cron path. Don't waste a turn trying `ssh learnsimply "crontab -e"`. Write a clear UI walkthrough for Omar to click through; on this Hostinger UI, the weekday `7` = Sunday (Vixie cron semantics, both `0` and `7` accepted).

---

### Domain is `learrnsimply.com` (double r) — not a typo

**Why:** Ahmed registered the domain with a double `r`. Any audit/lint that flags `learrnsimply.com` as "looks like a typo" is a false positive. Real example: the code-audit agent confidently claimed it should be `learnsimply.com` and we almost shipped a "fix" that would have broken every link.

**How to apply:** Whenever you see `learrnsimply` in code or copy, leave it alone. The only valid `learnsimply.com` (single r) would be a future redirect domain Ahmed might buy — doesn't exist today.

---

### `/blog/` returns 404 to curl but works in browsers

**Why:** Hostinger or a bot-protection layer treats non-browser User-Agents differently. `curl -A "Mozilla/5.0"` still gets 404, but a real Chromium (Playwright) loads the page fine. The audit ran via curl and flagged BUG-001 as broken — Playwright disproved it.

**How to apply:** For any HTTP-level finding on this site, **verify with a real browser** (Playwright) before accepting it. Server-side renders, bot rules, and edge caches all behave differently for bots vs browsers. Trust the browser, not curl, for "does the user actually see X?" questions.

---

### Posts use root-level permalinks, not `/blog/<slug>/`

**Why:** WordPress permalink structure on this site is `/%postname%/` not `/blog/%postname%/`. So `/blog/` archive works, but individual articles live at `/<slug>/` (e.g., `/prompt/`).

**How to apply:** If you ever propose changing the navigation to use `/blog/<slug>` URLs, that requires a permalink rewrite on every existing post — heavy SEO consequences. The current scheme is a `permalink choice` Ahmed made; don't undo it lightly.

---

### Ahmed has two git identities — `Ahmed Adel` AND `mrrobot5-a`

**Why:** Both authors use the email `ahmedadel123422@gmail.com`. `mrrobot5-a` (73 commits, mostly experimental/test/revert) is Ahmed's working identity; `Ahmed Adel` (1 commit, a PR merge) is his "formal" signature.

**How to apply:** When reading the theme repo's commit history, treat `mrrobot5-a` as Ahmed. The repo's `git shortlog -s -n --all` is misleading at first glance. Also explains the "test"/"asd"/"ads" commits — those are Ahmed iterating on production, not random commits.

---

### Audit findings need verification before acting on them

**Why:** The 2026-05-23 audit reports had multiple false positives we caught only by checking:
- BUG-001 (`/blog/` 404) — wrong, page works in browser
- BUG-003 (duplicate `<title>` in header.php) — the source isn't in header.php; either parent theme or plugin
- "learrnsimply.com is a typo" — wrong, that's the real domain

**How to apply:** Before shipping any fix for an audit finding, **reproduce the symptom yourself** at the layer the audit ran (HTTP, code, DB). If the audit ran HTTP and you can fix it via code, also verify the code path actually contains what the audit thought it did. Saves shipping a broken "fix."

---

### Hostinger's daily backup is Layer 1 — don't over-engineer offsite

**Why:** Hostinger Business plans include automated daily backups with 7-30 day retention. That covers most recovery scenarios already. The GitHub weekly snapshot we built is **offsite redundancy** — different failure mode (Hostinger account loss, GitHub-side disasters), not "daily granularity."

**How to apply:** If asked to "make backups more frequent," first ask: what recovery scenario is this protecting against that Hostinger's daily doesn't already cover? Often the right answer is "test the Hostinger restore actually works on staging" — not add another layer.

---

### Plugin-level audit was already done — `comprehensive-audit.md` is the canonical entry

**Why:** Six audit files exist (`audit-channels`, `audit-commerce-deep`, `audit-technical`, `audit-tracking-funnel`, `audit-code-findings`, `phase-0-audit`). The `comprehensive-audit.md` is the **master synthesis** with the 10-dimension scorecard and Master Action List.

**How to apply:** New session, want the lay of the land? Open `comprehensive-audit.md` first. Drill into the specific audit file only when you need details behind a finding. Don't re-audit what's been audited.
