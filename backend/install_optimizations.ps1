# Windows PowerShell - Script de Instalación Rápida

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  INSTALACIÓN DE OPTIMIZACIONES" -ForegroundColor Cyan
Write-Host "  Hejdabil Admin - Backend" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Paso 1: Verificar que estamos en el directorio correcto
$currentDir = Get-Location
if (-not (Test-Path ".\artisan")) {
    Write-Host "ERROR: Este script debe ejecutarse desde el directorio 'backend'" -ForegroundColor Red
    Write-Host "Por favor, ejecuta: cd backend" -ForegroundColor Yellow
    exit 1
}

Write-Host "[1/7] Verificando directorio... OK" -ForegroundColor Green

# Paso 2: Verificar que .env existe
if (-not (Test-Path ".\.env")) {
    Write-Host "ERROR: Archivo .env no encontrado" -ForegroundColor Red
    Write-Host "Copia .env.example a .env y configura tus credenciales" -ForegroundColor Yellow
    exit 1
}

Write-Host "[2/7] Verificando configuración... OK" -ForegroundColor Green

# Paso 3: Agregar configuraciones al .env si no existen
Write-Host "[3/7] Actualizando .env..." -ForegroundColor Yellow

$envPath = ".\.env"
$envContent = Get-Content $envPath -Raw

# Función para actualizar o agregar variable en .env
function Update-EnvVariable {
    param (
        [string]$Content,
        [string]$Key,
        [string]$Value
    )
    
    $pattern = "^$Key=.*$"
    if ($Content -match $pattern) {
        # Actualizar existente
        return $Content -replace $pattern, "$Key=$Value"
    } else {
        # Agregar nueva
        return $Content + "`n$Key=$Value"
    }
}

# Actualizar variables individuales
$envContent = Update-EnvVariable -Content $envContent -Key "CACHE_DRIVER" -Value "redis"
$envContent = Update-EnvVariable -Content $envContent -Key "QUEUE_CONNECTION" -Value "redis"
$envContent = Update-EnvVariable -Content $envContent -Key "REDIS_DB" -Value "0"
$envContent = Update-EnvVariable -Content $envContent -Key "REDIS_CACHE_DB" -Value "1"
$envContent = Update-EnvVariable -Content $envContent -Key "DB_PERSISTENT" -Value "true"
$envContent = Update-EnvVariable -Content $envContent -Key "DB_POOL_MIN" -Value "2"
$envContent = Update-EnvVariable -Content $envContent -Key "DB_POOL_MAX" -Value "20"

# Agregar sección de optimizaciones si no existe
if (-not $envContent.Contains("# ===== OPTIMIZACIONES =====")) {
    $envContent += "`n`n# ===== OPTIMIZACIONES =====`n# Ver OPTIMIZACIONES.md para más detalles"
}

# Guardar cambios
Set-Content -Path $envPath -Value $envContent -NoNewline
Write-Host "    Variables actualizadas en .env" -ForegroundColor Green

# Paso 4: Limpiar cachés
Write-Host "[4/7] Limpiando cachés..." -ForegroundColor Yellow
php artisan cache:clear | Out-Null
php artisan config:clear | Out-Null
php artisan route:clear | Out-Null
php artisan view:clear | Out-Null
Write-Host "    Cachés limpiados" -ForegroundColor Green

# Paso 5: Ejecutar migraciones
Write-Host "[5/7] Ejecutando migraciones..." -ForegroundColor Yellow
$migrationOutput = php artisan migrate --force 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "    Migraciones ejecutadas correctamente" -ForegroundColor Green
} else {
    Write-Host "    WARNING: Algunas migraciones pueden haber fallado" -ForegroundColor Yellow
    Write-Host "    Revisa el output arriba" -ForegroundColor Yellow
}

# Paso 6: Optimizar autoloader
Write-Host "[6/7] Optimizando autoloader..." -ForegroundColor Yellow
php artisan optimize | Out-Null
Write-Host "    Optimizaciones aplicadas" -ForegroundColor Green

# Paso 7: Verificar Redis
Write-Host "[7/7] Verificando Redis..." -ForegroundColor Yellow
try {
    $redisCheck = redis-cli ping 2>&1
    if ($redisCheck -eq "PONG") {
        Write-Host "    Redis está corriendo - OK" -ForegroundColor Green
    } else {
        throw "Redis no responde"
    }
} catch {
    Write-Host "    WARNING: Redis no está corriendo o no está instalado" -ForegroundColor Yellow
    Write-Host "    En Laragon, inicia Redis desde: Menu -> Redis -> Start" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  INSTALACIÓN COMPLETADA" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "PRÓXIMOS PASOS IMPORTANTES:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Asegúrate de que Redis esté corriendo:" -ForegroundColor White
Write-Host "   Laragon -> Menu -> Redis -> Start" -ForegroundColor Gray
Write-Host ""
Write-Host "2. Inicia el Queue Worker en una terminal SEPARADA:" -ForegroundColor White
Write-Host "   cd backend" -ForegroundColor Gray
Write-Host "   php artisan queue:work redis --tries=3 --timeout=120" -ForegroundColor Gray
Write-Host ""
Write-Host "3. Para desarrollo, el queue worker debe estar corriendo siempre" -ForegroundColor White
Write-Host ""
Write-Host "4. Verifica las optimizaciones:" -ForegroundColor White
Write-Host "   php artisan queue:monitor" -ForegroundColor Gray
Write-Host ""
Write-Host "5. Revisa el archivo OPTIMIZACIONES.md para más detalles" -ForegroundColor White
Write-Host ""
Write-Host "BENEFICIOS:" -ForegroundColor Cyan
Write-Host "  + Rate limit aumentado a 5000 req/min" -ForegroundColor Green
Write-Host "  + Caché de consultas con Redis" -ForegroundColor Green
Write-Host "  + Envío asíncrono de emails" -ForegroundColor Green
Write-Host "  + Compresión de respuestas JSON" -ForegroundColor Green
Write-Host "  + Conexiones persistentes a BD" -ForegroundColor Green
Write-Host "  + Índices optimizados en BD" -ForegroundColor Green
Write-Host ""
