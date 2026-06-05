#!/bin/sh
set -e

echo "==> Waiting for PostgreSQL..."
until php -r "new PDO('pgsql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
    echo "Postgres not ready, retrying in 2s..."
    sleep 2
done
echo "==> PostgreSQL ready!"

echo "==> Generating app key..."
php artisan key:generate --no-interaction --force

echo "==> Generating JWT secret..."
php artisan jwt:secret --no-interaction --force

echo "==> Running migrations..."
php artisan migrate --no-interaction --force

echo "==> Seeding database..."
php artisan db:seed --no-interaction --force

echo "==> Generating Swagger docs..."
php artisan l5-swagger:generate || true

echo "==> Clearing caches..."
php artisan config:cache
php artisan route:cache

echo "==> Starting PHP-FPM..."
exec "$@"
