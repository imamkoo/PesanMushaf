#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="${ROOT_DIR}/.env"

if [[ ! -f "$ENV_FILE" ]]; then
    echo "Missing .env file." >&2
    exit 1
fi

set -a
source "$ENV_FILE"
set +a

if [[ -z "${APP_URL:-}" ]]; then
    echo "APP_URL is required." >&2
    exit 1
fi

if [[ "${MIDTRANS_IS_PRODUCTION:-}" != "false" ]]; then
    echo "MIDTRANS_IS_PRODUCTION must be false for Deepnote sandbox trial." >&2
    exit 1
fi

if [[ -z "${MIDTRANS_CLIENT_KEY:-}" || -z "${MIDTRANS_SERVER_KEY:-}" ]]; then
    echo "MIDTRANS_CLIENT_KEY and MIDTRANS_SERVER_KEY are required." >&2
    exit 1
fi

notification_url="${APP_URL%/}/api/midtrans/notification"

cat <<EOF
Midtrans sandbox configuration looks ready.

Notification URL:
${notification_url}

Next checks:
1. Set the sandbox notification URL in Midtrans Dashboard to the URL above.
2. From the frontend, create a test transaction and confirm POST /api/midtrans/snap-token succeeds.
3. Complete a sandbox payment and confirm POST /api/midtrans/sync-status updates the registration status.
EOF
