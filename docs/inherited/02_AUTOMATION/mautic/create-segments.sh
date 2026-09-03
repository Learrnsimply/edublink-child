#!/bin/bash
# Create Learn Simply strategic segments in Mautic via API
# Credentials sourced from brand .env (no hardcoded secrets)
#
# Usage:
#   export MAUTIC_API_USER=omar
#   export MAUTIC_API_PASSWORD=<from-bitwarden>
#   bash create-segments.sh
# Segments are DYNAMIC — Mautic cron rebuilds membership every 15 min

set -e

: "${MAUTIC_API_USER:?MAUTIC_API_USER not set — source brand .env or export it}"
: "${MAUTIC_API_PASSWORD:[REDACTED] not set — source brand .env or export it}"

API="${MAUTIC_API_URL:-https://mautic.learrnsimply.com/api}"
AUTH="${MAUTIC_API_USER}:${MAUTIC_API_PASSWORD}"

create_segment() {
    local name="$1"
    local alias="$2"
    local description="$3"
    local filters="$4"

    local payload="{\"name\":\"$name\",\"alias\":\"$alias\",\"description\":\"$description\",\"isPublished\":true,\"isGlobal\":true,\"filters\":$filters}"

    echo "Creating segment: $alias ..."
    response=$(curl -s -u "$AUTH" -X POST -H "Content-Type: application/json" -d "$payload" "$API/segments/new")
    if echo "$response" | grep -q '"id"'; then
        id=$(echo "$response" | grep -oP '"id":\s*\K[0-9]+' | head -1)
        echo "  ✓ Created with ID $id"
    else
        echo "  ✗ Failed: $response"
    fi
}

# === ALL CONTACTS (baseline for broadcast scoping) ===
create_segment "All Contacts" "all_contacts" "Every contact with a valid email" \
'[{"glue":"and","field":"email","object":"lead","type":"email","operator":"!empty","filter":"","display":""}]'

# === ENGAGEMENT TIERS ===
create_segment "Engaged Last 30 Days" "engaged_30d" "Active engagement signal in last 30 days — primary broadcast target" \
'[{"glue":"and","field":"last_active","object":"lead","type":"datetime","operator":"gte","filter":"-30 days","display":""}]'

create_segment "Dormant 90+ Days" "dormant_90d" "Re-engagement targets — the bulk of the 13K list" \
'[{"glue":"and","field":"email","object":"lead","type":"email","operator":"!empty","filter":"","display":""},{"glue":"and","field":"last_active","object":"lead","type":"datetime","operator":"lt","filter":"-90 days","display":""}]'

# === BUYER SEGMENTS ===
create_segment "WC Buyers" "wc_buyers" "Has at least 1 WooCommerce purchase" \
'[{"glue":"and","field":"course_count","object":"lead","type":"number","operator":"gt","filter":"0","display":""}]'

create_segment "High Value Buyers" "high_value" "Total spent > 5000 EGP — VIP segment" \
'[{"glue":"and","field":"total_spent","object":"lead","type":"number","operator":"gt","filter":"5000","display":""}]'

create_segment "Non-Buyers" "non_buyers" "Subscribed but never purchased — top conversion target" \
'[{"glue":"and","field":"email","object":"lead","type":"email","operator":"!empty","filter":"","display":""},{"glue":"and","field":"course_count","object":"lead","type":"number","operator":"empty","filter":"","display":""}]'

# === CART RECOVERY ===
create_segment "Active Cart" "active_cart" "Cart value > 0 — abandoned cart recovery target" \
'[{"glue":"and","field":"cart_value","object":"lead","type":"number","operator":"gt","filter":"0","display":""}]'

# === CHANNEL SEGMENTS ===
create_segment "WhatsApp Contacts" "whatsapp_contacts" "Has WhatsApp phone — for W3 agent" \
'[{"glue":"and","field":"whatsapp_phone","object":"lead","type":"text","operator":"!empty","filter":"","display":""}]'

create_segment "Telegram Contacts" "telegram_contacts" "Has Telegram chat ID — for W4 agent" \
'[{"glue":"and","field":"telegram_chat_id","object":"lead","type":"text","operator":"!empty","filter":"","display":""}]'

echo ""
echo "=== DONE ==="
curl -s -u "$AUTH" "$API/segments?limit=100" | grep -oP '"total":"?\K[0-9]+' | head -1
