FROM wordpress:php8.3-apache

COPY wp-content/themes/kepoli/ /opt/kepoli/wp-content/themes/kepoli/
COPY wp-content/mu-plugins/ /opt/kepoli/wp-content/mu-plugins/
COPY seed/ /seed/
COPY content/ /content/
COPY docker/wordpress/entrypoint.sh /usr/local/bin/kepoli-wordpress-entrypoint

RUN chmod +x /usr/local/bin/kepoli-wordpress-entrypoint /seed/bin/bootstrap.sh

ENTRYPOINT ["kepoli-wordpress-entrypoint"]
CMD ["apache2-foreground"]
