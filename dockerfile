# ==========================================
# STAGE 1: Build Assets Vue.js bằng Node.js
# ==========================================
FROM node:18-alpine AS build-stage
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# ==========================================
# STAGE 2: PHP 8.2 runtime for Render
# ==========================================
FROM php:8.2-cli-alpine AS production-stage

# Extensions cần cho Laravel + ca-certificates cho SSL của Aiven MySQL
RUN apk add --no-cache curl git zip unzip sqlite-dev oniguruma-dev ca-certificates \
    && update-ca-certificates \
    && docker-php-ext-install pdo pdo_sqlite pdo_mysql mbstring bcmath

# Cài Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy toàn bộ source code
COPY . /var/www/html

# Copy assets đã build từ stage 1
COPY --from=build-stage /app/public/build /var/www/html/public/build

# Cài đặt vendor PHP (production)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Copy CA certificate của Aiven (Let's Encrypt ISRG Root X1) để dùng SSL
COPY certs/aiven-ca.pem /var/www/html/certs/aiven-ca.pem

# Tạo thư mục + cấp quyền cho storage/cache (DB là MySQL bên ngoài, không tạo sqlite)
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs database \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache database

# Entrypoint: cache config/route/view + migrate rồi start server
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Render cấp PORT động, mặc định 8080 khi chạy local
ENV PORT=8080
EXPOSE 8080

CMD ["docker-entrypoint.sh"]