#!/bin/bash
set -e

echo "Starting Dev Memory application..."

# Wait for database to be ready
echo "Waiting for PostgreSQL..."
until PGPASSWORD=$DB_PASSWORD psql -h postgres -U "${DB_USERNAME:-dev_memory}" -d "${DB_DATABASE:-dev_memory}" -c '\q' 2>/dev/null; do
    echo "PostgreSQL is unavailable - sleeping"
    sleep 2
done

echo "PostgreSQL is up!"

# Run migrations
echo "Running migrations..."
php artisan migrate --force --no-interaction

# Seed database if in local/dev environment
if [ "$APP_ENV" = "local" ] || [ "$APP_ENV" = "development" ]; then
    echo "Seeding database..."
    php artisan db:seed --force --no-interaction
fi

# Clear and rebuild caches
echo "Optimizing application..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start services
echo "Starting services..."
service supervisor start

# Start PHP-FPM and Nginx
echo "Starting PHP-FPM..."
php-fpm

echo "Starting Nginx..."
nginx

# Keep container running
echo "Application started successfully!"
tail -f /dev/null
