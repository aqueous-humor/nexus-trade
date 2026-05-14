# ─── Stage 1: Composer dependencies ─────────────────────────────────────────
FROM php:8.3-cli-alpine AS builder

RUN apk add --no-cache git zip unzip libzip-dev \
    && docker-php-ext-install zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files first for layer caching
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --optimize-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

# Copy the rest of the application
COPY . .

# Run post-install scripts now that the full app is present
RUN composer run-script post-autoload-dump 2>/dev/null || true

# ─── Stage 2: Development image ──────────────────────────────────────────────
FROM php:8.4-cli-alpine AS development

RUN apk add --no-cache \
        git \
        curl \
        libxml2-dev \
        oniguruma-dev \
        sqlite-libs \
        sqlite-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo_mysql \
        pdo_sqlite \
        bcmath \
        mbstring \
        xml \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/* /tmp/pear

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 8000

# Bootstrap: install deps, run migrations, start dev server
CMD ["sh", "-c", "composer install --no-interaction --prefer-dist && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000"]

# ─── Stage 3: Production image ───────────────────────────────────────────────
FROM php:8.4-cli-alpine AS production

# Install PHP extensions
RUN apk add --no-cache \
        curl \
        libxml2-dev \
        oniguruma-dev \
        sqlite-libs \
        sqlite-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo_mysql \
        pdo_sqlite \
        bcmath \
        mbstring \
        xml \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/* /tmp/pear

WORKDIR /var/www/html

# Copy vendor from builder stage
COPY --from=builder /app/vendor ./vendor

# Copy application code
COPY . .

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8080

CMD ["sh", "-c", "php artisan config:cache && php artisan route:cache && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]
