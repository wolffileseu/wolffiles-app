#!/usr/bin/env bash
#
# Start script for the Wolffiles Tracker Queue Worker.
# Runs via PM2 alongside tracker-listener.

set -euo pipefail

APP_DIR="/var/www/vhosts/wolffiles.eu/httpdocs/wolffiles-app"
PHP_BIN="/var/www/vhosts/wolffiles.eu/.phpenv/shims/php"

if [ ! -x "$PHP_BIN" ]; then
    PHP_BIN="$(command -v php)"
fi

if [ ! -x "$PHP_BIN" ]; then
    echo "FATAL: php binary not found" >&2
    exit 1
fi

cd "$APP_DIR"

echo "=================================="
echo "Wolffiles Tracker Queue Worker"
echo "=================================="
echo "Date:     $(date -Iseconds)"
echo "PHP:      $PHP_BIN"
echo "App dir:  $APP_DIR"
echo "Queue:    tracker"
echo "PID:      $$"
echo "=================================="
echo

exec "$PHP_BIN" artisan queue:work \
    --queue=tracker \
    --tries=3 \
    --timeout=60 \
    --sleep=3 \
    --max-time=3600 \
    --backoff=5,30,120 \
    -v
