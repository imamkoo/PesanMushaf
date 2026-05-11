#!/usr/bin/env bash

set -euo pipefail

BASE_URL="${1:-http://127.0.0.1:8080}"

check_json_endpoint() {
    local path="$1"

    echo "Checking ${BASE_URL}${path}"
    curl --fail --silent --show-error "${BASE_URL}${path}" >/dev/null
}

check_html_endpoint() {
    local path="$1"

    echo "Checking ${BASE_URL}${path}"
    curl --fail --silent --show-error --location "${BASE_URL}${path}" >/dev/null
}

check_json_endpoint "/up"
check_json_endpoint "/api/districts"
check_json_endpoint "/api/batches"
check_json_endpoint "/api/price-categories"
check_html_endpoint "/admin"

echo "Deepnote smoke check passed for ${BASE_URL}"
