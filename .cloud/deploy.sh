#!/bin/bash

# Laravel Cloud Deploy Script for Shopletix

echo "Starting deployment..."

# Install PHP dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Install Node dependencies and build assets
npm install
npm run build

# Create storage link
php artisan storage:link

# Check if database is available
if php artisan tinker --execute="DB::connection()->getPdo();" 2>/dev/null; then
    echo "Database connection successful"
    
    # Run migrations
    php artisan migrate:fresh --force
    
    # Run seeders (only permissions)
    php artisan db:seed --force
else
    echo "Database not available yet, skipping migrations"
fi

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

echo "Deployment completed!"