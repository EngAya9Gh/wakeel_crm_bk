#!/bin/bash
set -e

echo "🚀 Starting Deployment..."

# 1. Enter maintenance mode
echo "⏳ Entering Maintenance Mode..."
php artisan down || true

# 2. Pull the latest changes from Git
echo "📥 Pulling latest code from GitHub..."
git pull origin main

# 3. Install/update Composer dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

# 4. Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# 5. Create storage/fonts directory if missing and set permissions
echo "📁 Setting up storage fonts permissions..."
mkdir -p storage/fonts
chmod -R 775 storage/fonts
# chown -R www-data:www-data storage/fonts # (Uncomment if needed based on server user)

# 6. Clear and recreate cache
echo "⚡ Optimizing and caching config/routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart queue worker (if Supervisor is configured)
echo "🔄 Restarting queue worker..."
php artisan queue:restart || true

# 7. Exit maintenance mode
echo "🟢 Exiting Maintenance Mode... Application is LIVE!"
php artisan up

echo "✅ Deployment completed successfully!"
