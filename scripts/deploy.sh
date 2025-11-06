#!/bin/bash

# Deployment script for AgriSiti LMS
# This script should be run on the server after code is deployed

set -e

echo "🚀 Starting deployment..."

# Get the directory where the script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_DIR"

echo "📁 Project directory: $PROJECT_DIR"

# Check if .env exists
if [ ! -f .env ]; then
    echo "⚠️  .env file not found. Please create it manually."
    echo "   Copy .env.example to .env and configure it."
    exit 1
fi

# Install/Update PHP dependencies
echo "📦 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Install/Update Node dependencies (if needed)
if [ -f package.json ]; then
    echo "📦 Installing Node dependencies..."
    npm ci --production || npm install --production
fi

# Build assets (if needed)
if [ -f package.json ] && [ -f vite.config.js ]; then
    echo "🏗️  Building assets..."
    npm run build
fi

# Run database migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force

# Clear and cache configuration
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimize application
echo "⚡ Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions
echo "🔐 Setting permissions..."
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true

# Create storage link if it doesn't exist
if [ ! -L public/storage ]; then
    echo "🔗 Creating storage link..."
    php artisan storage:link
fi

# Run queue workers restart (if using supervisor)
if command -v supervisorctl &> /dev/null; then
    echo "🔄 Restarting queue workers..."
    supervisorctl restart laravel-worker:* || true
fi

# Clear OPcache (if enabled)
if [ -f /etc/php/*/fpm/conf.d/10-opcache.ini ] || [ -f /etc/php/*/cli/conf.d/10-opcache.ini ]; then
    echo "🔄 Clearing OPcache..."
    php artisan opcache:clear || true
fi

echo "✅ Deployment completed successfully!"
echo ""
echo "📋 Next steps:"
echo "   1. Verify the application is running"
echo "   2. Check logs: tail -f storage/logs/laravel.log"
echo "   3. Test the API endpoints"
echo "   4. Test admin/tutor panels"

