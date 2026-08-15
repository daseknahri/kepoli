#!/bin/sh
set -e

export WP_CLI_ALLOW_ROOT=1

mkdir -p /var/www/html/wp-content/themes /var/www/html/wp-content/mu-plugins /var/www/html/wp-content/plugins

rm -rf /var/www/html/wp-content/themes/viral-reader
rm -rf /var/www/html/wp-content/plugins/automation-hamri
cp -a /opt/kepoli/wp-content/themes/viral-reader /var/www/html/wp-content/themes/viral-reader
cp -a /opt/kepoli/wp-content/mu-plugins/. /var/www/html/wp-content/mu-plugins/
cp -a /opt/kepoli/wp-content/plugins/automation-hamri /var/www/html/wp-content/plugins/automation-hamri

chown -R 33:33 \
  /var/www/html/wp-content/themes/viral-reader \
  /var/www/html/wp-content/mu-plugins \
  /var/www/html/wp-content/plugins/automation-hamri \
  /seed \
  /content 2>/dev/null || true

/bin/sh /seed/bin/bootstrap.sh

chown -R 33:33 \
  /var/www/html/wp-content \
  /var/www/html/wp-config.php 2>/dev/null || true
