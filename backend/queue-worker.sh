#!/bin/bash
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

set -a
source "$SCRIPT_DIR/.env"
set +a

DOMAIN="${SANCTUM_STATEFUL_DOMAINS%%,*}"

cd /home/u273956837/domains/${DOMAIN}/public_html/backend
/usr/bin/php artisan queue:work database --stop-when-empty --queue=emails,default --tries=3 --timeout=120 >> storage/logs/queue.log 2>&1