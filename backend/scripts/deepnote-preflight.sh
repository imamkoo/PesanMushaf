#!/usr/bin/env bash

set -euo pipefail

required_commands=(php composer git psql)

for command in "${required_commands[@]}"; do
    if ! command -v "$command" >/dev/null 2>&1; then
        echo "Missing required command: $command" >&2
        exit 1
    fi
done

php_version="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"

if ! php -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);'; then
    echo "PHP 8.3+ is required. Found: $php_version" >&2
    exit 1
fi

echo "Deepnote backend preflight passed."
echo "PHP version: $(php -r 'echo PHP_VERSION;')"
echo "Composer version: $(composer --version | sed 's/^Composer version //')"
echo "Git version: $(git --version | sed 's/^git version //')"
echo "psql version: $(psql --version | sed 's/^psql (PostgreSQL) //')"
echo
echo "Next steps:"
echo "1. Copy .env.deepnote.example to .env and fill the real values."
echo "2. Run ./scripts/deepnote-bootstrap.sh"
echo "3. Run ./scripts/deepnote-serve.sh and enable Deepnote incoming connections."
