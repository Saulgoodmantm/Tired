#!/usr/bin/env bash
# vps/deploy_pull.sh
# Safe deploy script to run on the VPS to update code from GitHub repo and restart basic services.
# Usage: run on VPS as your deploy user after adding the repo and SSH keys.

set -euo pipefail

REPO_DIR="/home/$USER/tiredprod"    # adjust if needed
BRANCH="${1:-main}"

echo "Deploy: pulling latest from origin/$BRANCH into $REPO_DIR"
if [ ! -d "$REPO_DIR" ]; then
  echo "Repo directory not found: $REPO_DIR"
  echo "Creating and cloning repo..."
  mkdir -p "$REPO_DIR"
  # Note: When invoked by GitHub Actions this will not be used to clone; ensure repo exists.
  exit 1
fi

cd "$REPO_DIR"

# Ensure clean working copy for safety
git fetch --all --prune
git reset --hard "origin/$BRANCH"
git clean -fd

# Install dependencies placeholders (uncomment the tools you use)
if [ -f package.json ]; then
  echo "Installing node dependencies..."
  npm ci --production
fi

# If PHP app uses composer:
if [ -f composer.json ]; then
  echo "Installing composer dependencies..."
  composer install --no-dev --optimize-autoloader
fi

# Migrate DB placeholder
# php artisan migrate --force # if Laravel used

# Restart services if defined (example: php-fpm, nginx, workers)
# sudo systemctl restart php8.2-fpm
# sudo systemctl restart nginx
# sudo systemctl restart my-worker.service

echo "Deploy complete: $(date)"