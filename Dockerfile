FROM php:8.4-fpm-alpine

ARG APP_ENV=dev

# 1) Runtime dependencies + OCR
RUN apk add --no-cache \
    bash git curl unzip \
    icu libzip oniguruma \
    poppler-utils imagemagick \
    tesseract-ocr tesseract-ocr-data-fra tesseract-ocr-data-eng

# 2) Build dependencies for PHP extensions
RUN apk add --no-cache --virtual .build-deps \
    icu-dev libzip-dev oniguruma-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    $PHPIZE_DEPS \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" intl pdo pdo_mysql zip opcache gd \
 && apk del .build-deps

# 3) Tesseract configuration
ENV TESSDATA_PREFIX=/usr/share/tessdata

# 4) Copy Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 5) PHP configuration
COPY docker/php/conf.d/app.ini /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html

# 6) Create app user
RUN addgroup -g 1000 -S app \
 && adduser -u 1000 -S app -G app

# 7) Copy application code
COPY --chown=app:app . .

# 8) Create necessary directories before composer install
RUN mkdir -p var/tmp var/cache var/log public/uploads/documents \
 && chmod -R 755 var public/uploads

# 9) Install dependencies as root (before switching user)
RUN if [ "$APP_ENV" = "prod" ]; then \
      composer install --no-dev --optimize-autoloader --no-interaction --no-scripts; \
    else \
      composer install --optimize-autoloader --no-interaction --no-scripts; \
    fi

# 10) Fix ownership
RUN chown -R app:app /var/www/html

# 11) Switch to app user
USER app

ENTRYPOINT ["bash", "docker/scripts/docker-entrypoint.sh"]
CMD ["php-fpm"]
