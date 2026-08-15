#!/bin/sh
set -e

mkdir -p /var/www/html/wp-content/themes /var/www/html/wp-content/mu-plugins /var/www/html/wp-content/plugins

rm -rf /var/www/html/wp-content/themes/viral-reader
rm -rf /var/www/html/wp-content/plugins/automation-hamri
cp -a /opt/kepoli/wp-content/themes/viral-reader /var/www/html/wp-content/themes/viral-reader
cp -a /opt/kepoli/wp-content/mu-plugins/. /var/www/html/wp-content/mu-plugins/
cp -a /opt/kepoli/wp-content/plugins/automation-hamri /var/www/html/wp-content/plugins/automation-hamri

chown -R www-data:www-data \
  /var/www/html/wp-content/themes/viral-reader \
  /var/www/html/wp-content/mu-plugins \
  /var/www/html/wp-content/plugins/automation-hamri \
  /seed \
  /content 2>/dev/null || true

exec docker-entrypoint.sh "$@"
