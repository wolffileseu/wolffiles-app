#!/bin/bash
# Prüft ob genug Tracker-Worker laufen. Startet fehlende nach.
# Läuft alle 5 Minuten via Cron.
# Silent bei OK; loggt nur wenn restart nötig.

APP_DIR=/var/www/vhosts/wolffiles.eu/httpdocs_new/wolffiles-app
LOG=$APP_DIR/storage/logs/keepalive.log
MIN_TOTAL=10   # unter dieser Zahl wird neu gestartet

# Nur Tracker-Worker zählen (filtert andere php-Jobs raus)
RUNNING=$(ps -eo pid,cmd | grep -E "queue:work (redis|database)" | grep -E "tracker" | grep -v grep | wc -l)

if [ "$RUNNING" -lt "$MIN_TOTAL" ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Nur $RUNNING/$MIN_TOTAL Worker aktiv, starte nach..." >> $LOG
    $APP_DIR/scripts/start-tracker-workers.sh >> $LOG 2>&1
    AFTER=$(ps -eo pid,cmd | grep -E "queue:work (redis|database)" | grep -E "tracker" | grep -v grep | wc -l)
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Nach Restart: $AFTER Worker aktiv" >> $LOG
fi
