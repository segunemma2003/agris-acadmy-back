#!/bin/bash

# Initial deployment script
# Run this script on your server for the first time

set -e

echo "🚀 Starting initial deployment..."

# Configuration
PROJECT_DIR="${1:-/var/www/agrisiti-academy}"
REPO_URL="${2:-https://github.com/yourusername/agrisiti-main-academy.git}"
BRANCH="${3:-main}"

echo "📁 Project directory: $PROJECT_DIR"
echo "🔗 Repository: $REPO_URL"
echo "🌿 Branch: $BRANCH"

# Create project directory
if [ ! -d "$PROJECT_DIR" ]; then
    echo "📁 Creating project directory..."
    mkdir -p "$PROJECT_DIR"
fi

cd "$PROJECT_DIR"

# Clone repository if it doesn't exist
if [ ! -d ".git" ]; then
    echo "📥 Cloning repository..."
    git clone -b "$BRANCH" "$REPO_URL" .
else
    echo "🔄 Pulling latest changes..."
    git pull origin "$BRANCH"
fi

# Check if .env exists
if [ ! -f .env ]; then
    echo "📝 Creating .env file..."
    cp .env.example .env
    echo "⚠️  Please edit .env file and configure your database and other settings"
    echo "   nano .env"
    read -p "Press Enter after you've configured .env file..."
fi

# Install PHP dependencies
echo "📦 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Install Node dependencies
if [ -f package.json ]; then
    echo "📦 Installing Node dependencies..."
    npm ci --production || npm install --production
fi

# Build assets
if [ -f package.json ] && [ -f vite.config.js ]; then
    echo "🏗️  Building assets..."
    npm run build
fi

# Generate application key
echo "🔑 Generating application key..."
php artisan key:generate

# Run database migrations
echo "🗄️  Running database migrations..."
php artisan migrate --force

# Create storage directories
echo "📁 Creating storage directories..."
mkdir -p storage/app/public
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Set proper permissions
echo "🔐 Setting permissions..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || chown -R $USER:$USER storage bootstrap/cache

# Create storage link
echo "🔗 Creating storage link..."
php artisan storage:link

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

# Create admin user
echo "👤 Creating admin user..."
php artisan tinker --execute="
use App\Models\User;
use Illuminate\Support\Facades\Hash;

\$admin = User::firstOrCreate(
    ['email' => 'admin@example.com'],
    [
        'name' => 'Admin User',
        'password' => Hash::make('password123'),
        'role' => 'admin',
        'is_active' => true,
    ]
);
echo 'Admin user created: admin@example.com / password123' . PHP_EOL;
"

echo ""
echo "✅ Initial deployment completed successfully!"
echo ""
echo "📋 Important information:"
echo "   - Admin email: admin@example.com"
echo "   - Admin password: password123"
echo "   - ⚠️  Please change the admin password immediately!"
echo ""
echo "🌐 Access points:"
echo "   - Admin Panel: http://your-domain.com/admin"
echo "   - Tutor Panel: http://your-domain.com/tutor"
echo "   - API: http://your-domain.com/api"
echo ""
echo "📝 Next steps:"
echo "   1. Configure your web server (Nginx/Apache)"
echo "   2. Set up SSL certificate"
echo "   3. Configure queue workers (if needed)"
echo "   4. Set up cron jobs for scheduled tasks"
echo "   5. Change default admin password"
echo "   6. Configure email settings"

