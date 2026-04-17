#!/usr/bin/env bash
#
# Start script for the Wolffiles Enhanced Tracker UDP listener daemon.
# Run via PM2 — see pm2 list / pm2 logs tracker-listener
#
# This wrapper ensures:
#   - we're in the correct working directory (Laravel app root)
#   - PHP binary is the one configured via phpenv
#   - exec replaces the shell so PM2 gets the actual PHP process PID
#   - any startup errors surface cleanly in PM2 logs

set -euo pipefail

APP_DIR="/var/www/vhosts/wolffiles.eu/httpdocs_new/wolffiles-app"
PHP_BIN="/var/www/vhosts/wolffiles.eu/.phpenv/shims/php"

# Fallback if phpenv shim is missing (shouldn't happen, but safety net)
if [ ! -x "$PHP_BIN" ]; then
    PHP_BIN="$(command -v php)"
fi

if [ ! -x "$PHP_BIN" ]; then
    echo "FATAL: php binary not found" >&2
    exit 1
fi

if [ ! -d "$APP_DIR" ]; then
    echo "FATAL: app directory not found: $APP_DIR" >&2
    exit 1
fi

cd "$APP_DIR"

# Print banner so PM2 logs show what started
echo "=================================="
echo "Wolffiles Enhanced Tracker Listener"
echo "=================================="
echo "Date:     $(date -Iseconds)"
echo "PHP:      $PHP_BIN"
echo "PHP ver:  $($PHP_BIN -r 'echo PHP_VERSION;')"
echo "App dir:  $APP_DIR"
echo "User:     $(whoami)"
echo "PID:      $$"
echo "=================================="
echo

# exec replaces the shell process so PM2 tracks PHP directly.
# --verbose (-v) shows each received packet in pm2 logs — remove after testing.
exec "$PHP_BIN" artisan tracker:listen -v
