#!/bin/bash
# Create Learn Simply custom contact fields in Mautic via API
# Credentials sourced from brand .env (no hardcoded secrets)
#
# Usage:
#   export MAUTIC_API_USER=omar
#   export MAUTIC_API_PASSWORD=<from-bitwarden>
#   bash create-custom-fields.sh
# OR:
#   source /path/to/brand/.env && bash create-custom-fields.sh

set -e

: "${MAUTIC_API_USER:?MAUTIC_API_USER not set — source brand .env or export it}"
: "${MAUTIC_API_PASSWORD:[REDACTED] not set — source brand .env or export it}"

API="${MAUTIC_API_URL:-https://mautic.learrnsimply.com/api}"
AUTH="${MAUTIC_API_USER}:${MAUTIC_API_PASSWORD}"

create_field() {
    local label="$1"
    local alias="$2"
    local type="$3"
    local extra="$4"

    local payload="{\"label\":\"$label\",\"alias\":\"$alias\",\"type\":\"$type\",\"group\":\"core\",\"object\":\"lead\",\"isVisible\":true,\"isPublished\":true,\"isListable\":true${extra:+,$extra}}"

    echo "Creating field: $alias ($type)..."
    response=$(curl -s -u "$AUTH" -X POST -H "Content-Type: application/json" -d "$payload" "$API/fields/contact/new")
    if echo "$response" | grep -q '"id"'; then
        id=$(echo "$response" | grep -oP '"id":\s*\K[0-9]+' | head -1)
        echo "  ✓ Created with ID $id"
    else
        echo "  ✗ Failed: $response"
    fi
}

# === CORE BUSINESS FIELDS ===
create_field "WC Customer ID" "wc_customer_id" "number"
create_field "Course Interest" "course_interest" "text" '"charLengthLimit":191'
create_field "Last Purchase Date" "last_purchase_date" "datetime"
create_field "Course Count" "course_count" "number"
create_field "Total Spent (EGP)" "total_spent" "number"
create_field "Cart Value (EGP)" "cart_value" "number"
create_field "Last Course Completed" "last_course_completed" "text" '"charLengthLimit":191'

# === ACQUISITION CHANNEL ===
create_field "Source Channel" "source_channel" "select" '"properties":{"list":[{"label":"Website","value":"website"},{"label":"WhatsApp","value":"whatsapp"},{"label":"Telegram","value":"telegram"},{"label":"Facebook","value":"facebook"},{"label":"Instagram","value":"instagram"},{"label":"YouTube","value":"youtube"},{"label":"TikTok","value":"tiktok"},{"label":"Direct","value":"direct"},{"label":"Other","value":"other"}]}'

# === MULTI-CHANNEL IDs ===
create_field "Telegram Chat ID" "telegram_chat_id" "text" '"charLengthLimit":100'
create_field "WhatsApp Phone (E164)" "whatsapp_phone" "text" '"charLengthLimit":20'

# === REFERRER / UTM ===
create_field "Referrer / UTM Source" "referrer" "text" '"charLengthLimit":191'

echo ""
echo "=== DONE ==="
curl -s -u "$AUTH" "$API/fields/contact?limit=100" | grep -oP '"total":"?\K[0-9]+' | head -1
