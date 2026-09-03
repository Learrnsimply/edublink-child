# W2 — Dart Waitlist Popup → Mautic

> **Status:** 🟢 **ACTIVE — LIVE 2026-06-04**. Popup frontend shipped to `wp-content/mu-plugins/` on the new host; W2 activated; full chain re-verified post-migration (POST→200 `{success:true}`, contact tagged `dart-waitlist` in Mautic, test contact deleted).
> **Workflow ID:** `VMVSlPEcwNr1Bd6J` · **n8n:** https://n8n.learrnsimply.com
> **Purpose:** Collect emails from the on-site Dart-course popup (launch **15 يونيو 2026**) into Mautic — tagged `dart-waitlist` + added to segment **10 (Dart Waitlist)**. Foundation for the launch-day 50% coupon blast.

---

## Pipeline

```
Dart Popup Webhook (POST, CORS)
  → Validate & Build (honeypot + email regex)
  → Is Valid?
       ├─ TRUE  → Mautic Create Contact → Add to Segment 10 → Respond Success (200)
       └─ FALSE → Respond Rejected (400 invalid email / 200 honeypot)
```

| Node | What it does |
|---|---|
| **Dart Popup Webhook** | `POST`, `responseMode: responseNode`, `onError: continueRegularOutput` (always responds). CORS `allowedOrigins` = `https://learrnsimply.com,https://www.learrnsimply.com`. |
| **Validate & Build** | Defensive JS, never throws. Honeypot check (`website`/`hp` field non-empty → silent skip). Email regex. Builds Mautic body `{email, firstname?, tags:['dart-waitlist']}`. Emits one item carrying `{valid, statusCode, success, message, contactBody}`. |
| **Is Valid?** | IF v2.3. TRUE → create+segment; FALSE → reject response. |
| **Mautic Create Contact** | `POST /api/contacts/new`, HTTP Basic Auth. `retryOnFail 3×/2s`. Upserts on email. |
| **Add to Segment 10** | `POST /api/segments/10/contact/{{ id }}/add`. `retryOnFail 3×/2s` + `onError: continueRegularOutput` (contact + tag already saved; a segment blip never fails the user response). Returns `{success:1}`. |
| **Respond Success / Rejected** | JSON responses the browser `fetch()` reads to show success/error state. |

---

## Webhook URL (production)

```
POST  https://n8n.learrnsimply.com/webhook/dart-waitlist-97ae34dfa856
```

⚠️ The path token `97ae34dfa856` is a **shared secret** — the primary gate against random contact injection. Treat like a credential. Don't paste it publicly. (Returns 404 while the workflow is INACTIVE.)

---

## Frontend contract (for the popup mu-plugin)

The popup JS must POST JSON to the webhook URL:

```js
await fetch('https://n8n.learrnsimply.com/webhook/dart-waitlist-97ae34dfa856', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: emailValue,        // required
    firstname: nameValue,     // optional
    website: ''               // HONEYPOT — keep this hidden field EMPTY; bots fill it → silently skipped
  })
});
```

- **200 `{success:true}`** → show "تم تسجيلك 🎉" success state.
- **400 `{success:false, message}`** → show the Arabic error inline (invalid email).
- **CORS:** webhook already allows `learrnsimply.com` (+ www). If the site serves the popup from a different origin/subdomain, add it to the webhook's `allowedOrigins`.
- **once-per-visitor:** gate with `localStorage` client-side (the backend upserts on email, so a repeat submit is harmless — no duplicate).

---

## Field mapping (popup → Mautic alias)

| Mautic field | Source | Notes |
|---|---|---|
| `email` | `email` | Lowercased, trimmed. Required (regex-validated). Upsert key. |
| `firstname` | `firstname` / `first_name` / `name` | Only written if non-empty. UTF-8 Arabic preserved (verified: `أحمد`). |
| `tags` | — | Always adds `dart-waitlist`. |
| segment 10 | — | Explicit add (segment 10 is **manual**, `filters: []` → won't auto-populate from the tag). |

### Design decisions (don't "fix" these)
- **`source_channel` intentionally NOT set.** It's a `select` field with a fixed option list; `dart-popup` is not an option → sending it would reject the whole create. The `dart-waitlist` tag is the marker. (Per HANDOFF 2026-06-02.)
- **Honeypot returns 200 success-looking** so bots think they succeeded; no contact is created.
- **Invalid email returns 400** so the popup can show an inline error.

---

## n8n credentials used
| Name | Type | ID |
|---|---|---|
| Mautic HTTP Basic — Learn Simply | httpBasicAuth | `HcKVugtv8k1Yr47c` (same as W1) |

---

## Test evidence (2026-06-03, laptop PUZZLE)

| Case | Sent | Result |
|---|---|---|
| Valid email + firstname | `{email, firstname}` | ✅ 200 `{success:true}` · contact created · tag `dart-waitlist` · `Add to Segment 10` → `{success:1}` |
| UTF-8 Arabic firstname | `{firstname:"أحمد"}` (via UTF-8 file) | ✅ stored as `أحمد` (the earlier `?????` was a Windows-Bash `curl` encoding artifact, not a workflow bug) |
| Invalid email | `{email:"notanemail"}` | ✅ 400 `{success:false}` · no contact |
| Honeypot filled | `{email, website:"spam"}` | ✅ 200 `{success:true,"ok"}` · **no contact** |
| Idempotency | re-POST same email | ✅ updated same contact (no duplicate) |

All test contacts deleted after verification (list clean).

---

## Go-live checklist (next session, after popup ships)
1. ✅ **Popup mu-plugin BUILT** (2026-06-03) — `01_WEB/mu-plugins/dart-popup/learnsimply-dart-popup.php`. Just needs deploy (copy to `wp-content/mu-plugins/` on the **new** host). **Note:** the popup posts to a same-origin WP REST route (`/wp-json/learnsimply/v1/dart-waitlist`) that forwards server-side to this webhook — so the secret token stays out of the browser. CORS `allowedOrigins` on the webhook is now redundant (requests come from the WP server, not the browser), but harmless to leave. Honeypot is still relayed.
2. **Activate W2** in n8n (one toggle — `n8n_update_partial_workflow activateWorkflow`).
3. Submit one real email from the live popup → confirm it lands in segment 10.
4. Build the WooCommerce 50% launch coupon (when the Dart product exists).

> 📋 Full deploy steps: [`../../../MIGRATION-DEPLOY-RUNBOOK.md`](../../../MIGRATION-DEPLOY-RUNBOOK.md) §4. mu-plugin reference: [`../../../01_WEB/mu-plugins/dart-popup/README.md`](../../../01_WEB/mu-plugins/dart-popup/README.md).

## Notes
- **Mautic contact delete** needs the `/delete` suffix: `DELETE /api/contacts/{id}/delete` (plain `DELETE /api/contacts/{id}` is a silent no-op).
- Segment 10 "Dart Waitlist" is a **manual** segment (`filters: []`) — membership comes only from the explicit add step here (or manual UI), never auto from the tag.
