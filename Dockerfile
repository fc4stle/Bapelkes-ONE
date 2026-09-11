# Stage 1: Build frontend assets with Node LTS
FROM node:22-alpine AS node-builder

WORKDIR /build

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

# Stage 2: PHP runtime for Render
FROM php:8.3-cli-alpine

# Install system dependencies for PHP extensions
RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libzip-dev \
    zlib-dev \
    libxml2-dev \
    oniguruma-dev \
    autoconf \
    g++ \
    make \
    libjpeg-turbo-dev \
    freetype-dev \
    linux-headers

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        mbstring \
        xml \
        bcmath \
        gd \
        zip \
        tokenizer \
        fileinfo \
        openssl \
        ctype \
        json \
        pdo

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP dependencies (production only)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Copy application code
COPY . .

# Copy built assets from Stage 1
COPY --from=node-builder /build/public/build ./public/build

# Set permissions for Laravel storage and cache
RUN chown -R www-data:www-data storage bootstrap/cache

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

# Expose port (Render injects PORT env, default 8080)
EXPOSE 8080

ENTRYPOINT ["docker-entrypoint"]
CMD ["docker-entrypoint"]
