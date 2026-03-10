FROM php:8.4-fpm-alpine

RUN apk add --no-cache imagemagick

# 1) Dépendances runtime + OCR
RUN apk add --no-cache \
    bash git curl unzip \
    icu libzip oniguruma \
    poppler-utils \
    tesseract-ocr tesseract-ocr-data-fra tesseract-ocr-data-eng

# 2) Dépendances build (virtuelles) + compilation extensions PHP
RUN apk add --no-cache --virtual .build-deps \
    icu-dev libzip-dev oniguruma-dev $PHPIZE_DEPS \
 && docker-php-ext-install -j"$(nproc)" intl pdo pdo_mysql zip opcache \
 && apk del .build-deps

# 3) Tesseract: où trouver les traineddata (évite le /opt/homebrew, etc.)
ENV TESSDATA_PREFIX=/usr/share/tessdata

# 4) Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 5) Config PHP
COPY docker/php/conf.d/app.ini /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html

# 6) User + droits (tmp/cache/log/uploads)
RUN addgroup -g 1000 -S app \
 && adduser  -u 1000 -S app -G app \
 && mkdir -p /var/www/html/var/tmp /var/www/html/var/cache /var/www/html/var/log \
 && mkdir -p /var/www/html/public/uploads/documents \
 && chown -R app:app /var/www/html

USER app