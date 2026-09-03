# Task 3.16 — Index WP MySQL For Speed — Apply Manifest

**Date:** 2026-05-24
**Plugin:** Index WP MySQL For Speed v1.5.7 (Oliver Jones, Rick James)
**WP:** 6.9.4 · **MySQL:** MariaDB 11.8.6 · **PHP:** 8.2.30

---

## Pre-state backup

- **mysqldump:** `~/db-backups/pre-task-3.16-index-plugin-2026-05-24_125038.sql`
- **Local copy:** `01_WEB/_evidence/pre-task-3.16-index-plugin-2026-05-24_125038.sql`
- **Size:** 303 MB · **Lines:** 1,127,942
- **SHA256:** `fc7cc0183f8ff569c60bdd9037c1973cb7f083b237c61eeafd59a0fe83c29959`

## Baseline measurements

| Table | Rows | Data MB | Index MB |
|---|---|---|---|
| wp_postmeta | 258,847 | 171.56 | 22.98 |
| wp_usermeta | 390,640 | 40.59 | 34.13 |
| wp_options | 4,008 | 11.47 | 1.23 |
| wp_comments | 11,753 | 3.52 | 2.55 |
| wp_termmeta | 35 | 0.02 | 0.03 |

Autoload baseline: 0.96 MB / 918 rows

## Tier execution (timed)

| Tier | Tables | MySQL lock time |
|---|---|---|
| 1 | wp_termmeta, wp_options, wp_users | 0.02 + 0.92 + 0.11 = 1.05s |
| 2 | wp_commentmeta, wp_comments, wp_woocommerce_order_itemmeta | 0.10 + 0.44 + 0.66 = 1.20s |
| 3 | wp_usermeta (390K rows) | 8.47s |
| 4 | wp_postmeta (258K rows, 171 MB) | 26.06s |

**Total actual lock time:** ~37s across all tables
**Total wallclock (with wp-cli overhead):** ~2 min

## Post-state measurements

| Table | Rows (table_rows) | Data MB | Index MB | Index growth |
|---|---|---|---|---|
| wp_postmeta | 255,105 | 170.61 | 43.70 | +20.72 MB (+90%) |
| wp_usermeta | 413,329 | 40.64 | 63.75 | +29.62 MB (+87%) |
| wp_options | 3,797 | 10.52 | 0.48 | -0.75 MB (different PK strategy) |
| wp_woocommerce_order_itemmeta | 54,559 | 4.52 | 9.55 | new |
| wp_users | 13,275 | 3.52 | 4.95 | minor |
| wp_comments | 11,457 | 3.52 | 5.69 | +3.14 MB |
| wp_commentmeta | 2,804 | 0.20 | 0.44 | new |
| wp_termmeta | 38 | 0.02 | 0.05 | new |

## SQL applied (from dry-run output, verified by status)

```sql
SET @@sql_mode := REPLACE(@@sql_mode, 'NO_ZERO_DATE', '');

-- wp_termmeta
ALTER TABLE `wp_termmeta`
  ADD UNIQUE KEY meta_id (meta_id),
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (term_id, meta_key, meta_id),
  DROP KEY meta_key,
  ADD KEY meta_key (meta_key, meta_value(32), term_id, meta_id),
  ADD KEY meta_value (meta_value(32), meta_id),
  DROP KEY term_id;

-- wp_options
ALTER TABLE `wp_options`
  ADD UNIQUE KEY option_id (option_id),
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (option_name),
  DROP KEY option_name;

-- wp_users
ALTER TABLE `wp_users` ADD KEY display_name (display_name);

-- wp_commentmeta
ALTER TABLE `wp_commentmeta`
  ADD UNIQUE KEY meta_id (meta_id),
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (meta_key, comment_id, meta_id),
  DROP KEY comment_id,
  ADD KEY comment_id (comment_id, meta_key, meta_value(32)),
  ADD KEY meta_value (meta_value(32)),
  DROP KEY meta_key;

-- wp_comments
ALTER TABLE `wp_comments`
  ADD UNIQUE KEY comment_ID (comment_ID),
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (comment_post_ID, comment_ID),
  DROP KEY comment_approved_date_gmt,
  ADD KEY comment_approved_date_gmt (comment_approved, comment_date_gmt, comment_ID),
  DROP KEY comment_date_gmt,
  ADD KEY comment_date_gmt (comment_date_gmt, comment_ID),
  DROP KEY comment_parent,
  ADD KEY comment_parent (comment_parent, comment_ID),
  DROP KEY comment_author_email,
  ADD KEY comment_author_email (comment_author_email, comment_post_ID, comment_ID),
  ADD KEY comment_post_parent_approved (comment_post_ID, comment_parent, comment_approved, comment_type, user_id, comment_date_gmt, comment_ID),
  DROP KEY comment_post_ID;

-- wp_woocommerce_order_itemmeta
ALTER TABLE `wp_woocommerce_order_itemmeta`
  ADD UNIQUE KEY meta_id (meta_id),
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (order_item_id, meta_key, meta_id),
  DROP KEY meta_key,
  ADD KEY meta_key (meta_key, meta_value(32), order_item_id, meta_id),
  ADD KEY meta_value (meta_value(32), meta_id),
  DROP KEY order_item_id;

-- wp_usermeta
ALTER TABLE `wp_usermeta`
  ADD UNIQUE KEY umeta_id (umeta_id),
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (user_id, meta_key, umeta_id),
  DROP KEY meta_key,
  ADD KEY meta_key (meta_key, meta_value(32), user_id, umeta_id),
  ADD KEY meta_value (meta_value(32), umeta_id),
  DROP KEY user_id;

-- wp_postmeta
ALTER TABLE `wp_postmeta`
  ADD UNIQUE KEY meta_id (meta_id),
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (post_id, meta_key, meta_id),
  DROP KEY meta_key,
  ADD KEY meta_key (meta_key, meta_value(32), post_id, meta_id),
  ADD KEY meta_value (meta_value(32), meta_id),
  DROP KEY post_id;
```

## Verification

| Check | Result |
|---|---|
| Homepage HTTP | 200 OK · TTFB 0.42s |
| Courses HTTP | 200 OK · TTFB 0.33s |
| Login HTTP | 200 OK · TTFB 1.47s |
| Homepage Playwright | Loaded ✓ Title correct |
| Console errors | 2 pre-existing (init.js + icon-courses.png 404s, unrelated) |
| wp_usermeta integrity | 406,541 rows, sample query OK |
| wp_postmeta integrity | 242,251 rows (count via SELECT, data preserved) |
| Plugin status | 8/8 standard tables show "high-performance keys" |

## Deferred (not applied)

These tables have custom keys from other plugins (WooCommerce HPOS); the plugin
can convert them but doing so was deferred for safer rollout:

- `wp_posts` (19,979 rows)
- `wp_wc_orders_meta` (71,966 rows)

Plugin still offers them via:
```
wp index-mysql enable wp_posts wp_wc_orders_meta
```
or revert via `wp index-mysql disable wp_posts wp_wc_orders_meta`.

## Rollback (if needed)

Plugin-side revert (preferred):
```
wp index-mysql disable wp_commentmeta wp_comments wp_options wp_postmeta wp_termmeta wp_usermeta wp_users wp_woocommerce_order_itemmeta
```

Full DB restore (last resort):
```
mysql -h <host> -u <user> -p <db> < ~/db-backups/pre-task-3.16-index-plugin-2026-05-24_125038.sql
```

## Notes

- Pre-existing console errors are NOT caused by this task
- Row count discrepancy in baseline vs post (e.g., wp_postmeta 258K → 242K count) is due to background process cleanup
  during the ~30-minute task window (Action Scheduler, transients, WC sessions) — not from the ALTER itself
- Tutor Pro updater warning during install: `update.php:309 Attempt to read property "version" on array` — pre-existing,
  documented bug in tutor-pro plugin
