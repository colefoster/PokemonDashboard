# Multi-stage build for Laravel application
FROM php:8.4-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    icu-dev \
    postgresql-dev \
    mysql-client \
    supervisor \
    nginx

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    zip \
    exif \
    pcntl \
    bcmath \
    gd \
    intl

# Install Redis extension
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del pcre-dev $PHPIZE_DEPS

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ============================================
# Build stage: Install dependencies and build assets
# ============================================
FROM base AS build

# Install Node.js for asset building
RUN apk add --no-cache nodejs npm

# Copy dependency files
COPY composer.json composer.lock ./
COPY package.json package-lock.json ./

# Install PHP dependencies (no dev dependencies for production)
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader --no-scripts

# Install Node dependencies
RUN npm ci

# Copy application files
COPY . .

# Build frontend assets
RUN npm run build

# Generate optimized autoloader
RUN composer dump-autoload --optimize

# ============================================
# Production stage: Final lean image
# ============================================
FROM base AS production

# Copy built application from build stage
COPY --from=build /var/www/html /var/www/html

# Copy nginx configuration
COPY .docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY .docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Copy supervisor configuration
COPY .docker/supervisor/supervisord.conf /etc/supervisord.conf

# Copy PHP-FPM configuration
COPY .docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY .docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create log directories
RUN mkdir -p /var/log/nginx /var/log/php-fpm \
    && chown -R www-data:www-data /var/log/nginx /var/log/php-fpm

# Expose port 80
EXPOSE 80

# Use supervisor to run nginx and php-fpm
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]