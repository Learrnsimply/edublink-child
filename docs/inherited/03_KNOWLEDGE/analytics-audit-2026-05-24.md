# Analytics Stack Audit — 2026-05-24

> ⚠️ **محدّث 2026-06-25 — كتير من ده اتغيّر/اتصلّح. الحالة الحالية في memory `project_focus_pivot_website_tracking_2026-06-25`:**
> - **Meta** بقت متوصّلة بالكامل (FBE2: browser pixel + CAPI) — مش "broken" زي ما تحت.
> - **GA4** بقى فيه ecommerce events (swap لـ "Google Analytics for WooCommerce" بدل MonsterInsights).
> - **Clarity** اتركّب (project `xckdxrkgej`).
> - **اللي تحت = snapshot تاريخي 2026-05-24** (محفوظ للمرجع — مش الحالة الحالية).

**Auditor:** Omar (GTM Engineer)
**Method:** Read-only (plugin list + DB options + frontend HTML + ld+json + script trace)
**Pages tested:** homepage, course page (`/courses/java-course-level1/`), cart, checkout (302 redirect)
**Risk to site:** Zero — no writes performed.

---

## 🎯 Executive verdict

| Channel | Browser Pixel | Server-side | Conversion events | Status |
|---|---|---|---|---|
| **GA4** | ✅ firing | ❌ | ❌ PageView only | 🟡 **HALF-WORKING** — basic traffic data, zero revenue attribution |
| **TikTok** | ✅ firing | ✅ MAPI active | ✅ 2,398 orders / 1.04M EGP attributed | 🟢 **FULLY WIRED** |
| **Meta (FB/IG)** | ❌ NOT firing | ❌ access_token empty | ❌ pixel_id = 0 | 🔴 **COMPLETELY BROKEN** despite "active" plugin |
| **GTM** | n/a | n/a | n/a | ⚪ **NOT INSTALLED** |

**TL;DR:** Ahmed is running Meta ads with **zero pixel data** going back — every Meta ad dollar is optimized on noise. TikTok integration is excellent (2+ years of attribution data). GA4 is recording pageviews but not purchases, so reports underestimate revenue impact.

---

## 1. Plugin layer — what's installed and active

Active tracking-related plugins (4 of 56 total):

| Plugin | Version | Function | Configured? |
|---|---|---|---|
| `google-analytics-for-wordpress` (MonsterInsights) | 10.2.0 | GA4 via gtag wrapper | ✅ Yes (free tier — no eCommerce addon) |
| `facebook-for-woocommerce` | 3.7.0 | Meta Pixel + CAPI + Catalog | ❌ Pixel = 0, token empty |
| `tiktok-for-business` | 1.4.0 | TikTok Pixel + MAPI + Catalog | ✅ Fully configured |
| `optinmonster` | 2.16.24 | Lead capture (related to attribution) | (separate audit) |

**NOT installed (gap):**
- No dedicated GTM plugin (`gtm4wp`, `google-tag-manager-for-wordpress`)
- No PixelYourSite (which was removed in Task 3.11 cleanup as orphan)
- No standalone Meta Pixel plugin
- No Google Site Kit

**`Insert Headers and Footers` / WPCode is installed** — but the 2 published snippets are:
- "Redirect After Login Based on Role"
- "Block Email Domains"

Neither contains tracking code. Conclusion: no manually-injected pixels.

---

## 2. DB options layer — credentials discovered

### GA4 (MonsterInsights gtag mode)

| Key | Value | Source |
|---|---|---|
| **Measurement ID** | `G-DT3Z0RSEBK` | `monsterinsights_settings.v4_id` |
| Tracking mode | `gtag` | settings.tracking_mode |
| Events mode | `js` (client-side only) | settings.events_mode |
| Force SSL | true | settings.* |
| Link attribution | true | settings.* |
| **License** | (none — FREE tier) | no `monsterinsights_license_*` key in DB |

⚠️ **Critical gap:** MonsterInsights free tier only fires `gtag('config', 'G-...')` = automatic PageView. The eCommerce addon (which fires `view_item`, `add_to_cart`, `begin_checkout`, `purchase`) **requires MonsterInsights Pro** (~$199/year) or a different plugin entirely.

### Meta / Facebook

| Key | Value | Source |
|---|---|---|
| **Pixel ID** | `0` (literally zero!) | `facebook_config.pixel_id` |
| **Access Token** | (empty string) | `facebook_config.access_token` |
| use_pii | true | facebook_config |
| use_s2s (server-side) | false | facebook_config |
| CAPI integration status | `1` (active) | `facebook_capi_integration_status` |
| CAPI events filter | `Microdata, SubscribedButtonClick` | `facebook_capi_integration_events_filter` |
| External business ID | `slearrnsimplycom-6804ea776f7c0` | `wc_facebook_external_business_id` |
| Feed URL secret | (active) | `wc_facebook_feed_url_secret` |

⚠️ **The "CAPI active" flag is misleading.** It means the *integration handshake* completed (catalog sync runs), but:
1. `pixel_id = 0` → no events can be sent
2. `access_token = empty` → no CAPI POST can be authenticated
3. Filter is `Microdata + SubscribedButtonClick` only → even if it worked, it would NOT send the WooCommerce `Purchase` event

### TikTok ✅ FULLY WIRED

| Key | Value | Source |
|---|---|---|
| **Pixel Code** | `D0E92UBC77U9B73T7LL0` | `tt4b_pixel_code` |
| **Access Token** | `7a0fdfa3cdb3ddaf81614d3ceb4c5b9f356de6aa` | `tt4b_access_token` |
| Secret | `06fbbd45c2295384029830aabb39686e8fcaa178` | `tt4b_secret` |
| Advertiser ID | `7502022393087590401` | `tt4b_advertiser_id` |
| App ID | `7502030692981669889` | `tt4b_app_id` |
| Business Center ID | `7502019388069969927` | `tt4b_bc_id` (matches `.env`) |
| Catalog ID | `7502009266149050113` | `tt4b_catalog_id` |
| External business ID | `tt4b_woocommerce_681c912b2f9f8` | `tt4b_external_business_id` |
| External data key | `a2450517-74c5-47da-aafa-bc62338cd58d` | `tt4b_external_data_key` |
| Advanced matching | enabled (1) | `tt4b_advanced_matching` |
| User country | EG | `tt4b_user_country` |
| Plugin version | 1.4.0 | `tt4b_version` |

**Server-side attribution snapshot:**

| Metric | Value | Source |
|---|---|---|
| MAPI total orders tracked | **2,398** | `tt4b_mapi_total_orders` |
| MAPI total GMV tracked | **1,040,047 EGP** | `tt4b_mapi_total_gmv` |
| Tenure (days) | 806 days (~2.2 years) | `tt4b_mapi_tenure` |
| Last full sync | 2026-05-15 22:27 UTC | `tt4b_last_full_sync_time` (unix 1778992057) |
| Last product sync | 2026-05-23 18:27 UTC | `tt4b_last_product_sync_time` (unix 1779596855) |

### GTM — NOT INSTALLED

- No `GTM-*` container ID found in any wp_options row
- No `gtm.js` script in any tested page
- No `gtm4wp_*` options (the standard "Google Tag Manager for WordPress" plugin)

---

## 3. Frontend HTML layer — what actually fires

### Homepage (`/`)

| Tracking signal | Found? | Detail |
|---|---|---|
| `G-DT3Z0RSEBK` (GA4) | ✅ 1 | `<script src="//www.googletagmanager.com/gtag/js?id=G-DT3Z0RSEBK">` + `__gtagTracker('config', ...)` |
| `analytics.tiktok.com` (TikTok bundle) | ✅ 1 | TikTok base SDK loaded |
| `ttq.load('D0E92UBC77U9B73T7LL0')` | ✅ 1 | TikTok pixel configured |
| `monsterinsights` (script tag) | ✅ 5 | MonsterInsights frontend module |
| `connect.facebook.net` (Meta Pixel) | ❌ 0 | NO Meta Pixel SDK loaded |
| `fbq(...)` calls | ❌ 0 | NO Pixel event calls |
| `GTM-` container | ❌ 0 | NOT installed |
| Other (Hotjar, Clarity, Mixpanel, Segment) | ❌ 0 | None |

### Course page (`/courses/java-course-level1/`)

| Tracking signal | Found? | Detail |
|---|---|---|
| `G-DT3Z0RSEBK` | ✅ 4 hits | same as homepage |
| `ttq.load + ttq.instance` | ✅ both | TikTok configured but no `ttq.track('ViewContent')` in inline HTML — fires from JS execution |
| `connect.facebook.net / fbq` | ❌ 0 | No Meta pixel |
| GA4 `view_item` event | ❌ 0 | NOT firing (would need MonsterInsights Pro eCommerce addon) |
| `dataLayer.push` ecommerce shape | ❌ 0 | only bootstrap stub `dataLayer.push(arguments)` |

### Cart page (`/cart-1/`)

Same as course page — no `add_to_cart` GA4 event, no `AddToCart` Meta event.

### Checkout page (`/checkout/`)

HTTP 302 redirect when session empty (normal WC behavior). Could not test without authenticated session.

---

## 4. Conversion layer — purchase event flow

### What SHOULD happen on the `thankyou` page (post-purchase):

| Channel | Required event | Currently happens? |
|---|---|---|
| GA4 | `gtag('event', 'purchase', { transaction_id, value, currency, items })` | ❌ NO (free MonsterInsights doesn't fire it) |
| Meta Pixel | `fbq('track', 'Purchase', { value, currency })` | ❌ NO (pixel_id = 0) |
| Meta CAPI | POST to `graph.facebook.com/v17.0/{pixel_id}/events` with `Purchase` event | ❌ NO (access_token empty) |
| TikTok Pixel | `ttq.track('CompletePayment', { value, currency, contents })` | ⚠️ Browser pixel uncertain — but **MAPI server-to-server confirmed firing** (2,398 orders tracked) |
| TikTok MAPI | POST to TikTok Events API server-side | ✅ YES — 1.04M EGP attributed |

**One-line summary:** TikTok is the only channel reliably attributing revenue. GA4 sees traffic but not money. Meta sees nothing.

---

## 5. Revenue impact — what we're losing

| Loss | Estimate | Reasoning |
|---|---|---|
| **Meta ad optimization wasted** | Whatever Ahmed is spending on Meta ads / month | Optimizing on landing-page visits only, no conversions = algo can't find converters |
| **GA4 revenue blind-spot** | Reports underestimate actual revenue ≈ 100% (zero purchases recorded) | Free MonsterInsights only fires `page_view` |
| **No cross-channel attribution** | Cannot tell if Telegram → site → buyer flow works | No UTM consolidation in GA4 reports without conversions |
| **Meta Advantage+ campaigns blocked** | Cannot launch them at all | Meta requires Pixel + Purchase events for Advantage+ |

**Conservative estimate:** if Ahmed spends 5,000 EGP/month on Meta with broken pixel, that's likely **50-70% wasted** vs. having a working pixel — easily 2,500-3,500 EGP/month recoverable.

---

## 6. Recommended remediation (priority order)

### 🔴 P0 — Install Meta Pixel + CAPI properly (this week)

**Why first:** Ahmed is actively running Meta ads (Meta Business Manager confirmed in `.env`). Every day = wasted ad spend.

**How:**
1. Get Pixel ID from Meta Business Manager (Events Manager → Data sources) → fill `META_PIXEL_ID` in `.env`
2. Generate CAPI access token (Events Manager → Settings → Conversions API) → fill `META_CAPI_ACCESS_TOKEN`
3. In `facebook-for-woocommerce` plugin settings → Pixel section: paste both
4. Verify: load any page in incognito → Network tab → confirm `facebook.com/tr` POST fires with `PageView`
5. Make test purchase → confirm `Purchase` event appears in Events Manager within 1 min

**Risk:** Low. Plugin is already installed, just needs IDs.
**Time:** 15-20 min once Omar has Meta Business access (currently pending invite acceptance).

### 🟡 P1 — Install GA4 Enhanced Ecommerce (this/next week)

**Why:** Without this, ALL GA4 reports show 0 revenue. Conversion rate, LTV, channel ROAS — all broken.

**Three options ranked best→worst for cost/effort:**

| Option | Cost | Effort | Pros | Cons |
|---|---|---|---|---|
| **A. WooCommerce Google Analytics Integration** (official, free) | $0 | 5 min | Free, fires all eCommerce events natively | Less polished UI than MonsterInsights |
| **B. MonsterInsights Pro eCommerce addon** | $199/yr | 5 min | Keeps existing setup intact | Recurring cost |
| **C. GTM container + custom GA4 tags** | $0 | 2-3 hrs | Most flexible, also tracks Meta/TikTok from one place | Time to configure |

**Recommend Option A first** (free, native WC, swappable later).

### 🟢 P2 — Verify TikTok Pixel browser events (this week)

The MAPI side is bullet-proof (2,398 orders). But TikTok's browser pixel may be missing `ttq.track('ViewContent')` on product pages — meaning re-targeting audiences are smaller than they could be. Verify via Playwright network tab on a course page.

### ⚪ P3 — Decide GTM later (3-6 month horizon)

Don't rush GTM. The current per-plugin setup will work fine for 1-2 years at this revenue level. Switch to GTM only when:
- More than 5 tracking channels active (would consolidate)
- A/B testing tools (Google Optimize successor, Convert) need tags
- Server-side GTM becomes financially worth it (~$100/mo for sGTM Cloud Run)

---

## 7. What's now in `.env`

Updated after this audit (commit pending):

```env
# GA4
GA4_MEASUREMENT_ID=G-DT3Z0RSEBK         # discovered from MonsterInsights settings
GA4_PROPERTY_ID=                         # pending — need GA admin email login
GA4_API_SECRET=                          # pending — for MeasurementProtocol (server-side events)

# TikTok (NEW — fully filled)
TIKTOK_PIXEL_ID=D0E92UBC77U9B73T7LL0
TIKTOK_ACCESS_TOKEN=[REDACTED]
TIKTOK_SECRET=[REDACTED]
TIKTOK_ADVERTISER_ID=7502022393087590401
TIKTOK_APP_ID=7502030692981669889
TIKTOK_CATALOG_ID=7502009266149050113

# Meta — pending invitation acceptance, then need to fill from Events Manager
META_PIXEL_ID=
META_CAPI_ACCESS_TOKEN=

# GTM — NOT INSTALLED (intentional, deferred to P3)
GTM_CONTAINER_ID=
GTM_ACCOUNT_ID=
```

---

## 8. Next concrete step

**For Omar to do:**
1. Accept Meta Business Manager invitation (Gmail check) — needed for any P0 work
2. Decide on P1 option (A/B/C above)

**For Claude to do once Omar approves:**
1. P0: Install Meta Pixel + CAPI (after Meta acceptance)
2. P1: Install WooCommerce Google Analytics Integration plugin + map purchase events

**Verification checklist post-fix:**
- [ ] Meta Events Manager shows live test events
- [ ] GA4 Realtime → ecommerce_purchase event appears after test order
- [ ] TikTok Events Manager browser pixel matching score > 7/10
- [ ] Lighthouse Performance score unchanged (no slowdown from new scripts)
