#!/bin/bash
cd /home/u273956837/domains/stagingbackend.billogg.se/public_html/backend
/usr/bin/php artisan queue:work redis --stop-when-empty --queue=emails,default --tries=3 --timeout=120 >> storage/logs/queue.log 2>&1