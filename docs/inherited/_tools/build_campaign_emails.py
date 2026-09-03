"""
Build the Dart campaign emails (A-D) as UNPUBLISHED DRAFTS in Mautic.

- Mirrors email id 1's wrapper exactly (Cairo + RTL white card + gradient CTA).
- Open decisions (price / coupon / date / deadline / CTA link) are wrapped in a
  red highlight span so we never forget to fill them after Ahmed confirms.
- Saves a local HTML preview for each, then POSTs as draft (isPublished=false).
- All on-site links are auto-tagged with UTM (see tag_utm) so email-driven sales
  are attributable in WooCommerce instead of showing up as "(direct)".

Usage:
  python _tools/build_campaign_emails.py --dry    # render previews only, no POST
  python _tools/build_campaign_emails.py          # render + POST drafts to Mautic

⚠️ POST-LAUNCH STALE (as of 2026-06-23): the Dart campaign already shipped. The
COUPON / LAUNCH / DEADLINE / countdown constants below are pre-launch values that
are NO LONGER TRUE (Dart is LIVE at 350 direct, no coupon, no deadline). Do NOT
re-run this tool blindly — it would recreate drafts with wrong content. Update the
constants to the current Dart offer first. See 02_AUTOMATION/mautic/campaigns/email-copy-drafts.md.
"""
import sys, io, os, json, base64, urllib.request

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8")

HERE = os.path.dirname(__file__)
ENV = os.path.join(HERE, "..", ".env")
PREVIEW_DIR = os.path.join(HERE, "..", "02_AUTOMATION", "mautic", "campaigns", "previews")
DRY = "--dry" in sys.argv


def env(key):
    with open(ENV, encoding="utf-8") as f:
        for line in f:
            if line.startswith(key + "="):
                return line.split("=", 1)[1].strip()
    raise SystemExit(f"missing {key} in .env")


API, USER, PW = env("MAUTIC_API_URL"), env("MAUTIC_API_USER"), env("MAUTIC_API_PASSWORD")
AUTH = "Basic " + base64.b64encode(f"{USER}:{PW}".encode()).decode()

# ---------- template primitives (1:1 with reengagement-email-preview.html) ----------

def ph(text):
    """Assumed value — shown plainly. Confirmation tracked in the Notion
    decisions table, not marked inside the client-facing email."""
    return text


def p(html):
    return f'<p style="margin:14px 0;">{html}</p>'


def quote(html):
    return (f'<p style="margin:18px 0; padding:14px 18px; background-color:#f5f3ff; '
            f'border-right:4px solid #7c3aed; border-radius:8px; color:#4c1d95; '
            f'font-size:17px; font-weight:bold;">{html}</p>')


def bullet(html):
    return f'<p style="margin:10px 0; padding-right:6px;">{html}</p>'


def cta(href, label):
    return (
        '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:26px 0;">'
        '<tr><td align="center" style="border-radius:10px; '
        'background:linear-gradient(90deg,#2563eb,#7c3aed);">'
        f'<a href="{href}" style="display:inline-block; padding:14px 30px; '
        "font-family:'Cairo',Tahoma,Arial,sans-serif; font-size:16px; font-weight:bold; "
        f'color:#ffffff; text-decoration:none; border-radius:10px;">{label}</a>'
        '</td></tr></table>'
    )


DART_LINK = "https://learrnsimply.com/dart"  # ✅ waitlist landing LIVE 2026-06-04


def cta_tbd(label, note=""):
    """CTA → the live /dart waitlist landing page (was a pre-launch placeholder).
    NOTE: at launch, retarget the launch/last-chance CTAs to the real checkout URL
    (or let /dart redirect to it)."""
    return cta(DART_LINK, label)


SIGNOFF = p('<strong>أحمد — اتعلم ببساطة</strong>')
YOUTUBE = "https://www.youtube.com/@Learn_Simply"

# ---------- UTM tagging (so email-driven sales are attributable, not "(direct)") ----------
# WooCommerce Order Attribution reads utm_source/medium/campaign from the landing URL.
# Any on-site link without UTM => the sale is recorded as "(direct)" and we lose attribution.
import re

UTM_SOURCE, UTM_MEDIUM = "mautic", "email"
# campaign + content per email key (keep stable so reporting groups correctly)
UTM_BY_KEY = {
    "A": ("dart_waitlist", "welcome"),
    "B": ("dart_launch", "announce"),
    "C": ("dart_launch", "launch"),
    "D": ("dart_lastchance", "lastchance"),
}


def tag_utm(html, campaign, content):
    """Append UTM params to every on-site (learrnsimply.com) href in the rendered HTML.
    Leaves external links (YouTube) and Mautic tokens like {unsubscribe_url} untouched."""
    def repl(m):
        url = m.group(1)
        if "{" in url:           # skip Mautic tokens
            return m.group(0)
        sep = "&amp;" if "?" in url else "?"
        return (f'href="{url}{sep}utm_source={UTM_SOURCE}&amp;utm_medium={UTM_MEDIUM}'
                f'&amp;utm_campaign={campaign}&amp;utm_content={content}"')
    return re.sub(r'href="(https://learrnsimply\.com[^"]*)"', repl, html)


def render(title, preheader, body_html):
    return f"""<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{title}</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap">
<style>
  body, table, td, p, a, div, strong, span {{ font-family:'Cairo', Tahoma, Arial, sans-serif !important; }}
</style>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f7; -webkit-text-size-adjust:100%;">

  <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:#f4f4f7;">{preheader}</div>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f7;">
    <tr><td align="center" style="padding:32px 16px;">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 2px 10px rgba(20,20,40,0.06);">
        <tr><td style="height:6px; background:linear-gradient(90deg,#2563eb,#7c3aed);"></td></tr>
        <tr><td style="padding:30px 36px 8px 36px; text-align:right;">
          <div style="font-size:20px; font-weight:bold; color:#1f2937;">اتعلم ببساطة</div>
        </td></tr>
        <tr><td style="padding:8px 36px 8px 36px; color:#374151; font-size:16px; line-height:1.9; text-align:right;">
{body_html}
        </td></tr>
        <tr><td style="padding:18px 36px 28px 36px; border-top:1px solid #eef0f4; color:#9ca3af; font-size:13px; line-height:1.8; text-align:center;">
          اتعلم ببساطة · learrnsimply.com<br>
          لو مش عايز توصلك إيميلات تانية، <a href="{{unsubscribe_url}}" style="color:#6b7280; text-decoration:underline;">اضغط هنا</a> — بكل بساطة.
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>"""


NAME = "{contactfield=firstname|صديقي}"  # firstname token w/ fallback

# ---------- the 4 emails ----------

PRICE = "350"              # ✅ confirmed by Ahmed 2026-06-04 (discounted)
OLDPRICE = "700"           # ✅ original price (700 → 350 = clean 50% off)
COUPON = "DART50"          # ✅ confirmed by Ahmed ("زي الفل")
LAUNCH = "15 يونيو"         # ✅ confirmed
DEADLINE = "48 ساعة"        # ✅ confirmed

# ----- Countdown timer (Ahmed asked for one in the launch email) -----
# Live timer = animated GIF from Sendtric (gen.sendtric.com/countdown/<id>).
# DEADLINE_DATETIME drives the minted GIF. Launch 15 Jun + 48h → 17 Jun 23:59 Cairo.
# If COUNTDOWN_URL is empty, the template degrades to a static urgency badge
# (still renders fine; just not ticking). Mint the GIF, paste its URL here, re-run.
DEADLINE_DATETIME = "2026-06-17 23:59 (Cairo, UTC+2)"
COUNTDOWN_URL = "https://gen.sendtric.com/countdown/gf3pgkgnqk"  # ✅ minted 2026-06-04 (Sendtric)


def countdown():
    """Centered live-countdown GIF with a text fallback for image-blocking clients.
    Falls back to a pure-HTML urgency badge when no GIF URL is set yet."""
    alt = "العرض بينتهي خلال 48 ساعة من الإطلاق ⏳"
    if not COUNTDOWN_URL:
        return (f'<p style="margin:20px 0; text-align:center; font-size:18px; '
                f'font-weight:bold; color:#7c3aed;">⏳ {alt}</p>')
    return (
        '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:20px auto;">'
        '<tr><td align="center" style="padding:6px 0;">'
        f'<img src="{COUNTDOWN_URL}" alt="{alt}" width="300" '
        'style="display:block; border:0; outline:none; text-decoration:none;">'
        '</td></tr>'
        '<tr><td align="center" style="font-size:13px; color:#6b7280; padding-top:6px;">'
        'العرض بينتهي مع نهاية العدّاد</td></tr></table>'
    )

emails = []

# A — Waitlist welcome (triggered on Dart popup signup) — EVERGREEN, no placeholders
emails.append(dict(
    key="A", name="Dart 01 — ترحيب قايمة الانتظار [DRAFT]",
    emailType="template",
    subject="تمام، حجزت مكانك \U0001f3af",
    preheader="إنت دلوقتي في قايمة كورس Dart — وهتبقى أول من يعرف",
    body="".join([
        p(f"أهلاً يا {NAME}،"),
        p("وصلني إنك سجّلت في قايمة انتظار كورس <strong>Dart</strong> — أهلاً بيك! \U0001f3af"),
        p("إنت دلوقتي من أول ناس هتعرف أول ما الكورس ينزل، وهيكون ليك <strong>خصم خاص</strong> يوم الإطلاق — مش هيتكرر بعد كده."),
        p("Dart هي اللغة اللي <strong>Flutter</strong> كله مبني عليها. لو نفسك تعمل تطبيقات موبايل احترافية، دي البداية الصح — وهشرحهالك ببساطة من الأول للاحتراف."),
        p("استنى مني إيميل قريّب فيه كل التفاصيل + الخصم."),
        p("وفي أي وقت، اتفرّج على شرحي على يوتيوب \U0001f447"),
        cta(YOUTUBE, "\U0001f3ac قناة اتعلم ببساطة"),
        SIGNOFF,
    ]),
))

# B — Dart announcement (engaged cohort). emailType=template → sent via Mautic
# Campaign drip step (audience = engaged segment, attached on the campaign, not the email).
emails.append(dict(
    key="B", name="Dart 02 — إعلان (engaged) [DRAFT]",
    emailType="template",
    subject="حاجة جديدة جايّة... وإنت أول من يعرف \U0001f440",
    preheader="كورس Dart قرّب — وليك أسبقية وخصم",
    body="".join([
        p(f"أهلاً يا {NAME}،"),
        p("فاكر من كام يوم لما قلتلك فيه حاجة بنجهزلها؟ دي هي. \U0001f447"),
        p(f"بنطلق <strong>كورس Dart من الصفر</strong> يوم {LAUNCH} — اللغة اللي Flutter كله قايم عليها، بشرح بسيط من أول سطر كود لحد الاحتراف."),
        p("وعشان إنت معانا من بدري، ليك حاجتين:"),
        bullet("\U0001f947 <strong>أسبقية:</strong> هتعرف أول واحد أول ما ينزل."),
        bullet(f"\U0001f381 <strong>خصم 50%:</strong> السعر هيبقى {PRICE} جنيه بدل {OLDPRICE} — ليوم الإطلاق بس."),
        p("لو حابب تضمن مكانك والخصم \U0001f447"),
        cta_tbd("احجز أسبقيتك", "لينك التأكيد / صفحة الانتظار — للتأكيد"),
        SIGNOFF,
    ]),
))

# C — Launch day
emails.append(dict(
    key="C", name="Dart 03 — يوم الإطلاق [DRAFT]",
    emailType="template",
    subject="\U0001f680 نزل! كورس Dart — خصم 50% النهاردة",
    preheader="خصم 50% على كورس Dart — العرض محدود",
    body="".join([
        p(f"أهلاً يا {NAME}،"),
        quote("كورس Dart من الصفر نزل! \U0001f389"),
        p(f"وزي ما وعدتك — ليك <strong>خصم 50%</strong>: {PRICE} جنيه بدل {OLDPRICE}."),
        p("الكورس بياخدك من أول سطر كود لحد ما تكتب Dart بثقة — الأساس اللي لازم تمشكه كويس قبل ما تدخل Flutter."),
        countdown(),
        cta_tbd(f"\U0001f680 احجز مكانك بـ {PRICE}ج", "لينك صفحة الكورس — بعد ما المنتج يتعمل"),
        p(f"استخدم كوبون {COUPON} عند الدفع — شغّال لحد {DEADLINE}."),
        SIGNOFF,
    ]),
))

# D — Last chance
emails.append(dict(
    key="D", name="Dart 04 — آخر فرصة [DRAFT]",
    emailType="template",
    subject="⏳ آخر فرصة — خصم Dart بيخلص",
    preheader="كوبون 50% على وشك ينتهي",
    body="".join([
        p(f"أهلاً يا {NAME}،"),
        p("تذكير سريع: خصم الـ <strong>50%</strong> على كورس <strong>Dart</strong> بيخلص قريّب."),
        p(f"بعد كده السعر يرجع {OLDPRICE}. لو كنت بتأجّل، دي اللحظة. ⏳"),
        countdown(),
        cta_tbd(f"احجز بـ {PRICE}ج قبل ما يخلص", "لينك صفحة الكورس — بعد ما المنتج يتعمل"),
        p(f"كوبون: {COUPON}"),
        SIGNOFF,
    ]),
))

# ---------- render + save previews + POST ----------

os.makedirs(PREVIEW_DIR, exist_ok=True)


def existing_by_name():
    """Map of existing email name -> id, so re-runs don't duplicate."""
    req = urllib.request.Request(f"{API}/emails?limit=200")
    req.add_header("Authorization", AUTH)
    data = json.loads(urllib.request.urlopen(req, timeout=30).read())
    return {v["name"]: v["id"] for v in data.get("emails", {}).values()}


def post_draft(e, html):
    payload = json.dumps({
        "name": e["name"],
        "subject": e["subject"],
        "customHtml": html,
        "emailType": e["emailType"],
        "isPublished": False,
        "language": "en",
        "fromAddress": "contact@learrnsimply.com",
        "fromName": "اتعلم ببساطة",
        "replyToAddress": "contact@learrnsimply.com",
    }, ensure_ascii=False).encode("utf-8")
    req = urllib.request.Request(f"{API}/emails/new", data=payload, method="POST")
    req.add_header("Authorization", AUTH)
    req.add_header("Content-Type", "application/json")
    try:
        resp = json.loads(urllib.request.urlopen(req, timeout=30).read())
        return resp["email"]["id"]
    except urllib.error.HTTPError as err:
        body = err.read().decode("utf-8", "replace")
        print(f"[{e['key']}] HTTP {err.code} ERROR body: {body[:600]}")
        raise


def update_draft(eid, e, html):
    payload = json.dumps({"subject": e["subject"], "customHtml": html},
                         ensure_ascii=False).encode("utf-8")
    req = urllib.request.Request(f"{API}/emails/{eid}/edit", data=payload, method="PATCH")
    req.add_header("Authorization", AUTH)
    req.add_header("Content-Type", "application/json")
    urllib.request.urlopen(req, timeout=30).read()


seen = {} if DRY else existing_by_name()

for e in emails:
    html = render(e["subject"], e["preheader"], e["body"])
    _utm_campaign, _utm_content = UTM_BY_KEY.get(e["key"], ("dart", e["key"].lower()))
    html = tag_utm(html, _utm_campaign, _utm_content)
    preview_path = os.path.join(PREVIEW_DIR, f"dart-0{ord(e['key'])-64}-{e['key'].lower()}.html")
    with open(preview_path, "w", encoding="utf-8") as f:
        f.write(html)
    if DRY:
        print(f"[{e['key']}] rendered → {os.path.basename(preview_path)}  ({len(html)} chars, type={e['emailType']})")
    elif e["name"] in seen:
        update_draft(seen[e["name"]], e, html)
        print(f"[{e['key']}] DRAFT updated id={seen[e['name']]}  → {os.path.basename(preview_path)}")
    else:
        eid = post_draft(e, html)
        print(f"[{e['key']}] DRAFT created id={eid}  type={e['emailType']}  → {os.path.basename(preview_path)}")

print("\nDONE." + ("  (dry run — no POST)" if DRY else "  All 4 drafts are UNPUBLISHED in Mautic."))
