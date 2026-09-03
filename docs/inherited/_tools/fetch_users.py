"""Read-only: pull the full WP user list via wp-cli and save to a local temp CSV.
PII — the CSV is temp and must NOT be committed. Password from env."""
import os
import sys
import io
import paramiko

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8")

HOST, PORT, USER = "147.93.73.159", 65002, "u700430280"
WP = "/home/u700430280/domains/learrnsimply.com/public_html"
OUT = os.path.join(os.path.dirname(__file__), "_tmp_users.csv")

pw = os.environ["SHARED_PW"]
c = paramiko.SSHClient()
c.load_system_host_keys()  # verify against ~/.ssh/known_hosts
c.set_missing_host_key_policy(paramiko.RejectPolicy())  # fail closed on unknown fingerprint
c.connect(HOST, port=PORT, username=USER, password=pw,
          look_for_keys=False, allow_agent=False, timeout=25)

_in, out, err = c.exec_command(
    "cd {} && wp user list --number=-1 ".format(WP)
    + "--fields=ID,user_email,display_name,user_registered,roles --format=csv",
    timeout=180,
)
csv_text = out.read().decode("utf-8", "replace")
errtxt = err.read().decode("utf-8", "replace").strip()
c.close()

with open(OUT, "w", encoding="utf-8", newline="") as f:
    f.write(csv_text)

rows = csv_text.count("\n")
print("SAVED:", OUT)
print("LINES (incl header):", rows)
print("ERR:", errtxt or "(none)")
