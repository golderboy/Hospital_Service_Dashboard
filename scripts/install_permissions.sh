#!/usr/bin/env bash
set -euo pipefail

APP_PATH="${1:-/var/www/html/dashboard}"
WEB_GROUP="${2:-apache}"

mkdir -p "$APP_PATH/storage/logs" "$APP_PATH/storage/cache" "$APP_PATH/assets/tmp"
touch "$APP_PATH/storage/logs/web_audit.log" "$APP_PATH/storage/logs/etl_monitor.log"

chown -R root:"$WEB_GROUP" "$APP_PATH/storage" "$APP_PATH/assets/tmp"
chmod 2750 "$APP_PATH/storage"
chmod 2770 "$APP_PATH/storage/logs" "$APP_PATH/storage/cache" "$APP_PATH/assets/tmp"
chmod 0660 "$APP_PATH/storage/logs/web_audit.log" "$APP_PATH/storage/logs/etl_monitor.log"

echo "Permission setup complete for $APP_PATH"
