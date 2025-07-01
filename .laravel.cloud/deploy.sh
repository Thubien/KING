#!/bin/bash

echo "🚀 Starting deployment..."

# Install composer dependencies
echo "📦 Installing composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Generate application key if not exists
if [ -z "$APP_KEY" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force
fi

# Run database migrations
echo "🗄️ Running migrations..."
php artisan migrate --force

# Clear and cache config
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

echo "📝 Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link
echo "🔗 Creating storage link..."
php artisan storage:link

# Install npm dependencies and build assets
echo "📦 Installing npm dependencies..."
npm install

echo "🏗️ Building assets..."
npm run build

# Run seeders only if tables are empty
echo "🌱 Checking if seeding is needed..."
php artisan db:seed --force

echo "✅ Deployment completed!"