FROM wordpress:php8.3-apache

COPY wp-content/themes/viral-reader/ /opt/kepoli/wp-content/themes/viral-reader/
COPY wp-content/mu-plugins/ /opt/kepoli/wp-content/mu-plugins/
COPY wp-content/plugins/ /opt/kepoli/wp-content/plugins/
COPY seed/ /seed/
COPY content/ /content/
COPY docker/wordpress/kepoli-performance.conf /etc/apache2/conf-available/kepoli-performance.conf
COPY docker/wordpress/entrypoint.sh /usr/local/bin/kepoli-wordpress-entrypoint

RUN a2enmod headers expires deflate \
    && a2enconf kepoli-performance \
    # Suppress version disclosure at the source that wins: Debian's security.conf (loaded after
    # our conf) sets ServerTokens OS, and PHP defaults expose_php On (X-Powered-By header).
    && sed -ri 's/^ServerTokens\s+.*/ServerTokens Prod/' /etc/apache2/conf-available/security.conf \
    && sed -ri 's/^ServerSignature\s+.*/ServerSignature Off/' /etc/apache2/conf-available/security.conf \
    && printf 'expose_php = Off\n' > /usr/local/etc/php/conf.d/zz-kepoli-hardening.ini \
    && chmod +x /usr/local/bin/kepoli-wordpress-entrypoint /seed/bin/bootstrap.sh

ENTRYPOINT ["kepoli-wordpress-entrypoint"]
CMD ["apache2-foreground"]
