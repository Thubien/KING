#!/bin/bash

# KING Shopletix - Railway Deployment Script
# This script handles the complete deployment process for production

set -e

echo "🚀 Starting KING Shopletix Production Deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if Railway CLI is installed
if ! command -v railway &> /dev/null; then
    print_error "Railway CLI is not installed. Please install it first:"
    echo "npm install -g @railway/cli"
    exit 1
fi

# Check if logged in to Railway
if ! railway whoami &> /dev/null; then
    print_error "Not logged in to Railway. Please login first:"
    echo "railway login"
    exit 1
fi

print_status "Checking project status..."

# Verify we're in the correct directory
if [ ! -f "composer.json" ] || [ ! -f "artisan" ]; then
    print_error "This doesn't appear to be a Laravel project directory"
    exit 1
fi

# Install/Update dependencies
print_status "Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --only=production

# Build assets
print_status "Building production assets..."
npm run build

# Clear and optimize caches
print_status "Optimizing application..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Generate optimized caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

print_success "Local optimizations completed"

# Deploy to Railway
print_status "Deploying to Railway..."
railway up

print_status "Running post-deployment commands..."

# Generate application key if not exists
railway run php artisan key:generate --force

# Run database migrations
print_status "Running database migrations..."
railway run php artisan migrate --force

# Create storage link
print_status "Creating storage link..."
railway run php artisan storage:link

# Seed essential data if needed
print_warning "Checking if initial data seeding is needed..."
if railway run php artisan tinker --execute="echo App\\Models\\User::count();" | grep -q "^0$"; then
    print_status "Seeding initial data..."
    railway run php artisan db:seed --class=SuperAdminSeeder
    railway run php artisan db:seed --class=PermissionsAndRolesSeeder
    print_success "Initial data seeded"
else
    print_status "Database already contains data, skipping seeding"
fi

# Final cache optimization
print_status "Final cache optimization..."
railway run php artisan config:cache
railway run php artisan route:cache
railway run php artisan view:cache

# Health check
print_status "Performing health check..."
sleep 10 # Wait for deployment to be ready

HEALTH_URL=$(railway status --json | jq -r '.deployments[0].url')
if [ "$HEALTH_URL" != "null" ] && [ -n "$HEALTH_URL" ]; then
    HEALTH_URL="${HEALTH_URL}/health"
    if curl -f -s "$HEALTH_URL" > /dev/null; then
        print_success "Health check passed: $HEALTH_URL"
    else
        print_warning "Health check failed, but deployment completed. Check logs: railway logs"
    fi
else
    print_warning "Could not determine deployment URL for health check"
fi

print_success "🎉 Deployment completed successfully!"
print_status "Your application is now live!"

# Display useful information
echo ""
echo "📋 Post-Deployment Checklist:"
echo "1. ✅ Application deployed to Railway"
echo "2. ✅ Database migrations executed"
echo "3. ✅ Caches optimized"
echo "4. ✅ Storage linked"
echo "5. ⏳ Verify environment variables in Railway dashboard"
echo "6. ⏳ Set up custom domain (if needed)"
echo "7. ⏳ Configure monitoring and alerts"
echo ""

print_status "Useful commands:"
echo "- View logs: railway logs"
echo "- Check status: railway status"
echo "- Run commands: railway run [command]"
echo "- SSH into container: railway shell"
echo ""

print_status "Environment Variables to set in Railway:"
echo "- APP_KEY (auto-generated)"
echo "- SHOPIFY_CLIENT_ID"
echo "- SHOPIFY_CLIENT_SECRET"
echo "- STRIPE_PUBLIC_KEY"
echo "- STRIPE_SECRET_KEY"
echo "- MAIL_* settings"
echo ""

print_success "Deployment script completed! 🚀" 