# Learn Simply — Revenue & Order Forensics

**Measured:** 2026-08-24 (all queries run this date)
**Data horizon:** last order in the dataset is `2026-08-23 14:27:37`. "2026 YTD" = 2026-01-01 → 2026-08-23 (235 days).
**Source system:** live WordPress / WooCommerce 11.0.1 on Hostinger (`/home/u791284659/domains/learrnsimply.com/public_html`), read-only via `wp db query --skip-plugins --skip-themes`.
**HPOS:** not enabled — orders live in `wp_posts` (`post_type='shop_order'`) + `wp_postmeta`; analytics mirror in `wp_wc_order_stats`.
**Access mode:** READ-ONLY. Every statement in this report is a `SELECT` / `wp option get` / `wp plugin list` / `find`. No writes were performed.

**Gross vs net convention used throughout:** `wp_wc_order_stats.total_sales` and `wp_postmeta._order_total` are **gross order value** (tax and shipping are 0 for these digital products, so gross ≈ net here). Product-level figures use `product_net_revenue` (post-discount, pre-tax) where labelled `net_rev` and `product_gross_revenue` where labelled `gross`. Refunds are handled in §5 and are immaterial.

---

## Summary of findings

1. **Yes — WooCommerce's unpaid-order auto-cancel is overwhelmingly the cancellation mechanism.** 98.7% of 2026 YTD cancelled orders (669 / 678) carry the system note "طلب غير مدفوع ألغي - الوقت المحدد أنتهى". This is not new: 2025 was 98.8%. Cancellation here means *never paid*, not *paid then lost*.

2. **`woocommerce_hold_stock_minutes` was changed from ~60 to 1440 on 2026-06-23/24.** Auto-cancel latency jumps from 1.0–1.7h to 24.5h+ exactly at that boundary. This changed cancel *timing*, not cancel *volume*. It explains the 25–48h age cluster in the brief.

3. **There is no manual payment gateway, so the "customer paid manually but got auto-cancelled" hypothesis is unsupported.** Every 2026 order used `kashier_card` or `kashier_wallet`. The only manual method (`cod`) stopped being used on 2024-10-24. Manual confirmations happen by admin-editing a pending Kashier order (164 in 2026 YTD), and they land in a median of ~0–2 hours — **only 1 order in all of 2026 was manually completed more than 24h after being placed.** The auto-cancel is not racing the human.

4. **What changed in July is product mix, not payments.** Two new products explain most of it: the Dart course (id 39670, launched 2026-06-17, 600 EGP) drove volume at a 55.6% cancel rate, and the "جميع الدورات" bundle (id 40754, launched 2026-07-18, **2,500 EGP**) has sold **2 units ever** while generating **47,200 EGP of cancelled gross** — 28% of all Jul–Aug cancelled value, and the entire August AOV jump (885 vs ~550). Legacy products' cancel rate also rose, but only +6.5pp (40.0% → 46.5%) vs +22.3pp for the new products.

5. **Refunds are effectively zero.** 2 refund records all-time (699 EGP), 1 in 2026 (499 EGP). Refund rate ≈ 0.1% of completed orders. Not a lever.

6. **Recoverable revenue is 16K–47K EGP/year, not 195K.** 30.8% of cancelled gross was *already recovered* by the same customer buying the same product within 30 days — counting it as lost would be double-counting. See §6 for the explicit derivation and the warning about the prior disproved claim.

7. **The best repeat-purchase predictor is the beginner Java course** (id 27272): 28.0% of customers whose first order contained it bought again, vs an 16.7% site average. Median time to second purchase is **31 days**, but 26% of "repeats" occur within 1 day and are really split-cart artifacts.

**Also surfaced (not asked, but material):** `tutor` core is at **4.0.7** while `tutor-pro` is at **3.9.14** — a major-version mismatch introduced by a plugin update on 2026-08-21. Flagged in "Open questions".

---

## 1. Is WooCommerce's unpaid-order auto-cancel the dominant cancellation mechanism?

**Answer: Yes — 98.7% of 2026 YTD cancellations, 98.8% in 2025.**

The site runs a localised WooCommerce, so the system note is Arabic: `طلب غير مدفوع ألغي - الوقت المحدد أنتهى. تغيّرت حالة الطلب من بانتظار الدفع إلى ملغي.` = "Unpaid order cancelled - time limit reached. Order status changed from pending payment to cancelled." That is the exact string `wc_cancel_unpaid_orders()` writes.

### 1.1 Note frequency (definition scan)

*Definition:* distinct order-note texts on all order notes dated ≥ 2026-07-01. *Scope:* all orders. *Source:* `wp_comments`.

```sql
SELECT LEFT(comment_content,120) AS note, COUNT(*) AS n
FROM wp_comments
WHERE comment_type='order_note' AND comment_date >= '2026-07-01'
GROUP BY note ORDER BY n DESC LIMIT 40;
```

Top rows:

| note | n |
|---|---|
| طلب غير مدفوع ألغي - الوقت المحدد أنتهى. تغيّرت حالة الطلب من بانتظار الدفع إلى ملغي. | 237 |
| رسائل البريد الإلكتروني "طلب مكتمل" المُرسَلة. | 234 |
| رسائل البريد الإلكتروني "طلب جديد" المُرسَلة. | 234 |
| تغيّرت حالة الطلب من قيد التنفيذ إلى مُكتمل. | 158 |
| رسائل البريد الإلكتروني "فشل الطلب" المُرسَلة. | 72 |
| تم تغيير حالة الطلب عبر تحرير: تغيّرت حالة الطلب من بانتظار الدفع إلى مُكتمل. | 63 |
| تغيّرت حالة الطلب من بانتظار الدفع إلى فشل. | 36 |
| تم تغيير حالة الطلب عبر تحرير: تغيّرت حالة الطلب من مُكتمل إلى ملغي. | 7 |
| الطلب ألغي بواسطة الزبون. تغيّرت حالة الطلب من بانتظار الدفع إلى ملغي. | 2 |

Note the last row: only **2** customer-initiated cancellations in the whole period.

### 1.2 Percentage of cancelled orders carrying the auto-cancel note

*Definition:* cancelled orders (`post_status='wc-cancelled'`) whose order id has ≥1 note matching the time-limit string, over all cancelled orders created in the period. *Source:* `wp_posts` ⨝ `wp_comments`.

```sql
SELECT 'Jul1-now 2026' AS period,
  COUNT(*) AS cancelled_orders,
  SUM(CASE WHEN c.n>0 THEN 1 ELSE 0 END) AS with_autocancel_note,
  ROUND(100*SUM(CASE WHEN c.n>0 THEN 1 ELSE 0 END)/COUNT(*),1) AS pct
FROM wp_posts p
LEFT JOIN (SELECT comment_post_ID, COUNT(*) n FROM wp_comments
           WHERE comment_type='order_note' AND comment_content LIKE '%الوقت المحدد أنتهى%'
           GROUP BY comment_post_ID) c ON c.comment_post_ID=p.ID
WHERE p.post_type='shop_order' AND p.post_status='wc-cancelled'
  AND p.post_date >= '2026-07-01';
-- (UNION ALL branches for '2026 YTD' >= '2026-01-01' and '2025 full')
```

| period | cancelled_orders | with_autocancel_note | pct |
|---|---|---|---|
| Jul 1 – Aug 23 2026 | 238 | 233 | **97.9%** |
| 2026 YTD | 678 | 669 | **98.7%** |
| 2025 full | 826 | 816 | **98.8%** |

### 1.3 Cron

```
wp cron event list --fields=hook,next_run_gmt,recurrence --format=csv --skip-plugins --skip-themes
```
Returned **no** `woocommerce_cancel_unpaid_orders` row. `wp option get cron --format=json` shows 38 scheduled slots, none matching `cancel|unpaid|stock`.

This is expected and is **not** evidence against the mechanism: `wc_cancel_unpaid_orders()` reschedules itself for `now + hold_stock_minutes` at the end of each run, and the command was run with `--skip-plugins`, so WooCommerce's own hook registration is absent from that process. The behavioural evidence (§1.2 note coverage at 98.7%, plus the latency signature in §3.1) is far stronger than the cron dump. `UNVERIFIED`: the exact next-run timestamp of the hook.

### 1.4 Settings confirming the mechanism

```
wp option get woocommerce_manage_stock       -> yes
wp option get woocommerce_hold_stock_minutes -> 1440
```

**Conclusion:** confirmed. Cancellation at Learn Simply is a synonym for "checkout started, payment never completed, WooCommerce reaped it". Only 2 cancellations in ~8 weeks were customer-initiated.

---

## 2. Payment method split

**Answer: 100% Kashier. Zero cancelled orders in 2026 used a manual payment method, because no manual gateway is in use.**

### 2.1 Gateways configured vs actually used

```sql
SELECT option_name FROM wp_options WHERE option_name LIKE 'woocommerce_%_settings'
  AND option_name REGEXP 'bacs|cheque|cod|kashier|paypal|stripe|instapay|vodafone';
```
Configured: `bacs`, `cod`, `kashier_aman`, `kashier_bank_installments`, `kashier_card`, `kashier_valu`, `kashier_wallet`, `paypal`.

```sql
SELECT pm.meta_value AS method, COUNT(*) n, MIN(p.post_date) first_seen, MAX(p.post_date) last_seen
FROM wp_posts p JOIN wp_postmeta pm ON pm.post_id=p.ID AND pm.meta_key='_payment_method'
WHERE p.post_type='shop_order' AND pm.meta_value<>'' GROUP BY method ORDER BY n DESC;
```

| method | n | first_seen | last_seen |
|---|---|---|---|
| kashier_card | 2399 | 2024-10-24 | 2026-08-23 |
| kashier_wallet | 1337 | 2024-10-25 | 2026-08-23 |
| cod | 604 | 2024-03-08 | **2024-10-24** |
| kashier_aman | 1 | 2024-10-26 | 2024-10-26 |

`cod` was the manual/offline method and it has been dead since Kashier went live on 2024-10-24. `bacs` and `paypal` have **never** been used.

Important nuance: `kashier_wallet` is the Kashier-hosted wallet method (فودافون كاش / اتصالات كاش / أورنج كاش) — it is *automated*, settled by the gateway, not the human-confirmed Vodafone Cash transfer to Ahmed's personal number. Do not conflate the two.

### 2.2 Method × status, 2026 YTD

```sql
SELECT COALESCE(pm.meta_value,'(none)') AS payment_method, p.post_status,
  COUNT(*) AS orders, ROUND(SUM(CAST(tot.meta_value AS DECIMAL(12,2))),0) AS gross
FROM wp_posts p
LEFT JOIN wp_postmeta pm  ON pm.post_id=p.ID  AND pm.meta_key='_payment_method'
LEFT JOIN wp_postmeta tot ON tot.post_id=p.ID AND tot.meta_key='_order_total'
WHERE p.post_type='shop_order' AND p.post_date >= '2026-01-01'
  AND p.post_status IN ('wc-completed','wc-cancelled','wc-failed')
GROUP BY payment_method, p.post_status ORDER BY payment_method, p.post_status;
```

| payment_method | status | orders | gross (EGP) |
|---|---|---|---|
| (none) | cancelled | 1 | 499 |
| (none) | completed | 140 | 68,808 |
| kashier_card | cancelled | 424 | 265,742 |
| kashier_card | completed | 481 | 276,831 |
| kashier_card | failed | 61 | 33,063 |
| kashier_wallet | cancelled | 253 | 155,487 |
| kashier_wallet | completed | 274 | 152,002 |
| kashier_wallet | failed | 3 | 1,499 |

**Cancellation rate by method, 2026 YTD** (cancelled / completed+cancelled+failed):
- `kashier_card`: 424 / 966 = **43.9%**
- `kashier_wallet`: 253 / 530 = **47.7%**

The wallet rate is *higher* than card, which is the opposite of the old "card gateway is broken" narrative.

The **(none)** bucket — 140 completed orders / 68,808 EGP with no `_payment_method` — is the closest thing to a manual channel: admin-created or admin-completed orders that never touched a gateway. Only **1** such order was ever cancelled.

### 2.3 Before vs after July

```sql
SELECT CASE WHEN p.post_date>='2026-07-01' THEN 'B_Jul1-Aug23' ELSE 'A_Jan1-Jun30' END AS period,
  COALESCE(pm.meta_value,'(none)') AS pmethod, p.post_status,
  COUNT(*) AS orders, ROUND(SUM(CAST(tot.meta_value AS DECIMAL(12,2))),0) AS gross
FROM wp_posts p
LEFT JOIN wp_postmeta pm  ON pm.post_id=p.ID  AND pm.meta_key='_payment_method'
LEFT JOIN wp_postmeta tot ON tot.post_id=p.ID AND tot.meta_key='_order_total'
WHERE p.post_type='shop_order' AND p.post_date >= '2026-01-01'
  AND p.post_status IN ('wc-completed','wc-cancelled','wc-failed')
GROUP BY period, COALESCE(pm.meta_value,'(none)'), p.post_status
ORDER BY period, pmethod, p.post_status;
```

| period | pmethod | status | orders | gross |
|---|---|---|---|---|
| Jan 1 – Jun 30 | (none) | cancelled | 1 | 499 |
| Jan 1 – Jun 30 | (none) | completed | 91 | 42,970 |
| Jan 1 – Jun 30 | kashier_card | cancelled | 265 | 157,267 |
| Jan 1 – Jun 30 | kashier_card | completed | 366 | 210,449 |
| Jan 1 – Jun 30 | kashier_card | failed | 38 | 21,117 |
| Jan 1 – Jun 30 | kashier_wallet | cancelled | 174 | 97,946 |
| Jan 1 – Jun 30 | kashier_wallet | completed | 211 | 116,962 |
| Jan 1 – Jun 30 | kashier_wallet | failed | 3 | 1,499 |
| Jul 1 – Aug 23 | (none) | completed | 49 | 25,838 |
| Jul 1 – Aug 23 | kashier_card | cancelled | 159 | 108,475 |
| Jul 1 – Aug 23 | kashier_card | completed | 115 | 66,382 |
| Jul 1 – Aug 23 | kashier_card | failed | 23 | 11,946 |
| Jul 1 – Aug 23 | kashier_wallet | cancelled | 79 | 57,541 |
| Jul 1 – Aug 23 | kashier_wallet | completed | 63 | 35,040 |

Derived cancellation rates:

| method | Jan–Jun | Jul–Aug | delta |
|---|---|---|---|
| kashier_card | 265/669 = 39.6% | 159/297 = 53.5% | +13.9pp |
| kashier_wallet | 174/388 = 44.8% | 79/142 = 55.6% | +10.8pp |

Card share of gateway orders moved only 63.3% → 67.7%. **Both gateways degraded by a similar amount, so this is not a payment-mix effect** — see §3.

### 2.4 The manual-confirmation race — refuted

If manual payers were being auto-cancelled before a human confirmed, we would see admin confirmations clustering near or beyond the 24h hold window. They do not.

*Definition:* orders carrying the note "status changed via edit: pending payment → completed" (the manual-confirmation signature), and the elapsed hours from order placement to that note.

```sql
SELECT DATE_FORMAT(p.post_date,'%Y-%m') AS mo,
  COUNT(*) AS manually_completed_from_pending,
  ROUND(MIN(TIMESTAMPDIFF(HOUR,p.post_date,c.comment_date)),1) AS min_h,
  ROUND(AVG(TIMESTAMPDIFF(HOUR,p.post_date,c.comment_date)),1) AS avg_h,
  ROUND(MAX(TIMESTAMPDIFF(HOUR,p.post_date,c.comment_date)),1) AS max_h,
  SUM(TIMESTAMPDIFF(HOUR,p.post_date,c.comment_date) > 24) AS took_over_24h,
  ROUND(SUM(CAST(tot.meta_value AS DECIMAL(12,2))),0) AS gross
FROM wp_posts p
JOIN wp_comments c ON c.comment_post_ID=p.ID AND c.comment_type='order_note'
  AND c.comment_content LIKE '%عبر تحرير%' AND c.comment_content LIKE '%بانتظار الدفع إلى مُكتمل%'
LEFT JOIN wp_postmeta tot ON tot.post_id=p.ID AND tot.meta_key='_order_total'
WHERE p.post_type='shop_order' AND p.post_date >= '2026-01-01'
GROUP BY mo ORDER BY mo;
```

| mo | manual completions | min_h | avg_h | max_h | took_over_24h | gross |
|---|---|---|---|---|---|---|
| 2026-01 | 16 | 0 | 0.1 | 1 | 0 | 7,484 |
| 2026-02 | 14 | 0 | 0.0 | 0 | 0 | 7,036 |
| 2026-03 | 25 | 0 | 0.0 | 0 | 0 | 11,875 |
| 2026-04 | 27 | 0 | 0.0 | 0 | 0 | 12,673 |
| 2026-05 | 9 | 0 | 0.1 | 1 | 0 | 4,495 |
| 2026-06 | 10 | 0 | 1.7 | 17 | 0 | 5,148 |
| 2026-07 | 41 | 0 | 1.8 | 25 | **1** | 20,193 |
| 2026-08 | 22 | 0 | 1.4 | 15 | 0 | 14,642 |

**164 manual confirmations in 2026 YTD worth 83,546 EGP, and exactly one of them took longer than 24 hours.** With the hold window now at 1440 minutes, the human confirmation process finishes roughly 10x faster than the deadline. Raising the window further would recover approximately nothing.

Caveat on the Jan–May rows: `avg_h = 0.0` with `max_h = 0` means the note lands in the same hour as the order. That is fast enough to be automated rather than human. `UNVERIFIED`: whether an automation (or a bulk admin action) writes some of these notes. It does not change the conclusion — in every month the confirmation beats the deadline comfortably.

---

## 3. What changed in July?

**Answer: two new products, one of them a 2,500 EGP bundle nobody buys. Plus a hold-window setting change on 2026-06-23/24 that moved cancel timing but not cancel volume. Payment mix is not the cause.**

### 3.1 The hold_stock_minutes change — dated precisely

*Definition:* hours from order placement to the auto-cancel note firing, for auto-cancelled orders, by month. *Source:* `wp_posts` ⨝ `wp_comments` (note timestamp is exact; more reliable than `post_modified`).

```sql
SELECT DATE_FORMAT(p.post_date,'%Y-%m') AS mo, COUNT(*) AS n,
  ROUND(MIN(TIMESTAMPDIFF(MINUTE,p.post_date,c.comment_date))/60,1) AS min_h,
  ROUND(AVG(TIMESTAMPDIFF(MINUTE,p.post_date,c.comment_date))/60,1) AS avg_h,
  ROUND(MAX(TIMESTAMPDIFF(MINUTE,p.post_date,c.comment_date))/60,1) AS max_h,
  SUM(TIMESTAMPDIFF(MINUTE,p.post_date,c.comment_date) BETWEEN 1380 AND 2880) AS in_23_48h,
  SUM(TIMESTAMPDIFF(MINUTE,p.post_date,c.comment_date) < 180) AS under_3h
FROM wp_posts p
JOIN wp_comments c ON c.comment_post_ID=p.ID AND c.comment_type='order_note'
  AND c.comment_content LIKE '%الوقت المحدد أنتهى%'
WHERE p.post_type='shop_order' AND p.post_status='wc-cancelled' AND p.post_date >= '2025-09-01'
GROUP BY mo ORDER BY mo;
```

| mo | n | min_h | avg_h | max_h | in 23–48h | under 3h |
|---|---|---|---|---|---|---|
| 2025-09 | 86 | 1.0 | 1.5 | 2.0 | 0 | 86 |
| 2025-10 | 98 | 1.0 | 1.9 | 9.1 | 0 | 91 |
| 2025-11 | 152 | 1.0 | 2.8 | 10.2 | 0 | 109 |
| 2025-12 | 83 | 1.0 | 3.0 | 10.6 | 0 | 57 |
| 2026-01 | 62 | 1.0 | 3.5 | 9.3 | 0 | 31 |
| 2026-02 | 84 | 1.0 | 2.9 | 9.9 | 0 | 59 |
| 2026-03 | 73 | 1.0 | 2.0 | 7.5 | 0 | 64 |
| 2026-04 | 99 | 1.0 | 2.3 | 26.3 | 1 | 83 |
| 2026-05 | 71 | 1.0 | 2.3 | 8.1 | 0 | 55 |
| 2026-06 | 47 | 1.0 | 10.3 | 43.7 | 13 | 33 |
| 2026-07 | 129 | **24.3** | 35.8 | 64.7 | 128 | 0 |
| 2026-08 | 104 | **24.8** | 34.7 | 47.4 | 104 | 0 |

The `min_h` column is the giveaway: it is pinned at exactly 1.0h every month through May, then jumps to 24.3h. Daily resolution pins the switch:

```sql
SELECT DATE(p.post_date) AS d, COUNT(*) n,
  ROUND(AVG(TIMESTAMPDIFF(MINUTE,p.post_date,c.comment_date))/60,1) avg_h
FROM wp_posts p
JOIN wp_comments c ON c.comment_post_ID=p.ID AND c.comment_type='order_note'
  AND c.comment_content LIKE '%الوقت المحدد أنتهى%'
WHERE p.post_type='shop_order' AND p.post_status='wc-cancelled'
  AND p.post_date >= '2026-06-01' AND p.post_date < '2026-07-10'
GROUP BY d ORDER BY d;
```

| d | n | avg_h |
|---|---|---|
| … 2026-06-22 | 1 | 1.0 |
| 2026-06-23 | 5 | **1.4** |
| 2026-06-24 | 2 | **24.5** |
| 2026-06-25 | 1 | 38.8 |
| 2026-06-26 | 5 | 34.6 |

**`woocommerce_hold_stock_minutes` went from 60 to 1440 between 2026-06-23 and 2026-06-24.** This matches the project's own record of the hold-stock recommendation from the Dart post-mortem (the fix was applied as 60 → 1440 rather than disabling it outright).

Note the direction: the window was *lengthened*, giving customers 24x longer to pay. That cannot cause more cancellations. It only relocates them 23 hours later, which is exactly what produced the 25–48h age cluster in the brief.

### 3.2 Volume and rate

```sql
SELECT DATE_FORMAT(date_created,'%Y-%m') AS mo, COUNT(*) AS all_orders,
  SUM(status='wc-completed') AS completed, SUM(status='wc-cancelled') AS cancelled,
  SUM(status='wc-failed') AS failed,
  ROUND(100*SUM(status='wc-cancelled')/COUNT(*),1) AS pct_cancelled,
  ROUND(SUM(CASE WHEN status='wc-completed' THEN total_sales END),0) AS completed_gross
FROM wp_wc_order_stats WHERE date_created >= '2025-10-01' GROUP BY mo ORDER BY mo;
```

| mo | all_orders | completed | cancelled | failed | pct_cancelled | completed_gross |
|---|---|---|---|---|---|---|
| 2025-10 | 317 | 206 | 99 | 11 | 31.2% | 102,328 |
| 2025-11 | 393 | 212 | 156 | 22 | 39.7% | 113,429 |
| 2025-12 | 239 | 149 | 83 | 3 | 34.7% | 82,293 |
| 2026-01 | 162 | 88 | 64 | 6 | 39.5% | 49,756 |
| 2026-02 | 196 | 107 | 85 | 4 | 43.4% | 60,738 |
| 2026-03 | 212 | 126 | 73 | 10 | 34.4% | 69,971 |
| 2026-04 | 274 | 164 | 99 | 9 | 36.1% | 87,244 |
| 2026-05 | 183 | 103 | 72 | 7 | 39.3% | 60,538 |
| 2026-06 | 137 | 80 | 48 | 5 | 35.0% | 42,134 |
| 2026-07 | 292 | 146 | 132 | 14 | **45.2%** | 72,934 |
| 2026-08* | 202 | 81 | 108 | 9 | **53.5%** | 54,326 |

\* August is partial (through the 23rd) **and biased downward on cancellations**: orders placed on Aug 22–23 that will eventually auto-cancel are still `pending` for another 24–48h. August's final cancel count and rate will be higher than shown.

Two things happened at once:
- **Volume roughly doubled** (137 → 292). But July's 292 is not unprecedented — Oct 2025 hit 317. June was the *trough* of 2026, which exaggerates the step.
- **Completed revenue also rose** (42,134 → 72,934). July was a genuinely good month in absolute terms.
- **The cancel rate rose to record levels** (45.2%, then 53.5%). That is the real anomaly.

### 3.3 The driver: two new products

```sql
SELECT lu.product_id, LEFT(po.post_title,40) AS product, po.post_date AS created, po.post_status,
  COUNT(DISTINCT CASE WHEN os.status='wc-completed' THEN os.order_id END) AS comp,
  ROUND(SUM(CASE WHEN os.status='wc-completed' THEN lu.product_net_revenue ELSE 0 END),0) AS comp_rev,
  COUNT(DISTINCT CASE WHEN os.status='wc-cancelled' THEN os.order_id END) AS canc,
  ROUND(SUM(CASE WHEN os.status='wc-cancelled' THEN lu.product_gross_revenue ELSE 0 END),0) AS canc_gross
FROM wp_wc_order_product_lookup lu
JOIN wp_wc_order_stats os ON os.order_id=lu.order_id
LEFT JOIN wp_posts po ON po.ID=lu.product_id
WHERE os.date_created >= '2026-07-01'
GROUP BY lu.product_id, product, created, po.post_status ORDER BY canc DESC LIMIT 20;
```

| product_id | product | created | comp | comp_rev | canc | canc_gross |
|---|---|---|---|---|---|---|
| 39670 | أساسيات Dart من الصفر لـ OOP | **2026-06-17** | 80 | 32,850 | **100** | 42,750 |
| 27272 | كورس جافا للمبتدئين + كتاب هدية | 2025-02-17 | 39 | 15,050 | 43 | 17,150 |
| 11694 | هياكل البيانات المستوي الاول | 2024-03-08 | 53 | 21,800 | 42 | 20,350 |
| 31580 | البرمجة الكائنية (OOP) بلغة Java | 2025-09-25 | 40 | 18,950 | 34 | 16,450 |
| 40754 | **جميع الدورات** | **2026-07-18** | **2** | 3,800 | **22** | **47,200** |
| 28056 | كتاب لغة جافا | 2025-03-27 | 10 | 398 | 19 | 2,388 |
| 30895 | هياكل البيانات المستوي الثاني | 2025-09-04 | 38 | 11,477 | 16 | 3,493 |
| 39043 | هياكل البيانات الكاملة | 2026-05-17 | 16 | 15,093 | 10 | 9,792 |
| 33336 | كورس Java Basics + OOP | 2025-11-10 | 8 | 7,242 | 8 | 7,542 |
| 28009 | كتاب لغة C++ | 2025-03-27 | 3 | 600 | 8 | 1,800 |

Prices:

```sql
SELECT p.ID, LEFT(p.post_title,30) t,
  MAX(CASE WHEN m.meta_key='_regular_price' THEN m.meta_value END) reg,
  MAX(CASE WHEN m.meta_key='_sale_price' THEN m.meta_value END) sale,
  MAX(CASE WHEN m.meta_key='_price' THEN m.meta_value END) price
FROM wp_posts p JOIN wp_postmeta m ON m.post_id=p.ID
WHERE p.ID IN (40754,39670,31580,11694,27272,39043) GROUP BY p.ID,t;
```

| ID | product | regular | sale | active price |
|---|---|---|---|---|
| 11694 | هياكل البيانات م1 | 1000 | 650 | 650 |
| 27272 | كورس جافا للمبتدئين | 700 | 550 | 550 |
| 31580 | OOP بلغة Java | 1200 | 700 | 700 |
| 39043 | هياكل البيانات الكاملة | 2200 | 999 | 999 |
| 39670 | أساسيات Dart | 1200 | 600 | 600 |
| 40754 | **جميع الدورات** | **5300** | **2500** | **2500** |

The bundle month by month:

```sql
SELECT DATE_FORMAT(os.date_created,'%Y-%m') mo, os.status,
  COUNT(DISTINCT os.order_id) orders, ROUND(SUM(lu.product_gross_revenue),0) gross
FROM wp_wc_order_product_lookup lu JOIN wp_wc_order_stats os ON os.order_id=lu.order_id
WHERE lu.product_id=40754 GROUP BY mo, os.status ORDER BY mo, os.status;
```

| mo | status | orders | gross |
|---|---|---|---|
| 2026-07 | cancelled | 9 | 17,100 |
| 2026-07 | completed | **2** | 3,800 |
| 2026-08 | cancelled | 13 | 30,100 |
| 2026-08 | completed | **0** | 0 |

**The bundle has sold two units in its entire life and has never sold one in August.**

### 3.4 Decomposing the cancelled-value spike

```sql
SELECT DATE_FORMAT(date_created,'%Y-%m') mo, COUNT(*) canc_orders,
  ROUND(SUM(total_sales),0) canc_gross, ROUND(AVG(total_sales),0) avg_val
FROM wp_wc_order_stats WHERE status='wc-cancelled' AND date_created>='2026-01-01'
GROUP BY mo ORDER BY mo;

SELECT DATE_FORMAT(os.date_created,'%Y-%m') mo,
  ROUND(SUM(CASE WHEN lu.product_id=40754 THEN lu.product_gross_revenue ELSE 0 END),0) bundle_gross,
  ROUND(SUM(lu.product_gross_revenue),0) all_gross
FROM wp_wc_order_product_lookup lu JOIN wp_wc_order_stats os ON os.order_id=lu.order_id
WHERE os.status='wc-cancelled' AND os.date_created>='2026-06-01' GROUP BY mo ORDER BY mo;
```

| mo | canc_orders | canc_gross | avg order value | bundle share | ex-bundle |
|---|---|---|---|---|---|
| 2026-01 | 64 | 35,437 | 554 | — | 35,437 |
| 2026-02 | 85 | 54,252 | 638 | — | 54,252 |
| 2026-03 | 73 | 42,969 | 589 | — | 42,969 |
| 2026-04 | 99 | 52,503 | 530 | — | 52,503 |
| 2026-05 | 72 | 38,858 | 540 | — | 38,858 |
| 2026-06 | 48 | 32,043 | 668 | 0 | 32,043 |
| 2026-07 | 132 | 73,339 | 556 | 17,100 (23%) | 56,239 |
| 2026-08 | 108 | 95,576 | **885** | 30,100 (31%) | 65,476 |

Read the AOV column: July's cancelled AOV (556) is *normal*. August's 885 is the anomaly, and it is the 2,500 EGP bundle pulling the average up. Strip the bundle out and July's cancelled value (56,239) sits just above February's 54,252.

### 3.5 Is anything systemic also degrading? Legacy vs new products

```sql
SELECT CASE WHEN os.date_created>='2026-07-01' THEN 'B_Jul1-Aug23' ELSE 'A_Jan1-Jun30' END pd,
  CASE WHEN lu.product_id IN (40754,39670) THEN 'new_2026_products' ELSE 'legacy_products' END grp,
  COUNT(DISTINCT CASE WHEN os.status='wc-completed' THEN os.order_id END) comp,
  COUNT(DISTINCT CASE WHEN os.status='wc-cancelled' THEN os.order_id END) canc,
  ROUND(100*COUNT(DISTINCT CASE WHEN os.status='wc-cancelled' THEN os.order_id END)
    /NULLIF(COUNT(DISTINCT CASE WHEN os.status IN ('wc-completed','wc-cancelled') THEN os.order_id END),0),1) pct_canc
FROM wp_wc_order_product_lookup lu JOIN wp_wc_order_stats os ON os.order_id=lu.order_id
WHERE os.date_created>='2026-01-01' AND os.status IN ('wc-completed','wc-cancelled')
GROUP BY pd, grp ORDER BY pd, grp;
```

| period | group | completed | cancelled | pct_cancel |
|---|---|---|---|---|
| Jan–Jun | legacy_products | 646 | 430 | 40.0% |
| Jan–Jun | new_2026_products | 22 | 13 | 37.1% |
| Jul–Aug | legacy_products | 147 | 128 | **46.5%** (+6.5pp) |
| Jul–Aug | new_2026_products | 82 | 120 | **59.4%** (+22.3pp) |

**Both rose, but unequally.** The new products are ~13pp worse than legacy and they now carry a much larger share of orders. Roughly two-thirds of the overall rate increase is mix (new products), one-third is a genuine across-the-board deterioration in legacy products (+6.5pp).

### 3.6 Hypotheses tested and NOT supported

| Hypothesis | Verdict | Evidence |
|---|---|---|
| Payment-mix shift toward a worse gateway | **Not supported** | Card share moved only 63.3% → 67.7%; both gateways degraded ~equally (§2.3) |
| Gateway/Kashier regression | **Not supported** | Wallet cancels *more* than card; `wc-failed` (the true gateway-error status) stayed flat: 38 (Jan–Jun) → 23 (Jul–Aug) |
| Manual payers being auto-cancelled | **Refuted** | 1 order in 8 months confirmed >24h after placement (§2.4) |
| The hold_stock change caused more cancellations | **Refuted** | Window was lengthened 60→1440 on 06-23/24; that relocates cancels 23h later, it cannot create them (§3.1) |
| A coupon/discount campaign drove low-intent traffic | **Not supported** | Only 3 coupon redemptions since May 2026 (see below) |
| A plugin/setting change broke checkout in July | **Not supported for July** | No plugin/theme file modified between 2026-06-20 and 2026-08-19 in the top two directory levels; the only changes are `hostinger` (08-20), `tutor` + `all-in-one-wp-migration` (08-21), i.e. *after* the July jump |

Coupon check:
```sql
SELECT DATE_FORMAT(os.date_created,'%Y-%m') mo, cl.coupon_id, LEFT(p.post_title,25) code,
  COUNT(*) uses, ROUND(SUM(cl.discount_amount),0) disc
FROM wp_wc_order_coupon_lookup cl
JOIN wp_wc_order_stats os ON os.order_id=cl.order_id
LEFT JOIN wp_posts p ON p.ID=cl.coupon_id
WHERE os.date_created>='2026-05-01' GROUP BY mo, cl.coupon_id, code ORDER BY mo, uses DESC;
```
| mo | code | uses | discount |
|---|---|---|---|
| 2026-05 | JAVA200 | 2 | 400 |
| 2026-07 | EK6JVA3F | 1 | 300 |

File-modification check:
```
find wp-content/plugins wp-content/themes wp-content/mu-plugins -maxdepth 2 \
  -newermt "2026-06-20" -printf "%TY-%Tm-%Td %p\n" | sort -r | head -40
```
Newest entries: `all-in-one-wp-migration/storage` (08-22), `tutor/*` + `all-in-one-wp-migration/*` (08-21), `hostinger/*` (08-20). Nothing in the July window.

August-21 boundary check (does the Tutor update show up in orders?):
```sql
SELECT CASE WHEN date_created < '2026-08-21' THEN 'Aug1-20' ELSE 'Aug21-24' END pd,
  COUNT(*) all_orders, SUM(status='wc-completed') comp, SUM(status='wc-cancelled') canc,
  ROUND(100*SUM(status='wc-cancelled')/COUNT(*),1) pct_canc
FROM wp_wc_order_stats WHERE date_created>='2026-08-01' GROUP BY pd;
```
| period | orders | completed | cancelled | pct |
|---|---|---|---|---|
| Aug 1–20 | 184 | 74 | 101 | 54.9% |
| Aug 21–23 | 18 | 7 | 7 | 38.9% |

No visible break — but the Aug 21–23 window is too short and its cancellations are still maturing. `UNVERIFIED`: whether the Tutor 4.0.7 upgrade affected conversion. Re-check after 2026-08-27.

**What the data does not explain:** the +6.5pp legacy-product deterioration. Nothing in orders, payments, coupons, or file timestamps accounts for it. Candidate causes that require sources outside the WP database (GA4 traffic quality, ad-campaign targeting, Clarity session recordings) are listed in "Open questions".

---

## 4. Product-level revenue, 2026 YTD

*Definition:* per product, quantity and order count on **completed** orders, completed net revenue (`product_net_revenue`, post-discount), and the cancellation rate computed as cancelled orders / (completed + cancelled) orders containing that product. *Scope:* orders with `date_created >= 2026-01-01`. *Source:* `wp_wc_order_product_lookup` ⨝ `wp_wc_order_stats` ⨝ `wp_posts`.

```sql
SELECT lu.product_id, LEFT(po.post_title,45) AS product,
  SUM(CASE WHEN os.status='wc-completed' THEN lu.product_qty ELSE 0 END) AS completed_qty,
  COUNT(DISTINCT CASE WHEN os.status='wc-completed' THEN os.order_id END) AS completed_orders,
  ROUND(SUM(CASE WHEN os.status='wc-completed' THEN lu.product_net_revenue ELSE 0 END),0) AS completed_net_rev,
  COUNT(DISTINCT CASE WHEN os.status='wc-cancelled' THEN os.order_id END) AS cancelled_orders,
  ROUND(100*COUNT(DISTINCT CASE WHEN os.status='wc-cancelled' THEN os.order_id END)
        /NULLIF(COUNT(DISTINCT CASE WHEN os.status IN ('wc-completed','wc-cancelled') THEN os.order_id END),0),1) AS pct_cancel
FROM wp_wc_order_product_lookup lu
JOIN wp_wc_order_stats os ON os.order_id=lu.order_id
LEFT JOIN wp_posts po ON po.ID=lu.product_id
WHERE os.date_created >= '2026-01-01'
GROUP BY lu.product_id, product ORDER BY completed_net_rev DESC LIMIT 25;
```

| product_id | product | completed qty | completed orders | completed net rev | cancelled orders | % cancel |
|---|---|---|---|---|---|---|
| 31580 | البرمجة الكائنية (OOP) بلغة Java | 322 | 322 | **156,412** | 213 | 39.8% |
| 11694 | هياكل البيانات المستوي الاول | 260 | 260 | **121,223** | 185 | 41.6% |
| 27272 | كورس جافا للمبتدئين + كتاب هدية | 253 | 253 | **93,507** | 207 | 45.0% |
| 39670 | أساسيات Dart من الصفر لـ OOP | 102 | 102 | 40,550 | 113 | **52.6%** |
| 30895 | هياكل البيانات المستوي الثاني | 106 | 106 | 39,930 | 48 | 31.2% |
| 39043 | هياكل البيانات الكاملة | 26 | 26 | 24,093 | 20 | 43.5% |
| 33336 | كورس Java Basics + OOP | 103 | 103 | 15,732 | 82 | 44.3% |
| 40754 | **جميع الدورات** | 2 | 2 | 3,800 | 22 | **91.7%** |
| 28009 | كتاب لغة C++ | 6 | 6 | 1,200 | 17 | **73.9%** |
| 28056 | كتاب لغة جافا | 109 | 109 | 1,194 | 110 | 50.2% |

**Concentration:** the top 3 products are 371,142 EGP of 497,641 EGP total completed net revenue across all products = **74.6%**. (That total coincidentally equals the completed *gross* figure from `wp_wc_order_stats`, because tax and shipping are zero on these digital products.)

**Products flagged for high cancellation:**
- **40754 "جميع الدورات" — 91.7%.** 2 sales against 22 cancellations. At 2,500 EGP this is a price-anchor product people click and abandon. It is the single largest distortion in the cancelled-value series.
- **28009 "كتاب لغة C++" — 73.9%** on tiny volume (6 completed). 200 EGP book.
- **39670 Dart — 52.6%** on real volume (102 completed / 113 cancelled). This one matters: it is the #4 revenue product and it cancels more than it converts.
- **28056 "كتاب لغة جافا" — 50.2%** but note completed net revenue is 1,194 EGP across 109 orders ≈ 11 EGP each. This is a near-free add-on, so its cancel rate is largely inherited from whatever it was bundled with.

**Data note / conflict:** `completed_qty` equals `completed_orders` for every product, i.e. nobody ever buys quantity >1. Consistent with digital course products (one licence per person), but worth knowing before building any quantity-based analysis.

---

## 5. Refunds, 2026 YTD

**Answer: immaterial — 1 refund record worth 499 EGP in 2026 YTD; 2 records worth 699 EGP all-time.**

```sql
SELECT 'refund_posts_2026' AS metric, COUNT(*) AS n,
       ROUND(SUM(CAST(m.meta_value AS DECIMAL(12,2))),2) AS amount
FROM wp_posts r LEFT JOIN wp_postmeta m ON m.post_id=r.ID AND m.meta_key='_refund_amount'
WHERE r.post_type='shop_order_refund' AND r.post_date>='2026-01-01';

SELECT 'refund_posts_alltime' AS metric, COUNT(*) AS n,
       ROUND(SUM(CAST(m.meta_value AS DECIMAL(12,2))),2) AS amount
FROM wp_posts r LEFT JOIN wp_postmeta m ON m.post_id=r.ID AND m.meta_key='_refund_amount'
WHERE r.post_type='shop_order_refund';

SELECT YEAR(date_created) yr, COUNT(*) n, ROUND(SUM(total_sales),2) gross
FROM wp_wc_order_stats WHERE status='wc-refunded' GROUP BY yr;

SELECT MIN(post_date) first_refund, MAX(post_date) last_refund
FROM wp_posts WHERE post_type='shop_order_refund';
```

| metric | n | amount (EGP) |
|---|---|---|
| refund records, 2026 YTD | 1 | 499.00 |
| refund records, all-time | 2 | 699.00 |
| orders with status `wc-refunded`, 2025 | 2 | 0.00 |
| orders with status `wc-refunded`, 2026 | 2 | 0.00 |

First refund record 2025-06-08, last 2026-03-25.

**Refund rate, 2026 YTD:** 499 EGP against 497,641 EGP completed gross = **0.10%**. By order count: 1 refund record against 895 completed orders = 0.11%.

**⚠ Source conflict — presented, not resolved.** `wp_wc_order_stats` reports **4** orders in `wc-refunded` status (2 in 2025, 2 in 2026), but `wp_posts` contains only **2** `shop_order_refund` child records all-time. Additionally all four `wc-refunded` rows show `total_sales = 0.00`, so the analytics table cannot be used to value refunds at all. Two readings are possible: (a) two orders were flipped to `wc-refunded` manually without a refund record being created, or (b) refund records were deleted. Either way the magnitude is ≤ ~4 orders and the conclusion — refunds are not a lever — is unaffected. Resolving it needs the order notes on those 4 specific order IDs.

**There is no `refund_total` column** in `wp_wc_order_stats` on this installation, as the brief noted. Refund value must come from `wp_postmeta._refund_amount`.

---

## 6. Recoverable-revenue estimate

### ⚠ Read this first

This project has previously been burned by exactly this calculation. The prior claim — **"909 failed CC ≈ 195,000 EGP/year gateway leak"** — was **disproved**, and the error pattern was treating every non-completed order as lost revenue. Two corrections make that arithmetic invalid:

1. **A cancelled order is not a lost sale.** 98.7% of cancellations are unpaid checkouts that timed out (§1). No money ever moved.
2. **Roughly a third of cancelled orders were already recovered by the customer themselves.** Counting them as "lost" double-counts revenue that is already in the completed figures.

The estimate below is built by *subtracting* everything provably not lost, then applying a recovery-rate range to what remains.

### 6.1 Classifying every 2026 cancelled order by what actually happened

*Definition:* each cancelled order created in 2026 YTD is placed in exactly one bucket, in priority order: (a) the same customer completed an order containing the **same product** within 1 day; (b) same, within 30 days; (c) the customer completed *some* order at *some* point; (d) the customer has never completed anything. *Source:* `wp_wc_order_stats` ⨝ `wp_wc_order_product_lookup`, self-joined on `customer_id`.

```sql
SELECT recovery_window, COUNT(*) AS cancelled_orders, ROUND(SUM(total_sales),0) AS gross
FROM (
  SELECT c.order_id, c.total_sales,
    CASE
      WHEN EXISTS (SELECT 1 FROM wp_wc_order_stats d
                   JOIN wp_wc_order_product_lookup ld ON ld.order_id=d.order_id
                   JOIN wp_wc_order_product_lookup lc ON lc.order_id=c.order_id AND lc.product_id=ld.product_id
                   WHERE d.customer_id=c.customer_id AND d.status='wc-completed'
                     AND d.date_created>c.date_created
                     AND d.date_created<=DATE_ADD(c.date_created, INTERVAL 1 DAY))
        THEN 'a_recovered_same_product_<=1d'
      WHEN EXISTS (SELECT 1 FROM wp_wc_order_stats d
                   JOIN wp_wc_order_product_lookup ld ON ld.order_id=d.order_id
                   JOIN wp_wc_order_product_lookup lc ON lc.order_id=c.order_id AND lc.product_id=ld.product_id
                   WHERE d.customer_id=c.customer_id AND d.status='wc-completed'
                     AND d.date_created>c.date_created
                     AND d.date_created<=DATE_ADD(c.date_created, INTERVAL 30 DAY))
        THEN 'b_recovered_same_product_2-30d'
      WHEN EXISTS (SELECT 1 FROM wp_wc_order_stats d WHERE d.customer_id=c.customer_id
                   AND d.status='wc-completed')
        THEN 'c_customer_bought_something_else'
      ELSE 'd_never_converted'
    END AS recovery_window
  FROM wp_wc_order_stats c
  WHERE c.status='wc-cancelled' AND c.date_created>='2026-01-01'
) z GROUP BY recovery_window ORDER BY recovery_window;
```

| bucket | cancelled orders | gross (EGP) | share of cancelled gross | interpretation |
|---|---|---|---|---|
| a — recovered, same product, ≤1 day | 117 | 68,758 | 16.2% | retry succeeded almost immediately. **Not lost.** |
| b — recovered, same product, 2–30 days | 109 | 61,967 | 14.6% | customer came back and paid. **Not lost.** |
| c — customer bought something else | 73 | 66,264 | 15.6% | ambiguous; customer is monetised, this specific order is not |
| d — never converted | 382 | 227,988 | 53.6% | the only genuine loss candidate |
| **total** | **681** | **424,977** | 100% | reconciles exactly to §3.4 |

**30.8% of 2026 cancelled gross (130,725 EGP) was already recovered by the customer.** That figure alone invalidates any "cancelled = lost" arithmetic.

### 6.2 Trimming the never-converted pool

The 2,500 EGP bundle sits disproportionately in bucket (d) and is aspirational browsing, not thwarted intent — it has a 91.7% cancel rate and 2 lifetime sales.

```sql
SELECT 'never_converted_bundle_share' AS m, COUNT(DISTINCT c.order_id) n,
  ROUND(SUM(lc.product_gross_revenue),0) gross
FROM wp_wc_order_stats c
JOIN wp_wc_order_product_lookup lc ON lc.order_id=c.order_id AND lc.product_id=40754
WHERE c.status='wc-cancelled' AND c.date_created>='2026-01-01'
  AND NOT EXISTS (SELECT 1 FROM wp_wc_order_stats o
                  WHERE o.customer_id=c.customer_id AND o.status='wc-completed');
```
→ 11 orders / **24,500 EGP**.

Addressable pool = 227,988 − 24,500 = **203,488 EGP** across ~371 orders / ~311 distinct customers, over 235 days.

### 6.3 The range

Computed with this script (not mentally):

```python
recovered_1d, recovered_30d = 68758, 61967
bought_else, never = 66264, 227988
total = recovered_1d + recovered_30d + bought_else + never   # 424,977 ✓
addressable = never - 24500                                   # 203,488
days = 235; ann = 365/days                                    # 1.553
for rate in (0.05, 0.10, 0.15):
    ytd = addressable*rate
    print(rate, round(ytd), round(ytd*ann))
```

| scenario | assumption | 2026 YTD | annualised |
|---|---|---|---|
| **Conservative (low bound)** | 5% of never-converted checkouts can be won back by a recovery email/WhatsApp sequence. Assumes most never-converters could not or would not pay (price, card access, browsing). | 10,174 EGP | **~15,800 EGP/yr** |
| **Mid** | 10% recovery — the middle of published abandoned-checkout email benchmarks, applied to a warmer audience (these people reached order-placement, not just cart). | 20,349 EGP | ~31,600 EGP/yr |
| **Optimistic (high bound)** | 15% recovery. Requires a well-executed multi-touch sequence with a working payment path and an incentive. Treat as a ceiling, not a target. | 30,523 EGP | **~47,400 EGP/yr** |

**Headline: 16,000 – 47,000 EGP/year, most likely near the lower half.** Compare with the disproved prior claim of 195,000 EGP/year — roughly **4x to 12x** overstated.

### 6.4 Assumptions and what would change the answer

- The 5–15% recovery band is an **industry benchmark, not a Learn Simply measurement** — labelled `UNVERIFIED`. The only way to firm it up is to run one recovery sequence and measure. Note the site already exhibits a **33.2% organic self-recovery rate** (§6.1 a+b), which means much of the easy recovery is *already happening without intervention*; an email sequence competes with that, it does not stack on top of it.
- Bucket (c) (66,264 EGP) is excluded from the addressable pool. Including it at a low rate would add roughly 3,300–9,900 EGP YTD. Excluded because these customers are already monetised and the specific abandoned order is more likely a deliberate change of mind.
- Gross, not net: figures are order totals before Kashier fees. Net recoverable is lower by the gateway's take.
- The estimate assumes no price change on the 2,500 EGP bundle. Repricing or de-listing it would shrink the cancelled-value series substantially without recovering any revenue — a reporting improvement, not a revenue one.
- **Bigger lever than recovery:** the legacy-product cancel rate rising 40.0% → 46.5% (§3.5) applies to ~275 Jul–Aug orders on the core catalogue. Returning legacy products to their Jan–Jun rate is worth more than any recovery sequence, but the cause is not yet identified (see Open questions).

---

## 7. Repeat purchase

### 7.1 Baseline reproduced

```sql
SELECT COUNT(*) AS customers_with_completed,
       SUM(cnt=1) AS bought_1, SUM(cnt=2) AS bought_2,
       SUM(cnt BETWEEN 3 AND 4) AS bought_3_4, SUM(cnt>=5) AS bought_5plus,
       SUM(customer_id=0) AS guest_bucket
FROM (SELECT customer_id, COUNT(*) cnt FROM wp_wc_order_stats
      WHERE status='wc-completed' GROUP BY customer_id) t;
```

| customers | 1x | 2x | 3–4x | 5x+ | guest bucket |
|---|---|---|---|---|---|
| 2,235 | 1,861 | 302 | 65 | 7 | 0 |

Matches the brief exactly. `guest_bucket = 0` confirms every completed order carries a real `customer_id`, so no guest-order blind spot.

Repeat customers = 374 / 2,235 = **16.7%**.

```sql
SELECT SUM(CASE WHEN cnt>1 THEN rev ELSE 0 END) repeat_rev, SUM(rev) total_rev,
  ROUND(100*SUM(CASE WHEN cnt>1 THEN rev ELSE 0 END)/SUM(rev),1) pct
FROM (SELECT customer_id, COUNT(*) cnt, SUM(total_sales) rev FROM wp_wc_order_stats
      WHERE status='wc-completed' GROUP BY customer_id) t;
```
→ repeat_rev 411,320.98 / total_rev 1,220,253.17 = **33.7%**. Confirms the brief. *Scope:* lifetime, all completed orders, gross.

### 7.2 Time to second purchase

*Definition:* days between a customer's first completed order and their next completed order. Lifetime scope, 374 repeat customers.

```sql
SELECT CASE
  WHEN d<=1 THEN 'a_0-1d' WHEN d<=7 THEN 'b_2-7d' WHEN d<=30 THEN 'c_8-30d'
  WHEN d<=90 THEN 'd_31-90d' WHEN d<=180 THEN 'e_91-180d' WHEN d<=365 THEN 'f_181-365d'
  ELSE 'g_365d+' END AS bucket, COUNT(*) n
FROM (
  SELECT o1.customer_id, DATEDIFF(MIN(o2.date_created), MIN(o1.date_created)) d
  FROM (SELECT customer_id, MIN(date_created) date_created FROM wp_wc_order_stats
        WHERE status='wc-completed' AND customer_id>0 GROUP BY customer_id) o1
  JOIN wp_wc_order_stats o2 ON o2.customer_id=o1.customer_id AND o2.status='wc-completed'
       AND o2.date_created > o1.date_created
  GROUP BY o1.customer_id
) z GROUP BY bucket ORDER BY bucket;
```

| bucket | n | share | cumulative |
|---|---|---|---|
| 0–1 day | **96** | 25.7% | 25.7% |
| 2–7 days | 33 | 8.8% | 34.5% |
| 8–30 days | 57 | 15.2% | 49.7% |
| 31–90 days | 78 | 20.9% | 70.6% |
| 91–180 days | 65 | 17.4% | 88.0% |
| 181–365 days | 31 | 8.3% | 96.3% |
| 365+ days | 14 | 3.7% | 100% |

**Median = 31 days** (exact, computed by row-numbering the sorted distribution). **Mean = 81.6 days** — dragged right by the long tail.

**Important caveat: the 0–1 day bucket (96 customers, 25.7% of all "repeats") is not retention.** These are almost certainly split-cart or same-session second orders — a customer buying two courses in two transactions minutes apart. Treating them as loyalty inflates the repeat picture by a quarter. **True post-purchase repeat rate = (374 − 96) / 2,235 = 12.4%.**

The genuine retention window is **8–180 days**, holding 200 of 374 repeats (53.5%). A win-back / next-course sequence should fire in that window — roughly **day 14 to day 60** captures the rising edge.

### 7.3 Which first product predicts a second purchase

*Definition:* for each product, the number of customers whose **first** completed order contained it, and what share of them later completed another order. Two versions: `pct_any` (any later order) and `pct_after_1d` (later order more than 1 day afterwards — strips the split-cart artifact). Products with ≥30 first-time customers only. *Scope:* lifetime.

```sql
SELECT LEFT(po.post_title,42) AS first_product, lu.product_id,
  COUNT(DISTINCT f.customer_id) AS first_time_customers,
  SUM(CASE WHEN f.later_orders>0 THEN 1 ELSE 0 END) AS bought_again_any,
  ROUND(100*SUM(CASE WHEN f.later_orders>0 THEN 1 ELSE 0 END)/COUNT(DISTINCT f.customer_id),1) AS pct_any,
  SUM(CASE WHEN f.later_gt1d>0 THEN 1 ELSE 0 END) AS bought_again_after_1d,
  ROUND(100*SUM(CASE WHEN f.later_gt1d>0 THEN 1 ELSE 0 END)/COUNT(DISTINCT f.customer_id),1) AS pct_after_1d
FROM (
  SELECT c.customer_id, c.first_order, c.first_date,
    (SELECT COUNT(*) FROM wp_wc_order_stats o WHERE o.customer_id=c.customer_id
       AND o.status='wc-completed' AND o.date_created>c.first_date) AS later_orders,
    (SELECT COUNT(*) FROM wp_wc_order_stats o WHERE o.customer_id=c.customer_id
       AND o.status='wc-completed' AND o.date_created>DATE_ADD(c.first_date, INTERVAL 1 DAY)) AS later_gt1d
  FROM (SELECT customer_id, MIN(date_created) first_date,
               SUBSTRING_INDEX(GROUP_CONCAT(order_id ORDER BY date_created),',',1) first_order
        FROM wp_wc_order_stats WHERE status='wc-completed' AND customer_id>0
        GROUP BY customer_id) c
) f
JOIN wp_wc_order_product_lookup lu ON lu.order_id=f.first_order
LEFT JOIN wp_posts po ON po.ID=lu.product_id
GROUP BY lu.product_id, first_product
HAVING first_time_customers >= 30
ORDER BY pct_after_1d DESC;
```

| first product | id | first-time customers | bought again (any) | % any | bought again (>1d) | **% after 1d** |
|---|---|---|---|---|---|---|
| **كورس جافا للمبتدئين + كتاب هدية** | 27272 | 515 | 144 | **28.0%** | 120 | **23.3%** |
| البرمجة الكائنية (OOP) بلغة Java | 31580 | 371 | 71 | 19.1% | 40 | 10.8% |
| هياكل البيانات المستوي الاول | 11694 | 1413 | 165 | 11.7% | 145 | 10.3% |
| كتاب لغة جافا | 28056 | 143 | 15 | 10.5% | 11 | 7.7% |
| كورس Java Basics + OOP | 33336 | 136 | 13 | 9.6% | 9 | 6.6% |
| هياكل البيانات المستوي الثاني | 30895 | 52 | 5 | 9.6% | 3 | 5.8% |
| أساسيات Dart من الصفر لـ OOP | 39670 | 79 | 3 | 3.8% | 1 | 1.3% |

**"كورس جافا للمبتدئين + كتاب هدية" (id 27272, 550 EGP) is the standout acquisition product** — 23.3% of customers who start there buy again (excluding same-day), against a 12.4% site-wide true repeat rate. That is **1.9x better than average**, and 1.7x better on the `pct_any` measure (28.0% vs 16.7%).

The logic is intuitive: it is the cheapest real course and it sits at the *start* of a curriculum (Java basics → OOP → data structures), so it has somewhere to lead. By contrast "هياكل البيانات المستوي الاول" acquires the most customers by far (1,413) but converts them onward at only 10.3% — it is a terminal product for most buyers.

**Caveats:**
- Counts sum to more than 2,235 because a first order can contain several products; a customer is counted once per product in their first order.
- **Dart (39670) is censored data, not a bad product.** It launched 2026-06-17, so most of its buyers have not yet reached the 31-day median. Its 1.3% is uninformative. Re-measure after 2026-12-01.
- Products 30895 and 28056 have small or low-value bases; treat their rates as indicative only.

---

## Open questions / access gaps

1. **The unexplained +6.5pp legacy-product cancellation rise (§3.5).** Orders, payments, coupons, and file timestamps do not account for it. Needs sources outside the WP database:
   - **GA4** (`G-DT3Z0RSEBK`) — did traffic composition or source mix change in July? A new low-intent channel would explain it.
   - **Meta / TikTok ad delivery** — was a campaign launched or re-targeted in early July?
   - **Microsoft Clarity** — the plugin is active; session recordings of abandoned checkouts in Jul–Aug would settle it directly.
   None of these are reachable from the shell. `UNVERIFIED`.

2. **Tutor LMS version mismatch — flag for immediate attention.** `wp plugin list --status=active` shows `tutor` at **4.0.7** with `tutor-pro` at **3.9.14**, following a plugin update on 2026-08-21. Tutor 4.x is a major release; running 3.x Pro against it is unsupported. This is a *recommendation, not an action* — no changes were made. Worth verifying course enrolment and certificate delivery still work for post-2026-08-21 purchases.

3. **Refund record conflict (§5).** `wp_wc_order_stats` says 4 orders are `wc-refunded`; `wp_posts` holds only 2 `shop_order_refund` records. Resolvable by reading the order notes on those 4 order IDs. Immaterial to totals.

4. **Manual-confirmation notes with `avg_h = 0.0` (Jan–May, §2.4)** may be automated rather than human. Distinguishing them needs the note `user_id` / author field, which was not examined.

5. **The 5–15% recovery-rate band (§6.3) is a benchmark, not a measurement.** Only a live test produces a real number for this audience. Note the 33.2% organic self-recovery rate means an intervention must beat what already happens for free.

6. **August is incomplete in both directions.** Data ends 2026-08-23 14:27. Orders from Aug 22–23 that will auto-cancel are still `pending` (24–48h window), so August's cancel count and rate shown here are floors, not finals. Re-run after 2026-08-27 for a clean August.

7. **`wp cron event list` could not confirm the scheduled `woocommerce_cancel_unpaid_orders` hook** because it was run with `--skip-plugins` (§1.3). The behavioural evidence is conclusive, but the cron timestamp itself is `UNVERIFIED`.

8. **Attribution is absent from this analysis.** `wp_wc_order_stats` has no UTM/source column here, so "which channel produces the highest-cancelling orders" — probably the single most useful follow-up for the ads goal — cannot be answered from the order tables alone. It needs GA4 or the Meta/TikTok pixel data joined on order id.
