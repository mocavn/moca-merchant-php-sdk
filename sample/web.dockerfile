FROM php:7.4.13-fpm-alpine

# Install packages and remove default server definition
RUN apk --no-cache add nginx supervisor curl composer && \
    rm /etc/nginx/conf.d/default.conf

# Configure nginx
COPY sample/nginx/nginx.conf /etc/nginx/nginx.conf

# Configure PHP-FPM
COPY sample/nginx/fpm-pool.conf /usr/local/etc/php-fpm.conf
COPY sample/local.ini /usr/local/etc/php/conf.d/local.ini

# Configure supervisord
COPY sample/nginx/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Setup document root
RUN mkdir -p /var/www/html
COPY sample/ /var/www/html
COPY docs /var/www/html/docs
COPY sample/.env.example /var/www/html/.env

# Make sure files/folders needed by the processes are accessable when they run under the nobody user
RUN chown -R nobody.nobody /var/www/html && \
    chown -R nobody.nobody /run && \
    chown -R nobody.nobody /var/lib/nginx && \
    chown -R nobody.nobody /var/log/nginx

# Switch to use a non-root user from here on
USER nobody

# Add application
WORKDIR /var/www/html

RUN composer install && \
    php artisan key:generate

# Expose the port nginx is reachable on
EXPOSE 9900

# Let supervisord start nginx & php-fpm
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# Configure a healthcheck to validate that everything is up&running
HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:8080/fpm-ping
