# Learn Simply — CRM / Email / Automation State of Play

**measured_at:** 2026-08-24T01:47Z → 2026-08-24T01:56Z (UTC). All figures re-measured live in this window.
**Scope:** n8n (10 workflows), Mautic, Brevo, Evolution/WhatsApp agent "عمر", WooCommerce order outcomes, VPS host.
**Method:** READ-ONLY. SELECT-only SQL, GET-only HTTP. No workflow activated/deactivated/edited, no email or WhatsApp message sent, no campaign published, no contact or segment modified.

---

## 0. Headline

| # | Finding | Evidence |
|---|---|---|
| 1 | **The WhatsApp agent has been dead for 68 days.** Session dropped 2026-06-17T20:55:13Z with `device_removed`. Six "ACTIVE" workflows have processed nothing since. | Evolution `connectionStatus: close`, `disconnectionReasonCode: 401` |
| 2 | **W4 Cart Recovery is the only automation producing revenue.** 525 emails → 19 paid orders → **11,196 EGP** over 61 days. Idempotency holds at order level. | `omar.recovery_log` + `wp_wc_orders` |
| 3 | **Email is a dormant asset, not a channel.** 9 sends in the last two months, all transactional. Zero campaigns exist. **97.1% of the list has never been emailed.** | `email_stats`, `campaigns` (empty) |
| 4 | **The 48% cancellation rate is untouched.** `hold_stock_minutes = 1440` still set on a digital-product store. 249 cancelled orders / 173,265 EGP in 60 days. | `wp option get woocommerce_hold_stock_minutes` |
| 5 | **DNS is healthier than the docs claim** — DMARC `p=quarantine` strict is live. But **SPF does not authorize Brevo**, so DMARC survives on DKIM alone. | `dig` |
| 6 | **VPS is healthy.** 12/12 containers up 2 months, disk 10%, no restarts. | `docker ps`, `df -h`, `free -m` |

---

## 1. Execution health

### Measurement caveat — read this before trusting any 60-day claim

n8n prunes execution records at **336 hours (14 days)**:

```
$ ssh root@187.124.9.249 'docker exec n8n-n8n-1 printenv | grep EXECUTIONS'
EXECUTIONS_DATA_PRUNE=true
EXECUTIONS_DATA_PRUNE_MAX_COUNT=10000
EXECUTIONS_DATA_MAX_AGE=336
```

**A 60-day execution history does not exist and cannot be reconstructed.** The retained window is 2026-08-09 → 2026-08-24. For lifetime figures I used `workflow_statistics`, a cumulative counter table that pruning does not touch. Both are reported below and labelled.

### A. Retained window — 14 days (2026-08-09 → 2026-08-24)

*Definition:* rows in `execution_entity`. *Source:* `docker exec n8n-postgres-1 psql -U n8n -d n8n`. *Scope:* all 10 workflows.

| Workflow | Active | Execs | Success | Error | Last execution (Cairo) |
|---|---|---:|---:|---:|---|
| W4 — Cart Recovery | yes | 633 | 633 | 0 | 2026-08-23 22:40:00 |
| W1 — WC → Mautic Sync | yes | 108 | 107 | 1 | 2026-08-23 14:28:08 |
| W2 — Dart Waitlist | yes | 3 | 3 | 0 | 2026-08-18 19:48:02 |
| W3 — omar-inbound | **yes** | **0** | 0 | 0 | — |
| W3b — omar-alert | **yes** | **0** | 0 | 0 | — |
| W3c — omar-kb-search | **yes** | **0** | 0 | 0 | — |
| W3t — kb_search tool | **yes** | **0** | 0 | 0 | — |
| W3t-order — order_lookup | **yes** | **0** | 0 | 0 | — |
| W3t-mautic — mautic_upsert | **yes** | **0** | 0 | 0 | — |
| W3d — error handler | no | 0 | 0 | 0 | — |

Last 24h (independent check via `/api/v1/executions`): W4 = 45 success, W1 = 3 success, everything else zero. W4's 45/day exactly matches its cron.

### B. Lifetime — `workflow_statistics` (survives pruning)

| Workflow | Success | Error | Error rate | Last production event (Cairo) |
|---|---:|---:|---:|---|
| W4 — Cart Recovery | 2,722 | 0 | 0.0% | 2026-08-23 22:40:01 |
| W1 — WC → Mautic Sync | 625 | 10 | 1.6% | 2026-08-23 14:28:10 |
| W2 — Dart Waitlist | 591 | 0 | 0.0% | 2026-08-18 19:48:06 |
| W3 — omar-inbound | 954 | 7 | 0.7% | **2026-06-17 23:55:30** |
| W3c — omar-kb-search | 222 | 0 | 0.0% | **2026-06-17 22:25:32** |
| W3t — kb_search tool | 163 | 0 | 0.0% | **2026-06-17 22:25:32** |
| W3b — omar-alert | 75 | 0 | 0.0% | **2026-06-17 23:55:04** |
| W3t-order | 15 | 0 | 0.0% | **2026-06-17 23:27:07** |
| W3t-mautic | 13 | 0 | 0.0% | **2026-06-17 23:27:04** |
| W3d — error handler | *(no rows)* | — | — | **never executed** |

### C. Flags

**Six workflows are "active" but have executed nothing for 68 days** — W3, W3b, W3c, W3t, W3t-order, W3t-mautic. Every one is downstream of the dead WhatsApp session (§5). They are not broken; they are starved of input. The green "Active" badge in the n8n UI is actively misleading here.

**No workflow has a high error rate.** The worst is W1 at 1.6% lifetime (10 errors / 635 runs).

**Most common error (W1):** only 1 of the 10 lifetime errors survives pruning:

```
exec 4984 | 2026-08-15T19:59:35Z | node "Mautic Upsert Contact"
          | "Bad request - please check your parameters"
```

A Mautic API 400 on contact upsert — the same failure class as the documented `source_channel` SELECT-field rejection. Whether all 10 share this cause is **UNVERIFIED** (9 pruned).

### D. W3d is OFF — what that actually costs

`errorWorkflow` wiring, measured per workflow:

| Workflow | errorWorkflow |
|---|---|
| W3, W3b, W3c, W4 | `YktkjLMI12YUGWfc` (W3d) |
| **W1, W2, W3t, W3t-order, W3t-mautic** | **none** |

Two distinct blind spots, and the second is the bigger one:

1. **W3d itself.** In n8n an Error Trigger workflow does not need to be active to fire, so `active=false` is not automatically fatal. But `workflow_statistics` has **zero rows for W3d** while all nine other workflows have rows — and W3 logged 7 production errors in June. The cumulative counter says W3d has never run. Whether that is because it is disabled or because error-workflow invocations are not counted in `workflow_statistics` is **UNVERIFIED**; either way there is no evidence any error alert has ever reached Telegram.
2. **The unwired five.** W1, W2 and the three tool wrappers have **no error workflow at all**. W1's 10 errors were silent by construction — no alert path existed. This is the real gap, and turning W3d back on does not close it.

**Net:** the entire stack has been running for 68 days with no working error alerting. The reason nobody noticed the WhatsApp agent die is that nothing was watching. A dropped session is not an execution error, so even a perfectly wired error handler would not have caught it — that needs a liveness check, not an error trigger.

---

## 2. W4 Cart Recovery — real work or real damage?

**Verdict: real work, with a contained blast radius and one recurring annoyance.**

### Schedule (current, live)

```json
{"rule":{"interval":[{"field":"cronExpression","expression":"*/20 8-22 * * *"}]}}
timezone: Africa/Cairo
```

Every 20 minutes, 08:00–22:59 Cairo = 45 runs/day. Confirmed against measurement: 633 execs / 14 days = 45.2/day; 45 in the last 24h. **The 1-minute test cron from the prior incident is gone.**

### Idempotency — the fix held

```sql
recovery_log_pkey | p | PRIMARY KEY (order_id)
```

Idempotency is enforced by the **database**, not by workflow logic. Duplicate order rows: **0**. The table is populated: **271 rows**, first 2026-06-24 19:01, last write 2026-08-23 16:00. Each order can receive at most one R1 and one R2. The 4-duplicate incident class cannot recur for a given order.

### Actual send volume

*Definition:* non-null `r1_sent_at` / `r2_sent_at` in `omar.recovery_log`. *Period:* 2026-06-24 → 2026-08-23 (61 days). *Source:* `omar.recovery_log` on `omar-pgvector`.

| Metric | Value |
|---|---:|
| Orders tracked | 271 |
| Distinct recipient emails | 201 |
| R1 (first reminder) sent | 267 |
| R2 (last chance) sent | 258 |
| **Total emails sent** | **525** |
| Peak day (2026-07-18) | 41 |

### Duplicate-send exposure — order-safe, person-noisy

No order is emailed twice. But **45 of 201 people placed more than one pending order**, and each order gets its own pair:

| Recipient | Orders | Emails received |
|---|---:|---:|
| `learnsimplyhost@gmail.com` | 7 | **14** |
| `faresali4588@gmail.com` | 6 | **12** |
| `emad.halqa2020@gmail.com` | 5 | 9 |
| `saqraldahan118@gmail.com` | 4 | 8 |
| `khaldhamd2019@gmail.com` | 4 | 8 |
| `mohammedelsaqqa189@gmail.com` | 4 | 8 |

Worse in bursts — same person, same day: `faresali4588@gmail.com` received **6 R1 emails on 2026-07-13**; `khaldhamd2019@gmail.com` got 4 on 2026-08-03.

Two things worth naming:

- `learnsimplyhost@gmail.com` is the **client-side Hostinger account address** (it owns the domain per `.env` §Hostinger API). The top recipient of the cart-recovery sequence is almost certainly Ahmed's own test checkouts, not a customer. 14 of the 525 emails are self-inflicted.
- Six recovery emails in one day to one person is a spam-complaint risk on a free-tier ESP where the sending reputation is shared. Not damage yet — no complaints are measurable (§4) — but it is the sharp edge.

**Recommendation (not applied):** add a per-recipient throttle in `Decide Sends` — skip if that email received any recovery mail in the last 24h. The `Load Sent Log` query already pulls 3 days of rows, so the data is in hand; it is a filter, not a schema change.

### Data gap in the log

```
rows=271 | total_notnull=0 | currency_notnull=0 | course_notnull=0 | email_notnull=271
```

`total`, `currency` and `course` are **NULL/empty in all 271 rows** — the workflow writes the columns' definitions but never their values. Revenue attribution had to be reconstructed from WooCommerce instead. Worth fixing so the log is self-sufficient.

### Attributable revenue

*Definition:* current WooCommerce status of the 271 orders W4 emailed. *Source:* `wp db query` on `wp_wc_orders` (HPOS active). *measured_at:* 2026-08-24T01:52Z.

| Current status | Orders | Value (EGP) |
|---|---:|---:|
| cancelled | 246 | 171,914 |
| **completed** | **18** | **10,197** |
| **processing** | **1** | **999** |
| pending | 2 | 1,049 |
| failed | 1 | 550 |
| trash | 1 | 1,599 |

**19 of 271 recovery-targeted orders were paid = 11,196 EGP. Recovery rate 7.0%.**

Timing test — every paid order compared to its own first reminder:

- **19 of 19 were paid *after* the first recovery email.** Zero were paid before it.
- **10 of 19 were paid within 6 hours** of an email. Four within one hour: order 42151 paid **8 minutes** after R1, 41166 after **8 minutes**, 40904 after **20 min**, 41107 after **39 min**.

**Attribution honesty:** this is strong *correlational* evidence, not proof. There is no control group, no holdout, and the UTM parameters W4 stamps on the pay link (`utm_source=brevo&utm_campaign=…`) are not recorded anywhere I can query — so click-through cannot be confirmed. **Label the 11,196 EGP as timing-consistent but causally UNVERIFIED.** The sub-hour cluster is hard to explain any other way, but "hard to explain otherwise" is not measurement.

### The context that dwarfs W4

All orders created since 2026-06-25 (last 60 days):

| Status | Orders | Value (EGP) |
|---|---:|---:|
| completed | 242 | 135,857 |
| **cancelled** | **249** | **173,265** |
| failed | 25 | 12,995 |
| other | 6 | 6,546 |

**More order value is cancelled than completed. The cancellation rate is 48.0%** (249/519 excluding 3 trashed; 47.7% if trash is counted — 249/522).

And the known root cause is still armed:

```
$ wp option get woocommerce_hold_stock_minutes  → 1440
$ wp option get woocommerce_manage_stock        → yes
```

WooCommerce auto-cancels unpaid orders after 1,440 minutes (24h) because stock is being "held" — on a store selling **digital courses**, where there is no stock to hold. This matches the diagnosis already in memory (`project_dart_launch_results_and_payment_diagnosis`) and it has **not been fixed**.

**The proportion matters.** W4 recovered 11,196 EGP in 61 days. Disabling `hold_stock_minutes` addresses a 173,265 EGP cancellation pool directly. W4 is bailing; the hole is still open. This is a one-setting change and does not require Ahmed — Omar has wp-cli access.

---

## 3. Mautic reality check

*Source:* `docker exec mautic-r4bx-db-1 mysql -umautic mautic`, cross-checked against the REST API. *measured_at:* 2026-08-24T01:51Z.

### Contacts

| Metric | Value |
|---|---:|
| Total contacts | **14,630** |
| Distinct email addresses | 14,314 |
| Contacts with NULL/empty email | 316 |
| First added / last added | 2026-06-01 12:30 / 2026-08-23 06:44 |

Growth by month: Jun **14,358** · Jul **142** · Aug **130**. W1 is still feeding it daily — this part of the stack genuinely works.

### Segments

| ID | Alias | Members |
|---:|---|---:|
| 1 | allcontacts | 14,314 |
| 6 | nonbuyers | 13,808 |
| 11 | reengagementwave1 | 13,709 |
| 4 | wcbuyers | **506** |
| 10 | dartwaitlist | **421** |
| 2 | engaged30d | 26 |
| 3 | dormant90d | 0 |
| 5 | highvalue | 0 |
| 7 | activecart | 0 |
| 8 | whatsappcontacts | 0 |
| 9 | telegramcontacts | 0 |

`wcbuyers` has climbed 0 → 506 (W1 now populates the fields the segment filters on). `dartwaitlist` reached 421 from the 74 in the docs. Five segments remain permanently empty — `whatsappcontacts` never populated even while the agent was live, because the agent captured **zero** email addresses (§5).

### Emails

| ID | Name | Published | Sent | Opens | Open rate | Last actual send |
|---:|---|---|---:|---:|---:|---|
| 1 | Re-engagement Wave 1 - 13K | no | 4 | 3 | — | 2026-06-03 03:34 |
| 2 | Dart 01 — welcome | yes | 648 | 310 | 47.5% | **2026-08-18 16:48** |
| 3 | Dart 02 — announce [DRAFT] | no | 0 | 0 | — | 2026-06-06 10:12 |
| 4 | Dart 03 — launch day | yes | 404 | 105 | 26.0% | 2026-06-18 17:05 |
| 5 | Dart 04 — last chance | yes | 87 | 30 | 34.5% | 2026-06-22 23:54 |
| 10 | Dart — Warmup Value | yes | 270 | 81 | 30.0% | 2026-06-09 17:04 |

### Aggregate send reality

| Metric | Value |
|---|---:|
| Total sends, all time | **1,423** |
| Distinct recipients ever emailed | **425** |
| Total opens | 530 |
| Distinct openers | 292 |
| Recorded failures | **0** |
| Click events (`page_hits` source=email) | 64 |
| Distinct clickers | 27 |
| First send / **last send** | 2026-06-03 00:14 / **2026-08-18 16:48** |

Sends by month: **Jun 1,414 · Jul 6 · Aug 3.**

### Campaigns

```sql
SELECT id, name, is_published, date_added FROM campaigns;
-- 0 rows
```

**No campaign has ever been created.** There is no drip, no nurture, no automation on the Mautic side at all.

### Verdict: dormant asset, not a live channel

The last 66 days produced **9 email sends**, every one a `Dart 01` welcome fired by W2 when someone joined the waitlist (`source=api`, one per W2 execution). That is a transactional autoresponder, not marketing.

Email is **not** a revenue channel today. It is a configured, verified, hardened engine with nobody's hand on the throttle. The 425 people ever reached open at 68.7% — the asset is credible. It is simply idle.

---

## 4. Brevo capacity and usage

### Blocked: Brevo-side statistics are UNVERIFIED

```
$ curl -H "api-key: ..." https://api.brevo.com/v3/account
{"message":"We have detected you are using an unrecognised IP address
 2a02:4780:7:f434::1 ...","code":"unauthorized"}
```

Retried from the VPS (`187.124.9.249`) — **also rejected**. The Brevo account has an IP allowlist and neither this workstation nor the sending VPS is on it.

**Therefore UNVERIFIED from Brevo's side:** plan/daily limit, bounce counts, spam-complaint rate, blocklist size, domain-auth status as Brevo sees it.
**To unblock (Omar, one action):** add the IP at `https://app.brevo.com/security/authorised_ips`, or read the numbers off the dashboard.

### What *is* verified

**The credential is alive.** SMTP AUTH handshake completed, connection closed, **no message sent**:

```
SMTP AUTH: SUCCESS -> credential is ALIVE (no message sent)
host=smtp-relay.brevo.com:587 STARTTLS
```

**Brevo is genuinely the live sender.** From Mautic's running config:

```php
'mailer_transport' => 'smtp',
'mailer_host'      => 'smtp-relay.brevo.com',
'mailer_user'      => 'adc76f001@smtp-brevo.com',
'mailer_from_email'=> 'contact@learrnsimply.com',
```

W4's `Send Recovery Email` node uses credential `YTlcYxVu93s62OuL` = "SMTP — Brevo (Learn Simply)". **Both senders route through Brevo.** No SES, no Hostinger SMTP anywhere in the live path.

### Volume actually sent (measured from our side)

| Source | Period | Emails |
|---|---|---:|
| W4 Cart Recovery | 2026-06-24 → 2026-08-23 | 525 |
| Mautic | 2026-07-01 → 2026-08-24 | 9 |
| **Last 30 days (2026-07-25 → 2026-08-24), combined** | | **266–275** |

Last-30-days figure is reproducible: W4 sends summed per-day over the window = **266** (`sum(r1)+sum(r2)` from `recovery_log`), plus **≤9** Mautic sends (the Jul+Aug total, an upper bound since some Jul sends predate 2026-07-25) = **266–275**.

Peak single day: **41** (2026-07-18, W4). Against the documented 300/day free tier that is **~14% of capacity at peak**, and roughly **9 emails/day on average**. Capacity is not a constraint; demand is.

### Domain authentication — verified by DNS, not by notes

*Command:* `dig +short TXT <name> @8.8.8.8`, *measured_at:* 2026-08-24T01:50Z.

| Record | Live value | Assessment |
|---|---|---|
| **SPF** | `v=spf1 include:_spf.mail.hostinger.com include:_spf.reach.hostinger.com include:amazonses.com ~all` | ⚠️ **Brevo not included** |
| **DMARC** | `v=DMARC1; p=quarantine; rua=mailto:dmarc@learrnsimply.com; ruf=…; fo=1; adkim=s; aspf=s; pct=100` | ✅ **Present, strict** |
| **DKIM brevo1** | CNAME → `b1.learrnsimply-com.dkim.brevo.com`, key resolves (`k=rsa;p=MIIBIjANBgkq…`) | ✅ |
| **DKIM brevo2** | CNAME → `b2.learrnsimply-com.dkim.brevo.com`, key resolves | ✅ |
| DKIM hostingermail-a | CNAME → `hostingermail-a.dkim.mail.hostinger.com` | ✅ |
| brevo-code | *(empty)* | fine post-verification |
| MX | `mx1/mx2.hostinger.com` | ✅ |
| NS | `ns1/ns2.dns-parking.com` | Hostinger DNS |

**Two corrections to the repo's notes.** First, DMARC is **not** stuck at `p=none` and is **not** "unstorable on Hostinger DNS" — the full record, semicolons and all, is live and hardened at `p=quarantine` with strict alignment. Second, SPF was rebuilt and includes `amazonses.com`.

**But the live gap:** SPF authorizes Hostinger and Amazon SES — **the two senders that are not being used** — and omits `spf.brevo.com`, the sender that actually delivers everything. Combined with `aspf=s` (strict), Brevo mail **fails SPF alignment** and DMARC passes on **DKIM alone**. The DKIM CNAMEs resolve correctly today, so mail is being delivered. It is a single point of failure with no second leg: if either Brevo DKIM CNAME breaks, `p=quarantine` sends the mail to spam immediately.

**Recommendation (not applied):** add `include:spf.brevo.com` to the SPF record. One DNS edit, restores the second authentication leg. Optionally drop `amazonses.com` — SES was denied and is unused.

### Deliverability signals — mostly unmeasurable

| Signal | Value | Note |
|---|---:|---|
| Mautic recorded failures | **0** / 1,423 sends | implausible for a 3-year-old list |
| DNC (do-not-contact) entries | **3** | |
| Monitored-inbox / bounce config rows | **0** | |

Zero bounces across 1,423 sends to a list imported from 3-year-old WordPress registrations is not a clean-list signal — it is a **not-measuring signal**. The bounce mailbox that `.env` records as configured (`bounces@learrnsimply.com`, IMAP test passed 2026-06-01) has **no rows in `plugin_integration_settings`**. Bounce processing did not survive whatever container recreation followed. **Real bounce and complaint rates are UNVERIFIED**, and will stay that way until either the IMAP monitor is restored or the Brevo dashboard is read.

This matters most for §6: there is currently **no mechanism that would tell you a broadcast was going badly** while it went badly.

---

## 5. WhatsApp agent "عمر" — dead, with a precise cause and time of death

### It is dead. Plainly.

```json
{
  "name": "omar-support",
  "connectionStatus": "close",
  "ownerJid": "201030127228@s.whatsapp.net",
  "integration": "WHATSAPP-BAILEYS",
  "disconnectionReasonCode": 401,
  "disconnectionObject": {
    "error": {"data": {"tag": "conflict", "attrs": {"type": "device_removed"}}},
    "date": "2026-06-17T20:55:13.720Z"
  },
  "disconnectionAt": "2026-06-17T20:55:13.722Z"
}
```

*Source:* `GET /instance/fetchInstances` on Evolution v2.3.7, run twice (container-internal and via `https://evolution.learrnsimply.com`) — identical.

**Dead since 2026-06-17T20:55:13Z — 68 days as of measurement.** It is **not** receiving messages today.

**Cause: `device_removed`, not a ban.** The linked device was removed from the WhatsApp account on the phone — Settings → Linked Devices → log out, or a phone reset/number re-registration. This is the *good* failure mode: the feared Baileys ban would show as a different error, and a ban would make re-linking impossible. **Re-linking by scanning a fresh QR should restore the agent.** The container has been up 2 months and never attempted a reconnect, because `device_removed` is terminal for that session.

Corroborating evidence, independently: W3's last production event is **2026-06-17 23:55:30 Cairo** = 20:55:30 UTC — the same minute the socket closed. Last row in `omar.messages` is **2026-06-17 23:55:26 Cairo**. Three independent sources agree.

### Volume and value while it lived

*Period:* 2026-06-10 08:54 → 2026-06-17 23:55 Cairo — **8 days**. *Source:* `omar` schema on `omar-pgvector`.

| Metric | Value |
|---|---:|
| Messages logged | **1,381** |
| — from customers | 750 |
| — from agent | 631 |
| Distinct phone numbers | **65** |
| Contacts created | 65 |
| **Contacts with an email captured** | **0** |
| Escalations raised | **50** |

Evolution's own counters (broader — includes traffic before the agent and messages not processed by W3): **3,979 messages, 555 contacts, 530 chats.**

Daily trend:

| Date | Messages | Distinct contacts |
|---|---:|---:|
| 2026-06-10 | 279 | 8 |
| 2026-06-11 | 220 | 12 |
| 2026-06-12 | 151 | 9 |
| 2026-06-13 | 148 | 10 |
| 2026-06-14 | 52 | 6 |
| 2026-06-15 | 81 | 10 |
| 2026-06-16 | 212 | **19** |
| 2026-06-17 | 238 | 15 |

**It was growing when it died.** Contact count peaked on the last two days. This was not an experiment winding down — it was ramping, at ~8 distinct customers/day and rising.

### The escalation backlog is the buried problem

| Type | Priority | Count | Alerted |
|---|---|---:|---:|
| for_ahmed | normal | 12 | 11 |
| access | high | 8 | 7 |
| payment | normal | 6 | 5 |
| angry | **urgent** | **5** | 5 |
| certificate | normal | 5 | 5 |
| site_access | high | 4 | 3 |
| access | **urgent** | **3** | 3 |
| for_ahmed | high | 2 | 2 |
| site_access | normal | 2 | 2 |
| for_ahmed | **urgent** | **1** | 1 |
| payment_intl | high | 1 | 1 |
| access | normal | 1 | 1 |

```
unresolved | total
        50 |    50
```

**All 50 escalations are still `status = open`. Zero were ever resolved.** Nine are `urgent` — five of them tagged `angry`. **Five escalations (10%) were never alerted at all** (`alerted_at IS NULL`) — those customers were flagged as needing a human and no human was ever pinged.

These are real customers who asked for help in June and, as far as this database knows, never got it. The `resolved_at`/`resolved_by` columns exist and are entirely unused, which suggests no resolution workflow was ever built — the alert fires into Telegram and the DB row stays open forever.

### Configuration state (intact, waiting)

- Active prompt: **v10**, created 2026-06-17 23:20 Cairo — **35 minutes before the session died**. That version has essentially never been exercised in production.
- `Test Users Only` filter node: **`disabled: true`** → the public gate is open. Whenever the session is restored, the agent goes live to all customers immediately.
- Webhook, credentials, tool wrappers, RAG endpoint: all intact and active.

**Recovery is a QR scan, not a rebuild.** But note the sequencing risk: restoring the link re-opens a fully public agent, on a prompt version with no production track record, with 50 unresolved escalations behind it and no working error alerting (§1D). Worth re-enabling the test gate for one round before going public again.

---

## 6. The 13,711-contact list — asset or liability?

*Source:* `leads`, `email_stats`, `lead_donotcontact` in Mautic MySQL. *measured_at:* 2026-08-24T01:53Z.

### Re-verified count

**14,630** (not 13,711). The old figure is 2+ months stale; W1 has added ~920 since.

| Metric | Value | % of list |
|---|---:|---:|
| **Total contacts** | **14,630** | 100% |
| Distinct email addresses | 14,314 | 97.8% |
| Contacts with NULL/empty email | **316** | 2.2% |
| **Ever emailed** | **425** | **2.9%** |
| **Never emailed** | **14,205** | **97.1%** |
| **Ever opened** | 292 | 2.0% |
| Ever clicked | 27 | 0.2% |
| On do-not-contact | 3 | 0.02% |
| Recorded bounces | **0** | — |
| **NULL `last_active`** | **14,022** | **95.8%** |

### Reachability is unknown, not bad

Of those actually emailed, **292/425 = 68.7% opened** — an excellent rate. But that cohort is self-selected: it is mostly the Dart waitlist, people who had just typed their address into a form.

The other **14,205 contacts have never received a single email.** Their deliverability is not "poor" — it is **untested**. And with bounce processing dead (§4), the first real broadcast would not tell you how bad it was until reputation damage was already done.

Known-bad signal in the data: **54 contacts on `gamil.com`** (a `gmail.com` typo) — guaranteed hard bounces. Plus 316 with no address at all. Those alone are ~2.5% of the list that will fail on contact.

### Verdict: a real asset carrying a real, currently-unmeasured risk

It is **not** a liability — no complaint history, negligible DNC, 11,933 Gmail addresses from genuine course registrations. But it is not ready to mail, for three compounding reasons:

1. **No bounce/complaint measurement** — you would be flying blind through the exact phase where blindness is expensive (§4).
2. **Capacity mismatch** — 14,205 contacts at 300/day is **48 days** of continuous sending on the free tier, on shared Brevo IPs, with no warmup history (the last real volume was 1,414 emails in June).
3. **Cold by 3 years** — 95.8% have no engagement signal whatsoever.

**Order of operations, if this gets activated:** restore bounce processing → run list validation on the 14,205 to strip the `gamil.com` class and dead domains → fix SPF (§4) → staged warmup starting at a few hundred to the most recent registrations → only then scale. Broadcasting first and measuring after is the one path that can convert this asset into an actual liability.

---

## 7. VPS health

*Source:* `docker ps`, `df -h`, `free -m`, `uptime`. *measured_at:* 2026-08-24T01:47Z.

**Nothing is down. Nothing is restarting.** All 12 containers `Up 2 months`; every container with a healthcheck reports `healthy`.

| Container | Status | Image |
|---|---|---|
| omar-evolution | Up 2 months | evoapicloud/evolution-api:v2.3.7 |
| omar-pgvector | Up 2 months (healthy) | pgvector/pgvector:pg16 |
| omar-redis | Up 2 months (healthy) | redis:7-alpine |
| n8n-n8n-1 | Up 2 months | n8nio/n8n:latest (v2.22.5) |
| n8n-n8n-worker-1 | Up 2 months | n8nio/n8n:latest |
| n8n-postgres-1 | Up 2 months (healthy) | postgres:16-alpine |
| n8n-redis-1 | Up 2 months (healthy) | redis:7-alpine |
| mautic-r4bx-mautic-1 | Up 2 months (healthy) | mautic/mautic:latest |
| mautic-r4bx-mautic-worker-1 | Up 2 months | mautic/mautic:latest |
| mautic-r4bx-mautic-cron-1 | Up 2 months | mautic/mautic:latest |
| mautic-r4bx-db-1 | Up 2 months (healthy) | mysql:8.4 |
| traefik-traefik-1 | Up 2 months | traefik:latest |

| Resource | Value |
|---|---|
| Disk | 19 GB used / 193 GB — **10%** |
| Memory | 3,660 MB used / 15,992 MB — **12,332 MB available** |
| Swap | 0 (none configured) |
| Uptime | **83 days**, load 0.21 / 0.14 / 0.10 |

**SSL certificates — all auto-renewing correctly:**

| Host | Expires |
|---|---|
| learrnsimply.com | 2026-10-08 |
| mautic.learrnsimply.com | 2026-10-29 |
| n8n.learrnsimply.com | 2026-10-29 |
| evolution.learrnsimply.com | 2026-11-07 |

`.env` warns `MAUTIC_SSL_VALID_UNTIL=2026-08-29` — that note is stale by two renewal cycles. Traefik ACME is working; there is no cert risk.

One structural note: the WhatsApp agent died 68 days ago on a host that has been perfectly healthy the whole time. **Infrastructure monitoring would not have caught it, and did not.** Container health ≠ service liveness.

---

## 8. What is actually live vs what the docs claim

| Claim in `CLAUDE.md` / `ROADMAP.md` | Measured reality | Verdict |
|---|---|---|
| "مساعد واتساب عمر: **FULLY LIVE** للجميع على 201030127228" (ROADMAP, CLAUDE.md CURRENT STATE) | Session closed **2026-06-17T20:55:13Z** (`device_removed`). Zero messages in 68 days. | ❌ **Dead 68 days.** The single biggest doc/reality gap. |
| "W3 ACTIVE (28 نود) — أول محادثات حقيقية نجحت" | W3 is active in n8n and has **0 executions** since 2026-06-17. Now 38 nodes, not 28. | ⚠️ Active flag true, function zero |
| "13,711 contact في Mautic" | **14,630** (+920). | ⚠️ Stale by 2 months |
| "Brevo = PRIMARY sender … 300/يوم" | ✅ Confirmed in `local.php` + W4 credential; SMTP AUTH alive. Peak use 41/day = 14% of cap. | ✅ **Correct** |
| "DMARC مش بيتخزّن على Hostinger (الـ panel بيقطع TXT عند `;`)" | DMARC is live, **complete, with semicolons**: `p=quarantine … adkim=s; aspf=s; pct=100`. | ❌ **Disproved** — fixed since |
| "MAUTIC_DNS_SPF_STATUS = RESET BY MIGRATION — hardening wiped" | SPF rebuilt and live with `amazonses.com`. But **omits Brevo**, the only real sender. | ⚠️ Half-right; new gap |
| "SES … appeal in Phase 002 for 13K capacity" | Volume is **~260/30 days**. Capacity has never been the constraint. | ⚠️ Solving a problem that isn't occurring |
| "MAUTIC_BOUNCE_MAILBOX_STATUS = ✅ DONE, Test Connection Success" | **0 rows** in monitored-inbox config; 0 bounces on 1,423 sends. | ❌ **Not active** |
| "MAUTIC_SSL_VALID_UNTIL=2026-08-29" | Renewed to 2026-10-29. | ❌ Stale, harmless |
| "إيراد شهري ~67K EGP · Orders ملغية 30.2%" | 60 days: 135,857 EGP completed (~68K/mo, holds). Cancellation **48%**, not 30.2%. | ⚠️ Revenue right, **cancellation much worse** |
| "🔴 Kashier gateway migration → 195K EGP/سنة" (CLAUDE.md "أكبر فرص" #1) | Already disproved in memory. All 19 recoveries paid **through Kashier** (wallet + card). Gateway works. | ❌ **Disproved — do not migrate** |
| "🔴 Cart recovery على 1645 session → ~150K EGP/أسبوعين" | Built and running. Actual: **11,196 EGP / 61 days**. Off by ~27×. | ❌ Wildly optimistic |
| "🔴 تنشيط الـ 13K email subscriber" | Never started. **97.1% never emailed**, 0 campaigns exist. | ⚠️ Still entirely open |
| ROADMAP Phase 001 "🔄 ACTIVE — deadline 15 يونيو" | Deadline passed **70 days** ago. Dart launched (16 sales). Roadmap frozen at 2026-06-11. | ❌ **Stale by 2.5 months** |
| ROADMAP blocker: "منتج Dart في WooCommerce — مستني أحمد" | Dart orders exist and are being paid (e.g. 350 EGP orders in July). | ✅ Resolved, not recorded |
| W3d error handler "بيشتغل من غير تفعيل" | Plausible in n8n, but **zero rows in `workflow_statistics`** — no evidence it ever ran. And W1/W2/W3t* have **no** error workflow. | ⚠️ Misleading — real gap is the unwired five |
| VPS / Mautic / n8n infra "LIVE + hardened" | ✅ 12/12 containers healthy, 83d uptime, 10% disk, certs auto-renewing. | ✅ **Correct** |

### The pattern

The docs describe **2026-06-10 to 2026-06-17** with high fidelity and then stop. Everything built before 2026-06-17 is documented accurately at the moment of writing; nothing after is recorded. Two things died quietly in that gap (the WhatsApp session, Mautic bounce processing) and one thing was built and never written down (W4, created 2026-06-24 — it appears in **no** repo doc, yet it is the only automation currently generating revenue).

The failure mode is not inaccuracy — it is that **the docs are written as status and status decays**. A green "FULLY LIVE" badge from June reads identically whether the thing is alive or 68 days cold.

---

## 9. Recommendations (nothing applied — all read-only findings)

Ordered by measured value at stake.

| # | Action | Value at stake | Effort | Who |
|---|---|---|---|---|
| 1 | **Disable `woocommerce_hold_stock_minutes`** (1440 → blank) on a digital-product store | 173,265 EGP cancelled / 60 days | one wp-cli command | Omar (has access) |
| 2 | **Re-link WhatsApp** (fresh QR on 201030127228) — re-enable `Test Users Only` for one validation round first, given prompt v10 is untested and 50 escalations are open | agent was at ~8 new customers/day and rising | QR scan + phone | Omar + Ahmed |
| 3 | **Triage the 50 open escalations** — 9 urgent, 5 never alerted. Real customers from June. | reputation, unknown revenue | manual | Omar |
| 4 | **Add `include:spf.brevo.com` to SPF** — DMARC currently passes on DKIM alone under `p=quarantine` | all email deliverability | one DNS edit | Omar |
| 5 | **Restore Mautic bounce processing** — currently 0 config rows, so list health is unmeasurable | blocks any safe broadcast | Mautic UI | Omar |
| 6 | **Wire error workflows on W1, W2, W3t, W3t-order, W3t-mautic** and add a **liveness check** (a session-state probe on Evolution) — an error trigger would never have caught a dropped session | 68 days of silent failure | n8n config | Omar |
| 7 | **Per-recipient 24h throttle in W4** `Decide Sends` — 6 emails/person/day at worst; `Load Sent Log` already fetches the data | complaint risk on shared IPs | ~10 lines | Omar |
| 8 | **Populate `total`/`currency`/`course` in `recovery_log`** — all NULL, forces WooCommerce round-trips for attribution | measurement quality | small | Omar |
| 9 | **Whitelist an IP in Brevo** so bounce/complaint stats become queryable | unblocks §4 | 1 min | Omar |
| 10 | **Refresh ROADMAP.md / CLAUDE.md** — stale by 2.5 months; W4 undocumented; drop the disproved Kashier-migration item | orientation cost every session | writing | Omar |

---

## 10. Evidence appendix

**Access used (read-only throughout):**
- `ssh root@187.124.9.249` → `docker ps`, `df -h`, `free -m`, `uptime`, `printenv`, `docker exec … psql`, `docker exec … mysql`
- `ssh learnsimply` (WordPress host `46.202.158.231:65002`) → `wp db query` (SELECT/SHOW only), `wp option get`
- `GET https://n8n.learrnsimply.com/api/v1/{workflows,executions}` with `X-N8N-API-KEY`
- `GET https://mautic.learrnsimply.com/api/{contacts,segments,emails,campaigns}` with HTTP Basic
- `GET /instance/fetchInstances` on Evolution (container-internal and public)
- `dig +short … @8.8.8.8`; `openssl s_client` for cert expiry
- `smtplib` EHLO → STARTTLS → AUTH → QUIT against Brevo (**no message sent**)

**Databases queried (SELECT / SHOW only):**
- `n8n` on `n8n-postgres-1` — `workflow_entity`, `execution_entity`, `workflow_statistics`
- `omar_agent` on `omar-pgvector` — `omar.{messages,contacts,escalations,agent_prompts,recovery_log}`, `pg_constraint`
- `mautic` on `mautic-r4bx-db-1` — `leads`, `lead_lists`, `lead_lists_leads`, `emails`, `email_stats`, `campaigns`, `lead_donotcontact`, `page_hits`, `plugin_integration_settings`
- WordPress MySQL — `wp_wc_orders` (HPOS active), `wp_posts`, `wp_options`

**Known gaps, carried forward honestly:**
1. **n8n execution history >14 days does not exist** (`EXECUTIONS_DATA_MAX_AGE=336`). Lifetime figures come from `workflow_statistics` (counts only, no error text). 9 of W1's 10 lifetime errors are unrecoverable.
2. **Brevo-side metrics UNVERIFIED** — API IP-allowlisted, both origins rejected. Daily limit, bounces, complaints, blocklist all unread.
3. **W4 revenue attribution is correlational, not causal** — no control group, no holdout, UTM click-through not recorded anywhere queryable. 19/19 paid after the email and 10/19 within 6h, but that is timing, not proof.
4. **`date_paid` is not a column in `wp_wc_orders`** — `date_updated_gmt` used as the payment-time proxy for completed orders.
5. **W3d's execution history is inferred** from its absence in `workflow_statistics`. Whether error-workflow runs increment that table is unconfirmed.
6. **The `.env` WooCommerce key is stale** — returns `invalid_username` from both this host and the VPS, while W4's n8n-stored copy works. The n8n credential holds a different, working key. Order data was read via the WordPress DB instead.
7. **Evolution's counters (3,979 msgs / 555 contacts) and the `omar` schema's (1,381 / 65) measure different things** — Evolution counts all WhatsApp traffic on the number; `omar.messages` counts only what W3 processed. Both reported; neither reconciled.
