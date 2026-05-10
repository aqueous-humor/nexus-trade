#!/bin/sh
set -e

echo "==> Running NexusTrade API entrypoint..."

# Wait for MySQL to be ready
echo "==> Waiting for database..."
until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT:-3306};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
    echo "    Database not ready, retrying in 2s..."
    sleep 2
done
echo "==> Database is ready."

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    echo "==> Generating application key..."
    php artisan key:generate --force
fi

# Run migrations
echo "==> Running migrations..."
php artisan migrate --force

# Cache config and routes in production
if [ "$APP_ENV" = "production" ]; then
    echo "==> Caching configuration..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Set storage permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "==> Starting services..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
