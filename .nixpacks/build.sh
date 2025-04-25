#!/bin/bash

# Instalar Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Instalar dependencias
composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Optimizar Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Configurar permisos
chmod -R 775 storage bootstrap/cache