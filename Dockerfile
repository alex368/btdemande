FROM php:8.4-fpm-alpine

RUN apk add --no-cache imagemagick

# 1) Dépendances runtime + OCR
RUN apk add --no-cache \
    bash git curl unzip \
    icu libzip oniguruma \
    poppler-utils \
    tesseract-ocr tesseract-ocr-data-fra tesseract-ocr-data-eng

# 2) Dépendances build + compilation extensions PHP
RUN apk add --no-cache --virtual .build-deps \
    icu-dev libzip-dev oniguruma-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    $PHPIZE_DEPS \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" intl pdo pdo_mysql zip opcache gd \
 && apk del .build-deps

# 3) Tesseract
ENV TESSDATA_PREFIX=/usr/share/tessdata

# 4) Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 5) Config PHP
COPY docker/php/conf.d/app.ini /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html

# 6) User
RUN addgroup -g 1000 -S app \
 && adduser -u 1000 -S app -G app

# 7) Copie du code
COPY --chown=app:app . .

# 8) Dossiers nécessaires
RUN mkdir -p var/tmp var/cache var/log public/uploads/documents \
 && chown -R app:app /var/www/html

USER app

# 9) Composer install
RUN composer install --no-dev --optimize-autoloader --no-interaction