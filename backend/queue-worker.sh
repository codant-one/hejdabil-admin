#!/bin/bash
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
LOG_FILE="$SCRIPT_DIR/storage/logs/queue-worker-bootstrap.log"
FALLBACK_LOG_FILE="/tmp/queue-worker-bootstrap.log"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] START script_path='$0' script_dir='${SCRIPT_DIR}'" >> "$FALLBACK_LOG_FILE"

APP_BACKEND=""

if [ -f "$SCRIPT_DIR/.env" ]; then
	APP_BACKEND="$(grep -m1 '^APP_BACKEND=' "$SCRIPT_DIR/.env" | cut -d= -f2-)"
	APP_BACKEND="${APP_BACKEND%\r}"
	APP_BACKEND="${APP_BACKEND%\"}"
	APP_BACKEND="${APP_BACKEND#\"}"
else
	echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: no existe .env en '${SCRIPT_DIR}/.env'" >> "$FALLBACK_LOG_FILE"
fi

RAW_BACKEND_DOMAIN="${APP_BACKEND}"
RAW_BACKEND_DOMAIN="${RAW_BACKEND_DOMAIN%%,*}"
RAW_BACKEND_DOMAIN="${RAW_BACKEND_DOMAIN#http://}"
RAW_BACKEND_DOMAIN="${RAW_BACKEND_DOMAIN#https://}"
DOMAIN="${RAW_BACKEND_DOMAIN%%:*}"
DOMAIN="${DOMAIN%%/*}"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] APP_BACKEND='${APP_BACKEND}' | DOMAIN='${DOMAIN}'" >> "$LOG_FILE"
echo "[$(date '+%Y-%m-%d %H:%M:%S')] APP_BACKEND='${APP_BACKEND}' | DOMAIN='${DOMAIN}'" >> "$FALLBACK_LOG_FILE"

if [ -z "$DOMAIN" ]; then
	echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: DOMAIN vacío. Revisa APP_BACKEND en .env" >> "$LOG_FILE"
	echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: DOMAIN vacío. Revisa APP_BACKEND en .env" >> "$FALLBACK_LOG_FILE"
	exit 1
fi

TARGET_DIR="/home/u273956837/domains/${DOMAIN}/public_html/backend"

if [ ! -d "$TARGET_DIR" ]; then
	echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: no existe TARGET_DIR='${TARGET_DIR}'" >> "$LOG_FILE"
	echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: no existe TARGET_DIR='${TARGET_DIR}'" >> "$FALLBACK_LOG_FILE"
	exit 1
fi

cd "$TARGET_DIR"
echo "[$(date '+%Y-%m-%d %H:%M:%S')] RUN queue:work in TARGET_DIR='${TARGET_DIR}'" >> "$FALLBACK_LOG_FILE"
/usr/bin/php artisan queue:work database --stop-when-empty --queue=emails,default --tries=3 --timeout=120 >> storage/logs/queue.log 2>&1