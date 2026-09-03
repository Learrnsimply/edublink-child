# Task 3.11 — Autoload Cleanup Manifest

**Date:** 2026-05-24
**Approach:** Mode B (Hybrid — autoload=no for inactive plugins, DELETE for confirmed orphans)
**WP:** 6.9.4 · **MySQL:** MariaDB 11.8.6

---

## Pre-state backup

- **mysqldump:** `~/db-backups/pre-task-3.11-autoload-cleanup-2026-05-24_132016.sql`
- **Local copy:** `01_WEB/_evidence/pre-task-3.11-autoload-cleanup-2026-05-24_132016.sql`
- **Size:** 303 MB
- **SHA256:** `27adcbe6caae290293f79b3fbe2ccc8be5a42f37df748e806766d0ad69b186f7`

---

## Discovery: WP 6.6+ autoload schema

WordPress 6.6+ introduced expanded autoload states beyond legacy `yes`/`no`:

| State | Meaning | WP loads on every request? |
|---|---|---|
| `yes` | legacy autoload-on | YES |
| `on` | new explicit autoload-on | YES |
| `auto` | new default autoload-on | YES |
| `auto-on` | new variant of auto | YES |
| `off` | new explicit autoload-off | NO |
| `no` | legacy autoload-off | NO |

**True autoload baseline at Gate 1 (before changes):** 1,483 rows / ~1.18 MB
(Earlier 918/0.96 MB count only included `autoload='yes'`, missing the new states.)

---

## Phase 1 — Tier A deletes (orphan plugins, no longer installed)

| Batch | Plugin Family | Rows Deleted |
|---|---|---|
| 1 | Jetpack (`jetpack_*`) | 51 |
| 2 | Jetpack standalone (`jp_*`) | 2 |
| 3 | All-in-One SEO (`aioseo_*`) | 8 |
| 4 | ElementsKit Lite (`elementskit-lite__*`) | 4 |
| 5 | Essential Addons EAEL (`eael_*`) | 6 |
| 6 | WPins telemetry (`wpins_essential_adons_*`) | 3 |
| 7 | Essential Blocks (`eb_*`, `essential_all_blocks`) | 4 |
| 8 | PayPal Commerce (`woocommerce_ppcp*`, `wc_ppcp*`, `woocommerce_paypal_settings`) | 10 |
| 9 | Other Elementor add-ons (happy/visibility/essential-addons license) | 6 |
| **Subtotal Phase 1 Tier A** | — | **94** |

## Phase 1 — Tier B toggles (inactive plugins, autoload moved out of hot path)

| Batch | Plugin | Rows Toggled (yes → no) |
|---|---|---|
| 1 | LiteSpeed Cache (inactive) | 211 |
| **Subtotal Phase 1 Tier B** | — | **211** |

## Phase 2 — Additional orphans (caught by broader autoload state filter)

| Batch | Plugin Family | Rows Deleted |
|---|---|---|
| 10 | PixelYourSite (`pys_*`) | 14 |
| 11 | BuildWooFunnels (`bwf_*`, `bwfan_*`, `wfacp_*`) | 11 |
| 12 | WP Dark Mode (`wp_dark_mode*`, `dracula_*`, `wp-dark-mode_*`) | 99 |
| 13 | Multi Currency (`woo_multi_currency_params`) | 1 |
| 14 | PayPal Commerce residue (auto/on states) | 23 |
| 15 | ElementsKit single-underscore variant | 1 |
| **Subtotal Phase 2 deletes** | — | **149** |

## Phase 2 — Nextend toggle

| Batch | Plugin | Rows Toggled |
|---|---|---|
| 16 | Nextend FB Connect (`nsl_*`) | 2 |

## Tier C — Big-size toggles (active plugins, but data not needed on frontend)

| Batch | Option | Size | Reason |
|---|---|---|---|
| 17 | `cartflows_docs_data` | 485 KB | Help docs cache (admin-only). CartFlows active but data not needed on frontend page loads. |
| 18a | `astra-settings` | 250 KB | Astra theme config — but active theme is edublink-child. Legacy data. |
| 18b | `astra-color-palettes` | 2.43 KB | Same Astra legacy |
| 18c | `astra-typography-presets` | 0.00 KB | Same |
| 18d | `ast-block-templates-version` | 0.01 KB | Same |
| **Subtotal Tier C toggles** | — | **5 entries / ~737 KB autoload payload removed** |

**Kept (Critical, do NOT touch):** `email_template_data` (49 KB) — Ahmed's Tutor LMS welcome emails في عربي.

---

## Final state

| Metric | Before | After | Change |
|---|---|---|---|
| Total autoload-positive rows | 1,483 | 1,023 | **-31%** |
| Total autoload-positive size | ~1.18 MB | **0.36 MB** | **-70%** |
| `autoload='yes'` rows | 918 | 612 | -33% |
| Total changes applied | — | 456 | (243 deletes + 213 toggles) |

## Verification

| Check | Result |
|---|---|
| Homepage HTTP | 200 OK · TTFB 0.34-0.47s (2 rounds) |
| Courses HTTP | 200 OK · TTFB 0.34s · Title: "جميع الدورات - اتعلم ببساطة" |
| Login HTTP | 200 OK · TTFB 1.66-1.85s |
| Checkout HTTP | 302 (redirect to cart, normal for empty cart) |
| Homepage Playwright | Loaded ✓ Title: "اتعلم ببساطة - تعلم البرمجة من الصفر" |
| Courses Playwright | Loaded ✓ Title correct |
| Console errors | 2 pre-existing (init.js + icon-courses.png 404s, unrelated to this task) |
| WP cache flushed | ✓ Twice (after Phase 1 Tier B, after Phase 2, after Tier C) |

---

## Rollback procedures

### Per-option rollback (toggle back)

```sql
-- For any toggled option (Tier B or Tier C):
UPDATE wp_options SET autoload = 'yes' WHERE option_name = '<name>';
```

### Per-orphan-family rollback (re-add from backup)

```bash
# Extract specific option from mysqldump:
grep -oP "INSERT INTO \`wp_options\` VALUES.*?<option_name>.*?;" \
  01_WEB/_evidence/pre-task-3.11-autoload-cleanup-2026-05-24_132016.sql \
  | head -1 > /tmp/restore.sql
# Then execute on server:
ssh learnsimply 'wp db query < /tmp/restore.sql'
```

### Full DB restore (last resort)

```bash
ssh learnsimply 'mysql -h $(wp config get DB_HOST --type=constant) \
  -u $(wp config get DB_USER --type=constant) \
  -p"$(wp config get DB_PASSWORD --type=constant)" \
  $(wp config get DB_NAME --type=constant) \
  < ~/db-backups/pre-task-3.11-autoload-cleanup-2026-05-24_132016.sql'
```

---

## Plugin ownership reference (orphans removed)

The following plugins are confirmed NOT installed on the site as of 2026-05-24 audit:

- Jetpack
- All-in-One SEO (active SEO = Rank Math)
- ElementsKit Lite
- Essential Addons for Elementor
- Essential Blocks
- PayPal Commerce Platform (PPCP) — active payment = Kashier
- PixelYourSite
- BuildWooFunnels (Funnel Builder)
- WP Dark Mode
- Multi Currency
- Happy Addons for Elementor
- Visibility Logic for Elementor

If any of these are re-installed in the future, they will recreate their own options on activation. No functionality lost — only orphan data removed.
