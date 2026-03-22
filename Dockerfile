FROM php:8.3-fpm

LABEL maintainer="Nando Dev <nandinhos@gmail.com>"

# Arguments
ARG WWWGROUP
ARG NODE_VERSION=20

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    supervisor \
    nginx \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd
RUN pecl install redis && docker-php-ext-enable redis

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application
COPY . .

# Install dependencies
RUN composer update --no-dev --optimize-autoloader --no-interaction

# Build assets
RUN npm ci \
    && npm run build

# Set permissions — 777 garante escrita por qualquer usuário (bind mount com host)
RUN chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache
RUN mkdir -p /var/www/html/storage/logs /var/www/html/storage/framework/views /var/www/html/storage/framework/cache/data /var/www/html/storage/framework/sessions

# Create storage link
RUN php artisan storage:link

# Clear config cache
RUN php artisan config:clear \
    && php artisan route:clear \
    && php artisan view:clear

# Copy startup script
COPY docker/startup.sh /usr/local/bin/startup.sh
RUN chmod +x /usr/local/bin/startup.sh

# Copy Nginx config
COPY docker/nginx.conf /etc/nginx/sites-available/default

# Copy Supervisor config
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create logs directory
RUN mkdir -p /var/log/supervisor

# Fix temp directory permissions
RUN chmod 777 /tmp

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/startup.sh"]
