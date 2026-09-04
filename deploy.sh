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
# STEP 8 — Build Frontend Assets (Laravel Vite) - Professional CI/CD
# ──────────────────────────────────────────────────────────────────────────
echo ""
echo "🎨 [8/9] Building Frontend Assets..."

# Load environment profiles to ensure NVM/NPM paths are loaded in non-interactive shells
[ -f ~/.bashrc ] && source ~/.bashrc
[ -f ~/.profile ] && source ~/.profile
[ -f ~/.bash_profile ] && source ~/.bash_profile
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
export PATH=$PATH:/usr/local/bin:/usr/local/n/versions/node/current/bin

if ! command -v npm >/dev/null 2>&1; then
    echo "❌ [ERROR] npm could not be found in the system PATH."
    echo "💡 The deployment script runs in a non-interactive shell which might not load your Node.js path."
    echo "👉 If you use NVM, ensure it is installed for the user running this script."
    exit 1
fi

echo "📦 Installing NPM dependencies (Clean Install)..."
if [ -f "package-lock.json" ]; then
    npm ci --legacy-peer-deps
else
    npm install --legacy-peer-deps
fi

echo "🔨 Compiling assets for production..."
npm run build
echo "✅ Frontend assets built successfully."

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
echo "   • Main App/API : https://your-domain.com"
echo "   • Super Admin  : https://your-domain.com/super/login"
echo "   • Login Email  : superadmin@wakeel.system (or admin@wakeel.cc)"
echo "   • ⚠️  Change the Super Admin password after first login!"
echo ""
