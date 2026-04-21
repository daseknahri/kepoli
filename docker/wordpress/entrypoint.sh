#!/bin/sh
set -e

mkdir -p /var/www/html/wp-content/themes /var/www/html/wp-content/mu-plugins

rm -rf /var/www/html/wp-content/themes/kepoli
cp -a /opt/kepoli/wp-content/themes/kepoli /var/www/html/wp-content/themes/kepoli
cp -a /opt/kepoli/wp-content/mu-plugins/. /var/www/html/wp-content/mu-plugins/

chown -R www-data:www-data \
  /var/www/html/wp-content/themes/kepoli \
  /var/www/html/wp-content/mu-plugins \
  /seed \
  /content 2>/dev/null || true

exec docker-entrypoint.sh "$@"
