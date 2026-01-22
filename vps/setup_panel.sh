#!/usr/bin/env bash
# =============================================================================
# VPS Panel Setup Script
# =============================================================================
# Sets up the VPS management panel at admin.tiredofdointm.com
# Run after the main setup_vps.sh script
# Usage: sudo bash setup_panel.sh
# =============================================================================

set -euo pipefail

# Configuration
PANEL_DOMAIN="admin.tiredofdointm.com"
DEPLOY_USER="deploy"
PANEL_DIR="/home/${DEPLOY_USER}/panel"
SITES_DIR="/home/${DEPLOY_USER}/TiredProductions"
SHARED_DIR="/home/${DEPLOY_USER}/shared"
REPO_URL="https://github.com/Saulgoodmantm/tired.git"
PHP_VERSION="8.2"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# Check root
if [ "$EUID" -ne 0 ]; then
    log_error "Please run as root (sudo)"
    exit 1
fi

log_info "Setting up VPS Panel..."

# =============================================================================
# CREATE DIRECTORY STRUCTURE
# =============================================================================
log_info "Creating directory structure..."

# Create main directories
mkdir -p ${PANEL_DIR}
mkdir -p ${SITES_DIR}
mkdir -p ${SHARED_DIR}/templates

# Set ownership
chown -R ${DEPLOY_USER}:${DEPLOY_USER} /home/${DEPLOY_USER}

# =============================================================================
# CLONE/UPDATE REPOSITORY
# =============================================================================
log_info "Cloning repository..."

TEMP_DIR="/tmp/tired_repo"
rm -rf ${TEMP_DIR}
git clone ${REPO_URL} ${TEMP_DIR}

# Copy panel files
log_info "Copying panel files..."
cp -r ${TEMP_DIR}/vps/panel/* ${PANEL_DIR}/

# Copy main site to TiredProductions (if not exists)
if [ ! -d "${SITES_DIR}/tiredofdointm.com" ]; then
    log_info "Setting up tiredofdointm.com..."
    mkdir -p ${SITES_DIR}/tiredofdointm.com
    cp -r ${TEMP_DIR}/website/* ${SITES_DIR}/tiredofdointm.com/
fi

# Cleanup
rm -rf ${TEMP_DIR}

# =============================================================================
# SETUP PANEL CONFIGURATION
# =============================================================================
log_info "Setting up panel configuration..."

# Create .env from example if not exists
if [ ! -f "${PANEL_DIR}/config/.env" ]; then
    cp ${PANEL_DIR}/config/.env.example ${PANEL_DIR}/config/.env
    log_warn "Created .env file - please edit with your credentials!"
fi

# Create sites.json from example if not exists
if [ ! -f "${PANEL_DIR}/config/sites.json" ]; then
    cp ${PANEL_DIR}/config/sites.json.example ${PANEL_DIR}/config/sites.json
fi

# Create storage directories
mkdir -p ${PANEL_DIR}/storage/{logs,sessions}
chmod 775 ${PANEL_DIR}/storage
chmod 775 ${PANEL_DIR}/storage/logs
chmod 775 ${PANEL_DIR}/storage/sessions

# Set ownership
chown -R ${DEPLOY_USER}:${DEPLOY_USER} ${PANEL_DIR}
chown -R ${DEPLOY_USER}:${DEPLOY_USER} ${SITES_DIR}
chown -R ${DEPLOY_USER}:${DEPLOY_USER} ${SHARED_DIR}

# =============================================================================
# CONFIGURE NGINX FOR PANEL
# =============================================================================
log_info "Configuring nginx for panel..."

# Copy nginx config
cp ${PANEL_DIR}/../nginx/panel.conf.template /etc/nginx/sites-available/${PANEL_DOMAIN}

# Update paths in config
sed -i "s|/home/deploy/panel|${PANEL_DIR}|g" /etc/nginx/sites-available/${PANEL_DOMAIN}

# Enable site
ln -sf /etc/nginx/sites-available/${PANEL_DOMAIN} /etc/nginx/sites-enabled/

# Test nginx config
nginx -t

# Reload nginx
systemctl reload nginx

# =============================================================================
# SETUP SUDOERS FOR DEPLOY USER
# =============================================================================
log_info "Configuring sudo permissions..."

cat > /etc/sudoers.d/${DEPLOY_USER} << SUDOERS
# Allow deploy user to manage services
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /bin/systemctl restart nginx
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /bin/systemctl reload nginx
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /bin/systemctl restart php${PHP_VERSION}-fpm
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /bin/systemctl status *
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/bin/certbot
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/bin/tail /var/log/nginx/*
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/bin/tail /var/log/php*
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/bin/tail /var/log/auth.log
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/sbin/nginx -t
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/sbin/ufw status*
${DEPLOY_USER} ALL=(ALL) NOPASSWD: /usr/bin/fail2ban-client status*
SUDOERS

chmod 440 /etc/sudoers.d/${DEPLOY_USER}

# =============================================================================
# FINAL STEPS
# =============================================================================
log_info ""
log_info "=============================================="
log_info "VPS Panel Setup Complete!"
log_info "=============================================="
log_info ""
log_info "Panel location: ${PANEL_DIR}"
log_info "Sites location: ${SITES_DIR}"
log_info ""
log_info "Next steps:"
log_info "1. Point ${PANEL_DOMAIN} DNS to this server"
log_info "2. Edit ${PANEL_DIR}/config/.env with Google OAuth credentials"
log_info "3. Run: sudo certbot --nginx -d ${PANEL_DOMAIN}"
log_info "4. Access panel at https://${PANEL_DOMAIN}"
log_info ""
log_info "Google OAuth setup:"
log_info "  1. Go to https://console.cloud.google.com/apis/credentials"
log_info "  2. Create OAuth 2.0 credentials"
log_info "  3. Set redirect URI to: https://${PANEL_DOMAIN}/auth/google/callback"
log_info "  4. Add credentials to .env file"
log_info ""
log_info "=============================================="
