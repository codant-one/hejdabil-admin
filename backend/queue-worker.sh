#!/bin/bash
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="$SCRIPT_DIR/.env"

APP_BACKEND=$(grep -E '^APP_BACKEND=' "$ENV_FILE" | head -n 1 | cut -d '=' -f2- | tr -d '"\r')

if [ -z "$APP_BACKEND" ]; then
	echo "APP_BACKEND no está definido en $ENV_FILE"
	exit 1
fi

cd "/home/u273956837/domains/${APP_BACKEND}/public_html/backend" || exit 1
/usr/bin/php artisan queue:work database --stop-when-empty --queue=emails,default --tries=3 --timeout=120 >> storage/logs/queue.log 2>&1