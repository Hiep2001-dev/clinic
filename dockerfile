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
# STAGE 2: PHP 8.2 + Nginx + Composer
# ==========================================
FROM php:8.2-fpm-alpine AS production-stage

# Cài pdo_mysql để Laravel kết nối được MySQL
RUN apk add --no-cache nginx curl git zip unzip \
    && docker-php-ext-install pdo pdo_mysql

# Cài Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . /var/www/html
COPY --from=build-stage /app/public/build /var/www/html/public/build

# Cài đặt vendor PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Cấp quyền cho storage
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

COPY nginx.conf /etc/nginx/http.d/default.conf

EXPOSE 80

CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]