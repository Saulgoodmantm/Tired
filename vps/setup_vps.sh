#!/usr/bin/env bash
# =============================================================================
# TIREDOFDOINTM - VPS Setup Script
# =============================================================================
# Run this script on a fresh Ubuntu 22.04 VPS to set up the complete environment
# Usage: curl -sSL https://raw.githubusercontent.com/Saulgoodmantm/tired/main/vps/setup_vps.sh | bash
# =============================================================================

set -euo pipefail

# =============================================================================
# CONFIGURATION - EDIT THESE VALUES
# =============================================================================
DOMAIN="tiredofdointm.com"
PANEL_DOMAIN="admin.tiredofdointm.com"
REPO_URL="https://github.com/Saulgoodmantm/tired.git"
DEPLOY_USER="deploy"
# New folder structure
SITES_ROOT="/home/${DEPLOY_USER}/TiredProductions"
PANEL_DIR="/home/${DEPLOY_USER}/panel"
SHARED_DIR="/home/${DEPLOY_USER}/shared"
APP_DIR="${SITES_ROOT}/${DOMAIN}"
PHP_VERSION="8.2"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# =============================================================================
# CHECK ROOT
# =============================================================================
if [ "$EUID" -ne 0 ]; then
    log_error "Please run as root (sudo)"
    exit 1
fi

log_info "Starting TiredOfDoinTM VPS Setup..."
log_info "Domain: ${DOMAIN}"

# =============================================================================
# UPDATE SYSTEM
# =============================================================================
log_info "Updating system packages..."
apt update && apt upgrade -y

# =============================================================================
# INSTALL ESSENTIAL PACKAGES
# =============================================================================
log_info "Installing essential packages..."
apt install -y \
    curl \
    wget \
    git \
    unzip \
    software-properties-common \
    apt-transport-https \
    ca-certificates \
    gnupg \
    lsb-release \
    ufw \
    fail2ban

# =============================================================================
# INSTALL PHP
# =============================================================================
log_info "Installing PHP ${PHP_VERSION}..."
add-apt-repository -y ppa:ondrej/php
apt update

apt install -y \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-common \
    php${PHP_VERSION}-pgsql \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-readline \
    php${PHP_VERSION}-opcache

# Configure PHP-FPM
log_info "Configuring PHP-FPM..."
PHP_FPM_CONF="/etc/php/${PHP_VERSION}/fpm/pool.d/www.conf"
sed -i "s/^user = .*/user = ${DEPLOY_USER}/" ${PHP_FPM_CONF}
sed -i "s/^group = .*/group = ${DEPLOY_USER}/" ${PHP_FPM_CONF}
sed -i "s/^listen.owner = .*/listen.owner = ${DEPLOY_USER}/" ${PHP_FPM_CONF}
sed -i "s/^listen.group = .*/listen.group = ${DEPLOY_USER}/" ${PHP_FPM_CONF}

# PHP.ini optimizations
PHP_INI="/etc/php/${PHP_VERSION}/fpm/php.ini"
sed -i "s/^upload_max_filesize = .*/upload_max_filesize = 64M/" ${PHP_INI}
sed -i "s/^post_max_size = .*/post_max_size = 64M/" ${PHP_INI}
sed -i "s/^memory_limit = .*/memory_limit = 256M/" ${PHP_INI}
sed -i "s/^max_execution_time = .*/max_execution_time = 120/" ${PHP_INI}

# =============================================================================
# INSTALL NGINX
# =============================================================================
log_info "Installing Nginx..."
apt install -y nginx

# =============================================================================
# INSTALL CERTBOT FOR SSL
# =============================================================================
log_info "Installing Certbot..."
apt install -y certbot python3-certbot-nginx

# =============================================================================
# CREATE DEPLOY USER
# =============================================================================
if ! id "${DEPLOY_USER}" &>/dev/null; then
    log_info "Creating deploy user: ${DEPLOY_USER}..."
    useradd -m -s /bin/bash ${DEPLOY_USER}
    usermod -aG sudo ${DEPLOY_USER}
    
    # Copy SSH keys from root
    mkdir -p /home/${DEPLOY_USER}/.ssh
    if [ -f /root/.ssh/authorized_keys ]; then
        cp /root/.ssh/authorized_keys /home/${DEPLOY_USER}/.ssh/
    fi
    chown -R ${DEPLOY_USER}:${DEPLOY_USER} /home/${DEPLOY_USER}/.ssh
    chmod 700 /home/${DEPLOY_USER}/.ssh
    chmod 600 /home/${DEPLOY_USER}/.ssh/authorized_keys 2>/dev/null || true
else
    log_info "Deploy user ${DEPLOY_USER} already exists"
fi

# =============================================================================
# GENERATE SSH DEPLOY KEY (for private repos)
# =============================================================================
log_info "Setting up SSH deploy key for private repository access..."

DEPLOY_KEY="/home/${DEPLOY_USER}/.ssh/deploy_key"
if [ ! -f "${DEPLOY_KEY}" ]; then
    sudo -u ${DEPLOY_USER} mkdir -p /home/${DEPLOY_USER}/.ssh
    sudo -u ${DEPLOY_USER} ssh-keygen -t ed25519 -f ${DEPLOY_KEY} -N "" -C "deploy@${DOMAIN}"
    
    # Configure SSH to use deploy key for GitHub
    cat > /home/${DEPLOY_USER}/.ssh/config << 'SSH_CONFIG'
Host github.com
    HostName github.com
    User git
    IdentityFile ~/.ssh/deploy_key
    IdentitiesOnly yes
SSH_CONFIG
    chown ${DEPLOY_USER}:${DEPLOY_USER} /home/${DEPLOY_USER}/.ssh/config
    chmod 600 /home/${DEPLOY_USER}/.ssh/config
    
    echo ""
    log_warn "=============================================="
    log_warn "IMPORTANT: Add this deploy key to your GitHub repo!"
    log_warn "Go to: https://github.com/Saulgoodmantm/tired/settings/keys"
    log_warn "Click 'Add deploy key' and paste this:"
    echo ""
    cat ${DEPLOY_KEY}.pub
    echo ""
    log_warn "=============================================="
    echo ""
else
    log_info "Deploy key already exists"
fi

# Add GitHub to known hosts
sudo -u ${DEPLOY_USER} ssh-keyscan -t ed25519 github.com >> /home/${DEPLOY_USER}/.ssh/known_hosts 2>/dev/null

# =============================================================================
# SETUP DIRECTORY STRUCTURE
# =============================================================================
log_info "Setting up directory structure..."

# Create main directories
mkdir -p ${SITES_ROOT}
mkdir -p ${PANEL_DIR}
mkdir -p ${SHARED_DIR}/templates
mkdir -p ${APP_DIR}

chown -R ${DEPLOY_USER}:${DEPLOY_USER} /home/${DEPLOY_USER}

# Clone repository if not exists (using SSH for private repo)
REPO_SSH="git@github.com:Saulgoodmantm/tired.git"
TEMP_CLONE="/tmp/tired_clone"

if [ ! -d "${APP_DIR}/.git" ]; then
    log_info "Cloning private repository via SSH..."
    rm -rf ${TEMP_CLONE}
    sudo -u ${DEPLOY_USER} git clone ${REPO_SSH} ${TEMP_CLONE} || {
        log_error "Failed to clone. Make sure you added the deploy key to GitHub!"
        log_info "Deploy key public key:"
        cat ${DEPLOY_KEY}.pub
        exit 1
    }
    
    # Copy website to sites directory
    log_info "Setting up main website..."
    cp -r ${TEMP_CLONE}/website/* ${APP_DIR}/
    
    # Copy panel to panel directory
    log_info "Setting up VPS panel..."
    cp -r ${TEMP_CLONE}/vps/panel/* ${PANEL_DIR}/
    
    # Keep git repo in sites folder for deployments
    mv ${TEMP_CLONE}/.git ${APP_DIR}/
    rm -rf ${TEMP_CLONE}
else
    log_info "Repository already cloned, pulling latest..."
    cd ${APP_DIR}
    sudo -u ${DEPLOY_USER} git fetch --all
    sudo -u ${DEPLOY_USER} git reset --hard origin/main
fi

# Create storage directories for main site
mkdir -p ${APP_DIR}/storage/{cache,logs,sessions}
chown -R ${DEPLOY_USER}:${DEPLOY_USER} ${APP_DIR}/storage
chmod -R 775 ${APP_DIR}/storage

# Create storage directories for panel
mkdir -p ${PANEL_DIR}/storage/{logs,sessions}
chown -R ${DEPLOY_USER}:${DEPLOY_USER} ${PANEL_DIR}/storage
chmod -R 775 ${PANEL_DIR}/storage

# =============================================================================
# CONFIGURE NGINX
# =============================================================================
log_info "Configuring Nginx..."

# Main site nginx config
cat > /etc/nginx/sites-available/${DOMAIN} << 'NGINX_CONF'
server {
    listen 80;
    listen [::]:80;
    server_name DOMAIN_PLACEHOLDER www.DOMAIN_PLACEHOLDER;

    root SITE_PATH_PLACEHOLDER/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/json application/xml image/svg+xml;

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|webp|woff|woff2|ttf|svg)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP handling
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/phpPHP_VERSION_PLACEHOLDER-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 120;
    }

    # Block access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ /\.env {
        deny all;
    }

    location ~ /(config|storage|migrations|app)/ {
        deny all;
    }

    # Error pages
    error_page 404 /index.php;
    error_page 500 502 503 504 /index.php;

    # Logging
    access_log /var/log/nginx/DOMAIN_PLACEHOLDER.access.log;
    error_log /var/log/nginx/DOMAIN_PLACEHOLDER.error.log;
}
NGINX_CONF

# Replace placeholders
sed -i "s/DOMAIN_PLACEHOLDER/${DOMAIN}/g" /etc/nginx/sites-available/${DOMAIN}
sed -i "s|SITE_PATH_PLACEHOLDER|${APP_DIR}|g" /etc/nginx/sites-available/${DOMAIN}
sed -i "s/PHP_VERSION_PLACEHOLDER/${PHP_VERSION}/g" /etc/nginx/sites-available/${DOMAIN}

# Enable site
ln -sf /etc/nginx/sites-available/${DOMAIN} /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# =============================================================================
# CONFIGURE NGINX FOR PANEL
# =============================================================================
log_info "Configuring Nginx for admin panel..."

cat > /etc/nginx/sites-available/${PANEL_DOMAIN} << 'PANEL_NGINX'
server {
    listen 80;
    listen [::]:80;
    server_name PANEL_DOMAIN_PLACEHOLDER;

    root PANEL_PATH_PLACEHOLDER/public;
    index index.php;

    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/json;

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|webp|woff|woff2|ttf|svg)$ {
        expires 7d;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/phpPHP_VERSION_PLACEHOLDER-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 120;
    }

    location ~ /\. { deny all; }
    location ~ /\.env { deny all; }
    location ~ /(config|storage|app)/ { deny all; }

    access_log /var/log/nginx/PANEL_DOMAIN_PLACEHOLDER.access.log;
    error_log /var/log/nginx/PANEL_DOMAIN_PLACEHOLDER.error.log;
}
PANEL_NGINX

# Replace panel placeholders
sed -i "s/PANEL_DOMAIN_PLACEHOLDER/${PANEL_DOMAIN}/g" /etc/nginx/sites-available/${PANEL_DOMAIN}
sed -i "s|PANEL_PATH_PLACEHOLDER|${PANEL_DIR}|g" /etc/nginx/sites-available/${PANEL_DOMAIN}
sed -i "s/PHP_VERSION_PLACEHOLDER/${PHP_VERSION}/g" /etc/nginx/sites-available/${PANEL_DOMAIN}

# Enable panel site
ln -sf /etc/nginx/sites-available/${PANEL_DOMAIN} /etc/nginx/sites-enabled/

# Test nginx config
nginx -t

# =============================================================================
# CONFIGURE FIREWALL
# =============================================================================
log_info "Configuring firewall..."
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 'Nginx Full'
ufw --force enable

# =============================================================================
# SETUP ENVIRONMENT FILES
# =============================================================================
# Main site .env
ENV_FILE="${APP_DIR}/config/.env"
if [ ! -f "${ENV_FILE}" ]; then
    log_info "Creating main site .env file..."
    if [ -f "${APP_DIR}/config/.env.example" ]; then
        sudo -u ${DEPLOY_USER} cp "${APP_DIR}/config/.env.example" "${ENV_FILE}"
        log_warn "Please edit ${ENV_FILE} with your actual credentials!"
    fi
fi

# Panel .env
PANEL_ENV="${PANEL_DIR}/config/.env"
if [ ! -f "${PANEL_ENV}" ]; then
    log_info "Creating panel .env file..."
    if [ -f "${PANEL_DIR}/config/.env.example" ]; then
        sudo -u ${DEPLOY_USER} cp "${PANEL_DIR}/config/.env.example" "${PANEL_ENV}"
        log_warn "Please edit ${PANEL_ENV} with Google OAuth credentials!"
    fi
fi

# Panel sites.json
PANEL_SITES="${PANEL_DIR}/config/sites.json"
if [ ! -f "${PANEL_SITES}" ]; then
    log_info "Creating panel sites config..."
    if [ -f "${PANEL_DIR}/config/sites.json.example" ]; then
        sudo -u ${DEPLOY_USER} cp "${PANEL_DIR}/config/sites.json.example" "${PANEL_SITES}"
        # Update path in sites.json
        sed -i "s|/home/deploy/TiredProductions/tiredofdointm.com|${APP_DIR}|g" "${PANEL_SITES}"
    fi
fi

# =============================================================================
# SETUP DEPLOY SCRIPT
# =============================================================================
log_info "Setting up deploy script..."
DEPLOY_SCRIPT="/home/${DEPLOY_USER}/deploy.sh"
cat > ${DEPLOY_SCRIPT} << DEPLOY_EOF
#!/usr/bin/env bash
# Quick deploy script - pulls latest from specified branch
set -euo pipefail

SITE="\${1:-tiredofdointm.com}"
BRANCH="\${2:-main}"
SITES_ROOT="${SITES_ROOT}"

echo "Deploying \${SITE} branch: \${BRANCH}"

cd \${SITES_ROOT}/\${SITE}
git fetch --all --prune
git checkout \${BRANCH}
git reset --hard origin/\${BRANCH}
git clean -fd

# Run migrations if migrate.php exists
if [ -f "migrate.php" ]; then
    echo "Running migrations..."
    php migrate.php
fi

# Clear caches
if [ -d "storage/cache" ]; then
    rm -rf storage/cache/*
fi

# Restart PHP-FPM
sudo systemctl restart php${PHP_VERSION}-fpm

echo "Deploy complete: \$(date)"
DEPLOY_EOF

chown ${DEPLOY_USER}:${DEPLOY_USER} ${DEPLOY_SCRIPT}
chmod +x ${DEPLOY_SCRIPT}

# =============================================================================
# SETUP SUDOERS FOR DEPLOY USER
# =============================================================================
log_info "Configuring sudo permissions for deploy user..."

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
# START SERVICES
# =============================================================================
log_info "Starting services..."
systemctl enable php${PHP_VERSION}-fpm
systemctl restart php${PHP_VERSION}-fpm
systemctl enable nginx
systemctl restart nginx
systemctl enable fail2ban
systemctl start fail2ban

# =============================================================================
# FINAL OUTPUT
# =============================================================================
log_info ""
log_info "=============================================="
log_info "VPS Setup Complete!"
log_info "=============================================="
log_info ""
log_info "📁 DIRECTORY STRUCTURE:"
log_info "   /home/${DEPLOY_USER}/"
log_info "   ├── panel/              → VPS Panel (${PANEL_DOMAIN})"
log_info "   ├── TiredProductions/   → Your websites"
log_info "   │   └── ${DOMAIN}/"
log_info "   └── shared/             → Shared templates"
log_info ""
log_info "🔑 DEPLOY KEY (add this to GitHub):"
log_info "   Go to: https://github.com/Saulgoodmantm/tired/settings/keys"
log_info "   Click 'Add deploy key', check 'Allow write access', and paste:"
echo ""
cat /home/${DEPLOY_USER}/.ssh/deploy_key.pub
echo ""
log_info ""
log_info "📋 NEXT STEPS:"
log_info "1. Add the deploy key above to GitHub (Settings → Deploy keys)"
log_info "2. Point DNS to this server's IP: $(curl -s ifconfig.me 2>/dev/null || echo 'YOUR_IP')"
log_info "   - ${DOMAIN} → A record"
log_info "   - www.${DOMAIN} → A record"  
log_info "   - ${PANEL_DOMAIN} → A record"
log_info ""
log_info "3. Edit panel config for Google OAuth:"
log_info "   nano ${PANEL_DIR}/config/.env"
log_info ""
log_info "4. Edit main site config:"
log_info "   nano ${APP_DIR}/config/.env"
log_info ""
log_info "5. Enable SSL for both sites:"
log_info "   sudo certbot --nginx -d ${DOMAIN} -d www.${DOMAIN}"
log_info "   sudo certbot --nginx -d ${PANEL_DOMAIN}"
log_info ""
log_info "🎛️ VPS PANEL:"
log_info "   URL: https://${PANEL_DOMAIN}"
log_info "   - Manage sites, run commands, view logs"
log_info "   - Requires Google OAuth setup (see .env)"
log_info ""
log_info "🚀 MANUAL DEPLOYMENT:"
log_info "   ./deploy.sh ${DOMAIN} main"
log_info ""
log_info "=============================================="
