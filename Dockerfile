FROM php:8.3-cli-alpine

RUN docker-php-ext-install pdo_mysql

RUN printf "file_uploads=On\nupload_max_filesize=20M\npost_max_size=80M\nmax_file_uploads=40\n" > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app
COPY . .

RUN mkdir -p storage/uploads storage/logs storage/backups \
    && chown -R www-data:www-data storage

ENV APP_ENV=production \
    APP_BASE_PATH= \
    PORT=8080

EXPOSE 8080

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t public public/index.php"]
