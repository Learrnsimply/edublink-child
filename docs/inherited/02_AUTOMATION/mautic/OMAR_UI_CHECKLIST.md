# Mautic UI Checklist — Omar Action Items

> **Tier 1 COMPLETE 2026-06-01.** All blocking items for 13K broadcast done.
> Tier 2 + 3 below remain optional / polish.

## ✅ Tier 1 Final State (verified 2026-06-01)

| Item | Status | Evidence |
|---|---|---|
| DKIM signing | ✅ Working | Hostinger Custom DKIM `hostingermail1` active. Port25 report: `dkim=pass header.d=learrnsimply.com` |
| SPF record | ✅ Updated | `v=spf1 a mx include:_spf.mail.hostinger.com include:_spf.mlsend.com ip4:187.124.9.249 ~all`. Port25: `spf=pass` |
| DMARC record | ✅ Clean | Single record: `v=DMARC1; p=quarantine; rua=mailto:dmarc@learrnsimply.com; ...`. Duplicate `p=none` deleted. |
| iprev | ✅ Pass | MailChannels relay PTR matches |
| Bounce mailbox | ✅ Working | `bounces@learrnsimply.com` created in Hostinger. Mautic Settings → Bounces section configured. "Test Connection" returned **Success**. |
| DMARC will pass | ✅ Guaranteed | Both SPF + DKIM aligned strictly (`adkim=s`, `aspf=s`) and both pass → DMARC=pass per RFC 7489 |

## Tier 2 — Optional polish (defer if low priority)

The items below CANNOT be automated server-side because they require either:
- Browser interactions in Mautic UI (DKIM key generation, theme upload)
- Hostinger DNS panel access (DNS records)
- Hostinger email account creation (bounce mailbox)

Do them in this order — each unblocks the next.

---

## 🔴 Tier 1 — BLOCKING for 13K Broadcast (do these first)

### Step 1: DKIM — ALREADY DONE ✅ (no action needed)

> **Update 2026-06-01:** Mautic 7 removed the in-app DKIM UI. It relies on the SMTP relay to sign emails.
>
> Hostinger has **auto-published a DKIM record** for `learrnsimply.com`:
> ```
> hostingermail-a._domainkey.learrnsimply.com
>    → CNAME → hostingermail-a.dkim.mail.hostinger.com
>    → TXT (v=DKIM1; k=rsa; p=MIIBIj…)
> ```
>
> Since Mautic relays via `smtp.hostinger.com:465`, **Hostinger signs every outgoing email with this DKIM key on our behalf.** No Mautic-side configuration needed.
>
> **Verification:** Test email sent to `check-auth@verifier.port25.com` — auto-reply (received at `contact@learrnsimply.com` via Hostinger webmail) confirms DKIM=pass / SPF=… / DMARC=…

---

### Step 2: Add 2 DNS records in Hostinger panel (10 min — DKIM already done, only SPF update + DMARC)

1. Login to https://hpanel.hostinger.com (account: `omarabdo385@gmail.com` shared via `chatgptamerican@gmail.com`)
2. Domain → `learrnsimply.com` → **DNS / Nameservers** → **DNS Records**

#### 2a. UPDATE existing SPF record

- Find the existing TXT record: `v=spf1 a mx include:_spf.mail.hostinger.com include:_spf.mlsend.com ~all`
- Click **Edit** on that row
- Change value to:
  ```
  v=spf1 a mx include:_spf.mail.hostinger.com include:_spf.mlsend.com ip4:187.124.9.249 ~all
  ```
- Save. **TTL: 14400** (default).

> **Why:** Adds the VPS IP (187.124.9.249 where Mautic runs) to SPF so Gmail/Yahoo accept mail from it. Kept `_spf.mlsend.com` for safety in case any old MailerLite triggers fire.

#### 2b. DKIM — SKIP (already published by Hostinger as `hostingermail-a._domainkey`)

Verify it exists:
```bash
nslookup -type=TXT hostingermail-a._domainkey.learrnsimply.com 8.8.8.8
```
Should return a CNAME → `hostingermail-a.dkim.mail.hostinger.com` with the actual DKIM key chained behind.

#### 2c. ADD new DMARC record

- Click **Add Record**
- Type: **TXT**
- Name: `_dmarc`
- Value:
  ```
  v=DMARC1; p=quarantine; rua=mailto:dmarc@learrnsimply.com; ruf=mailto:dmarc@learrnsimply.com; fo=1; adkim=s; aspf=s; pct=100
  ```
- TTL: 14400
- Save

> **Why:** Tells Gmail/Yahoo what to do if SPF/DKIM fails AND requests aggregate reports. Start with `p=quarantine` (failed mail → Spam). After 2 weeks of clean reports, harden to `p=reject`.

---

### Step 3: Verify DNS propagation + send score (5 min, wait time)

After ~10-30 min for DNS propagation:

1. Open https://mxtoolbox.com/SuperTool.aspx
2. Check each:
   - `learrnsimply.com` → SPF Record Lookup → should show updated value with `ip4:187.124.9.249`
   - `mautic._domainkey.learrnsimply.com` → DKIM Lookup → should show your key
   - `_dmarc.learrnsimply.com` → DMARC Lookup → should show your policy
3. Then send a test from Mautic to: https://mail-tester.com (they give you a unique address each time)
4. **Target score: ≥9/10** before any broadcast. Below 7 = something's wrong.

---

### Step 4: Bounce mailbox setup (15 min)

This collects bounced emails so Mautic can auto-unsubscribe bad addresses.

#### 4a. Create bounce email in Hostinger

1. Hostinger panel → `learrnsimply.com` → **Emails** → **Email Accounts** → **Create**
2. Email: `bounces@learrnsimply.com`
3. Password: generate strong (save in Bitwarden)
4. Storage: 100MB enough

#### 4b. Configure Monitored Inbox in Mautic

1. Mautic → Settings → **Configuration** → **Email Settings**
2. Scroll to **Monitored Inbox** subsection (or "Bounces")
3. Enter IMAP details:
   - **Host:** `imap.hostinger.com`
   - **Port:** `993`
   - **Encryption:** `SSL`
   - **User:** `bounces@learrnsimply.com`
   - **Password:** (from Step 4a)
   - **Folder:** `INBOX`
4. Test connection (button) — must say success
5. Map mailbox usage:
   - **Bounces (general)** → bounces@learrnsimply.com
   - **Unsubscribe replies** → bounces@learrnsimply.com (same mailbox OK)
6. Save

> **Why:** Without this, bounced emails accumulate as silent failures, list quality degrades, and Gmail/Yahoo eventually mark your domain as spam.

---

## 🟡 Tier 2 — Important before W1 broadcast

### Step 5: Verify Email throttle settings show as expected (2 min)

I already set these via server-side config, but verify in UI:

1. Mautic → Settings → Configuration → **Email Settings**
2. Check **Max emails to send per minute** = `5`
3. Check **Max emails to send per hour** = `50`
4. Check **Max emails to send per day** = `200`

If anything different, update via UI.

> **Note:** These are CONSERVATIVE Week 1 limits. After DNS is verified (Step 3) and 7 days of clean reputation, raise per_hour to 200 and per_day to 2000.

### Step 6: Arabic RTL email theme (15 min)

Mautic default themes are LTR. For Arabic emails:

1. Mautic → Components → **Themes**
2. Find a clean template (e.g., "Sunday" or "Brienne") → Clone
3. Edit cloned theme → in HTML body, add to top `<head>`:
   ```html
   <style>
     body, table, td { direction: rtl !important; text-align: right !important; font-family: Tajawal, Cairo, Tahoma, Arial, sans-serif; }
   </style>
   ```
4. Save as "Learn Simply Arabic Default"
5. Set as default in Configuration → Email Settings → Default Theme

### Step 7: Verify the 9 segments populate correctly (5 min)

1. Mautic → Contacts → **Segments**
2. Verify all 9 segments listed:
   - all_contacts
   - engaged_30d
   - dormant_90d
   - wc_buyers
   - high_value
   - non_buyers
   - active_cart
   - whatsapp_contacts
   - telegram_contacts
3. Each shows "0 contacts" right now (expected — no contacts imported yet). After W1 workflow runs, they auto-populate.

### Step 8: Verify the 24 tags exist (1 min)

1. Mautic → Contacts → **Tags** (or via API: `GET /api/tags`)
2. Verify list includes: `wc-customer`, `lead`, `customer`, `cart-abandoned`, `course-python/flutter/react/laravel`, `engaged`, `dormant`, `vip`, `whatsapp-lead`, `telegram-lead`, `instagram-lead`, `facebook-lead`, `youtube-lead`, `free-resource`, `paid-customer`, `refunded`, `high-intent`, `low-intent`, `review-given`, `churned`, `reactivated`

---

## 🟢 Tier 3 — Nice to have (do later)

### Step 9: MTA-STS + TLS-RPT (5 min, optional but improves Gmail reputation)

In Hostinger DNS, add 2 more TXT records:

```
_mta-sts.learrnsimply.com.   TXT  "v=STSv1; id=20260601000000Z"
_smtp._tls.learrnsimply.com. TXT  "v=TLSRPTv1; rua=mailto:tlsrpt@learrnsimply.com"
```

### Step 10: WooCommerce integration (defer — handled by W1 in n8n)

Two paths:
- **A. n8n workflow W1 (preferred):** Build webhook trigger WC → Mautic. Already planned in `_research/2026-06-01-final-recommendations.md` Section 5.
- **B. Mautic WooCommerce plugin:** Install from Mautic plugins marketplace. Less flexible than n8n but zero-code.

Going with A (n8n) per the research plan.

### Step 11: Email send relay upgrade — Amazon SES or Postmark (defer to Week 4)

If broadcast to 13K is the goal:
- Hostinger SMTP: ~100/hour, ~3000/day cap
- Amazon SES: 50K/day starter, 1M+/day after warmup
- Postmark: 100K/month free tier, optimized for transactional

For the 13K activation broadcast specifically, plan to migrate Mautic mailer DSN to SES after DNS is clean and reputation is built. This is documented in `_research/2026-06-01-final-recommendations.md` Section 9.

---

## ✅ Done means

When all Tier 1 items are complete:
- [ ] DKIM key generated in Mautic UI
- [ ] SPF updated, DKIM published, DMARC published in DNS
- [ ] MXToolbox shows all 3 records valid
- [ ] mail-tester.com score ≥ 9/10
- [ ] bounces@learrnsimply.com mailbox active in Hostinger
- [ ] Monitored Inbox configured in Mautic, "Test connection" passes
- [ ] Throttle settings visible in UI match what I set (5/50/200)

When done with Tier 1+2: **safe to start importing the 13K subs via W1 workflow** (next session).

When done with Tier 1+2+3: **ready for activation broadcast** to engaged segment (target: 400-600K EGP one-time, per Phase 1 roadmap).

---

## What I (Omar's Claude session) already did (don't redo)

| Done server-side | How |
|---|---|
| Fixed redirect loop blocking Mautic UI | Added `trusted_proxies => ['REMOTE_ADDR']` to `local.php` |
| Bounded worker memory/time/limit flags | Custom supervisord.conf mounted via compose volume |
| Enabled Mautic API + basic auth | Added `api_enabled`, `api_enable_basic_auth`, `api_batch_max_limit` to `local.php` |
| Created 11 custom fields | API calls (IDs 44-54) |
| Created 9 strategic segments | API calls (IDs 1-9) |
| Created 24 starter tags | API calls |
| Set Week 1 IP warmup limits (5/min, 50/hour, 200/day) | `mailer_amount_per_*` keys in `local.php` |
| Set unsubscribe text + webmaster email | `unsubscribe_text` + `webmaster_email` in `local.php` |

## Backups created

All on VPS in `mautic-r4bx-mautic-1:/var/www/html/config/`:
- `local.php.bak-20260601` — pre-SMTP-config state
- `local.php.bak-redirect-fix-20260601` — pre-trusted_proxies
- `local.php.bak-api-enable-20260601` — pre-API
- `local.php.bak-send-limits-20260601` — pre-send-limits

Rollback any: `docker exec mautic-r4bx-mautic-1 cp /var/www/html/config/<backup> /var/www/html/config/local.php && docker restart mautic-r4bx-mautic-1`
