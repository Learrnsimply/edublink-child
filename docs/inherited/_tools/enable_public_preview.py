"""Enable Mautic public email preview on emails 1-5 and print the public URLs."""
import sys, io, json, base64, urllib.request

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8")

def env(k):
    for l in open(".env", encoding="utf-8"):
        if l.startswith(k + "="):
            return l.split("=", 1)[1].strip()

API, U, P = env("MAUTIC_API_URL"), env("MAUTIC_API_USER"), env("MAUTIC_API_PASSWORD")
BASE = API.rsplit("/api", 1)[0]
AUTH = "Basic " + base64.b64encode(f"{U}:{P}".encode()).decode()

for eid in [1, 2, 3, 4, 5]:
    payload = json.dumps({"publicPreview": True}).encode()
    req = urllib.request.Request(f"{API}/emails/{eid}/edit", data=payload, method="PATCH")
    req.add_header("Authorization", AUTH)
    req.add_header("Content-Type", "application/json")
    e = json.loads(urllib.request.urlopen(req, timeout=30).read())["email"]
    print(f"id={eid}  publicPreview={e['publicPreview']}  name={e['name']}")
    print(f"   {BASE}/email/preview/{eid}")
