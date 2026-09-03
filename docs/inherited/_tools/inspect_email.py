"""Quick inspect of an existing Mautic email to mirror its field structure."""
import sys, io, json, base64, urllib.request, os, re

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8")

ENV = os.path.join(os.path.dirname(__file__), "..", ".env")
def env(key):
    with open(ENV, encoding="utf-8") as f:
        for line in f:
            if line.startswith(key + "="):
                return line.split("=", 1)[1].strip()
    raise SystemExit(f"missing {key}")

API = env("MAUTIC_API_URL")
USER = env("MAUTIC_API_USER")
PW = env("MAUTIC_API_PASSWORD")

email_id = sys.argv[1] if len(sys.argv) > 1 else "1"
req = urllib.request.Request(f"{API}/emails/{email_id}")
req.add_header("Authorization", "Basic " + base64.b64encode(f"{USER}:{PW}".encode()).decode())
data = json.loads(urllib.request.urlopen(req, timeout=30).read())
e = data["email"]
# Print all scalar fields (skip the giant html for now)
for k, v in e.items():
    if k == "customHtml":
        print(f"{k}: <{len(v or '')} chars>")
    elif isinstance(v, (str, int, float, bool, type(None))):
        print(f"{k}: {v}")
    else:
        print(f"{k}: {json.dumps(v, ensure_ascii=False)[:200]}")
