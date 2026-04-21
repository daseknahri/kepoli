#!/bin/sh
set -eu

cd /var/www/html

: "${WORDPRESS_DB_HOST:?WORDPRESS_DB_HOST is required}"
: "${WORDPRESS_DB_NAME:?WORDPRESS_DB_NAME is required}"
: "${WORDPRESS_DB_USER:?WORDPRESS_DB_USER is required}"
: "${WORDPRESS_DB_PASSWORD:?WORDPRESS_DB_PASSWORD is required}"
: "${WP_ADMIN_USER:?WP_ADMIN_USER is required}"
: "${WP_ADMIN_PASSWORD:?WP_ADMIN_PASSWORD is required}"
: "${WP_ADMIN_EMAIL:?WP_ADMIN_EMAIL is required}"

SITE_URL="${SITE_URL:-https://kepoli.com}"
WP_LOCALE="${WP_LOCALE:-ro_RO}"
SITE_EMAIL="${SITE_EMAIL:-contact@kepoli.com}"

echo "Waiting for WordPress files..."
count=0
while [ ! -f wp-includes/version.php ] && [ "$count" -lt 90 ]; do
  count=$((count + 1))
  sleep 2
done

if [ ! -f wp-includes/version.php ]; then
  echo "WordPress files were not present; downloading core."
  wp core download --locale="$WP_LOCALE" --force
fi

if [ ! -f wp-config.php ]; then
  echo "Creating wp-config.php"
  wp config create \
    --dbname="$WORDPRESS_DB_NAME" \
    --dbuser="$WORDPRESS_DB_USER" \
    --dbpass="$WORDPRESS_DB_PASSWORD" \
    --dbhost="$WORDPRESS_DB_HOST" \
    --skip-check \
    --force \
    --extra-php <<'PHP'
define('DISALLOW_FILE_EDIT', true);
define('WP_POST_REVISIONS', 5);
define('AUTOMATIC_UPDATER_DISABLED', false);
PHP
fi

echo "Waiting for database..."
until wp db check >/dev/null 2>&1; do
  sleep 3
done

if ! wp core is-installed >/dev/null 2>&1; then
  echo "Installing WordPress"
  wp core install \
    --url="$SITE_URL" \
    --title="Kepoli" \
    --admin_user="$WP_ADMIN_USER" \
    --admin_password="$WP_ADMIN_PASSWORD" \
    --admin_email="$WP_ADMIN_EMAIL" \
    --locale="$WP_LOCALE"
fi

wp language core install "$WP_LOCALE" --activate || true
wp language plugin install --all "$WP_LOCALE" || true
wp option update siteurl "$SITE_URL"
wp option update home "$SITE_URL"
wp option update blogname "Kepoli"
wp option update blogdescription "Retete romanesti si articole de bucatarie pentru acasa"
wp option update admin_email "$SITE_EMAIL"
wp option update blog_public "1"
wp option update timezone_string "Europe/Bucharest"
wp option update date_format "j F Y"
wp option update time_format "H:i"
wp option update permalink_structure "/%category%/%postname%/"
wp rewrite structure "/%category%/%postname%/" --hard

wp theme activate kepoli
wp plugin activate kepoli-author-tools || true
wp plugin install google-site-kit --activate || true
wp plugin deactivate akismet hello >/dev/null 2>&1 || true

wp eval-file /seed/bootstrap.php
wp rewrite flush --hard

echo "Kepoli bootstrap complete."
