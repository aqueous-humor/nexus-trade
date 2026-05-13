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
        libpng-dev \
        libxml2-dev \
        oniguruma-dev \
        openssl-dev \
        sqlite-libs \
        sqlite-dev \
    && docker-php-ext-install \
        pdo_mysql \
        pdo_sqlite \
        bcmath \
        mbstring \
        xml \
        ctype \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/cache/apk/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 8000

# Bootstrap: install deps, run migrations, start dev server
CMD ["sh", "-c", "composer install --no-interaction --prefer-dist && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000"]

# ─── Stage 3: Production image ───────────────────────────────────────────────
FROM php:8.4-fpm-alpine AS production

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
        nginx \
        supervisor \
        curl \
        libpng-dev \
        libxml2-dev \
        oniguruma-dev \
        openssl-dev \
    && docker-php-ext-install \
        pdo_mysql \
        bcmath \
        mbstring \
        xml \
        ctype \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/cache/apk/*

# Install pdo_sqlite for testing/fallback
RUN apk add --no-cache sqlite-libs sqlite-dev \
    && docker-php-ext-install pdo_sqlite \
    && rm -rf /var/cache/apk/*

WORKDIR /var/www/html

# Copy vendor from builder stage
COPY --from=builder /app/vendor ./vendor

# Copy application code
COPY . .

# Copy nginx configuration
COPY nginx.conf /etc/nginx/http.d/default.conf

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port
EXPOSE 8080

ENTRYPOINT ["docker-entrypoint.sh"]
