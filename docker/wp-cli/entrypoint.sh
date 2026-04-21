#!/bin/sh
set -e

export WP_CLI_ALLOW_ROOT=1

mkdir -p /var/www/html/wp-content/themes /var/www/html/wp-content/mu-plugins

rm -rf /var/www/html/wp-content/themes/kepoli
cp -a /opt/kepoli/wp-content/themes/kepoli /var/www/html/wp-content/themes/kepoli
cp -a /opt/kepoli/wp-content/mu-plugins/. /var/www/html/wp-content/mu-plugins/

chown -R 33:33 \
  /var/www/html/wp-content/themes/kepoli \
  /var/www/html/wp-content/mu-plugins \
  /seed \
  /content 2>/dev/null || true

/bin/sh /seed/bin/bootstrap.sh

chown -R 33:33 \
  /var/www/html/wp-content \
  /var/www/html/wp-config.php 2>/dev/null || true
