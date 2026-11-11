#!/usr/bin/env bash
set -e

# Render menyediakan port via environment variable PORT (default 10000)
PORT="${PORT:-10000}"

echo "==> [SetorKu] Menyiapkan port Apache: ${PORT}..."
sed -i "s/Listen [0-9]*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Cek peringatan jika APP_KEY belum diisi
if [ -z "$APP_KEY" ]; then
    echo "==> [PERINGATAN] APP_KEY belum diset di environment variable!"
fi

# Optimasi konfigurasi, route, dan view
echo "==> [SetorKu] Melakukan caching konfigurasi, route, dan view..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Jalankan migrasi database
echo "==> [SetorKu] Menjalankan migrasi database..."
php artisan migrate --force

# Jalankan seeder produksi (idempoten & aman: hanya master jenis sampah & admin SEED_ADMIN_EMAIL)
echo "==> [SetorKu] Menjalankan database seeder..."
php artisan db:seed --force

echo "==> [SetorKu] Memulai web server Apache pada port ${PORT}..."
exec apache2-foreground
