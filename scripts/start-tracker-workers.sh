#!/bin/bash
# Startet alle Tracker-Worker: 
#  - database:tracker  → ProcessTrackerEventJob (raw events → player/slot/match/weapon)
#  - redis:tracker-high/low → PollServerJob und andere
#
# Usage: ./scripts/start-tracker-workers.sh
# Idempotent — killt vorhandene Worker und startet frisch.

cd /var/www/vhosts/wolffiles.eu/httpdocs/wolffiles-app

PHP=/opt/plesk/php/8.3/bin/php

# === Config ===
DB_WORKERS=10       # database:tracker → verarbeitet raw_events
REDIS_HIGH=10       # redis:tracker-high → verarbeitet PollServerJobs
REDIS_LOW=16       # redis:tracker-low → niedrige Priorität

# === Abräumen ===
echo "Stoppe alte Worker..."
pkill -f "artisan queue:work" 2>/dev/null
sleep 3
pkill -9 -f "artisan queue:work" 2>/dev/null
sleep 1

# === Start: database:tracker (ProcessTrackerEventJob) ===
echo "Starte $DB_WORKERS DB-Workers (database:tracker)..."
for i in $(seq 1 $DB_WORKERS); do
    nohup $PHP artisan queue:work database \
        --queue=tracker \
        --tries=3 --timeout=30 --sleep=1 \
        --backoff=5,30,120 \
        --max-time=3600 \
        >> storage/logs/worker-tracker.log 2>&1 &
done

# === Start: redis:tracker-high (PollServerJob etc.) ===
echo "Starte $REDIS_HIGH High-Priority Redis-Workers (tracker-high,default)..."
for i in $(seq 1 $REDIS_HIGH); do
    nohup $PHP artisan queue:work redis \
        --queue=tracker-high,default \
        --tries=2 --timeout=15 --sleep=1 \
        --max-time=3600 \
        >> storage/logs/worker-high.log 2>&1 &
done

# === Start: redis:tracker-low ===
echo "Starte $REDIS_LOW Low-Priority Redis-Workers (tracker-low,default)..."
for i in $(seq 1 $REDIS_LOW); do
    nohup $PHP artisan queue:work redis \
        --queue=tracker-low,default \
        --tries=2 --timeout=30 --sleep=2 \
        --max-time=3600 \
        >> storage/logs/worker-low.log 2>&1 &
done

sleep 2
TOTAL=$(ps aux | grep "artisan queue:work" | grep -v grep | wc -l)
echo ""
echo "✓ Fertig. Aktive Worker: $TOTAL (erwartet: $((DB_WORKERS + REDIS_HIGH + REDIS_LOW)))"
