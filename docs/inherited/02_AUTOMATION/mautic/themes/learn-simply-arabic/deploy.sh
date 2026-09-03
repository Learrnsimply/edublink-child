#!/bin/bash
# Deploy the "Learn Simply Arabic" Mautic email theme.
#
# WHAT: clones the built-in `sunday` theme (known-good, builder-compatible) and
#       applies a surgical RTL + Cairo-font patch to its email template head.
#       This is exactly the approach the Notion task prescribed
#       ("Clone Sunday → add CSS in head to flip direction + change font").
#
# WHY a deploy script (not a committed full theme): the themes dir inside the
#       Mautic container is NOT a mounted volume, so a `docker compose up`
#       recreate wipes custom themes. Re-running this script restores it in
#       seconds. The base (sunday) ships inside the image, so we only version
#       the diff (this script + config.json), not sunday's binaries/templates.
#
# RUN (on the VPS host):  bash deploy.sh
# Idempotent — safe to re-run. Additive only (no recreate, no restart).
set -euo pipefail

C=mautic-r4bx-mautic-1
T=/var/www/html/docroot/themes
W=/tmp/ls-theme-build

rm -rf "$W"; mkdir -p "$W"
docker cp "$C:$T/sunday" "$W/learn-simply-arabic"

# 1) Theme metadata → email-only Arabic theme.
cat > "$W/learn-simply-arabic/config.json" <<'JSON'
{
  "name": "Learn Simply Arabic",
  "author": "GrowthMora (Omar)",
  "authorUrl": "https://learrnsimply.com",
  "features": [
    "email"
  ]
}
JSON

# 2) Surgical RTL + Cairo patch into the email template <head> + <html dir>.
python3 - "$W/learn-simply-arabic/html/email.html.twig" <<'PY'
import sys, re
f = sys.argv[1]
s = open(f, encoding='utf-8').read()
rtl = ('<style type="text/css">'
       "@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');"
       'html,body,table,td,th,p,h1,h2,h3,h4,a,div,span,li{'
       'direction:rtl !important;text-align:right !important;'
       'font-family:"Cairo","Tajawal",Arial,sans-serif !important;}'
       '</style>')
s = re.sub(r'<html(\s)', r'<html dir="rtl" lang="ar"\1', s, count=1)
s = s.replace('<head>', '<head>\n' + rtl, 1)
open(f, 'w', encoding='utf-8').write(s)
print("email.html.twig patched")
PY

# 3) Copy the finished theme back into the container.
docker exec "$C" rm -rf "$T/learn-simply-arabic"
docker cp "$W/learn-simply-arabic" "$C:$T/learn-simply-arabic"

# 4) Verify.
echo "=== config.json ==="; docker exec "$C" cat "$T/learn-simply-arabic/config.json"
echo "=== email head (first 8 lines) ==="; docker exec "$C" head -8 "$T/learn-simply-arabic/html/email.html.twig"
echo "=== present in themes dir ==="; docker exec "$C" ls "$T" | grep learn-simply || echo "NOT FOUND"
rm -rf "$W"
echo "DONE — open Mautic → new Email → theme picker → 'Learn Simply Arabic'."
