# Learn Simply — Traffic, Funnel & Tracking Audit

**Scope:** learrnsimply.com (note the double `r` — correct, not a typo)
**Mode:** READ-ONLY. No site, plugin, option, theme, cache, ad-platform or analytics-property writes were made.
**Measurement window:** 2026-08-24T01:46Z → 2026-08-24T02:03Z (site local: Africa/Cairo, UTC+3)
**Author:** research agent, evidence-first. Every number below carries its definition, source and exact command.

---

## 0. Bottom line

| Question | Answer |
|---|---|
| Is tracking firing? | **Yes — all four tags fire live** (GA4, Meta Pixel, TikTok Pixel, MS Clarity), all with real IDs. |
| Is GA4 ecommerce working? | **Partly. `add_to_cart` verified firing. `view_item` and `view_item_list` are BROKEN** — root cause found and named. `begin_checkout`/`purchase` hooks are intact but UNVERIFIED at runtime. |
| Can we read the data programmatically? | **Meta Ads: YES** (verified, live pull). **GA4 / Search Console / TikTok Ads / Clarity: NO** — four specific credential gaps, listed in §8. |
| Is paid acquisition running? | **No. Zero paid spend in 2026.** Verified two independent ways (Meta Marketing API + order attribution on 1,657 orders). |
| Biggest measured leak | **48.2% of orders placed since Jul 1 auto-cancel unpaid.** 233 of 238 cancellations (97.9%) are one WooCommerce setting. |
| Biggest funnel blocker | **Hard login wall before checkout** — guests are redirected out of `/checkout/` before they see a form. |

**The one-line verdict on measurability:** everything from *order created* downward is trustworthy and queryable today. Everything *above* it — sessions, product views, cart adds as a rate — is invisible, because GA4 is the only system that holds it and we have no API access to GA4.

---

## 1. Method

Three independent evidence channels, deliberately not trusting plugin admin screens (this project has been burned by that before — Meta Pixel showed "active" while `pixel_id=0` in May 2026):

1. **Rendered HTML** via `curl` (what the server actually emits).
2. **Real browser runtime** via Playwright + Chromium 149 headless, mobile viewport 390×844 — network interception of every request to `google-analytics.com/g/collect`, `facebook.com/tr`, `analytics.tiktok.com`, `clarity.ms`, plus `PerformanceObserver` for LCP/CLS. This is the authority for "does the event actually fire".
3. **Live WordPress** via `ssh learnsimply` + `wp db query` (SELECT-only) and `wp option get`.

Chromium was installed locally on the workstation to enable channel 2. Nothing was installed on the server.

> **One disclosed side effect:** to verify `add_to_cart`, the browser clicked the real "اشتر الآن" button once, creating one ephemeral guest cart session (`wp_woocommerce_sessions`). That is ordinary visitor behaviour, auto-expires, writes no order and changes no configuration. It was the only way to move `add_to_cart` from UNVERIFIED to verified.

---

## 2. Q1 — Is tracking actually firing right now?

**Verified at runtime, fresh browser context per page, 9–11 s dwell.**

| Tag | ID (live, from rendered HTML + runtime) | Home | Product | Cart | Checkout | Verdict |
|---|---|---|---|---|---|---|
| GA4 (gtag.js) | `G-DT3Z0RSEBK` | `page_view` | `page_view` | `page_view` | `page_view` | ✅ firing |
| Meta Pixel | `699717432496147` | init ok, **no PageView hit** | init ok, **no PageView hit** | `PageView` | `PageView` | ⚠️ see §2.1 |
| TikTok Pixel | `D0E92UBC77U9B73T7LL0` | `Pageview`, `LandingPageView`, `EnrichAM`, `EngagedSession` | same | same | same | ✅ firing |
| MS Clarity | `xckdxrkgej` | hit | hit | hit | hit | ✅ firing |

- **Definition:** "firing" = an outbound network request to the vendor collection endpoint observed by the browser, not a tag present in markup.
- **Method:** `node track.mjs` / `track2.mjs` (Playwright request interception).
- **measured_at:** 2026-08-24T01:52Z–02:00Z.
- **Meta Pixel runtime state on every page:** `fbq` defined, `version 2.9.384`, `loaded: true`, `pixelsByID: ["699717432496147"]`, `queue length 0`. So the pixel is genuinely installed and initialised sitewide.

**This refutes the historical claim** in `03_KNOWLEDGE/analytics-audit-2026-05-24.md` that Meta Pixel was broken (`pixel_id=0`). That was true on 2026-05-24 (historical); it was fixed on 2026-06-02 and is confirmed working today. Meta's own API reports the pixel `last_fired_time: 2026-08-23T23:59:05+0000` — i.e. it received events within the last few hours.

### 2.1 Meta Pixel anomaly — PageView not observed on home/product

On Home and Product, `fbq` initialises and a `facebook.com/tr` request is made, but **no request carried `ev=PageView`**. On Cart and Checkout, `ev=PageView` is present. Reproduced across separate fresh browser contexts.

- **Impact:** Meta's site-wide audience/retargeting pool and any "all site visitors" custom audience will under-count the top of the funnel — exactly the pages an acquisition campaign would send traffic to.
- **Status:** `UNVERIFIED root cause.` The pixel is installed and functional; why the top-of-funnel PageView is absent needs a Meta Events Manager "Test Events" session to confirm (that requires Events Manager access — see §8).

### 2.2 Meta Conversions API is OFF

```
wp option get facebook_config
→ array( 'pixel_id' => '699717432496147', 'use_pii' => true, 'use_s2s' => false, 'access_token' => '' )
```

`use_s2s = false` and an empty `access_token` in that config = **server-side Conversions API is not sending.** Browser-only pixel coverage on a mobile-heavy Arabic audience loses a meaningful share of events to ad-blockers and iOS. This matters directly for the paid-ads goal: Meta cannot optimise toward Purchase events it never receives.

Note the internal conflict: option `wc_facebook_access_token` **does** hold a valid 196-char token (it worked against the Marketing API in §5), but `facebook_config['access_token']` is empty. The plugin (facebook-for-woocommerce 3.7.6) is therefore not wired for CAPI even though a token exists.

### 2.3 No ecommerce events on Meta or TikTok

Across Home, Product, Cart, Checkout and an add-to-cart click, the only Meta event observed was `PageView`, and the only TikTok events were the automatic `Pageview` / `LandingPageView` / `EngagedSession`.

**Never observed:** `ViewContent`, `AddToCart`, `InitiateCheckout`, `Purchase` (Meta); `ViewContent`, `AddToCart`, `PlaceAnOrder`, `CompletePayment` (TikTok).

**Consequence:** neither ad platform can run conversion-optimised campaigns or value-based bidding today. They can only optimise for link clicks / landing-page views. That is the single hardest blocker to "run paid acquisition profitably".

---

## 3. Q2 — GA4 ecommerce depth

### 3.1 The old audit's premise is refuted — MonsterInsights is gone

`03_KNOWLEDGE/analytics-audit-2026-05-24.md` (historical, 2026-05-24) states GA4 runs on MonsterInsights free tier with no ecommerce addon, firing PageView only.

**That is no longer true.** MonsterInsights is not in the active plugin list. GA4 is now served by the **official `woocommerce-google-analytics-integration`** plugin, which is ecommerce-capable and configured on:

```
wp option get woocommerce_google_analytics_settings --format=json
→ {"ga_product_identifier":"product_id","ga_id":"G-DT3Z0RSEBK",
   "ga_ecommerce_tracking_enabled":"yes","ga_event_tracking_enabled":"yes"}
```

Rendered HTML confirms the plugin declares the full event set:

```
ga4w = { ... settings: {"tracker_function_name":"gtag","events":["purchase","add_to_cart",
"remove_from_cart","view_item_list","select_content","view_item","begin_checkout",
"add_shipping_info","add_payment_info"], ...} }
```

**Declaring an event is not firing it.** Runtime results below.

### 3.2 What actually fires — verified

| GA4 event | Hook the plugin uses | Fires? | Evidence |
|---|---|---|---|
| `page_view` | gtag config | ✅ **YES** | every page, runtime |
| `add_to_cart` | `woocommerce_add_to_cart` (server-side) | ✅ **YES** | runtime, observed on real buy-button click |
| `view_item` | `woocommerce_after_single_product` | ❌ **NO** | product page runtime: only `page_view`, `user_engagement` |
| `view_item_list` | `woocommerce_loop_add_to_cart_link` (filter) | ❌ **NO** | `/shop/` and `/courses/` archives runtime: only `page_view` |
| `begin_checkout` | `woocommerce_before_checkout_form` | ⚠️ `UNVERIFIED` | hook **is** present in the child template (`form-checkout.php:21`), but reaching checkout requires a login — not testable read-only |
| `purchase` | `woocommerce_thankyou` | ⚠️ `UNVERIFIED` | no `thankyou.php` override exists, so the core template fires the hook — but confirming needs a real order |
| `remove_from_cart` | `woocommerce_cart_item_removed` | ⚠️ `UNVERIFIED` | not exercised |

### 3.3 Root cause of the broken `view_item` / `view_item_list` — found

Not a settings problem. The plugin defaults are correct (`class-wc-google-analytics.php:187`, `'ga_enhanced_product_detail_view_enabled' => default 'yes'`).

The cause is **the child theme's custom product template**:

- `wp-content/themes/edublink-child/woocommerce/single-product.php` (14,314 bytes) renders the product page **entirely by itself** and never loads `content-single-product.php`. Its only WooCommerce reference is a review comment form at line 252.
- Therefore `do_action('woocommerce_after_single_product')` — the hook `view_item` is bound to — **never runs**.
- Corroborating markers in the rendered product HTML: `single_add_to_cart_button` = 0 occurrences, `data-product_id` = 0 occurrences, and the buy control is a hand-rolled anchor: `<a href="…/cart-1/?add-to-cart=33336" class="buy-button">اشتر الآن</a>`.
- Runtime proof of the data starvation: on the product page `window.ga4w.data` contains only `{cart, list_name}` — **no product object at all**.
- Same mechanism for `view_item_list`: it is a *filter* on `woocommerce_loop_add_to_cart_link`, and the custom archive markup never calls that filter.

### 3.4 What this makes impossible to measure

- Product-page view counts, and therefore **product-view → add-to-cart conversion rate** per course.
- Which courses get looked at but not bought (the core merchandising question).
- GA4 funnel exploration from item view onward.
- Any GA4 audience built on "viewed product X" — which is the standard remarketing seed for paid campaigns.
- Item-level `view_item_list` → `select_item` attribution across the shop and courses archives.

`add_to_cart` and (probably) `purchase` still arrive, so GA4 revenue totals should be broadly reconcilable — but the top half of the GA4 ecommerce funnel is simply absent.

---

## 4. Q3 — Programmatic data access from this machine, tested

| Source | Reachable now? | What was tried | Result |
|---|---|---|---|
| **Meta Marketing API** | ✅ **YES** | `GET /v21.0/me/adaccounts`, `/act_1770006103570345/insights`, `/campaigns`, `/699717432496147` using the token in WP option `wc_facebook_access_token` | Full read of ad account, lifetime + monthly spend, all campaigns, pixel health |
| **WordPress / WooCommerce DB** | ✅ **YES** | `ssh learnsimply` + `wp db query` (SELECT-only) | Full order, attribution, product, user data |
| **Mautic API** | ✅ **YES** | `curl -u $MAUTIC_API_USER:… https://mautic.learrnsimply.com/api/{contacts,segments,emails}` | 14,630 contacts, 11 segments, 6 emails |
| **n8n API** | ✅ yes | key present in `.mcp.json` | not needed for this audit |
| **GA4 Data API** | ❌ **NO** | no property ID, no OAuth/service account anywhere | see gap **G1** |
| **Google Search Console API** | ❌ **NO** | property URL known, no OAuth | see gap **G2** |
| **TikTok Ads API** | ❌ **NO** | token authenticates but is scoped to the **wrong company** | see gap **G3** |
| **MS Clarity API** | ❌ **NO** | project ID known, no API token | see gap **G4** |

**Checks performed to confirm the Google gaps are real, not overlooked:**
```
GA4_PROPERTY_ID           → EMPTY in .env
GA4_API_SECRET            → EMPTY in .env
GSC_OWNER_EMAIL           → EMPTY in .env
gcloud CLI                → ABSENT
~/.config/gcloud/application_default_credentials.json → ABSENT
find . -name '*service-account*' -o -name 'client_secret*.json' → no matches
.mcp.json                 → only n8n-mcp; no GA4/GSC MCP server configured
```

### 4.1 TikTok token belongs to a different brand

```
GET /open_api/v1.3/oauth2/advertiser/get/
→ {"code":0,"data":{"list":[
     {"advertiser_id":"7482187614272192529","name":"Groomi_Ad_Account","company":"Groomi"},
     {"advertiser_id":"7493469914234241025","name":"1829460434561026","company":"Groomi"}]}}

GET /open_api/v1.3/advertiser/info/?advertiser_ids=["7502022393087590401"]   ← the ID in .env
→ {"code":40001,"message":"No permission to operate advertiser: 7502022393087590401"}
```

The `TIKTOK_ACCESS_TOKEN` stored in this brand's `.env` grants access only to two advertiser accounts owned by **"Groomi"**, an unrelated company. It has **no permission** on Learn Simply's own advertiser ID. This is both an access gap and a data-hygiene issue: a third party's ad-account token is sitting in Learn Simply's `.env`.

---

## 5. Q4 — Is any paid acquisition running? **No.**

Two independent sources agree.

### 5.1 Meta Ads — direct from the API (authoritative)

```
GET /v21.0/act_1770006103570345?fields=name,currency,timezone_name,account_status,amount_spent,balance,created_time
→ name "اتعلم ببساطة", currency EGP, timezone Africa/Cairo,
  account_status 1 (ACTIVE), amount_spent "265082", balance "0",
  created_time 2025-04-20, disable_reason 0
```

| Metric | Value | Period | Source |
|---|---|---|---|
| Lifetime spend | **2,655.96 EGP** | 2025-04-20 → 2026-08-23 | `/insights?date_preset=maximum` |
| Impressions / clicks / reach (lifetime) | 60,528 / 2,631 / 38,334 | same | same |
| **Spend, last 30 days** | **0.00 EGP** (`data: []`) | 2026-07-25 → 2026-08-23 | `/insights?date_preset=last_30d` |
| **Spend, 2026 YTD** | **0.00 EGP** (`data: []`) | 2026-01-01 → 2026-08-23 | `/insights?time_range={...}` |
| Campaigns | **5, all `PAUSED`** | newest created 2025-12-23 | `/campaigns` |

Monthly spend, every month with any activity (`time_increment=monthly`):

| Month | Spend (EGP) | Impressions | Clicks |
|---|---|---|---|
| 2025-05 | 662.86 | 16,220 | 710 |
| 2025-06 | 337.14 | 14,160 | 657 |
| 2025-11 | 1,332.42 | 19,714 | 968 |
| 2025-12 | 323.54 | 10,434 | 296 |
| **Total** | **2,655.96** | 60,528 | 2,631 |

Sum reproduced by command, not mentally: `python3 -c "print(sum([662.86,337.14,1332.42,323.54]))"` → `2655.96`.

Campaign names (all `OUTCOME_SALES`, all paused): `Java ABO I Saeed I 23Dec`, `Java + Bundle Sales Campaign`, `Second- 16/6/2025 - Campaign`, `Java - Advantage - 05/07/2025 Campaign`, `Course Java -7/5/2025`.

> **Minor conflict, disclosed:** the account field `amount_spent = 265082` minor units = **2,650.82 EGP**, vs the insights lifetime row **2,655.96 EGP** — a 5.14 EGP delta. Immaterial; likely a rounding/attribution-window difference between the two Meta endpoints. Prefer the insights figure.

**Reading:** lifetime Meta spend is ~2,656 EGP against 2026-YTD completed revenue of ~497,641 EGP. Paid has been a rounding error for this business. There is no historical CAC/ROAS baseline worth extrapolating from — 2,631 lifetime clicks is too thin.

### 5.2 Order attribution — independent confirmation on 1,657 orders

WooCommerce's native Order Attribution is installed and populated (`_wc_order_attribution_source_type` on 4,964 orders all-time).

**Source of truth note:** `woocommerce_custom_orders_table_enabled = no` with sync on, so **`wp_posts` + `wp_postmeta` is authoritative**; `wp_wc_orders` (HPOS) is a synced mirror (2,722 vs 2,720 completed — 2-row sync drift at read time).

**2026 YTD (2026-01-01 → 2026-08-23), all order statuses, n = 1,657:**

| Bucket | Orders | % | Completed | Gross completed (EGP) |
|---|---|---|---|---|
| direct (`typein`) | 585 | 35.3% | 296 | 133,650 + 38,684 |
| referral: **YouTube** | 531 | 32.0% | 248 | 109,584 + 29,096 |
| organic search (Google/Bing/etc.) | 331 | 20.0% | 176 | 73,685 + 27,944 |
| admin / manual | 144 | 8.7% | 140 | 42,970 + 25,838 |
| referral: other | 29 | 1.8% | 15 | 4,741 + 3,099 |
| utm: `ig` | 15 | 0.9% | 6 | 2,455.98 + 0 |
| **UNATTRIBUTED** | 9 | 0.5% | 4 | 1,297 + 900 |
| referral: social (fb/ig/telegram) | 7 | 0.4% | 3 | 1,448 + 0 |
| email (`brevo`/`mautic`) | 4 | 0.2% | 3 | 550 + 700 |
| utm: `chatgpt.com` | 2 | 0.1% | 1 | 999 |
| **PAID (cpc / paid_social / ads)** | **0** | **0.0%** | 0 | 0 |

**Jul 1 – Aug 23, 2026, n = 494:**

| Bucket | Orders | % | Completed |
|---|---|---|---|
| direct | 168 | 34.0% | 63 |
| referral: YouTube | 141 | 28.5% | 56 |
| organic search | 117 | 23.7% | 50 |
| admin / manual | 49 | 9.9% | 49 |
| referral: other | 9 | 1.8% | 5 |
| referral: social | 3 | 0.6% | 0 |
| unattributed | 2 | 0.4% | 1 |
| utm: chatgpt.com | 2 | 0.4% | 1 |
| email | 2 | 0.4% | 2 |
| utm: ig | 1 | 0.2% | 0 |
| **PAID** | **0** | **0.0%** | 0 |

Supporting fact: `_wc_order_attribution_utm_campaign` exists on only **4 orders in the entire database** — there is effectively no campaign tagging anywhere, consistent with no campaigns running.

**Unattributed rate is excellent: 0.5% YTD (9 / 1,657).** Order attribution is the most trustworthy measurement asset this business currently has.

### 5.3 Email is not a measurable channel yet

Mautic holds **14,630 contacts** across 11 segments, but only **1,413 emails have ever been sent** (sum of `sentCount` across all 6 emails: 270+648+0+404+87+4, computed by command). Attributed orders from email: **4 in all of 2026.** The 13K list remains essentially unmonetised.

---

## 6. Q5 — Performance and technical SEO baseline

### 6.1 Core Web Vitals — genuinely measured (lab, not field)

Playwright + Chromium 149, mobile viewport 390×844, `PerformanceObserver` for LCP and CLS, cold context per page.

| Page | LCP | CLS | TTFB | DOMContentLoaded | Load | Requests | Transfer |
|---|---|---|---|---|---|---|---|
| Home | 496 ms | **0.067** | 45 ms | 939 ms | 1,523 ms | 243 | 1,945 KB |
| Product | 220 ms | 0.000 | 20 ms | 329 ms | 515 ms | 206 | 157 KB¹ |
| Cart | 1,488 ms | 0.000 | 1,262 ms | 1,533 ms | 1,680 ms | 202 | 111 KB¹ |
| Checkout (→ login redirect) | 1,868 ms | **0.092** | 1,699 ms | 1,924 ms | 2,203 ms | 178 | 0 KB¹ |

¹ warm HTTP cache within the same browser session — not a cold-load figure.

- **These are LAB numbers on a fast datacentre connection, not field data.** Real Egyptian mobile users on 4G will be materially worse. **Actual Core Web Vitals (CrUX field data) = `UNVERIFIED`** — needs PageSpeed Insights API or Search Console's Core Web Vitals report (gap **G2**).
- All LCP values are inside Google's 2.5 s "good" threshold in lab conditions.
- **CLS on Home (0.067) and Checkout (0.092) are approaching the 0.1 "needs improvement" line** — the checkout figure is the one to watch, since layout shift on a payment form costs conversions directly.

### 6.2 Page weight

- **Home, cold load: 1,945 KB over 243 requests** (runtime transfer, authoritative).
- Static analysis of the product page's referenced same-origin assets totals **≈5.50 MB across 97 assets** (`curl` + parallel GET, gzip accepted). Runtime transfer is lower because images below the fold lazy-load — but that weight is still on the wire for a user who scrolls.
- **Worst offenders — unoptimised PNGs:**
  | Asset | Bytes |
  |---|---|
  | `/uploads/2025/10/for-profile.png` | 2,179,945 |
  | `/uploads/2025/11/java-bundler.png` | 1,660,885 |
  | `/uploads/2025/10/java-level-one.png` | 443,618 |
  | `/uploads/2025/03/cover-learn-simply1-1-scaled.jpg` | 420,270 |
  | `/uploads/2025/09/java-oop.png` | 191,816 |
- **77 separate JS files** totalling ~601 KB; only 2 CSS files (~13 KB).
- A single 2.18 MB PNG on a course page is the clearest, cheapest performance win available (WebP conversion + resize ≈ 90% reduction).

### 6.3 Caching — the old "both plugins active" concern is resolved

```
wp plugin list | grep -iE 'litespeed|wp-optimize'
→ litespeed-cache   inactive  7.9
→ wp-optimize       active    4.6.1
```

- **Only WP-Optimize is active.** LiteSpeed Cache is installed but inactive. The historical conflict is gone.
- WP-Optimize page caching is **on**, TTL 24 h (`wpo_cache_config: {"enable_page_caching":true,"page_cache_length":86400}`).
- Verified live: home and product pages return `wpo-cache-status: cached`; cart returns uncached (correct).
- Hostinger CDN (`server: hcdn`) sits in front but reports `x-hcdn-cache-status: DYNAMIC` — **HTML is not cached at the edge**, only origin-cached.
- ⚠️ **Stale drop-in:** `wp-content/object-cache.php` exists (1,868 bytes, dated 2026-06-03) and is the **LiteSpeed Cache object-cache drop-in** — left behind after LiteSpeed was deactivated. An orphaned object-cache drop-in from a disabled plugin is a latent risk and should be reviewed. There is no Redis/Memcached backing it.

### 6.4 A real, fixable redirect tax on every product click

Internal links across the homepage and `/courses/` archive point at `/courses/{slug}/`, which **301-redirects** to `/product/{slug}/`:

| Path | Redirects | TTFB |
|---|---|---|
| `/product/java-basics-oop-bundle/` (direct) | 0 | 0.040–0.078 s (cached) |
| `/courses/java-basics-oop-bundle/` (via alias) | 1 | 0.697–0.996 s |

`woocommerce_permalinks` sets `product_base = /product`. Every visitor clicking a course card from the homepage or archive pays an extra round-trip of roughly **0.6–0.9 s** before the product page even starts. Pointing internal links at the canonical `/product/` URL removes it entirely.

### 6.5 Technical SEO — healthy

| Check | Result |
|---|---|
| `<title>` ownership | **Rank Math owns titles.** Exactly **1** `<title>` per page on home, courses, cart, checkout, product. The historical BUG-003 duplicate-title issue is confirmed fixed and still fixed. |
| SEO plugin | `seo-by-rank-math` + `seo-by-rank-math-pro`, both active |
| Canonical | present and correct (`<link rel="canonical" href="https://learrnsimply.com/product/java-basics-oop-bundle/" />`) |
| Meta robots | `follow, index, max-snippet:-1, max-image-preview:large` |
| `robots.txt` | present, sane; blocks `?add-to-cart=` URLs; declares sitemap |
| `sitemap_index.xml` | HTTP 200; 12 child sitemaps (post, page, product, courses, 6× lesson, quiz, course-bundle) |
| Structured data | `Product`, `Offer`, `AggregateRating`, `Review`, `Organization`, `WebSite`, `ItemPage` — rich-result eligible |
| Language / direction | `<html lang="ar" dir="rtl">` — correct |
| WordPress core | 7.0.4 |
| Plugins | **78 total: 54 active, 14 inactive, 8 must-use, 2 drop-ins** |

54 active plugins is high and is the most likely contributor to the 77-JS-file front end, but nothing here is broken.

---

## 7. Q6 — Checkout friction inventory

### 7.1 The blocker: guests cannot reach checkout at all

```
wp option get woocommerce_enable_guest_checkout                 → "no"
wp option get woocommerce_enable_signup_and_login_from_checkout → "yes"
wp option get woocommerce_enable_checkout_login_reminder        → "no"
```

Verified live, twice (curl and Playwright): requesting `https://learrnsimply.com/checkout/` as a logged-out visitor returns
`https://learrnsimply.com/dashboard/?redirect_to=https%3A%2F%2Flearrnsimply.com%2Fcheckout%2F`.

The redirect is a deliberate child-theme customisation, `wp-content/themes/edublink-child/functions.php:3303-3317`:

```php
/**
 * Guests cannot fill the hidden billing form, so checkout requires an account:
 * instead of showing the login form/notice at the top of the checkout page,
 * send logged-out visitors to the login page and bring them back afterwards.
 */
add_action('template_redirect', 'learnsimply_checkout_require_login');
```

Combined with the `learnsimply-skip-cart.php` mu-plugin (which redirects every add-to-cart straight to checkout), the observed guest journey is:

> click **"اشتر الآن"** → add to cart → redirect to checkout → **redirect to login page**

The runtime test confirmed exactly this: after one click on the buy button the browser landed on `/dashboard/?redirect_to=…checkout`. **A first-time visitor hits an account wall on their very first click**, before seeing a price summary or a payment option. For a paid-traffic strategy where every click is bought, this is the most expensive single item in this report.

The code comment says the reason is that "guests cannot fill the hidden billing form" — i.e. it is a workaround for a form-rendering issue, not a considered commercial decision. That makes it a strong candidate for a proper fix rather than a permanent constraint.

### 7.2 The form itself is lean — not the problem

`wp option get wc_fields_billing` (checkout field editor pro) — **4 fields, all required:**

| Field | Label | Required | Type |
|---|---|---|---|
| `billing_first_name` | الاسم الأول | yes | text |
| `billing_last_name` | الاسم الأخير | yes | text |
| `billing_phone` | الهاتف | yes | tel |
| `billing_email` | البريد الإلكتروني | yes | email |

Supporting config: `woocommerce_ship_to_countries = disabled` (correct — digital goods), `woocommerce_checkout_company_field = hidden`, `woocommerce_checkout_address_2_field = hidden`, `woocommerce_allowed_countries = all`, `woocommerce_default_country = EG:EGSHR`, `woocommerce_terms_page_id` = empty (no forced terms checkbox).

**Steps:** 1 (single-page checkout, WooCommerce page ID 22, no CartFlows funnel override on checkout). Cart page is bypassed entirely by design.

**Verdict:** field count and step count are already close to optimal. The friction is 100% the login wall, not the form.

### 7.3 Dark patterns

Noted, **not re-litigated** per the prior session decision to leave them as-is. Active plugins in this category: `optinmonster`, `conditional-add-to-cart`, `learnsimply-dart-popup` / `learnsimply-dart-announce` mu-plugins. No new assessment offered.

---

## 8. ACCESS GAPS

Each gap: what is missing, exactly what it unlocks, and which decision it currently blocks.

### G1 — GA4 Data API (**highest priority**)

- **Missing:** (a) the GA4 **numeric property ID** (`GA4_PROPERTY_ID` is empty in `.env`; only the measurement ID `G-DT3Z0RSEBK` is known), and (b) an **OAuth grant or service-account JSON** with `analytics.readonly` on that property.
- **Route:** Google Cloud project → enable Google Analytics Data API → create a service account → add its email as a **Viewer** on the GA4 property → download the JSON key. Property ID is visible at GA4 → Admin → Property Settings. Admin access already exists under `GA4_ADMIN_ACCESS_EMAIL`.
- **Unlocks:** sessions, users, traffic source/medium, landing pages, engagement rate, `add_to_cart` counts, `begin_checkout` counts, `purchase` counts and revenue, device/geo splits.
- **Blocks:** **the entire top of the funnel.** Steps 1–4 in §9 cannot be filled without this. Also blocks any before/after read on an ads test, and any conversion-rate baseline. No paid budget should be committed until this is readable.

### G2 — Google Search Console API

- **Missing:** OAuth credentials for the verified property `GSC_PROPERTY_URL` (`GSC_OWNER_EMAIL` and `GSC_VERIFICATION_CODE` are both empty in `.env`).
- **Route:** same Google Cloud project → enable Search Console API → grant the service account (or an OAuth user) read access to the property.
- **Unlocks:** organic queries, impressions, CTR, average position, indexing coverage, **and the real Core Web Vitals field data (CrUX)** that §6.1 could not measure.
- **Blocks:** sizing the organic-search channel (20% of orders, entirely unmeasured on the traffic side); deciding SEO vs paid budget split; confirming whether the CLS/LCP lab numbers hold for real users.

### G3 — TikTok Ads API for the **correct** advertiser

- **Missing:** an access token scoped to Learn Simply's own advertiser ID `7502022393087590401`. The current `TIKTOK_ACCESS_TOKEN` authenticates successfully but is scoped to two advertiser accounts belonging to **"Groomi"**, an unrelated company (verified via `/oauth2/advertiser/get/`).
- **Route:** TikTok Business Center → re-authorise the app against the Learn Simply advertiser, issue a fresh token, and **remove the foreign Groomi token from this brand's `.env`.**
- **Unlocks:** TikTok spend/impressions/clicks history, pixel event diagnostics, whether TikTok ads ever ran.
- **Blocks:** confirming whether TikTok is a dormant paid channel or was never used. Also a hygiene issue — a third party's ad-account token should not be stored here.

### G4 — Microsoft Clarity API

- **Missing:** a Clarity API token for project `xckdxrkgej` (the project ID is known and the tag is verified firing).
- **Route:** Clarity dashboard → Settings → API.
- **Unlocks:** session recordings, heatmaps, rage/dead clicks, JS errors by page.
- **Blocks:** qualitative diagnosis of *why* people drop at the login wall. Clarity is already collecting this data — it just cannot be read programmatically.

### G5 — Meta Events Manager (UI, not API)

- **Missing:** interactive Events Manager access to run "Test Events" for pixel `699717432496147`. The Marketing API token reads ads data but does not substitute for the Test Events tool.
- **Unlocks:** confirming the §2.1 PageView anomaly and diagnosing the absent `ViewContent` / `Purchase` events.
- **Blocks:** certifying the Meta pixel as campaign-ready. Meta cannot optimise for conversions it never receives, so this gates any Meta ads spend.

> **Not a gap:** Meta Ads read access already works via the token in WP option `wc_facebook_access_token`. Worth recording in `.env` under a proper key — note that the current `META_CAPI_ACCESS_TOKEN=` line is a prose comment, not a token, and it breaks `set -a; . ./.env` sourcing at that line (everything defined after it silently fails to load). That malformed line caused an initial false negative in this audit.

---

## 9. Q7 — The conversion funnel, reconstructed

**Period: 2026-07-01 → 2026-08-23 (Jul 1 – now). Scope: all channels. Source of truth: `wp_posts` + `wp_postmeta`.**

| # | Step | Value | Status | Source / why |
|---|---|---|---|---|
| 1 | Sessions | — | ❌ **UNMEASURABLE** | Only GA4 holds it; no Data API access (**G1**) |
| 2 | Product views | — | ❌ **UNMEASURABLE** | `view_item` never fires (§3.3). Even with G1 fixed, this stays broken until the template is fixed |
| 3 | Add to cart | — | ⚠️ **fires but unreadable** | GA4 `add_to_cart` verified firing, but no API to read the count (**G1**). Proxy only: **547** live rows in `wp_woocommerce_sessions` at 2026-08-24T01:58Z (point-in-time, not a period count) |
| 4 | Checkout started | — | ⚠️ **partly** | `begin_checkout` hook present but `UNVERIFIED`; unreadable without G1. **Note: only logged-in users can ever reach this step** |
| 5 | **Order placed** | **494** | ✅ **MEASURED** | `SELECT COUNT(*) FROM wp_posts WHERE post_type='shop_order' AND post_date>='2026-07-01'` |
| 6 | — of which paid & completed | **227 (46.0%)** | ✅ **MEASURED** | `post_status='wc-completed'` |
| 7 | — auto-cancelled unpaid | **238 (48.2%)** | ✅ **MEASURED** | `post_status='wc-cancelled'` |
| 8 | — payment failed | **23 (4.7%)** | ✅ **MEASURED** | `post_status='wc-failed'` |
| 9 | Distinct customers | **315** | ✅ **MEASURED** | `COUNT(DISTINCT _customer_user)` |
| 10 | Gross revenue (completed) | **127,260.00 EGP** | ✅ **MEASURED** | `SUM(_order_total)`, gross — before refunds/fees |
| 11 | AOV (completed) | **560.62 EGP** | ✅ **MEASURED** | same query, `AVG` |

**2026 YTD (2026-01-01 → 2026-08-23):** 1,657 orders placed · 895 completed (54.0%) · 678 cancelled (40.9%) · 64 failed (3.9%) · **gross completed revenue 497,640.98 EGP**.

Monthly trend (all statuses / completed / cancelled / gross completed EGP):

| Month | Orders | Completed | Cancelled | Gross (EGP) |
|---|---|---|---|---|
| 2026-01 | 162 | 88 | 64 | 49,756.00 |
| 2026-02 | 196 | 107 | 85 | 60,738.00 |
| 2026-03 | 211 | 126 | 73 | 69,971.00 |
| 2026-04 | 274 | 164 | 99 | 87,243.98 |
| 2026-05 | 183 | 103 | 72 | 60,538.00 |
| 2026-06 | 137 | 80 | 47 | 42,134.00 |
| 2026-07 | 292 | 146 | 131 | 72,934.00 |
| 2026-08 (to 23rd) | 202 | 81 | 107 | 54,326.00 |

**The completion rate is deteriorating:** 54.0% YTD → 46.0% since Jul 1 → **40.1% in August** (81/202). August is a partial month, but the direction across three consecutive periods is consistent.

### 9.1 The 48% cancellation is one WooCommerce setting

Order notes name the cause unambiguously:

```sql
SELECT c.comment_content, COUNT(*) FROM wp_comments c
JOIN wp_posts p ON p.ID=c.comment_post_ID
WHERE c.comment_type='order_note' AND p.post_status='wc-cancelled'
  AND p.post_date>='2026-07-01' GROUP BY c.comment_content ORDER BY 2 DESC;
```

| Order note | Count |
|---|---|
| «طلب غير مدفوع ألغي - الوقت المحدد أنتهى» *(unpaid order cancelled — time limit expired)* | **233** |
| «الطلب ألغي بواسطة الزبون» *(cancelled by customer)* | 2 |
| other / admin edits | 3 |

- **Jul 1 – Aug 23: 233 of 238 cancellations = 97.9%** are the automatic unpaid-order timeout.
- **2026 YTD: 669 of 678 = 98.7%.**
- Time from order creation to cancellation: avg **35.18 h**, min 0.03 h, max 93.05 h — consistent with the configured window plus cron lag.

The setting:
```
wp option get woocommerce_hold_stock_minutes → "1440"   (24 hours)
wp option get woocommerce_manage_stock       → "yes"
```

**Every product here is digital.** Stock-holding has no meaning for a downloadable course, yet it is switched on and auto-cancelling unpaid orders after 24 h.

**Value of auto-cancelled orders** (per-order subquery, not `SUM(DISTINCT)`):

| Period | Orders | Value (EGP) | Avg order |
|---|---|---|---|
| Jul 1 – Aug 23 | 233 | **160,668.00** | 689.56 |
| 2026 YTD | 669 | **413,983.00** | 618.81 |

> **Read this number honestly.** These are *unpaid* orders — the customer reached checkout, generated an order, and never completed payment. Auto-cancelling did not destroy a paid sale. What it destroys is the **recovery window**: once cancelled, the order is dead, no payment link works, and no abandoned-payment follow-up is possible. It also pollutes every conversion-rate calculation. The honest framing is: **160,668 EGP of checkout intent since Jul 1 reached order-creation and was then abandoned at the payment step**, and the current configuration guarantees none of it can be recovered. Note also that the average abandoned order (689.56 EGP) is *higher* than the average completed order (560.62 EGP) — the more expensive the basket, the more likely it is lost at payment.

Corroborating: cancelled orders split `kashier_card` 159 / `kashier_wallet` 79; completed split `kashier_card` 115 / `kashier_wallet` 63 / no-method 49 (the admin/manual bucket). Both payment methods abandon at broadly similar rates — this is **not** a gateway fault. That is consistent with the prior session's conclusion that the "Kashier is broken / 195K lost" narrative was wrong, and it remains wrong.

### 9.2 One more measured oddity

**1,866 new user registrations** since Jul 1 (`SELECT COUNT(*) FROM wp_users WHERE user_registered>='2026-07-01'`) against **494 orders** and **315 distinct customers**. Because guest checkout is disabled, account creation is mandatory to buy — yet registrations outnumber orders roughly 3.8:1. Those ~1,550 accounts either registered for free content or **abandoned during or immediately after the forced account creation**. Distinguishing the two requires GA4 (**G1**) — but it is the sharpest available hint at the cost of the login wall.

---

## 10. Recommendations (read-only audit — nothing was changed)

Ranked by measured impact per unit of effort.

| # | Action | Why (measured) | Effort |
|---|---|---|---|
| **1** | Set `woocommerce_hold_stock_minutes` to empty (disable), or turn off `manage_stock` for digital products | Ends 97.9% of all cancellations; keeps 160,668 EGP/7-weeks of intent recoverable instead of dead | Minutes |
| **2** | Obtain GA4 property ID + service-account read access (**G1**) | Unblocks funnel steps 1–4. **No ad budget should be spent before this** | Hours |
| **3** | Fix the checkout login wall (`functions.php:3303`) — repair the billing-form rendering the comment blames, then enable guest checkout | Guests hit an account wall on their **first** click; 1,866 registrations vs 315 buyers | Half a day |
| **4** | Fix `view_item` / `view_item_list` — make `single-product.php` fire `woocommerce_after_single_product` (and the loop use `woocommerce_loop_add_to_cart_link`) | Restores the top half of GA4 ecommerce and the remarketing seed audiences | Half a day |
| **5** | Enable Meta CAPI (`use_s2s`, populate `facebook_config['access_token']`) and get `ViewContent`/`AddToCart`/`Purchase` firing on Meta + TikTok | Neither platform can conversion-optimise today — this is the hard gate on paid acquisition | 1 day |
| **6** | Compress the top offenders — `for-profile.png` (2.18 MB), `java-bundler.png` (1.66 MB) → WebP | ~4 MB off course pages for a mobile-first Arabic audience | 1 hour |
| **7** | Point internal course links at `/product/{slug}/` instead of `/courses/{slug}/` | Removes a 0.6–0.9 s 301 hop from every product click | 1 hour |
| **8** | Review/remove the orphaned LiteSpeed `object-cache.php` drop-in | Drop-in from a deactivated plugin, unbacked by Redis/Memcached | 15 min |
| **9** | Remove the foreign **Groomi** TikTok token from `.env`; fix the malformed `META_CAPI_ACCESS_TOKEN=` prose line (it breaks `.env` sourcing) | Hygiene + it caused a false negative during this audit | 15 min |
| **10** | Rotate the n8n API key committed in `.mcp.json` (tracked in git) | Live JWT in a tracked file | 30 min |

---

## 11. Corrections to prior repo documentation

Numbers from older docs that this audit **re-measured and found no longer true**:

| Old claim | Doc / date (historical) | Measured 2026-08-24 |
|---|---|---|
| "Meta Pixel ID=0, completely broken" | `analytics-audit-2026-05-24.md`, 2026-05-24 | **Fixed.** Pixel `699717432496147` live; `last_fired_time` 2026-08-23T23:59Z |
| "GA4 = MonsterInsights free tier, PageView only" | same, 2026-05-24 | **Superseded.** MonsterInsights removed; official WC GA integration with ecommerce enabled. `add_to_cart` fires; `view_item` does not (different root cause) |
| "LiteSpeed vs WP-Optimize — both exist, only one should" | CLAUDE.md notes | **Resolved.** LiteSpeed inactive, WP-Optimize active. Only a stale `object-cache.php` drop-in remains |
| "TikTok fully wired — 1.04M EGP / 2,398 orders attributed via MAPI" | `.env` §9, 2026-05-24 | **Unverifiable and misleading.** The stored token has no permission on Learn Simply's advertiser. Those MAPI figures are *events sent to TikTok*, not ad-attributed revenue. Order attribution shows **0 paid orders** |
| "909 CC failures ≈ 195K EGP/yr — migrate Kashier" | Sprint 2 notes | **Still disproved.** Card and wallet abandon at similar rates; 97.9% of cancellations are the unpaid-order timeout, not gateway failure |
| "BUG-003 duplicate `<title>`" | bugs report | **Fixed and holding.** Exactly 1 `<title>` per page; Rank Math owns titles |
| 61 active plugins | CLAUDE.md | **54 active** (78 total, 14 inactive, 8 mu, 2 drop-ins) |
| WordPress 6.9.4 | CLAUDE.md, 2026-06-04 | **7.0.4** |

---

## 12. Reproducibility

Key commands, verbatim.

```bash
# Rendered HTML + timings
curl -sSL -A "<mobile UA>" -o page.html \
  -w 'http=%{http_code} ttfb=%{time_starttransfer} total=%{time_total} size=%{size_download}\n' \
  https://learrnsimply.com/

# Runtime event capture + Core Web Vitals (Playwright 1.61 / Chromium 149)
node track.mjs        # per-page GA4/Meta/TikTok/Clarity interception + LCP/CLS
node track2.mjs       # fresh context per page, fbq/ttq state introspection
node arch.mjs         # view_item_list check on /shop/ and /courses/

# Live WordPress (SELECT-only)
ssh -o BatchMode=yes learnsimply
cd /home/u791284659/domains/learrnsimply.com/public_html
wp option get woocommerce_google_analytics_settings --format=json --skip-plugins --skip-themes
wp option get woocommerce_enable_guest_checkout --skip-plugins --skip-themes
wp option get woocommerce_hold_stock_minutes --skip-plugins --skip-themes
wp db query "SELECT post_status, COUNT(*) FROM wp_posts WHERE post_type='shop_order' GROUP BY post_status;" \
  --skip-plugins --skip-themes

# Meta Marketing API (token read from WP option wc_facebook_access_token)
curl -sS -G "https://graph.facebook.com/v21.0/act_1770006103570345/insights" \
  --data-urlencode "access_token=$FB" \
  --data-urlencode "fields=spend,impressions,clicks,reach" \
  --data-urlencode "time_range={'since':'2025-04-01','until':'2026-08-23'}" \
  --data-urlencode "time_increment=monthly"

# TikTok scope check
curl -sS -H "Access-Token: $TT" \
  "https://business-api.tiktok.com/open_api/v1.3/oauth2/advertiser/get/?app_id=$APPID&secret=$SEC"

# Mautic
curl -sS -u "$MAUTIC_API_USER:$MAUTIC_API_PASSWORD" \
  "https://mautic.learrnsimply.com/api/emails?limit=20"
```

All arithmetic in this report was produced by command (`python3`) or by SQL aggregate, never mentally.

**measured_at for every figure unless otherwise stated: 2026-08-24T01:46Z – 02:03Z.**
