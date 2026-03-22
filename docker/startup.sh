#!/bin/bash
set -e

echo "Starting Dev Memory application..."

# Wait for database to be ready using PHP PDO
echo "Waiting for PostgreSQL..."
until php -r "
try {
    new PDO(
        'pgsql:host=postgres;dbname=${DB_DATABASE:-dev_memory}',
        '${DB_USERNAME:-dev_memory}',
        '${DB_PASSWORD:-secret}'
    );
    exit(0);
} catch (Exception \$e) {
    exit(1);
}
" 2>/dev/null; do
    echo "PostgreSQL is unavailable - sleeping"
    sleep 2
done

echo "PostgreSQL is up!"

# Run migrations
echo "Running migrations..."
php artisan migrate --force --no-interaction

# Fix storage permissions for www-data
echo "Fixing storage permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Clear all caches
echo "Clearing caches..."
rm -rf bootstrap/cache/*.php storage/framework/cache/data/* storage/framework/views/* 2>/dev/null

# Start supervisord (handles PHP-FPM, Nginx, queue workers, scheduler)
echo "Starting supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
