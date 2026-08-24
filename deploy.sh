#!/bin/bash
set -e

echo "🚀 Starting Deployment — Wakeel CRM"
echo "=================================="

# ──────────────────────────────────────────────────────────────────────────
# ERROR HANDLING (Prevent stuck in maintenance mode)
# ──────────────────────────────────────────────────────────────────────────
cleanup() {
    local exit_code=$?
    if [ $exit_code -ne 0 ]; then
        echo ""
        echo "❌ [ERROR] Deployment failed or was interrupted!"
        echo "🔄 Automatically bringing the site back online to avoid 503 error..."
        
        # Ensure we are in the project root to run artisan
        if [ ! -f "artisan" ] && [ -f "../artisan" ]; then
            cd ..
        fi
        
        php artisan up || true
    fi
    exit $exit_code
}

# Trap any exit (success or failure) and run cleanup
trap cleanup EXIT

# ──────────────────────────────────────────────────────────────────────────
# CONFIGURATION
# ──────────────────────────────────────────────────────────────────────────
ADMIN_PANEL_DIR="admin-panel"   # Next.js admin panel directory
APP_ENV="${APP_ENV:-production}"

# ──────────────────────────────────────────────────────────────────────────
# STEP 1 — Maintenance mode
# ──────────────────────────────────────────────────────────────────────────
echo ""
echo "⏳ [1/9] Entering maintenance mode..."
php artisan down || true

# ──────────────────────────────────────────────────────────────────────────
# STEP 2 — Pull latest code
# ──────────────────────────────────────────────────────────────────────────
echo ""
echo "📥 [2/9] Pulling latest code from GitHub..."
git fetch origin main
git reset --hard origin/main

# ──────────────────────────────────────────────────────────────────────────
# STEP 3 — PHP dependencies
# ──────────────────────────────────────────────────────────────────────────
echo ""
echo "📦 [3/9] Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

# ──────────────────────────────────────────────────────────────────────────
# STEP 4 — Database migrations (SAFE — never seeds, never truncates)
# ──────────────────────────────────────────────────────────────────────────
echo ""
echo "🗄️  [4/9] Running database migrations..."
php artisan migrate --force
echo "✅ Migrations done. Existing data is intact."

# ──────────────────────────────────────────────────────────────────────────
# STEP 5 — Super Admin seeder (SAFE — uses firstOrCreate, won't duplicate)
# ──────────────────────────────────────────────────────────────────────────
# NOTE: This ONLY creates the super admin user if it doesn't already exist.
#       It NEVER touches tenant data, client data, invoices, or any
#       existing business data. Safe to run on every deployment.
#       The TenantSeeder is NOT called here — it was a one-time setup tool.
echo ""
echo "👤 [5/9] Ensuring Super Admin user exists..."
php artisan db:seed --class=SuperAdminSeeder --force
echo "✅ Super Admin check done."

# ──────────────────────────────────────────────────────────────────────────
# STEP 6 — Storage & permissions
# ──────────────────────────────────────────────────────────────────────────
echo ""
echo "📁 [6/9] Setting up storage & permissions..."
mkdir -p storage/fonts storage/logs || true
chmod -R 775 storage bootstrap/cache || true
php artisan storage:link --force || true

# ──────────────────────────────────────────────────────────────────────────
# STEP 7 — Clear & rebuild cache
# ──────────────────────────────────────────────────────────────────────────
echo ""
echo "⚡ [7/9] Optimizing config, routes & views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# ──────────────────────────────────────────────────────────────────────────
# STEP 8 — Admin Panel (Next.js) build
# ──────────────────────────────────────────────────────────────────────────
echo ""
echo "🎨 [8/9] Building Admin Panel (Next.js)..."
if [ -d "$ADMIN_PANEL_DIR" ]; then
    cd "$ADMIN_PANEL_DIR"
    if command -v npm >/dev/null 2>&1; then
        npm ci --omit=dev
        npm run build
        echo "✅ Admin Panel built successfully."
    else
        echo "⚠️  npm is not installed on this server. Skipping Admin Panel build."
    fi
    cd ..
else
    echo "⚠️  Admin panel directory '$ADMIN_PANEL_DIR' not found. Skipping."
fi

# ──────────────────────────────────────────────────────────────────────────
# STEP 9 — Restart queue worker & exit maintenance
# ──────────────────────────────────────────────────────────────────────────
echo ""
echo "🔄 [9/9] Restarting queue workers & going live..."
php artisan queue:restart || true
php artisan up

echo ""
echo "══════════════════════════════════════════"
echo "✅  Deployment completed successfully!"
echo "══════════════════════════════════════════"
echo ""
echo "📌 Important reminders:"
echo "   • API Backend  : https://your-domain.com/api"
echo "   • Admin Panel  : https://admin.your-domain.com  (or :3000 locally)"
echo "   • Super Admin  : superadmin@wakeel.system"
echo "   • ⚠️  Change the Super Admin password after first login!"
echo ""
