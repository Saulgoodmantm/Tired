#!/usr/bin/env bash
# =============================================================================
# TIREDOFDOINTM - VPS Deploy Script
# =============================================================================
# Pull latest code from GitHub and restart services
# Usage: ./deploy_pull.sh [branch]
# Examples:
#   ./deploy_pull.sh          # Deploy main branch
#   ./deploy_pull.sh main     # Deploy main branch
#   ./deploy_pull.sh develop  # Deploy develop branch
#   ./deploy_pull.sh v1.2.3   # Deploy specific tag
# =============================================================================

set -euo pipefail

# Configuration
REPO_DIR="/home/${USER:-deploy}/tiredprod"
BRANCH="${1:-main}"
PHP_VERSION="8.2"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }
log_step() { echo -e "${BLUE}[STEP]${NC} $1"; }

echo ""
echo "=============================================="
echo "  TiredOfDoinTM Deployment"
echo "=============================================="
echo ""

log_info "Branch/Tag: ${BRANCH}"
log_info "Directory: ${REPO_DIR}"
log_info "Started: $(date)"
echo ""

# Check if repo exists
if [ ! -d "$REPO_DIR" ]; then
    log_error "Repository directory not found: $REPO_DIR"
    log_info "Please run the setup script first or clone the repo manually"
    exit 1
fi

cd "$REPO_DIR"

# =============================================================================
# STEP 1: Fetch latest code
# =============================================================================
log_step "Fetching latest code from origin..."
git fetch --all --prune --tags

# Check if branch/tag exists
if git rev-parse --verify "origin/$BRANCH" >/dev/null 2>&1; then
    log_info "Switching to branch: $BRANCH"
    git checkout "$BRANCH" 2>/dev/null || git checkout -b "$BRANCH" "origin/$BRANCH"
    git reset --hard "origin/$BRANCH"
elif git rev-parse --verify "$BRANCH" >/dev/null 2>&1; then
    log_info "Switching to tag: $BRANCH"
    git checkout "$BRANCH"
else
    log_error "Branch or tag not found: $BRANCH"
    log_info "Available branches:"
    git branch -r
    exit 1
fi

git clean -fd

# Show what was deployed
COMMIT_HASH=$(git rev-parse --short HEAD)
COMMIT_MSG=$(git log -1 --pretty=%B | head -1)
log_info "Deployed commit: ${COMMIT_HASH} - ${COMMIT_MSG}"

# =============================================================================
# STEP 2: Install dependencies
# =============================================================================
log_step "Checking dependencies..."

# Node.js dependencies (if applicable)
if [ -f "package.json" ]; then
    if command -v npm &> /dev/null; then
        log_info "Installing Node.js dependencies..."
        npm ci --production --silent 2>/dev/null || npm install --production --silent
    else
        log_warn "npm not found, skipping Node.js dependencies"
    fi
fi

# Composer dependencies (if applicable)
if [ -f "composer.json" ]; then
    if command -v composer &> /dev/null; then
        log_info "Installing Composer dependencies..."
        composer install --no-dev --optimize-autoloader --no-interaction
    else
        log_warn "composer not found, skipping PHP dependencies"
    fi
fi

# =============================================================================
# STEP 3: Run database migrations
# =============================================================================
log_step "Running database migrations..."

if [ -f "website/migrate.php" ]; then
    php website/migrate.php && log_info "Migrations completed" || log_warn "Migration script failed or no migrations needed"
else
    log_warn "No migration script found"
fi

# =============================================================================
# STEP 4: Clear caches
# =============================================================================
log_step "Clearing caches..."

# Clear PHP cache files
if [ -d "website/storage/cache" ]; then
    find website/storage/cache -type f -name "*.php" -delete 2>/dev/null || true
    log_info "Cache cleared"
fi

# Clear OPcache (if accessible)
if command -v php &> /dev/null; then
    php -r "if(function_exists('opcache_reset')) opcache_reset();" 2>/dev/null || true
fi

# =============================================================================
# STEP 5: Set permissions
# =============================================================================
log_step "Setting permissions..."

# Ensure storage is writable
chmod -R 775 website/storage 2>/dev/null || true
chmod -R 775 website/public/uploads 2>/dev/null || true

# =============================================================================
# STEP 6: Restart services
# =============================================================================
log_step "Restarting services..."

# Restart PHP-FPM
if sudo systemctl restart "php${PHP_VERSION}-fpm" 2>/dev/null; then
    log_info "PHP-FPM restarted"
else
    log_warn "Could not restart PHP-FPM (may need sudo privileges)"
fi

# Optionally restart Nginx (usually not needed for code changes)
# sudo systemctl reload nginx

# =============================================================================
# STEP 7: Verify deployment
# =============================================================================
log_step "Verifying deployment..."

# Check if index.php exists
if [ -f "website/public/index.php" ]; then
    log_info "✓ index.php exists"
else
    log_error "✗ index.php not found!"
fi

# Check if .env exists
if [ -f "website/config/.env" ]; then
    log_info "✓ .env configured"
else
    log_warn "✗ .env not found - copy from .env.example and configure"
fi

# Check PHP syntax
if php -l website/public/index.php >/dev/null 2>&1; then
    log_info "✓ PHP syntax valid"
else
    log_error "✗ PHP syntax errors detected!"
fi

echo ""
echo "=============================================="
log_info "Deployment complete!"
log_info "Branch: ${BRANCH}"
log_info "Commit: ${COMMIT_HASH}"
log_info "Time: $(date)"
echo "=============================================="
echo ""