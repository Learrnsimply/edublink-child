"""Read-only investigation of the shared-host WP users via wp-cli.
No server-side changes. Password from env."""
import os
import sys
import io
import paramiko

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8")

HOST = "147.93.73.159"
PORT = 65002
USER = "u700430280"
WP = "/home/u700430280/domains/learrnsimply.com/public_html"

pw = os.environ["SHARED_PW"]

c = paramiko.SSHClient()
c.load_system_host_keys()  # verify against ~/.ssh/known_hosts
c.set_missing_host_key_policy(paramiko.RejectPolicy())  # fail closed on unknown fingerprint
c.connect(HOST, port=PORT, username=USER, password=pw,
          look_for_keys=False, allow_agent=False, timeout=25)


def run(cmd):
    _in, out, err = c.exec_command(cmd, timeout=180)
    return out.read().decode("utf-8", "replace").strip(), err.read().decode("utf-8", "replace").strip()


# How many users have an empty email?
o, e = run("cd {} && wp user list --fields=user_email --format=csv".format(WP)
           + " | tail -n +2 | awk 'NF==0{c++} END{print c+0}'")
print("EMPTY_EMAIL_COUNT:", o, "| err:", e)

# Registration date range (min / max)
o, e = run("cd {} && wp user list --fields=user_registered --format=csv".format(WP)
           + " | tail -n +2 | sort | sed -n '1p;$p'")
print("DATE_RANGE (oldest / newest):\n" + o, "| err:", e)

# The 158 no-role users: sample 8 of them to judge if junk
o, e = run("cd {} && wp user list --role='' --number=8 --fields=ID,user_email,user_registered --format=csv".format(WP))
print("NO_ROLE_SAMPLE:\n" + o, "| err:", e)

# Sample 5 subscribers to see normal data shape
o, e = run("cd {} && wp user list --role=subscriber --number=5 --fields=ID,user_email,display_name,user_registered --format=csv".format(WP))
print("SUBSCRIBER_SAMPLE:\n" + o, "| err:", e)

c.close()
print("DONE")
