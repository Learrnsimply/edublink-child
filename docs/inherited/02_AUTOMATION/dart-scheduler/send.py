#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Dart campaign smart scheduler — runs daily via cron on the VPS.
For each scheduled send-day it: (1) safety-checks Brevo (spam complaints),
(2) picks the right recipients (warm-up = dartwaitlist not-yet-sent; sequence = engaged openers),
(3) sends via Mautic->Brevo with a gentle pace, (4) alerts Telegram.
Run with arg "dryrun" to compute everything WITHOUT sending.
"""
import json, datetime, subprocess, urllib.request, urllib.error, base64, time, urllib.parse, os, re, sys

BASE_DIR  = "/opt/dart-scheduler"
SCHED     = os.path.join(BASE_DIR, "schedule.json")
LOG       = os.path.join(BASE_DIR, "scheduler.log")
MAUTIC    = "https://mautic.learrnsimply.com/api"
# Secrets are loaded from the environment — set them in /opt/dart-scheduler/.env
# (chmod 600, NOT committed to git; see .env.example). Never hardcode here.
def _load_env_file(path):
    try:
        with open(path, encoding="utf-8") as fh:
            for raw in fh:
                line = raw.strip()
                if line and not line.startswith("#") and "=" in line:
                    k, v = line.split("=", 1)
                    os.environ.setdefault(k.strip(), v.strip())
    except FileNotFoundError:
        pass

_load_env_file(os.path.join(BASE_DIR, ".env"))

def _require(name):
    val = os.environ.get(name)
    if not val:
        sys.stderr.write("FATAL: missing env var %s — set it in %s/.env\n" % (name, BASE_DIR))
        sys.exit(2)
    return val

MAUTIC_AUTH = "Basic " + base64.b64encode((_require("MAUTIC_USER") + ":" + _require("MAUTIC_PASS")).encode()).decode()
BREVO_KEY = _require("BREVO_KEY")
TG_TOKEN  = _require("TG_TOKEN")
TG_CHAT   = os.environ.get("TG_CHAT", "6726176133")   # chat id, not a secret
UA        = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36"
DARTWAITLIST_SEGMENT = 10
ENGAGED_SOURCE_EMAILS = "2,10"   # opened welcome(2) or value(10) = engaged
SEND_DELAY_SEC = 1.2

DRYRUN = "dryrun" in sys.argv

def log(m):
    line = "[" + datetime.datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S") + "Z] " + m
    try: open(LOG, "a", encoding="utf-8").write(line + "\n")
    except Exception: pass
    print(line)

def tg(msg):
    if DRYRUN:
        log("TG(dry): " + msg); return
    try:
        data = urllib.parse.urlencode({"chat_id": TG_CHAT, "text": msg}).encode()
        urllib.request.urlopen("https://api.telegram.org/bot" + TG_TOKEN + "/sendMessage", data=data, timeout=15)
    except Exception as e:
        log("tg_fail: " + str(e)[:80])

def brevo(path):
    req = urllib.request.Request("https://api.brevo.com/v3" + path)
    req.add_header("api-key", BREVO_KEY); req.add_header("Accept", "application/json"); req.add_header("User-Agent", UA)
    return json.loads(urllib.request.urlopen(req, timeout=30).read())

def mautic(method, path):
    req = urllib.request.Request(MAUTIC + path, method=method); req.add_header("Authorization", MAUTIC_AUTH)
    try:
        r = urllib.request.urlopen(req, timeout=40); return r.status
    except urllib.error.HTTPError as e:
        return e.code

def dq(sql):
    """Run a doctrine SQL query in the Mautic container, return list of int ids."""
    out = subprocess.run(
        ["docker", "exec", "-u", "www-data", "-w", "/var/www/html", "mautic-r4bx-mautic-1",
         "php", "bin/console", "doctrine:query:sql", sql],
        capture_output=True, text=True, timeout=90).stdout
    return [int(x) for x in re.findall(r"^\s*(\d+)\s*$", out, re.M)]

def target_ids(entry):
    eid = entry["email_id"]; cap = entry.get("cap", 110)
    if entry["mode"] == "warmup":
        sql = ("SELECT l.id FROM lead_lists_leads ll JOIN leads l ON l.id=ll.lead_id "
               "WHERE ll.leadlist_id=%d AND ll.manually_removed=0 "
               "AND l.id NOT IN (SELECT lead_id FROM email_stats WHERE email_id=%d) "
               "ORDER BY l.date_added DESC LIMIT %d" % (DARTWAITLIST_SEGMENT, eid, cap))
    else:  # sequence -> engaged openers only
        sql = ("SELECT l.id FROM lead_lists_leads ll JOIN leads l ON l.id=ll.lead_id "
               "WHERE ll.leadlist_id=%d AND ll.manually_removed=0 "
               "AND l.id IN (SELECT lead_id FROM email_stats WHERE email_id IN (%s) AND is_read=1) "
               "AND l.id NOT IN (SELECT lead_id FROM email_stats WHERE email_id=%d) "
               "ORDER BY l.id LIMIT %d" % (DARTWAITLIST_SEGMENT, ENGAGED_SOURCE_EMAILS, eid, cap))
    return dq(sql)

def main():
    today = datetime.date.today().isoformat()
    for a in sys.argv[1:]:               # optional YYYY-MM-DD arg overrides "today" (testing only)
        if re.match(r"^\d{4}-\d{2}-\d{2}$", a): today = a
    sched = json.load(open(SCHED, encoding="utf-8"))
    entry = next((s for s in sched if s.get("date") == today), None)
    if not entry:
        log("no send scheduled for " + today + (" [dryrun]" if DRYRUN else "")); return
    note = entry.get("note", "?"); eid = entry["email_id"]
    log("scheduled today: '%s' email=%d mode=%s%s" % (note, eid, entry.get("mode"), " [DRYRUN]" if DRYRUN else ""))

    # 1) SAFETY GATE — fail-safe (any error or any complaint => do NOT send)
    try:
        rep = brevo("/smtp/statistics/aggregatedReport?days=1")
        spam = rep.get("spamReports", 0) or 0
        bounce = (rep.get("hardBounces", 0) or 0)
        log("brevo today: spam=%d hardBounce=%d delivered=%d" % (spam, bounce, rep.get("delivered", 0) or 0))
        if spam >= 1:
            tg("🔴 أتمتة Dart: لقيت %d شكوى spam النهاردة — أوقفت إرسال «%s». محتاج تدخّلك قبل ما نكمّل." % (spam, note))
            log("ABORT: spamReports=%d" % spam); return
    except Exception as e:
        log("SAFETY_CHECK_FAILED: " + str(e)[:120])
        tg("⚠️ أتمتة Dart: مقدرتش أفحص حالة Brevo قبل «%s» — أوقفت احتياطياً (fail-safe). راجع يدوياً." % note)
        return

    # 2) TARGETS
    try:
        ids = target_ids(entry)
    except Exception as e:
        log("TARGET_QUERY_FAILED: " + str(e)[:120])
        tg("⚠️ أتمتة Dart: مشكلة في تحديد المتلقّيين لـ «%s» — أوقفت." % note); return
    log("targets: %d contacts" % len(ids))
    if not ids:
        log("no new targets"); tg("ℹ️ أتمتة Dart: «%s» — مفيش متلقّيين جدد (كلهم اتبعتلهم قبل كده)." % note); return

    if DRYRUN:
        log("DRYRUN — would send email %d to %d contacts: %s ..." % (eid, len(ids), ids[:10]))
        tg("🧪 [تجربة] أتمتة Dart جاهزة: «%s» هتروح لـ %d شخص. (مفيش إرسال فعلي)" % (note, len(ids)))
        return

    # 3) SEND (paced)
    ok = 0
    for cid in ids:
        if mautic("POST", "/emails/%d/contact/%d/send" % (eid, cid)) in (200, 201):
            ok += 1
        time.sleep(SEND_DELAY_SEC)
    log("SENT %d/%d for '%s'" % (ok, len(ids), note))
    tg("✅ أتمتة Dart: اتبعت «%s» لـ %d شخص عبر Brevo. هراقب الوصول وأبلّغك بأي خطر." % (note, ok))

if __name__ == "__main__":
    main()
