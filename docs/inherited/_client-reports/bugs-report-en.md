# Learn Simply — Website Audit Report

> **Prepared for:** Ahmed Adel
> **Date:** May 23, 2026
> **Audit method:** 3 parallel review tracks (code, live site, data) with manual consolidation and false-positive review.

---

## Executive Summary

We performed a comprehensive bug & security audit of the `learrnsimply.com` platform across three parallel tracks:

- **Code track:** Theme files (PHP, Twig templates, JavaScript)
- **Live site track:** Page-by-page navigation, link integrity, public endpoints
- **Data track:** Products, orders, configuration, content state

**Result: 63 confirmed bugs.**

| Severity | Code | Live Site | Data | **Total** |
|---|---|---|---|---|
| 🔴 Critical | 4 | 3 | 3 | **10** |
| 🟠 High | 8 | 5 | 8 | **21** |
| 🟡 Medium | 8 | 6 | 7 | **21** |
| 🟢 Low | 6 | 4 | 1 | **11** |
| | | | | **63 confirmed** |

> **Note on false positives:** Our code agent flagged `learrnsimply.com` (double "r") as a misspelling. **This is incorrect** — your actual domain is intentionally spelled with two r's. All links using the double-r spelling are correct and should not be modified. *(There is, however, a legitimate marketing observation about the domain — see the Notes section near the end.)*

The three most urgent items are highlighted at the close of the Critical section below.

---

## 🔴 Critical Issues (10) — Address First

These items represent immediate business or security risk. Recommended resolution window: **1 week**.

### Security (3)

#### 1. XSS Vulnerability in Sales Pages
**Files:** `single-course.twig` (lines 220, 293, 344), `single-product.twig`, `single-product-bundle.twig`

Course content and embedded video URLs are rendered through Twig's `|raw` filter without sanitization. Any actor with content-editing permission can inject arbitrary JavaScript that executes in visitors' browsers — a classic stored XSS vector.

**Recommended fix:** Apply `wp_kses_post()` in PHP before passing variables to the Twig templates.

#### 2. Debug Scripts Active in Production
**File:** `functions.php` (lines 14–15)

The theme actively loads `list-lessons.php` and `create-quizzes-topic1.php` on every request. The second script responds to public query parameters:
- `?create_quizzes_topic1=1` — creates quizzes
- `?delete_...` — deletes quizzes

This is a destructive, unauthenticated endpoint exposed to the open internet.

**Recommended fix:** Remove the `require` statements and delete both files entirely.

#### 3. Admin Information Disclosure via REST API
**Endpoint:** `learrnsimply.com/wp-json/wp/v2/users`

This endpoint is publicly accessible and exposes:
- Admin username: `ahmedadel123422`
- Superadmin flag: `is_super_admin: true`

This is half the attack surface for any credential-based attempt.

**Recommended fix:** Disable the users endpoint via filter in `functions.php`.

### Broken Functionality (4)

#### 4. Dead Code Rendered as Text in Cart Page
**File:** `tutor/ecommerce/cart.php` (lines 254–255)

Stray `exit;` and `}` statements exist outside PHP tags and print as raw text on the cart page. Additionally, the entire template appears twice in the same file.

**Recommended fix:** Delete lines 257–448 (the duplicate older template).

#### 5. Blog Page Returns 404
**Affects:** Header link, footer link, "View All Articles" button

Visitors clicking "المدونة" land on a 404 page. Three different navigation surfaces point to a missing destination.

**Recommended fix:** Either create the blog index page or correct all three links to a valid destination.

#### 6. "About Me" Link Returns 404 in Footer
The footer links to `/about-me` (hyphen), but the actual page is `/about_me/` (underscore).

#### 7. Duplicate `<title>` Tag on Every Page
**File:** `header.php`

A legacy `<title>` tag is injected before `wp_head()`, while RankMath injects another. Result:
- Every page on the site has two title tags
- Browsers display the older (incorrect) version
- SEO is broken site-wide

**Recommended fix:** Remove the legacy `<title>` line from `header.php`.

### Data & Operations (3)

#### 8. 663 Stuck Orders in "Processing" Status ⚠️
- **322 orders** older than one year
- **~347 orders** carry real monetary value — meaning **customers may have paid without receiving course access**
- Oldest stuck order: March 2024

**Recommended action:** Manual reconciliation of every order with non-zero value — either complete the fulfillment or process a refund. Urgent.

#### 9. "Python Projects" Course Published Without WooCommerce Product
There is no purchasable product linked to this course. If the course is intentionally free, the buy buttons need to be hidden gracefully. Otherwise, the product needs to be created and linked.

#### 10. "Terms & Conditions" Page Not Configured in WooCommerce
- `page_set: false` in WooCommerce settings
- Two duplicate terms pages exist on the site
- This is a legal requirement for the checkout flow

---

## 🟠 High Priority Issues (21)

### Code (8)

| # | Issue | File |
|---|---|---|
| H1 | Stale temp file `archive-courses_temp.php` with deprecated code | Theme root |
| H2 | Font loaded three times (duplicate enqueue) | `functions.php` + `features-section.twig` |
| H3 | "Buy Now" button displays even when course has no linked product → broken link | `featured-courses-section.twig:222` |
| H4 | `courses_archive_url` called incorrectly (extra `site.` prefix) → "View All Courses" button is dead | `featured-courses-section.twig:231` |
| H5 | `instructor.title` field does not exist in Timber → always renders the fallback "مدرب" | `single-course.twig:311` |
| H6 | Product ID `33336` hardcoded → silently breaks if the product is ever replaced | `bundles-section.twig:129` |
| H7 | Bundle prices hardcoded instead of pulled from WooCommerce → pricing inconsistency | `bundles-section.twig:123` |
| H8 | `ajaxurl` hardcoded inline instead of using `wp_localize_script` | `single-course.twig:677` |

### Live Site (5)

| # | Issue |
|---|---|
| H9 | 10+ duplicate staging pages indexed by Google (`tutor-login-2..5`, `checkout-3`, `dashboard-2`, …) |
| H10 | "Contact Me" button routes to "About Instructor" page instead of an actual contact page |
| H11 | Signup page titled "signup" (English) on an Arabic-first site |
| H12 | Shop page `/shop-2/` titled "Shop" (English) in RankMath |
| — | REST API user leak (already listed above as Critical #3) |

### Data (8)

| # | Issue |
|---|---|
| H13 | **7 of 8 products have no full description** — sales pages are effectively empty |
| H14 | 4 of 8 products have no short description (the one shown on the shop grid) |
| H15 | 6 of 8 products are in "Uncategorized" — shop filtering is effectively non-functional |
| H16 | The only active coupon (`java200`) expired 11 days ago but is still in `publish` state |
| H17 | All 8 products have no SKU |
| H18 | WooCommerce transactional emails send from a **personal Gmail address** (`ahmedadel123422@gmail.com`) — affects deliverability and brand perception |
| H19 | 316 zero-value orders stuck in processing (likely residue from a previous 100%-off coupon) |
| H20 | Two draft courses titled "دورة جديدة" sitting untouched for 9 months |

---

## 🟡 Medium (21) + 🟢 Low (11) — Summary

**Medium (notable):**
- Countdown timer runs in the visitor's browser timezone instead of Cairo time
- Page-type detection logic duplicated entirely twice in `functions.php`
- Typo: "جنية" (incorrect) used instead of "جنيه" (Egyptian pound) on 3 sales pages
- Static text "3 days" not connected to the actual countdown component
- **Fake purchase notifications with invented names** — recommend reviewing for transparency and trust impact
- `/prompt/` page contains four nested HTML documents
- "Refund Policy" page has been in draft since 2023
- Taxes disabled in WooCommerce settings
- Shop page title field is empty
- Duplicate pages: 5 login, 4 my-account, 8 user-login/register, 3 shop
- Various typos in the footer

**Low (notable):**
- Hero statistics hardcoded ("+1000 students") rather than pulled from real data
- "Only 12 spots left" — hardcoded fake scarcity
- "40 hours" fallback duration is fabricated
- Some templates do not extend `base.twig`
- "خطاء" misspelling on the 404 page (should be "خطأ")
- Author archive pages expose admin usernames
- One order contains an invalid email address (`.con` TLD)

> Full per-bug details with file paths and line numbers are available on request.

---

## Important Clarification: False Positive

Our automated code agent flagged the domain `learrnsimply.com` (double "r") as a misspelling and suggested correcting it to `learnsimply.com` (single "r"). **This recommendation is wrong** — your actual domain is `learrnsimply.com` with the double "r" intentionally. All links using the double-r spelling are correct and should not be modified.

🟡 **However**, the agent inadvertently surfaced a legitimate concern: the domain is **easy to mistype**. Anyone typing `learnsimply.com` (single "r") will not find the site. This is a real traffic leak that may be worth addressing through:

- Acquiring `learnsimply.com` as a redirect domain
- Registering the single-r spelling as a parked redirect
- A longer-term branding decision

This is a strategic discussion, not a bug — flagging for awareness only.

---

## Recommended Fix Order

### Sprint 1 — High-impact code fixes (≈ 1 day, all in the theme code already shared)
- Remove duplicate `<title>` tag (#7)
- Fix `/blog/` 404 and "About Me" footer link (#5, #6)
- Disable REST users endpoint (#3)
- Delete debug scripts (#2)
- Remove dead code in `cart.php` (#4)
- Apply `wp_kses` sanitization to fix XSS (#1)
- Fix `courses_archive_url` and broken buy buttons (H3, H4)
- Delete `archive-courses_temp.php` (H1)

### Sprint 2 — Operations & Data
- **Reconcile the 663 stuck orders (#8 — most urgent)**
- Configure Terms & Conditions page in WooCommerce (#10)
- Clean up duplicate pages
- Complete product data (descriptions, SKUs, categories)
- Update email From-address (H18)
- Unpublish or refresh the expired coupon (H16)

### Sprint 3 — Polish
- Typo corrections
- robots.txt cleanup
- `/prompt/` page fix
- Real countdown timer with Cairo timezone
- Replace fake scarcity numbers with real data

---

## Bottom Line

**63 confirmed bugs.** **10 are Critical.** The three most urgent:

1. 🔴 **663 stuck orders** with ~347 carrying real value — customers may have paid without receiving course access. Needs reconciliation immediately.
2. 🔴 **XSS vulnerabilities** in sales pages + publicly exposed debug scripts — active security attack surface.
3. 🔴 **Duplicate `<title>` tag** silently breaking SEO across the entire site.

**The good news:** Most Sprint 1 fixes are small and live in the theme code already shared. They can be prepared quickly for your review.

> 📌 All proposed fixes will be presented for your approval before any changes are deployed to the live site.

---

**Prepared by:** Omar Abdo — GTM Engineer & AIOS Architect
**Contact:** omarabdo385@gmail.com
**Audit date:** May 23, 2026
