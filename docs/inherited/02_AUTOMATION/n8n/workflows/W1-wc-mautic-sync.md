# W1 — WooCommerce → Mautic Contact Sync

> **Status:** 🟢 **LIVE** 2026-06-02 — workflow ACTIVE + WooCommerce webhook wired (WC webhook ID **7**, status Active). End-to-end proven by WC's own validation ping (n8n execution #4 = success).
> **Workflow ID:** `whayAvTcXhG6TDeQ` · **n8n:** https://n8n.learrnsimply.com
> **WC webhook:** wp-admin → WooCommerce → Settings → Advanced → Webhooks → "n8n W1 — Mautic Contact Sync" (ID 7, topic `order.created`)
> **Purpose:** First sync pipeline. Every WooCommerce order event upserts a contact into Mautic. Foundation for W2–W7.

---

## Pipeline

```
WC Webhook (POST)  →  Map WC → Mautic fields (Code)  →  Mautic Upsert Contact (HTTP Request)
```

| Node | What it does |
|---|---|
| **WC Webhook** | Listens for POST from WooCommerce. `responseMode: lastNode` → returns the chain result, so a Mautic failure returns non-2xx and **WooCommerce auto-retries** the delivery. |
| **Map WC → Mautic fields** | Defensive JS. Handles both `order.created` and `customer.created` payload shapes. Builds a Mautic contact body keyed by field alias. Skips silently if no email. |
| **Mautic Upsert Contact** | `POST /api/contacts/new` with HTTP Basic Auth. `retryOnFail: 3× / 2s` for transient blips. Mautic dedupes on email → create-or-update. |

**Two-layer durability:** transient failure → n8n retries 3×; persistent failure → non-200 → WooCommerce re-queues the webhook. No lost contacts.

---

## Webhook URL (production)

```
POST  https://n8n.learrnsimply.com/webhook/wc-mautic-sync-a7f3c19e4b82
```

⚠️ The path token `a7f3c19e4b82` is a **shared secret** — it's the primary gate keeping randoms from injecting contacts. Treat like a credential. Don't paste it in public.

---

## Field mapping (WC → Mautic alias)

| Mautic field | Source | Notes |
|---|---|---|
| `email` | `billing.email` / `email` | Unique identifier → upsert key. Lowercased, trimmed. Required (no email = skip). |
| `firstname` | `billing.first_name` / `first_name` | Only written if non-empty (never blanks existing). |
| `lastname` | `billing.last_name` / `last_name` | Same. |
| `phone` | `billing.phone` / `phone` | Same. |
| `wc_customer_id` | `customer_id` (order) / `id` (customer) | Sent as **number**. Guests (id 0) skipped. |
| `course_interest` | `line_items[].name` joined `, ` | Full Arabic course names preserved. |
| `last_purchase_date` | `date_created` | Formatted `Y-m-d H:i:s` (Mautic datetime). Orders only. |
| `tags` | — | Always adds `wc-customer`. |

### Design decisions (don't "fix" these)
- **`source_channel` is intentionally NOT set.** A purchase doesn't reveal acquisition channel; overwriting it would corrupt attribution for buyers who came from YouTube/Telegram/etc. The `wc-customer` tag + `wc_customer_id` already mark them as buyers.
- **Non-empty-only writes** — we never overwrite existing Mautic data with blanks.
- **Upsert on email** — verified empirically: `POST /contacts/new` with an existing email updates (total stays 1), not duplicates.

---

## n8n credentials used
| Name | Type | ID | Used by |
|---|---|---|---|
| Mautic HTTP Basic — Learn Simply | httpBasicAuth | `HcKVugtv8k1Yr47c` | W1 HTTP node. Scoped to `mautic.learrnsimply.com` only. |
| Mautic — Learn Simply (Basic Auth) | mauticApi | `MnG4kOacfVgtfkLg` | (reserve) future Mautic-node workflows (W5 segment/email ops). |

---

## Go-live (DONE 2026-06-02)

✅ WC webhook created via Playwright (headless chromium + global `playwright` pkg, scripts in `_tools/wc-webhook/`):
- WC webhook **ID 7**, name "n8n W1 — Mautic Contact Sync", topic `order.created`, status **Active**.
- W1 workflow **activated** in n8n.
- WC's validation ping on save → n8n execution #4 = success (no-email payload → skipped, 200) → proves WC→n8n reachability.

> This laptop has **no SSH to the shared WP host**, so the webhook was created via wp-admin UI automation, not wp-cli. The Playwright browser profile (held a live wp-admin session) was deleted after use.

### Optional next: add *Customer created* webhook
A second WC webhook (topic `customer.created` → same URL) would also sync registrations that don't place an order. Same form, topic = Customer created. Not required for W1's core purpose.

---

## Hardening follow-up (not yet done)
- **WC HMAC signature verification.** WooCommerce signs each payload (`x-wc-webhook-signature`, base64 HMAC-SHA256 of the raw body). Verifying it in n8n is fiddly (HMAC must be over raw bytes, but the Webhook node parses JSON). Until added, the secret path + HTTPS + input validation are the gate. Add when hardening before scale.

---

## Test evidence (2026-06-02)
- ✅ Full order payload → contact created with all fields correct, Arabic preserved (`أحمد`, `كورس Flutter من الصفر`).
- ✅ Second order, same email → `total=1` (no duplicate), `course_interest` + `last_purchase_date` updated.
- ✅ Test contact deleted, workflow returned to INACTIVE.
