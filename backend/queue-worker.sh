#!/bin/bash
cd /home/u273956837/domains/${SANCTUM_STATEFUL_DOMAINS}/public_html/backend
/usr/bin/php artisan queue:work database --stop-when-empty --queue=emails,default --tries=3 --timeout=120 >> storage/logs/queue.log 2>&1