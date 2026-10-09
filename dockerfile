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

# Extensions cần cho Laravel (sqlite mặc định, thêm pdo_mysql nếu dùng MySQL)
RUN apk add --no-cache curl git zip unzip sqlite-dev oniguruma-dev \
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

# Tạo file DB sqlite + cấp quyền cho storage
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs database \
    && touch database/database.sqlite \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache database

# Entrypoint: cache config/route/view + migrate rồi start server
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Render cấp PORT động, mặc định 8080 khi chạy local
ENV PORT=8080
EXPOSE 8080

CMD ["docker-entrypoint.sh"]