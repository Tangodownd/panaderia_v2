FROM php:8.1-fpm

# Instalar dependencias
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    nodejs \
    npm

# Limpiar cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Configurar directorios
RUN mkdir -p /var/log/nginx /var/cache/nginx

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configurar directorio de trabajo
WORKDIR /app

# Copiar archivos de la aplicación
COPY . /app/

# Instalar dependencias de Composer
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Instalar dependencias de npm (si las necesitas)
RUN if [ -f package.json ]; then npm ci; fi

# Configurar Laravel
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && chmod -R 775 storage bootstrap/cache

# Configurar Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Exponer puerto
EXPOSE 8080

# Iniciar Nginx y PHP-FPM
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]