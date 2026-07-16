#!/usr/bin/env bash
# One-time provisioning for an Ubuntu VPS to host crm.moathhamad.space.
# Run as root/sudo. Review before executing.
set -euo pipefail

DOMAIN="crm.moathhamad.space"
APP_DIR="/var/www/crm-system"
REPO="https://github.com/muathhamad111-lgtm/crm-system.git"

apt-get update
apt-get install -y software-properties-common curl git unzip
add-apt-repository -y ppa:ondrej/php
apt-get update

# PHP 8.4 + extensions
apt-get install -y php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring \
  php8.4-xml php8.4-curl php8.4-zip php8.4-bcmath php8.4-gd php8.4-intl

# Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Node 22 LTS
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt-get install -y nodejs

# MySQL + Nginx + Certbot
apt-get install -y mysql-server nginx certbot python3-certbot-nginx

# App directory
mkdir -p "$APP_DIR"
git clone "$REPO" "$APP_DIR" || (cd "$APP_DIR" && git pull)
chown -R www-data:www-data "$APP_DIR"

echo "Next steps:"
echo "  1) mysql: CREATE DATABASE crm_system; CREATE USER 'crm_user'@'localhost' IDENTIFIED BY '...'; GRANT ALL ON crm_system.* TO 'crm_user'@'localhost';"
echo "  2) cp .env.production.example .env  (fill DB_PASSWORD, then: php artisan key:generate)"
echo "  3) bash deploy/deploy.sh"
echo "  4) cp deploy/nginx.conf /etc/nginx/sites-available/$DOMAIN && ln -s ../sites-available/$DOMAIN /etc/nginx/sites-enabled/ && nginx -t && systemctl reload nginx"
echo "  5) certbot --nginx -d $DOMAIN"
echo "  6) cp deploy/crm-worker.service /etc/systemd/system/ && systemctl enable --now crm-worker"
echo "  7) crontab -e  ->  add the line from deploy/scheduler.crontab"
