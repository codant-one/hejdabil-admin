# Production Configuration

This document explains how to configure optimizations on a production server.

## 1. Supervisor for Queue Worker

Supervisor is essential to keep the queue worker running in production.

### Install Supervisor (Ubuntu/Debian)

```bash
sudo apt-get install supervisor
sudo systemctl enable supervisor
sudo systemctl start supervisor
```

### Supervisor Configuration

Create file: `/etc/supervisor/conf.d/hejdabil-worker.conf`

```ini
[program:hejdabil-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/hejdabil-admin/backend/artisan queue:work redis --sleep=3 --tries=3 --timeout=120 --max-jobs=1000
autostart=true
autorestart=true
stopasec=10
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/hejdabil-admin/backend/storage/logs/worker.log
stopwaitsecs=3600
```

### Start Supervisor

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start hejdabil-worker:*
```

### Check status

```bash
sudo supervisorctl status hejdabil-worker:*
```

### View logs

```bash
tail -f /var/www/hejdabil-admin/backend/storage/logs/worker.log
```

## 2. Redis Configuration

### Redis as Service

```bash
sudo systemctl enable redis-server
sudo systemctl start redis-server
sudo systemctl status redis-server
```

### Redis Configuration (/etc/redis/redis.conf)

```ini
# Maximum memory
maxmemory 512mb
maxmemory-policy allkeys-lru

# Persistence
save 900 1
save 300 10
save 60 10000

# Security (if needed)
requirepass your_secure_password

# Performance
tcp-backlog 511
timeout 300
tcp-keepalive 300
```

### Restart Redis after changes

```bash
sudo systemctl restart redis-server
```

## 3. Nginx Configuration

### Nginx Optimization

File: `/etc/nginx/sites-available/hejdabil-admin`

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/hejdabil-admin/backend/public;

    index index.php index.html;

    # Enable GZIP Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1000;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;

    # Cache static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # PHP processing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Increase timeout for long-running requests
        fastcgi_read_timeout 300;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }
}
```

### Enable site and restart Nginx

```bash
sudo ln -s /etc/nginx/sites-available/hejdabil-admin /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

## 4. PHP Configuration

### PHP-FPM Optimization

File: `/etc/php/8.2/fpm/pool.d/www.conf`

```ini
; Process manager
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500

; Increase timeouts
request_terminate_timeout = 300
```

### PHP.ini Optimization

File: `/etc/php/8.2/fpm/php.ini`

```ini
; Memory
memory_limit = 256M
max_execution_time = 300

; OPcache
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
opcache.fast_shutdown=1

; Realpath cache
realpath_cache_size=4096K
realpath_cache_ttl=600
```

### Restart PHP-FPM

```bash
sudo systemctl restart php8.2-fpm
```

## 5. MySQL/MariaDB Optimization

File: `/etc/mysql/mysql.conf.d/mysqld.cnf` or `/etc/my.cnf`

```ini
[mysqld]
# Connection handling
max_connections = 200
connect_timeout = 10
wait_timeout = 600
max_allowed_packet = 64M

# Memory
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_method = O_DIRECT

# Query cache (MySQL 5.7 only, removed in 8.0)
query_cache_type = 1
query_cache_size = 256M
query_cache_limit = 2M

# Logging (disable in production for performance)
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 2
```

### Restart MySQL

```bash
sudo systemctl restart mysql
```

## 6. Environment Variables (.env)

Production `.env` file:

```env
APP_NAME="Hejdabil Admin"
APP_ENV=production
APP_KEY=base64:your-key-here
APP_DEBUG=false
APP_URL=https://your-domain.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hejdabil_admin
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password
DB_PERSISTENT=true
DB_POOL_MIN=2
DB_POOL_MAX=20

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"
```

## 7. Deployment Process

### Initial Setup

```bash
# Clone repository
cd /var/www
git clone your-repo-url hejdabil-admin
cd hejdabil-admin/backend

# Install dependencies
composer install --no-dev --optimize-autoloader

# Set permissions
sudo chown -R www-data:www-data /var/www/hejdabil-admin
sudo chmod -R 755 /var/www/hejdabil-admin
sudo chmod -R 775 storage bootstrap/cache

# Run migrations
php artisan migrate --force

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Update Deployment

```bash
cd /var/www/hejdabil-admin/backend

# Put application in maintenance mode
php artisan down

# Pull latest changes
git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader

# Run new migrations
php artisan migrate --force

# Clear and rebuild cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Restart queue workers
php artisan queue:restart

# Bring application back online
php artisan up
```

## 8. Monitoring

### Application Monitoring

```bash
# Check performance
php artisan monitor:performance

# View queue status
php artisan queue:monitor

# View failed jobs
php artisan queue:failed
```

### Server Monitoring

```bash
# CPU and Memory
htop

# Disk usage
df -h

# MySQL processes
mysqladmin -u root -p processlist

# Redis statistics
redis-cli info stats
redis-cli info memory

# Nginx logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log

# Laravel logs
tail -f /var/www/hejdabil-admin/backend/storage/logs/laravel.log

# Supervisor logs
sudo tail -f /var/log/supervisor/supervisord.log
```

## 9. Backup Strategy

### Database Backup

Create script: `/usr/local/bin/backup-hejdabil.sh`

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/hejdabil"
DB_NAME="hejdabil_admin"
DB_USER="your_db_user"
DB_PASS="your_db_password"

mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup storage files
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz /var/www/hejdabil-admin/backend/storage

# Keep only last 7 days
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +7 -delete
find $BACKUP_DIR -name "storage_*.tar.gz" -mtime +7 -delete
```

Make executable and add to cron:

```bash
sudo chmod +x /usr/local/bin/backup-hejdabil.sh
sudo crontab -e

# Add line (daily backup at 2 AM):
0 2 * * * /usr/local/bin/backup-hejdabil.sh
```

## 10. SSL/HTTPS Configuration

### Install Certbot

```bash
sudo apt-get install certbot python3-certbot-nginx
```

### Obtain Certificate

```bash
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

### Auto-renewal

```bash
# Test renewal
sudo certbot renew --dry-run

# Certificate auto-renews via systemd timer
sudo systemctl status certbot.timer
```

## 11. Security Checklist

- ✅ APP_DEBUG=false in production
- ✅ Strong APP_KEY generated
- ✅ Database credentials secured
- ✅ Redis password set (if exposed)
- ✅ HTTPS enabled
- ✅ Firewall configured (UFW)
- ✅ SSH key-based authentication
- ✅ Regular backups automated
- ✅ Storage permissions correct (775 max)
- ✅ .env file not in repository
- ✅ Composer install with --no-dev
- ✅ Failed2ban configured for SSH

## 12. Troubleshooting

### Queue worker not processing jobs

```bash
# Check supervisor status
sudo supervisorctl status

# Restart supervisor
sudo supervisorctl restart hejdabil-worker:*

# View worker logs
tail -f /var/www/hejdabil-admin/backend/storage/logs/worker.log
```

### High memory usage

```bash
# Check Redis memory
redis-cli info memory

# Clear cache
php artisan cache:clear

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

### Slow queries

```bash
# Enable slow query log in MySQL
# Check /var/log/mysql/slow-query.log

# Add missing indexes
# See OPTIMIZACIONES.md for recommended indexes
```

---

**Last updated:** February 9, 2026
