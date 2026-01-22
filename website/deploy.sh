#!/bin/bash
# =============================================================================
# TIREDOFDOINTM - Deployment Script
# =============================================================================
# Run this on your DigitalOcean VPS to deploy the site
# Usage: chmod +x deploy.sh && ./deploy.sh
# =============================================================================

set -e

echo "🚀 Deploying TiredOfDoinTM..."

# Variables
APP_DIR="/var/www/tiredofdointm"
REPO_URL="https://github.com/YOUR_USERNAME/tiredofdointm.git"  # Update this
BRANCH="main"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "Please run as root (sudo ./deploy.sh)"
    exit 1
fi

echo -e "${YELLOW}Step 1: Installing dependencies...${NC}"
apt update
apt install -y nginx php8.2-fpm php8.2-pgsql php8.2-curl php8.2-mbstring php8.2-xml php8.2-zip php8.2-gd redis-server certbot python3-certbot-nginx

echo -e "${YELLOW}Step 2: Setting up directory...${NC}"
mkdir -p $APP_DIR
cd $APP_DIR

# If this is first deploy, clone. Otherwise, pull.
if [ ! -d "$APP_DIR/.git" ]; then
    echo "Cloning repository..."
    git clone $REPO_URL .
else
    echo "Pulling latest changes..."
    git fetch origin
    git reset --hard origin/$BRANCH
fi

echo -e "${YELLOW}Step 3: Setting permissions...${NC}"
chown -R www-data:www-data $APP_DIR
chmod -R 755 $APP_DIR
chmod -R 775 $APP_DIR/storage
chmod -R 775 $APP_DIR/public/uploads

echo -e "${YELLOW}Step 4: Setting up Nginx...${NC}"
cp $APP_DIR/nginx.conf /etc/nginx/sites-available/tiredofdointm.com
ln -sf /etc/nginx/sites-available/tiredofdointm.com /etc/nginx/sites-enabled/

# Test nginx config
nginx -t

echo -e "${YELLOW}Step 5: Setting up SSL (if not already done)...${NC}"
if [ ! -f "/etc/letsencrypt/live/tiredofdointm.com/fullchain.pem" ]; then
    certbot --nginx -d tiredofdointm.com -d www.tiredofdointm.com --non-interactive --agree-tos -m admin@tiredofdointm.com
fi

echo -e "${YELLOW}Step 6: Restarting services...${NC}"
systemctl restart php8.2-fpm
systemctl restart nginx
systemctl restart redis-server

echo -e "${YELLOW}Step 7: Running database migrations...${NC}"
# Run migrations (you'll need to create a migrate.php script)
# php $APP_DIR/migrate.php

echo -e "${GREEN}✅ Deployment complete!${NC}"
echo "Visit: https://tiredofdointm.com"
