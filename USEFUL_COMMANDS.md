# Useful Commands - Hejdabil Admin

## Cache Management

```bash
# View general system status
php artisan monitor:performance

# Clear all system caches
php artisan monitor:performance --clear-cache

# Or individually:
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize in production (after deploy)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

## Queue Management

```bash
# View queue status
php artisan queue:monitor

# Start worker (development)
php artisan queue:work redis --tries=3 --timeout=120

# Start worker with verbose output (debug)
php artisan queue:work redis --tries=3 --timeout=120 --verbose

# View failed jobs
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

# Retry specific job
php artisan queue:retry [job-id]

# Delete all failed jobs
php artisan queue:flush

# Restart workers (to apply code changes)
php artisan queue:restart
```

## Migrations and Database

```bash
# Run pending migrations
php artisan migrate

# Run migrations (production)
php artisan migrate --force

# View migration status
php artisan migrate:status

# Rollback last migration
php artisan migrate:rollback

# Rollback all migrations
php artisan migrate:reset

# Refresh database (WARNING: deletes data)
php artisan migrate:fresh

# Refresh database and run seeders
php artisan migrate:fresh --seed
```

## Tinker (Interactive Console)

```bash
# Start tinker
php artisan tinker

# Examples in tinker:
>>> use App\Models\Vehicle;
>>> Vehicle::count()
>>> use App\Services\CacheService;
>>> app('App\Services\CacheService')->getBrands()
>>> cache()->get('brands.all')
```

## Redis

```bash
# Connect to Redis CLI
redis-cli

# In Redis CLI:
> PING                    # Verify connection
> INFO stats              # View statistics
> INFO memory             # View memory usage
> KEYS *                  # List all keys (DON'T use in production)
> LLEN queues:emails      # Queue length
> FLUSHALL                # Clear everything (DANGEROUS!)
> exit                    # Exit Redis CLI
```

## Cache Service Testing

```bash
# Start tinker
php artisan tinker

# Test cache service
>>> $cache = app('App\Services\CacheService');
>>> $brands = $cache->getBrands();
>>> count($brands)
>>> $cache->clearCatalogCache();
>>> cache()->flush()
```

## Performance Monitoring

```bash
# General system status
php artisan monitor:performance

# Database queries (enable query log)
php artisan tinker
>>> DB::enableQueryLog();
>>> App\Models\Vehicle::with('model.brand')->first();
>>> DB::getQueryLog();

# Memory usage
php artisan tinker
>>> memory_get_usage(true) / 1024 / 1024 . ' MB'

# View Redis keys
redis-cli
> SCAN 0 MATCH brands* COUNT 100
```

## Logs

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Real-time log monitoring
tail -f storage/logs/laravel.log | grep ERROR

# Last 100 lines
tail -n 100 storage/logs/laravel.log

# Clear logs (careful!)
> storage/logs/laravel.log

# Using PowerShell (Windows)
Get-Content storage\logs\laravel.log -Tail 50
Get-Content storage\logs\laravel.log -Wait  # Real-time
```

## Git Operations

```bash
# View status
git status

# Create branch
git checkout -b feature/new-feature

# Commit changes
git add .
git commit -m "Description of changes"

# Push to remote
git push origin feature/new-feature

# Merge branch
git checkout main
git merge feature/new-feature

# View history
git log --oneline --graph --all

# Undo last commit (keep changes)
git reset --soft HEAD~1

# Undo last commit (discard changes)
git reset --hard HEAD~1
```

## Permissions (Linux/Mac)

```bash
# Set correct permissions
sudo chown -R www-data:www-data ./
sudo chmod -R 755 ./
sudo chmod -R 775 storage bootstrap/cache

# Fix permission issues
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## Composer

```bash
# Install dependencies
composer install

# Install without dev dependencies (production)
composer install --no-dev --optimize-autoloader

# Update all packages
composer update

# Update specific package
composer update vendor/package

# View outdated packages
composer outdated

# Clear composer cache
composer clear-cache
```

## NPM/Node (Frontend)

```bash
# Install dependencies
npm install

# Development build
npm run dev

# Production build
npm run build

# Watch for changes
npm run watch

# Clean install (delete node_modules)
rm -rf node_modules package-lock.json
npm install
```

## Server Management (Production)

```bash
# Supervisor (queue workers)
sudo supervisorctl status
sudo supervisorctl restart hejdabil-worker:*
sudo supervisorctl stop hejdabil-worker:*
sudo supervisorctl start hejdabil-worker:*

# Nginx
sudo systemctl status nginx
sudo systemctl restart nginx
sudo systemctl reload nginx
sudo nginx -t  # Test configuration

# PHP-FPM
sudo systemctl status php8.2-fpm
sudo systemctl restart php8.2-fpm

# MySQL
sudo systemctl status mysql
sudo systemctl restart mysql

# Redis
sudo systemctl status redis-server
sudo systemctl restart redis-server
```

## Database Backup and Restore

```bash
# Backup database
mysqldump -u username -p database_name > backup.sql

# Backup with GZIP compression
mysqldump -u username -p database_name | gzip > backup.sql.gz

# Restore database
mysql -u username -p database_name < backup.sql

# Restore from GZIP
gunzip < backup.sql.gz | mysql -u username -p database_name
```

## Testing

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/VehicleTest.php

# Run with coverage
php artisan test --coverage

# Run specific test method
php artisan test --filter testCanCreateVehicle
```

## Optimization Commands

```bash
# Full optimization (production)
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear all optimizations (development)
php artisan optimize:clear

# Generate autoload files
composer dump-autoload

# Generate IDE helper files (if installed)
php artisan ide-helper:generate
php artisan ide-helper:models
php artisan ide-helper:meta
```

## Debugging

```bash
# Enable maintenance mode
php artisan down

# Disable maintenance mode
php artisan up

# Maintenance mode with secret
php artisan down --secret="your-secret-token"
# Access with: https://your-site.com/your-secret-token

# View routes
php artisan route:list

# View routes for specific name
php artisan route:list --name=vehicle

# View events and listeners
php artisan event:list

# View scheduled tasks
php artisan schedule:list

# Run scheduled tasks manually
php artisan schedule:run
```

## Redis Debugging

```bash
# View all keys (use with caution)
redis-cli KEYS "*"

# View specific key value
redis-cli GET "brands.all"

# Delete specific key
redis-cli DEL "brands.all"

# View key type
redis-cli TYPE "brands.all"

# View list length
redis-cli LLEN "queues:emails"

# View list contents
redis-cli LRANGE "queues:emails" 0 -1

# Monitor Redis commands in real-time
redis-cli MONITOR

# Redis memory analysis
redis-cli --bigkeys
redis-cli --memkeys
```

## Custom Artisan Commands

```bash
# Performance monitoring
php artisan monitor:performance
php artisan monitor:performance --clear-cache

# Clear all catalog cache
php artisan cache:forget brands.all
php artisan cache:forget models.all
php artisan cache:forget gearboxes.all
```

## Quick Fixes

```bash
# "Class not found" error
composer dump-autoload

# "Configuration cached" error
php artisan config:clear

# "Route cached" error
php artisan route:clear

# "View not found" error
php artisan view:clear

# Permission denied on storage
sudo chmod -R 775 storage bootstrap/cache

# Queue not processing
php artisan queue:restart
```

## Development Workflow

```bash
# Start development
1. php artisan serve                    # Start Laravel server
2. npm run dev                          # Start frontend build
3. php artisan queue:work redis         # Start queue worker

# After code changes
php artisan optimize:clear              # Clear caches
php artisan queue:restart               # Restart workers
composer dump-autoload                  # Reload classes

# Before committing
php artisan test                        # Run tests
php artisan route:list                  # Verify routes
git status                              # Check changes
```

---

**Quick Reference Card**

| Task | Command |
|------|---------|
| Clear everything | `php artisan optimize:clear` |
| Cache everything | `php artisan optimize` |
| Check system | `php artisan monitor:performance` |
| Start queue | `php artisan queue:work redis` |
| View failed jobs | `php artisan queue:failed` |
| Restart workers | `php artisan queue:restart` |
| Run migrations | `php artisan migrate` |
| Clear Redis | `redis-cli FLUSHALL` |
| View logs | `tail -f storage/logs/laravel.log` |
| Test Redis | `redis-cli ping` |

---

**Last updated:** February 9, 2026
