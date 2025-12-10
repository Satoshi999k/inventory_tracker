FROM php:8.2-fpm-alpine

WORKDIR /app

# Install required PHP extensions and dependencies
RUN apk add --no-cache \
    curl \
    git \
    wget \
    && docker-php-ext-install pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose port
EXPOSE 8002

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8002/health || exit 1

# Start PHP built-in server
CMD ["php", "-S", "0.0.0.0:8002", "index.php"]
