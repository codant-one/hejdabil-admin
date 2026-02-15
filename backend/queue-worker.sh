#!/bin/bash
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_FILE="$SCRIPT_DIR/storage/logs/queue-worker-bootstrap.log"

set -a
source "$SCRIPT_DIR/.env"
set +a

RAW_BACKEND_DOMAIN="${APP_BACKEND%%,*}"
RAW_BACKEND_DOMAIN="${RAW_BACKEND_DOMAIN#http://}"
RAW_BACKEND_DOMAIN="${RAW_BACKEND_DOMAIN#https://}"
DOMAIN="${RAW_BACKEND_DOMAIN%%:*}"
DOMAIN="${DOMAIN%%/*}"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] APP_BACKEND='${APP_BACKEND}' | DOMAIN='${DOMAIN}'" >> "$LOG_FILE"

if [ -z "$DOMAIN" ]; then
	echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: DOMAIN vacío. Revisa APP_BACKEND en .env" >> "$LOG_FILE"
	exit 1
fi

TARGET_DIR="/home/u273956837/domains/${DOMAIN}/public_html/backend"

if [ ! -d "$TARGET_DIR" ]; then
	echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: no existe TARGET_DIR='${TARGET_DIR}'" >> "$LOG_FILE"
	exit 1
fi

cd "$TARGET_DIR"
/usr/bin/php artisan queue:work database --stop-when-empty --queue=emails,default --tries=3 --timeout=120 >> storage/logs/queue.log 2>&1