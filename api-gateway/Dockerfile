FROM php:8.2-fpm-alpine

WORKDIR /app

# Install required PHP extensions and dependencies
RUN apk add --no-cache \
    curl \
    git \
    wget \
    && docker-php-ext-install pdo pdo_mysql

# Copy application files
COPY . .

# Expose port
EXPOSE 8000

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8000/health || exit 1

# Start PHP built-in server
CMD ["php", "-S", "0.0.0.0:8000", "gateway.php"]
