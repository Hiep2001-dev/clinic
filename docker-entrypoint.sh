#!/bin/sh
# Không dùng `set -e` toàn cục: chỉ những lệnh quan trọng mới được phép làm sập container.
set -u

echo "==> Laravel container startup"

export APP_ENV="${APP_ENV:-production}"

# Render cấp biến PORT động; fallback 8080 khi chạy local
: "${PORT:=8080}"
export PORT

# Đảm bảo APP_KEY tồn tại (không ghi đè nếu Render đã set qua env)
if [ -z "${APP_KEY:-}" ]; then
    echo "==> APP_KEY chưa được set, đang generate..."
    # Không dùng `artisan key:generate` vì image không có file .env
    export APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"
    echo "==> Đã generate APP_KEY tạm thời cho session này"
fi

# Tạo sẵn các thư mục Laravel cần ghi (session/cache/log)
mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache/data \
    storage/logs \
    bootstrap/cache
export SESSION_DRIVER="${SESSION_DRIVER:-file}"
export CACHE_STORE="${CACHE_STORE:-file}"

# Nếu dùng MySQL (Aiven) thì đảm bảo CA cert tồn tại và đường dẫn đúng
if [ "${DB_CONNECTION:-mysql}" = "mysql" ]; then
    if [ -z "${MYSQL_ATTR_SSL_CA:-}" ] && [ -f /var/www/html/certs/aiven-ca.pem ]; then
        MYSQL_ATTR_SSL_CA=/var/www/html/certs/aiven-ca.pem
        export MYSQL_ATTR_SSL_CA
        echo "==> Dùng CA cert mặc định: ${MYSQL_ATTR_SSL_CA}"
    fi
fi

chown -R www-data:www-data storage bootstrap/cache database 2>/dev/null || true

# Storage symlink cho public disk
php artisan storage:link --force 2>/dev/null || true

# Xóa cache cũ trước khi tạo cache mới (tránh config cũ gây lỗi)
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

# Migrate database (không hỏi confirm). Lỗi migrate KHÔNG làm app thoát hẳn.
echo "==> Running migrations..."
php artisan migrate --force -v || echo "==> CẢNH BÁO: migrate thất bại, app vẫn tiếp tục khởi động"

# Cache cấu hình sau khi migrate để chạy nhanh hơn
php artisan config:cache && echo "==> Config cached" || echo "==> CẢNH BÁO: config:cache thất bại"
php artisan route:cache || true
php artisan view:cache || true

echo "==> Starting Laravel on 0.0.0.0:${PORT}"
# exec để PID 1 nhận tín hiệu từ Render (SIGTERM khi scale/restart)
exec php artisan serve --host=0.0.0.0 --port="${PORT}"
