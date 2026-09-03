# 📒 n8n Workflows Registry — Learn Simply

> **يتولّد آلياً** من `_tools/export_workflows.mjs` — متعدّلوش يدوي، شغّل الأداة.
> **n8n هو الـ source of truth** (https://n8n.learrnsimply.com) — الـ JSONs في `_exports/` نسخ احتياطية.
> آخر export: راجع تاريخ آخر commit للملف ده. الـ credentials في الـ JSONs = IDs بس (مفيش secrets).

| ID | الاسم | الحالة | Nodes | errorWorkflow | آخر تعديل | الدور |
|---|---|---|---|---|---|---|
| `R5xDtKKGSeBchue6` | W3t-mautic — mautic_upsert tool | 🟢 ACTIVE | 2 | `—` | 2026-06-10 16:04 | wrapper أداة mautic_upsert (toolWorkflow): Execute Trigger → HTTP Mautic create (لازم active) |
| `sv6ART3GjO4JUN81` | W3c — omar-kb-search (RAG endpoint) | 🟢 ACTIVE | 8 | `YktkjLMI12YUGWfc` | 2026-06-10 11:39 | endpoint الـ RAG: embed (Gemini) → omar.kb_search (pgvector) |
| `IfIlQ2RsfsubUkHW` | W3t — kb_search tool (wrapper for W3c) | 🟢 ACTIVE | 2 | `—` | 2026-06-10 15:59 | wrapper أداة kb_search (toolWorkflow): Execute Trigger → HTTP لـ W3c webhook (لازم active في queue mode) |
| `whayAvTcXhG6TDeQ` | W1 — WooCommerce → Mautic Contact Sync | 🟢 ACTIVE | 3 | `—` | 2026-06-01 23:46 | WC order جديد → contact في Mautic (webhook WC id 7) |
| `ESYkoJgz0e4ngMrM` | W3 — omar-inbound (مساعد واتساب عمر) | 🟢 ACTIVE | 38 | `YktkjLMI12YUGWfc` | 2026-06-10 17:01 | مساعد واتساب "عمر" — الرئيسي (debounce + AI Agent + 4 tools) |
| `YktkjLMI12YUGWfc` | W3d — omar-error-handler (إنذار أخطاء Telegram) | ⚪ inactive | 3 | `—` | 2026-06-10 00:02 | Error Trigger → Telegram (البوت المشترك) — متربط في settings.errorWorkflow |
| `gC9cPSiTBP3M6DZj` | W3t-order — order_lookup tool (WC orders) | 🟢 ACTIVE | 2 | `—` | 2026-06-10 16:04 | wrapper أداة order_lookup (toolWorkflow): Execute Trigger → HTTP WC orders (لازم active) |
| `VMVSlPEcwNr1Bd6J` | W2 — Dart Waitlist Popup → Mautic | 🟢 ACTIVE | 8 | `—` | 2026-06-06 14:08 | فورم popup/صفحة /dart → Mautic segment 10 (tag dart-waitlist) |
| `Er1W6KSwqOiEmgrd` | W4 — Cart Recovery (pending-order email reminders) | 🟢 ACTIVE | 6 | `YktkjLMI12YUGWfc` | 2026-06-24 | poll كل 20د (8ص–10:40م) → WC pending → Decide (R1/R2) → Brevo SMTP → omar.recovery_log. Mark بيقرا markSql من Decide via pairedItem (emailSend بيستبدل الـ item). doc: W4-cart-recovery.md |
| `ULoRfU57m5fSLD2B` | W3b — omar-alert (تصعيد عمر) | 🟢 ACTIVE | 10 | `YktkjLMI12YUGWfc` | 2026-06-10 16:34 | sub-workflow التصعيد: DB-first + إيميل Brevo + واتساب لأحمد وعمر |

## استرجاع workflow من الـ backup

1. افتح الـ JSON من `_exports/`.
2. n8n UI → Workflows → Import from File (أو `n8n_create_workflow` عبر n8n-MCP بالـ nodes/connections من الملف).
3. اربط الـ credentials تاني (الـ IDs في brand `.env` §20) — الـ import مش بينقل الـ credentials نفسها.
