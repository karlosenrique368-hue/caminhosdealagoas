FROM php:8.3-cli-alpine

RUN docker-php-ext-install pdo_mysql \
    && cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini 2>/dev/null || true

WORKDIR /app
COPY . .

# Rename uploads → uploads.baked so the volume mount at /app/storage/uploads
# doesn't shadow the files we ship in the image.
# The entrypoint copies them into the volume on first boot (cp -n = no-clobber).
RUN mv storage/uploads storage/uploads.baked \
    && mkdir -p storage/uploads storage/logs storage/backups \
    && chown -R www-data:www-data storage \
    && chmod +x docker-entrypoint.sh

ENV APP_ENV=production \
    APP_BASE_PATH= \
    PORT=8080

EXPOSE 8080

ENTRYPOINT ["sh", "docker-entrypoint.sh"]
