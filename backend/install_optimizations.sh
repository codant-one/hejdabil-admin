# Script de Instalación Rápida - Optimizaciones

## Paso 1: Backup de la base de datos
echo "Haciendo backup de la base de datos..."
# En Laragon, puedes usar phpMyAdmin o:
# mysqldump -u root -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

## Paso 2: Instalar dependencias de PHP (si es necesario)
cd backend
# composer install --no-dev --optimize-autoloader

## Paso 3: Ejecutar migraciones
echo "Ejecutando migraciones..."
php artisan migrate --force

## Paso 4: Configurar .env
echo "Asegúrate de que tu .env tenga estas configuraciones:"
cat >> .env << EOF

# ===== OPTIMIZACIONES =====
# Cache con Redis
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

# Queue con Redis
QUEUE_CONNECTION=redis

# Database Optimization
DB_PERSISTENT=true
DB_POOL_MIN=2
DB_POOL_MAX=20
EOF

## Paso 5: Limpiar cachés
echo "Limpiando cachés..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize

## Paso 6: Cache de configuración (solo en producción)
# php artisan config:cache
# php artisan route:cache
# php artisan view:cache

echo "¡Optimizaciones instaladas correctamente!"
echo ""
echo "SIGUIENTE PASO IMPORTANTE:"
echo "1. Asegúrate de que Redis esté corriendo (Laragon -> Redis -> Start)"
echo "2. Ejecuta el queue worker en una terminal separada:"
echo "   cd backend"
echo "   php artisan queue:work redis --tries=3 --timeout=120"
echo ""
echo "Para producción, configura Supervisor para gestionar el queue worker."
