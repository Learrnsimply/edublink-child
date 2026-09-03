# Learn Simply

The business Ahmed Adel runs: Arabic programming courses, the people who buy them, and the marketing and support automations around them. This file is the glossary those surfaces share — nothing else.

## Language

### Order outcomes

These three are counted separately and treated separately. Collapsing them is what produced the disproved "909 failed CC ≈ 195K EGP/year gateway leak" claim.

**Cancellation** (إلغاء):
An order that reached the store and was then ended before payment cleared — by the buyer, by a merchant action, or by an automatic store rule.
_Avoid_: failed payment, abandonment, lost order

**Payment refusal** (رفض دفع):
A payment attempt the bank or the gateway explicitly declined, including a 3DS challenge the cardholder never completed.
_Avoid_: failed order, gateway failure, broken gateway

**Abandonment** (تخلّي):
A checkout a visitor reached and left without ever submitting an order.
_Avoid_: cancelled cart, failed payment

### Order states

**Unpaid order** (طلب غير مدفوع):
An order that was submitted and is waiting for its payment to clear. Distinct from an abandonment — the buyer did submit — and distinct from a cancellation, which is what an unpaid order becomes if the store ends it. On a manual payment method the buyer may already have sent the money while the order is still unpaid, because clearing depends on a human confirming it.
_Avoid_: pending cart, abandoned order, failed order

### Customers

**Repeat customer** (عميل مكرر):
A customer with two or more **completed** orders over their lifetime. Orders that were cancelled, refused or refunded do not count toward the second purchase.
_Avoid_: returning visitor, active customer

**Sales baseline** (خط الأساس للمبيعات):
The measured figure a growth target is anchored to. A baseline is only usable when it states its period, its scope, whether it is gross or net, and how refunds and cancellations were treated — a bare revenue number is not a baseline.
_Avoid_: current revenue, run rate
