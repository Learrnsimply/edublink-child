# Learn Simply — Audience, Market and Competitor Research

**Observation date: 2026-08-24.** All figures below were measured on this date unless explicitly marked *historical*.
**Method:** external fetch of primary sources only (channel pages, the live site's public WordPress/WooCommerce REST endpoints, Google Trends API, competitor sites). No account access, no changes made anywhere.

**How to read this file**
- Every number carries its source URL and observation date.
- Figures taken from the repo's May–June 2026 knowledge base are labelled **[historical, DATE]** and are never presented as current.
- Anything that could not be confirmed from a primary source is labelled **UNVERIFIED** rather than estimated.

---

## 0. Headline: five things that changed since the repo's last snapshot

| # | Finding | Evidence |
|---|---|---|
| 1 | **The YouTube channel has pivoted to AI-tooling content.** 14 of the last 15 uploads are about Claude / Kimi / MiniMax / Skywork, not programming education. | YouTube RSS feed, §2.1 |
| 2 | **Prices rose 11–27% across the catalogue, and two new SKUs appeared** — a Dart course (600 EGP) and an all-courses bundle (2,500 EGP). | WooCommerce Store API, §3.1 |
| 3 | **Telegram has gone quiet** — no post for 31 days, and views per post fell 66% while subscribers rose. | t.me public feed, §1.2 |
| 4 | **Instagram nearly doubled** to 23.7K and TikTok now exists at 13.2K — both were written off in the repo audit. | Instagram web profile API / TikTok SSR data, §1.3 |
| 5 | **Search demand in Egypt is moving to AI, not to programming.** "chatgpt" now indexes ~32× "java" and ~65× "flutter"; "تعلم البرمجة" is flat-to-declining over five years. | Google Trends API, §7 |

The single strategic tension this research exposes: **the audience Ahmed is acquiring today is an AI-tools audience, and the catalogue he sells is a Java/Data-Structures catalogue.**

---

## 1. Audience size per channel, measured today

### 1.1 Summary table

| Channel | Metric | Value (2026-08-24) | Repo figure **[historical]** | Change |
|---|---|---|---|---|
| **YouTube** [@Learn_Simply](https://www.youtube.com/@Learn_Simply/about) | subscribers | **403,000** *(YouTube rounds public subscriber counts to 3 significant figures)* | 369,000 [2026-05] | +34K (+9.2%) |
| | lifetime views | **20,454,898** | 18,460,000 [2026-05] | +2.0M |
| | videos | **364** | 338 [2026-05] | +26 |
| | channel created | Aug 16, 2018 | — | — |
| | channel country | Egypt | — | — |
| **Telegram** [Et3lambBsata](https://t.me/Et3lambBsata) | subscribers | **25,096** | 24,400 [2026-05] | +696 (+2.9%) |
| | last post | **2026-07-24 (31 days silent)** | "منتظم" [2026-05] | 🔴 gone quiet |
| | views on latest post | **4,140 (16.5% of subs)** | 15,000–21,000 / 60–86% [2026-05] | 🔴 −66% per post |
| **Instagram** [@ahmed.aaddel](https://www.instagram.com/ahmed.aaddel/) | followers | **23,725** | 13,000 [2026-05] | +10,725 (+82%) |
| | posts | **116** | 79 [2026-05] | +37 |
| **Instagram** @simply_learn.1 | — | **account gone (HTTP 404)** | 107 followers [2026-05] | closed/renamed |
| **TikTok** [@ahmed.aaddel](https://www.tiktok.com/@ahmed.aaddel) | followers | **13,200** | "غائب تماماً" [2026-05] | 🟢 now exists |
| | total likes / videos | **55,300 / 75** | — | — |
| **Facebook** (AhmedAdel.Learn + others) | followers | **UNVERIFIED** — login wall | ~15K + 530 + a third page [2026-05] | fragmentation persists |
| **LinkedIn** | followers | **UNVERIFIED** this session | 2,254 [2026-05] | — |
| **Linktree** [ahmedadeel](https://linktr.ee/ahmedadeel) | live, functioning | 3 course links + 1 free tool + 5 social links | — | — |

**Follower counts summed across the four verified channels: 403,000 + 25,096 + 23,725 + 13,200 = 465,021.** Treat this as a *sum of follower counts, not a count of people* — overlap between the four is certainly large and cannot be measured from outside. Facebook and LinkedIn would add to it but could not be measured at all.

### 1.2 Telegram — the channel the repo called the "golden asset" has stalled

Source: [t.me/s/Et3lambBsata](https://t.me/s/Et3lambBsata), fetched 2026-08-24. The public feed exposes exact post dates and view counts.

| Date | Views | Content |
|---|---|---|
| 2026-04-07 | 12,200 | Launch of the "يقين" Android app |
| 2026-05-05 | 10,800 | Platform relaunch announcement → /courses/ |
| 2026-06-04 | 9,240 | Dart waitlist → /dart |
| 2026-07-14 | 5,800 | YouTube link |
| 2026-07-17 | 6,010 | YouTube link (Dart course) |
| **2026-07-24** | **4,140** | YouTube link — **last post on the channel** |

Verified as the final post: the last message id is 366; `?after=366` returns nothing further, and messages 367/368 resolve to the generic channel page rather than a post.

Two things matter here:
1. **Reach per post fell from 12.2K to 4.14K in 3.5 months (−66%) while the subscriber count went up.** Real engagement is now **16.5%**, not the 60–86% recorded in the repo audit **[historical, 2026-05]**. That claim should not be repeated in any deck.
2. **Content is now almost pure YouTube reposting.** Of the last 20 posts, 15 are a bare YouTube link with no copy. Only two posts in four months pointed at a commercial destination (the /courses/ relaunch on 2026-05-05 and the Dart waitlist on 2026-06-04). Telegram is not currently being used as a selling channel at all.

### 1.3 Instagram and TikTok — quietly became real channels

- Instagram [@ahmed.aaddel](https://www.instagram.com/ahmed.aaddel/): **23,725 followers, 167 following, 116 posts**, category "Education", bio link → `https://learrnsimply.com/`. Source: Instagram public web profile endpoint (`/api/v1/users/web_profile_info/?username=ahmed.aaddel`), 2026-08-24. Per-post dates were not returned, so **posting cadence is UNVERIFIED**.
- The old "official" brand account **@simply_learn.1 returns HTTP 404** — the split-account problem the repo flagged has resolved itself by consolidation onto the personal handle.
- TikTok [@ahmed.aaddel](https://www.tiktok.com/@ahmed.aaddel): **13,200 followers, 55,300 total likes, 75 videos**, bio "بساعدك تحب البرمجة وتتعلمها ببساطة", bio link `Learrnsimply.com`. Source: the profile page's `__UNIVERSAL_DATA_FOR_REHYDRATION__` payload, 2026-08-24. The video list is loaded by a signed API call and was not in the server-rendered payload, so **TikTok posting cadence is UNVERIFIED**.

The repo's channel audit **[historical, 2026-05]** recommended "launch an official TikTok" and "revive Instagram" as top-priority actions. Both have already happened. That section of the audit is now obsolete.

### 1.4 Facebook — still fragmented, still unmeasurable from outside

The YouTube channel's own link list (fetched 2026-08-24) advertises **two different Facebook destinations**: `facebook.com/AhmedAdel.Learn` and `facebook.com/اتعلم-ببساطه-104597857798737`. Search results additionally surface `facebook.com/2t3lm`, `facebook.com/et3lmbbsata` and `facebook.com/ahmedadelofficialpage` as live entities.

Follower counts are **UNVERIFIED**: Facebook returned a login wall or HTTP 400 to every unauthenticated fetch attempted (direct curl, WebFetch, and the firecrawl scraper, which refuses the domain outright). Getting these numbers requires the pending Meta Business access — it is not obtainable by research.

### 1.5 Which channels are quiet

| Channel | Verdict | Basis |
|---|---|---|
| YouTube | 🟢 **Very active** — 15 uploads in 42 days, one every 3.0 days | RSS feed, §2.1 |
| Instagram | 🟡 116 posts total, cadence UNVERIFIED | profile API |
| TikTok | 🟡 75 videos total, cadence UNVERIFIED | SSR payload |
| **Telegram** | 🔴 **Quiet — 31 days, no post** | public feed |
| **Blog / SEO** | 🔴 **Dead — exactly 1 published post, dated 2025-12-19** | `wp-json/wp/v2/posts`, `x-wp-total: 1` |
| Facebook | ⚪ UNVERIFIED | login wall |

The blog figure is worth stressing: the WordPress REST API returns `x-wp-total: 1` for published posts. There is **one** article on the entire site, eight months old. Organic search acquisition is effectively zero and there is nothing to improve — there is nothing there.

---

## 2. YouTube as an acquisition engine

### 2.1 Upload frequency and content mix — the channel changed subject

Source: [YouTube channel RSS feed](https://www.youtube.com/feeds/videos.xml?channel_id=UC5COvx1Z8fnfvVkafqL_UZg), which returns exact publish dates for the 15 most recent uploads. Fetched 2026-08-24.

| Published | Days ago | Title | Topic |
|---|---|---|---|
| 2026-08-19 | 5 | هل فعلا يستحق؟ (MiniMax) | AI tool |
| 2026-08-17 | 7 | 10,000 مهارة في كلود مجانا | AI tool |
| 2026-08-16 | 8 | ازاي تعمل فيديو زي دة | AI tool |
| 2026-08-12 | 12 | اقوي امر موجود في Claude | AI tool |
| 2026-08-07 | 17 | Claude Cowork في 25 دقيقة | AI tool |
| 2026-08-06 | 18 | كلود بقى بيشتغل بدالي | AI tool |
| 2026-08-03 | 21 | خلي كلود يعمل انترفيو لفكرتك | AI tool |
| 2026-07-31 | 24 | الموضوع اتغير \| Kimi k3 | AI tool |
| 2026-07-29 | 26 | الـمهارة اللي بتخلي Claude يكتب بأسلوبك | AI tool |
| 2026-07-28 | 27 | 4000 مهارة في موقع واحد \| Skywork | AI tool |
| 2026-07-24 | 31 | بنيت تطبيق كامل من غير Claude ولا سطر كود | AI tool |
| **2026-07-17** | **38** | **اول خطوة ليك لصناعة تطبيقات الموبايل \| Dart Course** | **course promo** |
| 2026-07-14 | 41 | اوامر الـ Ai انتهت | AI tool |
| 2026-07-11 | 44 | اضافة هتحسنلك اوامر الـ Ai | AI tool |
| 2026-07-08 | 47 | جرب الاوامر دي في كلود | AI tool |

**Cadence: 15 uploads across 42 days — one every 3.0 days (≈2.3/week).** The channel is not quiet; it is publishing harder than at any point the repo recorded.

**Content mix: 14 of 15 (93%) are AI-tooling videos. Exactly one promotes a course in the catalogue.**

Looking back a full 90 days (from the channel's video tab, relative dates, 2026-08-24): roughly 19 uploads, of which the only non-AI items are the Dart promo (2026-07-17) and "حل مسائل في ساعتين | Python" (~4 months ago, 17K views).

### 2.2 View range on recent videos

From the channel's video listing (relative dates as displayed 2026-08-24):

| Window | View range | Notable |
|---|---|---|
| Last 30 days | 3.0K – 45K | Kimi k3 = 45K |
| 1–3 months | 4.6K – 64K | "انسى Claude" = 64K; "اوامر الـ Ai انتهت" = 59K |
| 3–4 months | 6.9K – **345K** | **"كورس من الصفر \| Claude" (2026-04-03) = 345,571 views, 13,393 likes** |
| Python challenge series (5–6 months) | **1.7K – 5.6K** | eight videos, all under 6K |

This is the sharpest signal in the whole dataset. **Ahmed's AI content out-performs his programming-education content by roughly 50–200×** on the same channel, to the same subscriber base, in the same period. The 8-part Python challenge series averaged ~2.6K views per video; a single Claude crash-course hit 345K.

### 2.3 Do the videos link to the site? Yes — the CTA is not the problem

Verified by scraping full video descriptions (2026-08-24):

**"كورس من الصفر | Claude"** (345,571 views, published 2026-04-03) — description contains:
- `https://learrnsimply.com/courses/java-course-level1/`
- `https://learrnsimply.com/courses/javaoop/`
- `https://learrnsimply.com/#courses` with the line *"انضم لاكثر من 3000 طالب علي منصة اتعلم ببساطة"*

**"هل فعلا يستحق؟"** (most recent upload, 2026-08-19) — description contains four learrnsimply.com links (Dart, Java basics, Java OOP, #courses), plus the Yaqeen app, plus **two sponsor/affiliate links**: MiniMax (`platform.minimax.io/subscribe/coding-plan?code=7q03lolEhG`, 12% discount) and Hostinger (`hostinger.com/LEARNSIMPLY`, coupon `LEARNSIMPLY`).

**"اول خطوة ليك لصناعة تطبيقات الموبايل | Dart Course"** (4,651 views, 2026-07-17) — a dedicated promo with a single offer link (`/courses/dart/`, "خصم 50% لفترة محدودة") and a WhatsApp support link to `201030127228`.

Channel-level link (About tab): `learrnsimply.com/#courses`.

### 2.4 Testing the repo's thesis

> **Repo thesis [historical, 2026-05]:** YouTube is the brand's largest under-monetised asset; the fix is stronger CTAs on the mega-videos.

**The thesis is half right, and its prescribed fix is wrong.**

What the evidence supports:
- The asset is real and growing: 403K subs, +34K in ~3 months, 20.45M lifetime views.
- A funnel path exists end to end: video description → course page → Kashier checkout.

What the evidence contradicts:
- **"Missing CTA" is not the bottleneck.** Every description checked carries multiple course links. Adding more links to descriptions that already have four will not move revenue.
- **The real gap is intent mismatch.** 93% of recent uploads teach AI tooling. 100% of the paid catalogue teaches Java, Data Structures, and Dart. A viewer who arrived for "how do I use Claude" is offered "Java from scratch, 550 EGP". That is a cold-traffic offer, not a warm one — which is why 345K views on a Claude video produced no visible step change in course enrolments (§2.5).
- **There is no capture layer anywhere in the chain.** The free toolbox page ([/toolbox/](https://learrnsimply.com/toolbox/), created 2026-06-16) gives away its one asset — an AI website prompt — with **no email gate**. The blog has one post. Telegram is silent. So a viewer who is interested but not ready to buy today is lost permanently.
- **Ahmed has already found a second monetisation route for the AI audience: sponsorships.** MiniMax and Hostinger affiliate placements appear in current descriptions. This is revenue the repo does not model at all, and it is the honest explanation for why the content pivot happened.

### 2.5 Does traffic plausibly convert? A triangulation

Enrolment counts read from the live course pages, 2026-08-24, against the repo's knowledge-base snapshot of 2026-06-03 **[historical]** — a window of 82 days:

| Course | Students 2026-06-03 [hist.] | Students 2026-08-24 | Δ |
|---|---|---|---|
| هياكل البيانات م١ | 1,567 | **1,634** | +67 |
| كورس جافا للمبتدئين | 546 | **604** | +58 |
| البرمجة الكائنية (OOP) | 475 | **533** | +58 |
| هياكل البيانات م٢ | 137 | **189** | +52 |
| **أساسيات Dart** (new) | — | **103** | +103 |
| **Paid subtotal** | 2,725 | **3,063** | **+338** |
| مشاريع بايثون (free) | 2,103 | **2,936** | **+833** |

Two conclusions fall out:

1. **Paid enrolment is running at ~124/month** (338 ÷ 82 days × 30). At the 556 EGP AOV supplied for 2026 YTD, that is **≈69,000 EGP/month** — which independently reproduces the repo's ~67K/month revenue figure from a completely different data source. The revenue picture in the repo is sound.
2. **The free course out-recruits the entire paid catalogue by 2.5:1** (833 vs 338). Free-to-paid conversion is where the leak is, and it is a *content and offer* problem, not a link-placement problem.

**Caveat on the enrolment numbers:** these are Tutor LMS enrolment counters, which count bundle purchases once per included course and include coupon/free enrolments. They are a good directional proxy for order volume, not a substitute for the WooCommerce order table.

---

## 3. The catalogue and pricing, as the market sees it today

### 3.1 Live catalogue and prices

Source: the site's public WooCommerce Store API, `https://learrnsimply.com/wp-json/wc/store/v1/products?per_page=100`, fetched 2026-08-24. Cross-checked against the rendered homepage and each course page, which agree exactly.

| Product | Live price (EGP) | Anchor "regular" | Discount shown | KB price **[hist. 2026-06-03]** | Drift |
|---|---|---|---|---|---|
| **جميع الدورات (all courses)** — id 40754 | **2,500** | 5,300 | 53% | **did not exist** | 🆕 **NEW** |
| **أساسيات Dart** — id 39670 | **600** | 1,200 | 50% | **did not exist** | 🆕 **NEW** |
| هياكل البيانات الكاملة (L1+L2 bundle) — id 39043 | **999** | 2,200 | 55% | 900 | **+99 (+11%)** |
| Java Basics + OOP bundle — id 33336 | **999** | 2,150 | 54% | 849 | **+150 (+18%)** |
| البرمجة الكائنية (OOP) Java — id 31580 | **700** | 1,200 | 42% | 550 | **+150 (+27%)** |
| هياكل البيانات م١ — id 11694 | **650** | 1,000 | 35% | 550 | **+100 (+18%)** |
| كورس جافا للمبتدئين + كتاب — id 27272 | **550** | 700 | 21% | 450 | **+100 (+22%)** |
| هياكل البيانات م٢ — id 30895 | **499** | 1,200 | 58% | 499 | unchanged |
| كتاب لغة C++ — id 28009 | **200** | 250 | 20% | 200 | unchanged |
| كتاب لغة جافا — id 28056 | **199** | 250 | 20% | 199 | unchanged |
| مشاريع بايثون للمبتدئين | **free** | — | — | free | unchanged |

**Courses published: 6** (was 5). Source: `wp-json/wp/v2/courses`. The Dart course (id 39654) was published **2026-08-13**.

### 3.2 Drift the repo's knowledge base does not know about

This matters operationally: the WhatsApp agent "عمر" answers price questions from the repo knowledge base, and **that knowledge base is now wrong on six of eleven prices**. A customer asking "بكام كورس الجافا؟" would be told 450 when the site charges 550.

1. **Every non-book paid course except DS L2 went up 11–27%.** The brand has repriced upward across the board since June.
2. **Two new SKUs exist**, neither in the KB: the Dart course and a 2,500 EGP all-courses bundle.
3. **The Dart course price changed after launch.** Repo memory records the June Dart launch as 16 sales / 5,600 EGP — i.e. 350 EGP each **[historical, 2026-06]**. It now lists at 600. The course itself was only published to Tutor LMS on 2026-08-13, and already shows **103 students and 12 ratings (4.8)**.
4. **Ratings and enrolments have all moved** — see §2.5.
5. **A 7-day refund guarantee is now stated publicly**, on the homepage ("ضمان استرداد خلال 7 أيام") and in the terms page: *"يحق لك طلب استرداد المبلغ خلال 7 أيام من تاريخ الشراء"*. The repo audit **[historical, 2026-05]** listed "no refund guarantee at the buy button" as a CRO defect. Fixed.

### 3.3 Problems visible in the live catalogue

**a) The highest-value SKU is hard to reach.** The 2,500 EGP all-courses bundle is **absent from the homepage** (zero occurrences of `all_in_one` or a bundle link in the rendered HTML). It exists on `/courses/` (as a banner) and on `/shop-2/`, but the navigation's only route into the shop is `كتب برمجية → /shop-2/?product_cat=book`, which is **pre-filtered to books** — that filtered view contains just 2 products and no bundle. So the single SKU worth 4.5× the current AOV is reachable only by landing on `/courses/` or by knowing the direct URL. (The legacy `/shop/` page also does not carry it.)

**a2) The blog navigation link is broken.** The header menu item "المدونة" points to `https://learrnsimply.com/blog/`, which returns **HTTP 404**. Verified 2026-08-24.

**b) There are two different all-courses surfaces with three different anchor prices.**

| Surface | Price | Anchor | Claim |
|---|---|---|---|
| `/product/all_in_one/` | 2,500 | 5,300 | "خصم 53% لمدة 3 أيام فقط" |
| `/courses/` banner | 2,500 | 4,800 | "وفّر 2,300 جنيه · خصم 48%" · "422 درس" |
| `/course-bundle/allcourse/` | 2,500 | 5,300 | "وفّر 2,800 ج.م · 53%" · "448 درس" |

Same offer, three anchors, two different lesson counts.

**c) The "53% off" anchor is not a price anyone is charged.** The 5,300 figure is the sum of the struck-through list prices. The sum of the prices actually charged today is 550 + 700 + 650 + 499 + 600 = **2,999 EGP**. So the bundle's real saving is **≈17%, not 53%.** For a brand whose entire positioning is trust and plain honesty, that is the riskiest line on the site.

**d) The perpetual-urgency pattern flagged in the repo audit is still live — and the site's own source code documents it.** `/product/all_in_one/` shows "خصم 53% لمدة 3 أيام فقط — العرض سينتهي قريبًا!" on a product whose last-modified date is 2026-08-05, nineteen days earlier.

More significant: the **sitewide promo bar** ("عرض محدود خصم 50% على كورس دارت ... ينتهي خلال") ships with an empty `data-deadline` attribute, and the theme's own inline JavaScript on the homepage handles that case as follows — quoted verbatim from the rendered page source, 2026-08-24:

```js
// Deadline: use data-deadline if set, otherwise a rolling 3-day window
// persisted per-visitor in localStorage so it feels like a real offer.
...
deadline = Date.now() + 3 * 24 * 60 * 60 * 1000; // 3 days
localStorage.setItem('dartOfferDeadline', deadline);
```

Every visitor gets their own private three-day countdown, starting the moment they first arrive. It is not a deadline. For a brand whose entire positioning is *"البرمجة مش صعبة، بس محتاجة حد يعرف يشرحها"* — trust, plainness, no tricks — a comment in the production source reading *"so it feels like a real offer"* is the single largest brand risk found in this research. It is also a small fix: set a real `data-deadline`, or remove the timer.

**e) Level 2 is cheaper than Level 1.** DS L2 (499) undercuts DS L1 (650) despite being the advanced course with less content. Nothing in the page copy explains this.

**f) Public claims are inconsistent with each other and understate reality.** The homepage says "+10,000 طالب من 15 دولة" and "17M+ مشاهدة", while the bundle card on the same page says "+1000 طالب مشترك", and the real YouTube view count is 20.45M. The brand is under-claiming its strongest verifiable number.

### 3.4 Incidental technical observations (not the focus, but verified in passing)

- **Tracking is live and complete on the homepage:** GA4 `G-DT3Z0RSEBK`, Meta Pixel (`fbq` present and initialising), TikTok Pixel `D0E92UBC77U9B73T7LL0`, and **Microsoft Clarity** (`clarity.ms`). The repo's "Meta Pixel broken" finding **[historical, 2026-05]** no longer holds at the homepage level.
- Active commerce plugins include `woo-commerce-kashier-plugin-main`, `facebook-for-woocommerce`, `tiktok-for-business`, `easy-product-bundles-for-woocommerce`, `seo-by-rank-math-pro`.
- Footer advertises exactly three payment methods: **Vodafone Cash, Mastercard, Visa** (theme image assets `vodafone.png`, `mastercard.png`, `visa.png`). No InstaPay, no Fawry, no instalment logo anywhere on the public site.
- **The checkout page is login-gated**, so the actual list of enabled gateways is **UNVERIFIED** from outside.
- **A live PayPal.Me handle exists:** `paypal.com/paypalme/learnsimply` returns HTTP 200 with display name "LearnSimply", and it is linked from the YouTube About tab. This sits awkwardly against the repo's note **[historical, 2026-06]** that Egyptian PayPal is send-only. Whether funds actually settle is **UNVERIFIED** — it needs account access, not research.
- The site footer lists a **Dubai address** ("الامارات العربية المتحدة / امارة دبي / للزهرا 1"). Possibly a theme placeholder, possibly a real entity; **UNVERIFIED**, but it has direct bearing on which payment rails and which market the business can legitimately address.

---

## 4. Competitor scan — Arabic programming education

**FX note.** Two live sources on 2026-08-24: **1 USD = 50.8639 EGP** ([exchangerate-api, 2026-08-24 00:02 UTC](https://open.er-api.com/v6/latest/USD)) and **1 USD = 50.8944 EGP** ([exchangerates.org.uk USD/EGP 2026 history](https://www.exchangerates.org.uk/USD-EGP-spot-exchange-rates-history-2026.html)). All EGP conversions below use **50.89** and are *computed*, not listed prices.

**Subscriber-count method.** Channel-page scrapes returned contradictory numbers for some competitors and were discarded. The counts below were read from the YouTube embed player page, which renders the live count as text. Where a competitor's own site states a different figure, both are shown.

### 4.1 The realistic competitive set

An Egyptian or Gulf beginner searching in Arabic runs into three distinct groups, and the free group is a genuine substitute rather than background noise.

#### Elzero Web School — أكاديمية الزيرو (أسامة الزيرو)
- **Positioning:** «أكاديمية الزيرو هي منصة تعليمية تقوم على تقديم العديد من مسارات تطوير الويب وتعلم البرمجة بصفة عامة بشرح تفصيلي تفاعلي باللغة العربية، وخطط دراسة مع اختبارات وتحديات برمجية» — [elzero.org](https://elzero.org/)
- **Format:** free self-paced curriculum (HTML/CSS/JS/SASS/TypeScript/C++/PHP/Python + Front-End and PHP Back-End tracks) with a small paid tier on [elzero.courses](https://elzero.courses/).
- **Price:** free core; paid $40–$95 → **2,036–4,835 EGP**. Lifetime membership $60 → 3,054 EGP.
- **Payments:** UNVERIFIED (Teachable/Hotmart, USD-denominated).
- **Refund:** **UNVERIFIED** — `elzero.courses/p/terms` redirects to the homepage; no refund terms published.
- **YouTube:** **1.93M subscribers** (channel `UCSNkfKl4cU-55Nm-ovsvOHQ`).
- **Their edge:** a 1.93M-subscriber **free curriculum with sequenced study plans, quizzes and coding challenges** covering web development end to end. Learn Simply's free tier is a channel; Elzero's is a syllabus with assessment — and it is the default answer when an Arabic beginner asks "where do I start".

#### Codezilla (إسلام هشام محفوظ)
- **Positioning:** «اكتسب قوة المستقبل وحول افكارك إلى مشاريع حقيقية» — [codezilla.courses](https://www.codezilla.courses/)
- **Format:** one self-paced flagship, "Fundamentals of Programming & Problem Solving", **65+ hours**, Scratch → Python → OOP → web → DSA → ML → cybersecurity, lifetime access, private community with instructor participation.
- **Price:** **$220 → ≈11,197 EGP**, read from the live checkout ("Due now USD $220.00"). States the price will rise as remaining sections ship.
- **Payments:** Card (Stripe) and PayPal, **plus a dedicated Vodafone Cash route** — a separate «ادفع بفودافون كاش» button leading to `/introduction-to-programming-wallet`.
- **Refund:** **none published.** `codezilla.courses/terms` serves Podia's generic platform ToS; no money-back guarantee on the sales page or FAQ.
- **YouTube:** **1.1M subscribers** (channel `UCveX_0uBOHVHbpV838OGXVA`). *Sources disagree:* the instructor's own site metadata still says "more than 631,000 subscribers" — a stale self-reported figure.
- **Their edge:** **institutional curriculum pedigree.** His bio claims he led Udacity's Web & Programming Nanodegrees (100,000+ learners), directed Arabic content at freeCodeCamp, built the web track for مبادرة مصر الرقمية with Egypt's MCIT, and designed the from-scratch track for مبادرة مليون مبرمج عربي. That is what lets him charge **4.5× Learn Simply's entire all-courses bundle for one course.**

#### أكاديمية حسوب — Hsoub Academy
- **Positioning:** «تعلم البرمجة من الصفر حتى الاحتراف — محتوى عربي شامل ومتنوع» — [academy.hsoub.com](https://academy.hsoub.com/)
- **Format:** 9 self-paced courses (Front-End, JavaScript, PHP, Python, Ruby, CS, Game Dev, AI, Product Management), **650+ hours across 80 tracks**, trainer Q&A, graded exam plus capstone required for certification, post-graduation employment support.
- **Price:** **$390 per course, uniform → ≈19,849 EGP.** One-time, no subscription.
- **Payments:** Stripe present; gift cards sold. Full rails UNVERIFIED.
- **Guarantee (verbatim, [FAQ](https://academy.hsoub.com/pages/faq)):** «ضمان استرداد استثمارك خلال 6 أشهر — … إن لم تحصل على وظيفة أو عمل حر خلال 6 أشهر من موعد اجتيازك للامتحان … سنعيد لك ما دفعت.» Conditional on completing the course, passing the exam, and applying the CV coaching. Not a no-questions refund.
- **YouTube:** **164K subscribers** (channel `UCJv37tcBvJlBF2MoVMRMvbQ`).
- **Their edge:** a **6-month job-or-money-back guarantee behind an exam-gated certificate** — described on their own site as «ليست شهادة حضور بل شهادة مبنية على إنجاز حقيقي», issued only after a proctored exam plus interview, with a public verification URL and a hiring team reviewing CVs. Learn Simply issues completion certificates; Hsoub sells a job outcome and puts its own money behind it.

#### منصة المدرسة — Almdrasa
- **Positioning:** «تعلم البرمجة بالعربية — لن تحتاج أن تتقن الإنجليزية كي تحترف البرمجة» — [almdrasa.com](https://almdrasa.com/)
- **Format:** self-paced **tracks** (8–12 weeks at 5–10 hrs/week) with graded projects reviewed by humans, **weekly live Zoom sessions with the engineers**, per-track study groups, mentor chat. 7 tracks live including **Data Structures** and **Flutter/Dart** — the two that overlap Learn Simply directly.
- **Price (Data Structures track):** list **$99**, currently **$69 «مدى الحياة»** → **≈3,512 EGP now, 5,039 list** ([track page](https://almdrasa.com/products/tracks/data-structures)).
- **Payments (verbatim FAQ):** «**من داخل مصر:** فيزا/ماستر كارد أو خدمة كاش (فودافون كاش، أورانج كاش، اتصالات كاش، وي باي). **من خارج مصر:** فيزا/ماستر كارد وخدمة باي بال (Paypal).» Instalment plans on the diploma products.
- **Refund (verbatim, [terms](https://almdrasa.com/terms-conditions/) §24):** «يمكنك الإلغاء … خلال **٢٤ ساعة** … بشرط عدم مشاهدة او تصفح ما يزيد عن **٢٪** من محتوى أي مسار … مع العلم أن **الاشتراك لا يُسترد في حالة الاشتراك في الدورات المنفردة**.»
- **Caveat in their own FAQ:** mentoring, project review and Zoom access last **one year**; only the videos are lifetime.
- **YouTube:** **43.2K subscribers** (channel `UCSy7Y-SWLNCAX2AcpNuRHRg`).
- **Their edge:** **graded projects reviewed by named FAANG engineers, plus a weekly live Zoom with them** — instructors credentialed individually on the page (م. أحمد علي, "Lead Software Engineer at AWS"; م. محمد المصري, "Senior Software Engineer at Talabat"). Learn Simply is one teacher and recorded video; Almdrasa sells access to people, and prices its DS track at ~5.4× Learn Simply's DS L1.

#### Mahara-Tech / ITI (معهد تكنولوجيا المعلومات — Egyptian government)
- **Positioning:** ITI-authored courses "to serve Arab Youth in Information Technology Fields… and enrich Arabic content" — [maharatech.gov.eg](https://maharatech.gov.eg/)
- **Format:** self-paced Moodle, 17+ categories including **Java Web Development**, Python, MERN, Android, iOS, a 19-course Cyber Security Academy, Blockchain, IoT, UX, Software Testing.
- **Price:** **FREE** — every course in the Front-End category is labelled `FREE` ([category listing](https://maharatech.gov.eg/course/index.php?categoryid=13)). No paid tier observed.
- **Scale (enrolments shown per course, observed 2026-08-24):** HTML & CSS **102,933** · JavaScript **61,073** · Modern JS ES6 **32,173** · React **31,137** · Clean Code **26,300** · TypeScript **24,043**.
- **YouTube:** UNVERIFIED — no channel identified.
- **Their edge:** a **free, government-issued ITI certificate** from the institute that runs Egypt's flagship 9-month tech scholarships, attached to a catalogue that reaches well into paid-tier territory. For a budget-constrained Egyptian CS student this is the strongest price argument against paying anyone.

#### Programming Advices (د. محمد أبو هدهود) — the closest structural competitor
- **Positioning:** "We teach programming from practical experience, our 26+ Years of experience…" · «سلسلة اساسيات مهمة لكل مبرمج» — [programmingadvices.com](https://programmingadvices.com/courses)
- **Format:** a **numbered 24-course roadmap** (Stage 1 → Stage 2), lifetime access. Overlaps Learn Simply almost item for item: **C++ Levels 1–2, OOP as it Should Be, Data Structures Level 1, Data Structures Level 2 (C#), Algorithms & Problem Solving Levels 1–6**, plus C#, SQL, EF Core, REST APIs.
- **Price:** courses 01–04 **free**; each subsequent course **$20 → ≈1,018 EGP**; full 24-course bundle **$340 → ≈17,304 EGP** («بدل 400 دولار تصبح 340 دولار»).
- **Payments:** Teachable checkout with **instalments explicitly offered** ("or pay in installments"). Also sells a «كوبون دعم طالب» that funds seats for students who cannot pay.
- **Refund:** **UNVERIFIED** — none found.
- **YouTube:** **452K subscribers** (channel `UCuEOSK8blSM6j5jxVp3ttnQ`) — the closest in size to Learn Simply's 403K.
- **Their edge:** **a dependency-ordered roadmap where each course states when in the sequence it belongs** — "Learn core data structures clearly, correctly, **and at the right time**". The student buys a *path*, not a course. This is the single most direct threat to Learn Simply's Java/OOP/DS catalogue, at a comparable channel size.

#### Abdelrahman Gamal (عبدالرحمن جمال) — the free substitute that hurts most
- **Format:** **free-only YouTube**, long-form single-video full courses («كورس html كامل في فيديو واحد»), plus a full Arabic-narrated adaptation of **Harvard CS50** covering data structures, trees, relational databases.
- **Price:** free. A paid offering, if one exists, is UNVERIFIED.
- **YouTube:** **739K subscribers** (channel `UCbQh1yxBPVhyjB-G_blFFEQ`).
- **Their edge:** **a free Arabic Harvard CS50** delivers the exact CS-fundamentals content Learn Simply sells at 499–999 EGP, wrapped in a brand Learn Simply cannot match. For a student whose real need is "understand data structures before my exam", this costs nothing.

#### منصة سطر / Satr (Tuwaiq Academy, Saudi Federation for Cybersecurity, Programming & Drones)
- **Positioning:** «تعلم تقنيات المستقبل — في أي وقت ومن أي مكان» — [satr.codes](https://satr.codes/) → `satr.tuwaiq.edu.sa`
- **Format:** self-paced tracks and books plus **تحديات طويق** gamified challenges; sits alongside Tuwaiq's معسكرات منتهية بالتوظيف (employment-linked bootcamps).
- **Price / payments / refund:** **all UNVERIFIED** — no prices displayed on the landing or content pages, and no priced item could be opened. Treat as free-or-unpriced, not confirmed.
- **YouTube:** UNVERIFIED.
- **Their edge:** state-backed practice platform with a route into job-guaranteed programmes — relevant because the Gulf is Learn Simply's secondary market and nothing in its catalogue connects study to hiring.

### 4.2 Comparison table

| Competitor | Format | Price band (EGP @50.89) | Payments | Refund / guarantee | YouTube |
|---|---|---|---|---|---|
| **اتعلم ببساطة** *(baseline)* | Self-paced recorded | **499–999/course; 2,500 all-courses** | Cards via Kashier; Vodafone Cash + InstaPay manually | **7 days**, stated on site | **403K** |
| Abdelrahman Gamal | Free YouTube (incl. Arabic CS50) | **Free** | — | — | **739K** |
| Mahara-Tech / ITI | Free, government | **Free** (102,933 in one course) | — | — | UNVERIFIED |
| Elzero Web School | Free curriculum + small paid tier | Free; **2,036–4,835** paid | UNVERIFIED (USD) | **None published** | **1.93M** |
| Programming Advices | Numbered 24-course roadmap | 4 free; **1,018/course**; **17,304** bundle | Teachable, **instalments offered** | UNVERIFIED | **452K** |
| Almdrasa | Tracks + graded projects + weekly Zoom | **3,512** (DS track; list 5,039) | Visa/MC, **Vodafone/Orange/Etisalat Cash, WE Pay**; PayPal abroad | **24h**, <2% viewed; single courses non-refundable | **43.2K** |
| Codezilla | One 65h+ flagship | **11,197** | Stripe, PayPal, **Vodafone Cash** | **None published** | **1.1M** *(own bio says 631K)* |
| أكاديمية حسوب | Exam-gated cert + employment support | **19,849/course** | Stripe (full rails UNVERIFIED) | **6-month job-or-money-back**, conditional | **164K** |
| سطر / Tuwaiq | Tracks + challenges | **UNVERIFIED** | UNVERIFIED | UNVERIFIED | UNVERIFIED |

### 4.3 A ninth competitor the repo does not track at all

On the topic Ahmed's channel now actually publishes — AI tooling — the competitor is **[غريب الشيخ Ghareeb Elshaikh](https://www.youtube.com/@GhareebElshaikh): 520K subscribers, 34,916,972 views, 492 videos, UAE** (observed 2026-08-24). His «كورس تعلم كلود من الصفر || Claude 101 in Arabic» has **533,881 views in 4 months** — more than Ahmed's own 345K Claude course, from a larger channel. Arabic AI education is not an empty field; it is a field Ahmed has entered second.

---

## 5. Price positioning

### 5.1 Where the AOV sits

Supplied by the caller and measured from the live WooCommerce database on 2026-08-24: **AOV on completed orders = 556 EGP for 2026 YTD**, up from 466 (2025) and 278 (2024). At 50.89 EGP/USD that is **≈$10.93**.

**Important qualifier:** the 556 figure is a year-to-date average that includes six months of the *old*, lower price list. The catalogue was repriced upward 11–27% at some point between 2026-06-03 and 2026-08-24 (§3.1), so the **current run-rate AOV is almost certainly higher than 556**, and a like-for-like comparison should be re-measured on orders since the reprice. That measurement needs DB access and was not in scope here.

### 5.2 The ladder

| Tier | Player | Per-unit price (EGP) |
|---|---|---|
| **Free** | Abdelrahman Gamal (Arabic CS50), Mahara-Tech/ITI, Elzero core curriculum | **0** |
| **Budget paid** | Programming Advices | 1,018/course |
| **Budget paid** | **اتعلم ببساطة** | **499–999/course · AOV 556 · 2,500 all-in** |
| **Mid** | Elzero paid tier | 2,036–4,835 |
| **Mid** | Almdrasa (DS track) | 3,512 |
| **Premium** | Codezilla | 11,197 |
| **Premium** | Programming Advices (full 24-course path) | 17,304 |
| **Premium** | أكاديمية حسوب | 19,849/course |

### 5.3 The answer: budget, and the only paid player at that level

**Learn Simply is priced as the budget option of the paid market — the cheapest paid seller in the set except on single-course comparison with Programming Advices, where it is still cheaper (499–999 vs 1,018).**

The 2,500 EGP all-courses bundle — six courses, 448 lessons — costs **less than Almdrasa's single discounted Data Structures track** (3,512), **less than a quarter of one Codezilla course** (11,197), and **an eighth of one Hsoub course** (19,849).

This is not a small gap that could be closed by trimming a discount. It is a different market position.

### 5.4 What the evidence says about the floor

There is **no meaningful price floor from competitors, because the floor is zero and the free tier is genuinely substitutable.** Three of the eight players give away content that overlaps Learn Simply's paid catalogue:
- Abdelrahman Gamal's free Arabic CS50 covers data structures, trees and databases — the DS L1/L2 subject matter (1,149 EGP for both).
- Mahara-Tech/ITI offers **free Java web development** with a government certificate, and 102,933 people took just one of its free courses.
- Elzero's free curriculum is a sequenced syllabus with quizzes and challenges, backed by 1.93M subscribers.

**Implication: competing on being cheap is competing against zero, and losing.** Whatever a buyer pays Learn Simply for, it is not "access to information about Java" — that is free and abundant. It is Ahmed's explanation, the sequencing, the certificate, and the fact that he answers.

### 5.5 What the evidence says about the ceiling

Four independent signals say the current price is **below** the ceiling, not at it:

1. **A price rise has already been executed without visible damage.** Between 2026-06-03 and 2026-08-24 every paid course rose 11–27% (§3.1), and paid enrolment over that window ran at ~124/month (§2.5) — consistent with the ~122 orders/month recorded before the rise **[historical, 2026-05]**. No volume collapse is visible in the enrolment counters. *(Caveat: enrolment counters are a proxy for orders, and this is an observational before/after, not a controlled test.)*
2. **AOV has doubled in two years** — 278 → 466 → 556 — meaning this buyer has already absorbed repeated increases.
3. **A 2,500 EGP SKU exists and is live.** It is 4.5× the AOV and it did not require a new audience. *(How many have sold is **UNVERIFIED** — it needs the order table.)*
4. **The nearest comparable competitor by channel size charges ~1.7× per course.** Programming Advices (452K subs vs Learn Simply's 403K) sells the same C++/OOP/DS subject matter at 1,018 EGP against a Learn Simply average per-course price of 600 EGP (mean of 550/700/650/499/600).

**Where the real ceiling is set — income, not competitors.** Egypt's private-sector minimum wage is **EGP 7,000/month**, effective March 2025; the Prime Minister announced a rise to **EGP 8,000 from July 2026** ([Egyptian Streets, 2026-04-02](https://egyptianstreets.com/2026/04/02/egypt-raises-minimum-wage-to-8000-egp-from-july-2026/), which describes the announced rise as applying to the public sector). World Bank GNI per capita (Atlas method) for Egypt was **$3,260 in 2025**, down from $3,510 in 2024 and $3,840 in 2023 ([World Bank indicator NY.GNP.PCAP.CD](https://api.worldbank.org/v2/country/EGY/indicator/NY.GNP.PCAP.CD?format=json&mrv=4), fetched 2026-08-24) — a market whose dollar income has been *falling*.

Against a 7,000 EGP monthly floor: the 556 EGP AOV is **7.9% of a month's minimum wage**; the 2,500 EGP bundle is **36%**. The single-course band (499–999) is the comfortable impulse zone. **2,500 is where a real decision starts** — which is exactly where instalments stop being a nice-to-have (§6.5: valU's floor via gateway is 50 EGP and Kashier already supports it).

### 5.6 The open flank nobody in the set is defending

Two gaps show up across all eight competitors:

**Guarantees.** Codezilla ($220) and Programming Advices ($340 bundle) publish **no refund policy at all**. Elzero's terms page redirects to its homepage. Almdrasa's is 24 hours, capped at 2% viewed, and explicitly excludes single courses. Only Hsoub offers a strong guarantee, and it is heavily conditioned on passing an exam. **Learn Simply's plainly-worded 7-day refund is already better than five of the seven**, and it is buried — stated once on the homepage and once in the terms page, not at the buy button.

**Instalments.** Across all eight competitors, **zero offer valU, Sympl, Tabby or Tamara.** The only instalment mechanism observed anywhere in the set is Programming Advices' generic Teachable "pay in installments". Meanwhile Kashier — the gateway Learn Simply already uses — documents valU, Souhoola and Aman support in its own WooCommerce plugin (§6.5). **This is an unoccupied position that the existing infrastructure can already reach**, and it maps precisely onto the one price point (2,500 EGP) where affordability starts to bite.

---

## 6. Payment reality for this market

*Research in this section was gathered from central-bank publications, gateway documentation and sanctions notices. Every figure carries its source and date.*

### 6.1 Egypt — card-only checkout addresses a minority of buyers

| Measure | Value | Date | Source |
|---|---|---|---|
| Debit cards in circulation | 27.5 M | FY 2024/25 (CBE Annual Report) | [winnersegy.com summarising CBE, 26 May 2026](https://winnersegy.com/2026/05/26/%D8%A7%D9%84%D8%A8%D9%86%D9%83-%D8%A7%D9%84%D9%85%D8%B1%D9%83%D8%B2%D9%8A-%D9%8A%D8%B9%D9%84%D9%86-%D8%B7%D9%81%D8%B1%D8%A9-%D8%AA%D8%A7%D8%B1%D9%8A%D8%AE%D9%8A%D8%A9-%D9%81%D9%8A-%D8%A7%D9%84%D8%B4/) |
| Credit cards in circulation | 6.7 M | same | same |
| **Prepaid cards** | **35.0 M — 50.6% of the whole card base** | same | same |
| Adults holding a **credit** card | **~6%** | 2025 | [EBANX Egypt market page](https://www.ebanx.com/en/markets-coverage/egypt/) |
| CBE "financial inclusion" rate | 76.3% (counts a prepaid card or telco wallet as inclusion) | June 2025 | [CBE, 3 Sep 2025](https://www.cbe.org.eg/en/news-publications/news/2025/09/03/09/57/financial-inclusion-rates-egypt-as-of-june-2025) |
| World Bank Findex account ownership (household survey) | **43.1%** | 2024 data | [World Bank indicator FX.OWN.TOTL.ZS](https://api.worldbank.org/v2/country/EGY/indicator/FX.OWN.TOTL.ZS?format=json&mrv=8) |

**Sources disagree, and the disagreement matters.** The CBE's 76.3% counts anyone with a prepaid card or telco wallet; the World Bank's 43.1% asks people whether they have an account. Neither is a proxy for "can complete a card payment online" — that number is closer to the ~6% credit-card figure plus the subset of debit/prepaid cards not blocked.

**Why Egyptian cards decline — the documented mechanism:**
- Foreign-currency card transactions were largely switched off across Egyptian banks. HSBC Egypt suspended FX transactions on debit cards from **24 Oct 2023**; as of **July 2025** debit cards remained largely disabled for international use, with credit-card caps such as NBE $250/month ([Digital Boom, 14 Jul 2025](https://adigitalboom.com/market-watch/egypt-foreign-currency-card-limits/)).
- The CBE halted prepaid-card transactions abroad; of ~25M prepaid cards then in issue, ~7M could previously transact abroad. The article names the affected use case explicitly: buying social-media ads and **remote educational lessons** ([CNN Business Arabic, 31 May 2023](https://cnnbusinessarabic.com/banking-finance/25711/)).
- **The measured effect:** *"Direct charging in Egyptian pounds reduced declined-transaction rates from approximately **35% on cards** to **low single digits**"* ([Daily News Egypt, 19 Aug 2026](https://www.dailynewsegypt.com/2026/08/19/how-mobile-wallets-rewired-egypts-digital-economy/)).

> **UNVERIFIED:** the widely repeated claim that Egyptian debit cards are "not enabled for e-commerce by default." It appears only in undated aggregator pages. The *verified* mechanism is the FX/international block plus low credit-card ownership. Do not build an argument on the folklore version.

### 6.2 Wallets are where the volume actually is

| Measure | Value | Period | Source |
|---|---|---|---|
| Mobile-wallet transaction value | **EGP 943.4 bn** (+72% YoY) | Q2 2025 | [Daily News Egypt, 13 Sep 2025 (NTRA data)](https://www.dailynewsegypt.com/2025/09/13/mobile-wallet-transactions-in-egypt-surge-72-in-q2-2025-to-egp-943-4bn/) |
| Mobile-wallet transactions | 717.7 M (+80%) | Q2 2025 | same |
| Active e-wallets | 46.3 M (+29%) | Q2 2025 | same |
| **Vodafone Cash share** | **55% of wallets, 78% of transactions, 81% of value** | Q2 2025 | same |
| Total e-wallets (CBE definition, incl. bank wallets) | 55.5 M | June 2025 | [Daily News Egypt, 17 Nov 2025](https://www.dailynewsegypt.com/2025/11/17/cbes-digital-transformation-push-lifts-financial-inclusion-to-76-3-in-june-2025-assistant-governor/) |

Wallets **can** pay an online merchant, and this is documented rather than inferred: Paymob's own docs list Vodafone Cash, Orange Cash, e& money, We Pay and bank wallets as supported online methods with full/partial refunds ([Paymob mobile-wallets doc, updated 1 Jun 2026](https://developers.paymob.com/paymob-docs/payments-and-features/payment-methods/mobile-wallets-egy-ksa)). **Kashier's payment-session API accepts `wallet` in `allowedMethods` by default.**

### 6.3 InstaPay — big, but not a checkout method

| Measure | Value | Date | Source |
|---|---|---|---|
| InstaPay users | >16 M | June 2025 | [Daily News Egypt, 17 Nov 2025 (CBE)](https://www.dailynewsegypt.com/2025/11/17/cbes-digital-transformation-push-lifts-financial-inclusion-to-76-3-in-june-2025-assistant-governor/) |
| Cumulative transactions | >1.1 bn worth EGP 2.4 trn | by June 2025 | same |
| Fees | Free until **April 2025**, when transfer fees were introduced | Apr 2025 | [CBE fee-exemption decree, 30 Dec 2024](https://www.cbe.org.eg/en/news-publications/news/2024/12/30/12/31/cbe-extending-the-exemption-of-individuals-from-instapay-for-a-renewable-period-of-3-months) |

**InstaPay is not a selectable checkout method at either main gateway.** Kashier's documented `allowedMethods` are `card, bank_installments, wallet, bnpl` ([Kashier payment-sessions doc](https://developers.kashier.io/payment/payment-sessions)); Paymob's method list is Cards, Mobile Wallets, Apple/Google Pay, BNPL, Bank Installments, Kiosk ([Paymob overview, updated 3 Aug 2026](https://developers.paymob.com/paymob-docs/getting-started/overview)). Taking InstaPay online today means a **manual person-to-person transfer with a screenshot** — which is exactly the flow Learn Simply already runs through the WhatsApp agent. That flow is the state of the art, not a workaround.

### 6.4 Fawry / kiosk cash — the missing rail

| Measure | Value | Date | Source |
|---|---|---|---|
| Fawry acceptance | "250,000+ outlets", "used by 75% of adults", reaching "over 53 million consumers who are excluded from card-based payments" | 2025 | [EBANX Egypt](https://www.ebanx.com/en/markets-coverage/egypt/) |
| Cash reliance in Egyptian e-commerce | **64% of transactions** (vs 24% Africa average) | 2025 | same |

Outlet counts disagree across sources (194k / 250k / 300k / 395.7k) because they measure different things — branded locations vs service points vs POS terminals.

**It works for digital goods.** dLocal's Egypt spec for Fawry: type `TICKET`, min 5 EGP, reference valid up to **30 days**, notification **immediate**, refunds supported ([docs.dlocal.com/docs/egypt](https://docs.dlocal.com/docs/egypt), modified 2026-06-05). Paymob's "Kiosk [EGY]" does the same but **cannot refund** ([Paymob kiosk doc, updated 1 Jun 2026](https://developers.paymob.com/paymob-docs/payments-and-features/payment-methods/kiosk-egy)).

**Why this matters more than it looks:** cash-on-delivery is 64% of Egyptian e-commerce but structurally cannot apply to a course — there is no parcel and no courier. Fawry's "pay cash at a kiosk against a reference code" is **the only rail that preserves the same psychology** for a digital product. **Kashier's documented method list has no kiosk option.**

### 6.5 Instalments — viable on ticket size, unproven on merchant category

- **Kashier already supports valU, Souhoola and Aman** (WooCommerce plugin v4.0.0 README, [Kashier-WooCommerce-UI-Plugin](https://raw.githubusercontent.com/Kashier-payments/Kashier-WooCommerce-UI-Plugin/master/README.md)), with API targeting like `bnpl[valu]`.
- **valU's floor via gateway is 50 EGP**; plans of 1–3 months can be 0% on merchant-funded offers, 3–6 months 10–15%, longer ~2.5%/month; session expiry 15 minutes; refunds supported ([dLocal Egypt spec, modified 2026-06-05](https://docs.dlocal.com/docs/egypt)).
- So even a 199 EGP book qualifies on ticket size, and the 2,500 EGP bundle is squarely in range.
- **Merchant fees are UNVERIFIED for Egypt.** Neither valU nor Sympl publishes an MDR. Global BNPL MDRs run 2–8% (typically 4–6%) per secondary sources. Get a written rate before designing a funnel around it.

### 6.6 Gulf

| Measure | Value | Date | Source |
|---|---|---|---|
| E-payments share of Saudi retail individual transactions | **85%** (79% in 2024, 70% in 2023) | full-year 2025 | [SAMA](https://www.sama.gov.sa/en-US/News/Pages/news-1139.aspx) |
| mada e-commerce sales | **SR 30.7 bn**, +68% YoY (record month) | October 2025 | [Arab News](https://www.arabnews.com/node/2627424/business-economy) |
| Most-used online methods, Saudi | Apple Pay 36%, PayPal 29%, STC Pay 26% | 2025 (Worldpay-derived) | **secondary** — [KAE](https://www.kae.com/featured-insights/shifting-payment-behaviours-in-the-middle-east) |

A Gulf buyer expects a card or **Apple Pay**, priced in SAR/AED, with no redirect. Kashier advertises Apple Pay live and documents acquiring in **EGP, USD, GBP, EUR**. Paymob runs **separate merchant accounts per country** (`ksa.paymob.com`, `uae.paymob.com`) rather than one multi-currency checkout.

> **UNVERIFIED and worth checking before any Gulf spend:** one secondary source states Tabby rejects merchants in certain categories **including digital goods** ([ChampX Digital](https://champxdigital.ae/blog/bnpl-guide-gulf)). This could not be confirmed on Tabby's or Tamara's own merchant terms. Equally unverified: whether Kashier's acquirer authorises Saudi-issued mada/co-badged cards. Both are answered by one real test transaction, not by more research.

### 6.7 Yemen, Syria, Palestine — the gateway will not solve this

**Syria.** US sanctions were revoked by Executive Order on **30 June 2025** ([White House](https://www.whitehouse.gov/presidential-actions/2025/06/providing-for-the-revocation-of-syria-sanctions/)); Visa announced entry on **4 Dec 2025**; the Central Bank of Syria permitted licensed banks to work with Visa/Mastercard on **4 May 2026** ([Asharq Al-Awsat](https://english.aawsat.com/business/5269517-syrian-central-bank-allows-dealings-global-electronic-payment-companies)). Rails are being built, not built. Domestic wallets (Syriatel Cash, MTN Cash, Sham Cash) do not connect to international card networks. PayPal is not live.

**Yemen.** Ansarallah re-designated an FTO on **4 Mar 2025**; OFAC sanctioned Yemen Kuwait Bank (**17 Jan 2025**) and the International Bank of Yemen including SWIFT access (**Apr 2025**, [US State Dept](https://www.state.gov/sanctioning-international-bank-of-yemen-for-supporting-the-houthis)). General License 24A authorises **noncommercial personal remittances**. Domestic wallets Jawali/WeCash work locally ([jawali.com.ye](https://jawali.com.ye/en/about)).

**Palestine.** Commercial banks in the West Bank issue Visa/Mastercard under Palestine Monetary Authority oversight ([US ITA country commercial guide](https://www.trade.gov/country-commercial-guides/west-bank-and-gaza-trade-financing)). **PayPal does not allow Palestinians in WB&G to link bank accounts.** In Gaza, **93% of bank branches** are destroyed or damaged and **only 3 of 94 ATMs** are operational ([World Bank, 16 Dec 2024](https://www.worldbank.org/en/news/feature/2024/12/16/e-payments-as-a-pathway-to-growth-empowering-the-west-bank-and-gaza-through-digital-inclusion)).

**Practical read for all three:** the realistic payment paths are a relative abroad paying, an exchange-house transfer, or crypto — i.e. the manual Vodafone Cash / InstaPay flow already in use. What is missing is not a gateway, it is a **written policy** on which of those the business accepts, and awareness that any rail touching a designated Yemeni bank is a compliance problem.

### 6.8 Failure and abandonment

| Measure | Value | Scope / date | Source |
|---|---|---|---|
| Card declines vs EGP direct charging | **~35% → low single digits** | Egypt, 19 Aug 2026 | [Daily News Egypt](https://www.dailynewsegypt.com/2026/08/19/how-mobile-wallets-rewired-egypts-digital-economy/) |
| Consumers hit by a false decline in last 3 months | 23% | MENA, 14 May 2024 | [Checkout.com MENA report](https://www.checkout.com/newsroom/checkout-coms-4th-annual-mena-report-finds-cash-on-delivery-usage-halved-amongst-maturing-digital-economy) |
| Shoppers who switch to a competitor after a failed payment | **33%** | MENA, 14 May 2024 | same |
| Documented average cart abandonment | 70.22% (meta-analysis, 50 studies) | global, 2026 | [Baymard](https://baymard.com/lists/cart-abandonment-rate) |

**Egypt-specific 3-D Secure/OTP failure rates: UNVERIFIED — no dated source found.** The same for card-limit failures on the 2,500 EGP tier.

### 6.9 What this means for a 199–2,500 EGP course

1. **Wallets are the largest documented conversion lever, and they may be one config flag away.** Kashier's `allowedMethods` already defaults to including `wallet`. Vodafone Cash alone carries 78% of Egyptian wallet transactions and the footer already shows its logo — but whether it is actually enabled at checkout is **UNVERIFIED** (the checkout page is login-gated).
2. **Always price and charge in EGP.** Every dated source points the same way; Kashier's plugin has an explicit "Enforce EGP Payment" flag for this.
3. **A cash/kiosk rail is the one genuinely missing capability — and Kashier does not have it.** Adding it means adding or switching a provider. That is a cost and complexity decision, not an automatic yes.
4. **Instalments are already available on the current gateway** (valU/Souhoola/Aman) and the ticket sizes qualify. The open question is the merchant rate, which is not published.

---

## 7. Demand signals — what Arabic-speaking beginners are actually looking for

### 7.1 Search interest, Egypt (Google Trends API, geo=EG)

Values are Google's relative index, 0–100, scaled to the peak of the highest term **within each comparison**. They are comparable inside a row-group, not across groups. Queried 2026-08-24; window ends 2026-08-29.

**Group A — last 12 months, Latin-script terms**

| Term | Mean | Peak | First 4 weeks | Last 4 weeks | Direction |
|---|---|---|---|---|---|
| `c++` | **74.0** | 100 | 65.0 | 64.0 | flat |
| `python` | 47.4 | 95 | 26.0 | **49.2** | 🟢 +89% |
| `java` | 14.7 | 50 | 10.0 | 10.2 | flat |
| `flutter` | 10.1 | 15 | 12.8 | **6.5** | 🔴 −49% |
| `data structure` | 1.1 | 4 | 0.8 | 0.0 | negligible |

**Group B — last 12 months, Arabic-script terms**

| Term | Mean | Peak | Last 4 weeks |
|---|---|---|---|
| `بايثون` | **67.3** | 100 | 79.2 |
| `تعلم البرمجة` | 5.3 | 27 | 9.8 |
| `كورس برمجة` | 0.7 | 16 | 2.5 |
| `هياكل البيانات` | **0.0** | 0 | 0.0 |

**Group C — five years, Egypt**

| Term | 2021 (first 4 wks) | 2026 (last 4 wks) | Direction |
|---|---|---|---|
| `chatgpt` | **0.0** | **64.5** (peak 100) | 🟢 0 → dominant |
| `python` | 5.0 | 10.5 | 🟢 doubled |
| `java` | 3.2 | 2.0 | 🔴 declining |
| `flutter` | 2.8 | 1.0 | 🔴 −64% |
| `الذكاء الاصطناعي` | 25.8 | 51.8 | 🟢 doubled |
| `تعلم البرمجة` | 1.0 | 0.8 | 🔴 flat/declining |

**Group D — AI tools specifically, Egypt, 12 months**

| Term | Mean | First 4 wks | Last 4 wks |
|---|---|---|---|
| `chatgpt` | 74.0 | 78.5 | 65.0 |
| `claude ai` | 4.5 | **1.0** | **8.0** (🟢 8×) |
| `n8n` | 1.2 | 1.2 | 1.0 |
| `cursor ai` | 0.0 | 0.0 | 0.0 |

**Group E — Saudi Arabia, 12 months**

| Term | Mean | Last 4 wks |
|---|---|---|
| `chatgpt` | **83.3** | 82.2 |
| `python` | 6.9 | 3.8 |
| `java` | 3.8 | 2.5 |
| `flutter` | 1.1 | 0.5 |

**Caveats, stated plainly:** `فلاتر` in Arabic also means water filters and was excluded from the reported set for that reason. `c++`'s high Egyptian index is very likely driven by university curricula rather than career-switchers — which happens to be exactly Learn Simply's buyer. `الذكاء الاصطناعي` and `chatgpt` are broad topical terms and will always out-index a specific product category; the *direction* is the reliable signal, not the ratio.

### 7.2 What the search data supports

1. **AI is where attention is, in both markets.** In Egypt `chatgpt` went from zero in 2021 to a last-4-week index of 64.5; in Saudi it sits at 82.2 while `python` is 3.8 and `java` 2.5. Interest in AI is not a fad curve that has rolled over — it is the baseline now.
2. **`claude ai` in Egypt grew 8× in twelve months** (1.0 → 8.0). Ahmed's channel pivot is not a whim; it is riding a measurable local trend, and his 345K-view Claude video landed in the middle of it.
3. **Python is rising, Java is flat, Flutter is falling.** Over five years in Egypt: python doubled, java declined, flutter fell 64%. Learn Simply's paid catalogue is majority Java, and its newest investment (Dart/Flutter, published 2026-08-13) is in the one language whose search interest is falling fastest.
4. **Nobody searches "هياكل البيانات" in Arabic** — index 0.0 for the entire year — and the Latin `data structure` sits at 1.1. This is not a demand problem: DS L1 is the best-selling course with 1,634 students. It means DS demand **does not arrive through Google**. It arrives through YouTube, where Ahmed's own C++ video (4.3M views) ranks third for that topic. **Any SEO plan built on Arabic DS keywords is aimed at traffic that does not exist.**
5. **"تعلم البرمجة" as a search behaviour is flat-to-declining** over five years. The category is not growing through search; it is growing through video.

### 7.3 First-party demand signal: the channel's own view distribution

The strongest evidence available is Ahmed's own audience choosing between his own videos (§2.2):

| Content type | Views |
|---|---|
| Claude crash-course (2026-04-03) | **345,571** |
| Recent AI-tool videos (last 90 days) | 3K – 64K |
| **8-part Python challenge series** (5–6 months ago) | **1.7K – 5.6K** (avg ≈2.6K) |
| Dart course promo (2026-07-17) | 4,651 |

Same channel, same subscribers, same period: **AI content out-performs programming-teaching content by 50–200×.**

And in the market at large, a direct competitor is already ahead on this exact topic: **[غريب الشيخ](https://www.youtube.com/@GhareebElshaikh) — 520K subscribers, 34.9M views, 492 videos** (observed 2026-08-24), whose "كورس تعلم كلود من الصفر || Claude 101 in Arabic" has **533,881 views in 4 months** — more than Ahmed's equivalent video and from a bigger channel. Arabic AI education is not an empty field.

### 7.4 The demand conclusion

Arabic-speaking beginners are searching for, and watching, **AI tooling** — and they are watching it *from Ahmed*. Meanwhile they are buying **Java fundamentals and Data Structures** from him, because that is all he sells. Those are two different populations sharing one subscriber list. The catalogue answers a question the current audience is no longer asking, and there is no product at all for the question they *are* asking — the one that produced 345K views and zero revenue.

---

## Strategic implications

*Baseline for "doubling": ~124 paid enrolments/month ≈ 69,000 EGP/month (§2.5, triangulated independently from enrolment counters against the repo's ~67K/month revenue figure). Doubling means ~138,000 EGP/month.*

### What the evidence makes possible

1. **Build a paid AI product for the audience already being acquired.** This is the largest unexploited asset found. Evidence: one Claude video did **345,571 views** (§2.2) while the entire 8-part Python series did 1.7K–5.6K; `claude ai` search interest in Egypt grew **8× in 12 months** (§7.1 Group D); 14 of the last 15 uploads are AI content (§2.1); and there is **no AI product in the catalogue at all** (§3.1). The audience, the trust, the traffic and the CTAs all already exist — only the product is missing.

2. **Make the 2,500 EGP bundle findable.** It is absent from the homepage and the navigation's only shop route is pre-filtered to books (§3.3a). At 4.5× the AOV, moving even a small share of buyers onto it moves revenue more than any traffic increase. Zero cost, zero risk.

3. **Turn on wallet payments at checkout.** EGP-native charging takes declines from **~35% to low single digits** (§6.1); Vodafone Cash carries **78% of Egyptian wallet transactions** (§6.2); the footer already advertises the logo; and Kashier's `allowedMethods` defaults already include `wallet` (§6.4). Whether it is actually enabled is UNVERIFIED because checkout is login-gated — **so step one is to look, not to build.**

4. **Add instalments on the 2,500 SKU — an unoccupied position.** **None of the eight competitors offers valU, Sympl, Tabby or Tamara** (§5.6). Kashier already documents valU/Souhoola/Aman support, and valU's gateway floor is 50 EGP (§6.5). The 2,500 bundle is 36% of a month's minimum wage (§5.5) — precisely the price point where instalments convert. Gate this on getting a written merchant rate first.

5. **Move the 7-day guarantee to the buy button.** It is already better than five of the seven paid competitors, two of whom publish **no refund policy at all** (§5.6), yet it appears only on the homepage and in the terms page (§3.2).

6. **Raise prices again.** A 11–27% rise was already executed between June and August with no visible volume collapse (§3.1, §5.5), AOV has doubled in two years, and the nearest competitor by channel size charges ~1.7× per course (§5.3). The ceiling is set by Egyptian incomes, not by rivals — and the current single-course band (499–999) sits well inside the impulse zone at 7–14% of a month's minimum wage.

7. **Capture the free-course traffic.** The free Python course recruited **833 students in 82 days — 2.5× the entire paid catalogue's 338** (§2.5). The toolbox page gives away its one asset with **no email gate** (§2.4). This is the warmest, cheapest list in the business and it is being let go.

### What the evidence makes impossible, or not worth funding

8. **SEO and the blog cannot contribute.** The site has **exactly one published post, dated 2025-12-19**, and the navigation's blog link **404s** (§1.5, §3.3a2). Worse, Arabic-script `هياكل البيانات` has a Google Trends index of **0.0 for the whole year** and `data structure` sits at 1.1 (§7.1). The demand for the flagship product genuinely does not pass through Google. **Any plan with "revive the blog for SEO" as a growth pillar is aimed at traffic that does not exist.**

9. **"Add stronger CTAs to YouTube" is finished work.** Every description checked already carries three to four learrnsimply.com links plus the channel-level link (§2.3). The repo's headline recommendation **[historical, 2026-05]** has been implemented. Re-running it will not move revenue.

10. **Telegram cannot carry a doubling.** Views per post fell **66% (12.2K → 4.14K)** while subscribers rose, real engagement is now **16.5% not 60–86%**, and the channel has been **silent for 31 days** (§1.2). It is worth reviving as a support channel, but the repo's framing of it as the brand's best conversion asset is no longer true and should not be planned against.

11. **Competing on price is competing against zero.** Three competitors give away the same subject matter free — a full Arabic Harvard CS50 (739K subs), free ITI Java web development with a government certificate (102,933 enrolments in one course), and Elzero's sequenced free curriculum (1.93M subs) — §4.1, §5.4. Discounting deeper moves Learn Simply toward a price it cannot win at.

12. **The Gulf is not a near-term doubling lever on the current stack.** In Saudi Arabia `chatgpt` indexes 82.2 while `python` is 3.8 and `java` 2.5 (§7.1 Group E); Gulf buyers expect Apple Pay in local currency (§6.6); and whether Kashier's acquirer authorises Saudi-issued cards is **untested**. The cheap next step is one real test transaction, not a campaign.

13. **Yemen, Syria and Palestine cannot be solved by a gateway.** Syria's card rails were only permitted from 2026-05-04 and are not deployed; Yemen's banking sector is under active OFAC designations; Gaza has **3 of 94 ATMs** working (§6.7). The realistic paths are a relative abroad paying, an exchange house, or crypto — i.e. the manual flow already in use. What is missing is a **written policy**, not a product.

### Two things to fix before any growth spend

14. **The WhatsApp agent is quoting wrong prices right now.** Six of eleven prices in the repo knowledge base are stale (§3.2) — a customer asking about the Java course is told 450 when the site charges 550. Every marketing pound spent driving people to that agent is spent on a bad answer.

15. **The countdown timer is a stated fake, in production, on a trust brand.** The sitewide promo bar runs a per-visitor rolling 3-day timer whose own source comment reads *"persisted per-visitor in localStorage **so it feels like a real offer**"* (§3.3d). Alongside "خصم 53% لمدة 3 أيام فقط" on a product last touched 19 days ago, and a "53% saving" anchored to a 5,300 EGP price nobody is ever charged when the real saving is ~17% (§3.3c). For a brand whose entire positioning is *"البرمجة مش صعبة، بس محتاجة حد يعرف يشرحها"*, this is the highest-severity finding in the report and the cheapest to fix.

---

## Appendix — verification status

**Verified from primary sources today (2026-08-24):** YouTube subscribers/views/videos/upload dates/descriptions · Telegram subscribers, post dates, per-post views · Instagram followers/posts/bio · TikTok followers/likes/videos/bio · full live product catalogue and prices (WooCommerce Store API) · published course list (WP REST) · per-course enrolments and ratings · published post count · site pages list · refund policy text · promo-bar countdown source code · tracking tags present · footer payment logos · Linktree destinations · PayPal.Me handle resolves · Google Trends indices (EG and SA) · USD/EGP rate · Egypt GNI per capita · competitor positioning, prices, payment methods, refund policies and YouTube counts.

**UNVERIFIED — could not be confirmed and must not be treated as fact:**
- Facebook follower counts and posting cadence (login wall on every method attempted).
- LinkedIn follower count.
- Instagram and TikTok posting cadence (post dates not exposed in the payloads retrieved).
- Which payment gateways are actually enabled at Learn Simply's checkout (page is login-gated).
- Whether the PayPal.Me handle actually settles funds to an Egyptian account.
- Units sold of the 2,500 EGP all-courses bundle.
- Whether Ahmed still has an active Udemy instructor account (search results conflated him with a different Ahmed Adel; Udemy is Cloudflare-blocked).
- The Dubai address in the site footer — placeholder or a real entity.
- Egypt-specific 3-D Secure/OTP failure rates; card-limit failure rates at the 2,500 EGP tier.
- valU/Sympl merchant discount rates for Egyptian digital-only merchants (not published).
- Whether Tabby/Tamara onboard digital-goods merchants (one secondary source says they refuse; unconfirmed on their own terms).
- Elzero and Codezilla refund terms (neither publishes one); Elzero/Hsoub/Programming Advices checkout rails; all Satr pricing; Mahara-Tech and Satr YouTube presence.

**Sources that disagreed, both reported:** Egypt financial inclusion (CBE 76.3% vs World Bank Findex 43.1%) · Egyptian POS terminal count (CBE 1.3M vs IBS Intelligence ~258,000) · Fawry outlet count (194k/250k/300k/395.7k) · active e-wallets (NTRA 46.3M vs CBE 55.5M) · Codezilla subscribers (1.1M live vs 631K self-reported) · USD/EGP (50.8639 vs 50.8944).
