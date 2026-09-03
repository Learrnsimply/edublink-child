"""Batch-import WP users from the local temp CSV into Mautic via /contacts/batch/new.
Upserts on email (Mautic native dedupe). Tags: wp-import (+ wc-customer for buyers).
Sends NO email. Fully reversible via the wp-import tag.
Env: MAUTIC_PW (required), MAX_CONTACTS (0=all, default 0)."""
import os
import sys
import io
import csv
import json
import base64
import re
import urllib.request
import urllib.error

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8", line_buffering=True)

CSV_PATH = os.path.join(os.path.dirname(__file__), "_tmp_users.csv")
API = "https://mautic.learrnsimply.com/api/contacts/batch/new"
AUTH = base64.b64encode(("omar:" + os.environ["MAUTIC_PW"]).encode()).decode()
MAX = int(os.environ.get("MAX_CONTACTS", "0"))
BATCH = 100
EMAIL_RE = re.compile(r"^[^@\s]+@[^@\s]+\.[^@\s]+$")

contacts = []
skip_admin = skip_email = 0
with open(CSV_PATH, encoding="utf-8", newline="") as f:
    for row in csv.DictReader(f):
        roles = row.get("roles") or ""
        if "administrator" in roles:
            skip_admin += 1
            continue
        email = (row.get("user_email") or "").strip().lower()
        if not EMAIL_RE.match(email):
            skip_email += 1
            continue
        dn = (row.get("display_name") or "").strip()
        fn = ln = ""
        if dn and "@" not in dn:
            parts = dn.split()
            fn, ln = parts[0], " ".join(parts[1:])
        tags = ["wp-import"]
        if "customer" in roles:
            tags.append("wc-customer")
        obj = {"email": email, "tags": tags}
        if fn:
            obj["firstname"] = fn
        if ln:
            obj["lastname"] = ln
        contacts.append(obj)

if MAX > 0:
    contacts = contacts[:MAX]

print("PARSED: {} to import | skipped_admin={} skipped_bademail={}".format(len(contacts), skip_admin, skip_email))


def post_batch(batch):
    data = json.dumps(batch, ensure_ascii=False).encode("utf-8")
    req = urllib.request.Request(API, data=data, method="POST")
    req.add_header("Authorization", "Basic " + AUTH)
    req.add_header("Content-Type", "application/json")
    with urllib.request.urlopen(req, timeout=180) as r:
        return json.loads(r.read().decode("utf-8", "replace"))


total = len(contacts)
ok = 0
failed_batches = 0
for i in range(0, total, BATCH):
    batch = contacts[i:i + BATCH]
    try:
        body = post_batch(batch)
        ok += len(body.get("contacts", [])) if isinstance(body, dict) else 0
    except urllib.error.HTTPError as ex:
        failed_batches += 1
        print("  BATCH {} HTTP {}: {}".format(i // BATCH, ex.code, ex.read().decode("utf-8", "replace")[:300]))
    except Exception as ex:
        failed_batches += 1
        print("  BATCH {} ERROR: {}".format(i // BATCH, ex))
    if (i // BATCH) % 10 == 0:
        print("  progress: {}/{} processed, {} upserted".format(min(i + BATCH, total), total, ok))

print("DONE: upserted={}, failed_batches={}, total_attempted={}".format(ok, failed_batches, total))
