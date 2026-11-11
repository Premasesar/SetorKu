# Panduan Deployment Production — SetorKu

Dokumen ini berisi panduan teknis resmi deployment aplikasi **SetorKu (Sistem Informasi Bank Sampah)** ke lingkungan *production* (server VPS, Cloud VM, atau container Docker).

---

## 1. Arsitektur & Karakteristik Aplikasi

- **Framework**: Laravel 11 (PHP 8.3)
- **Database**: MySQL 8.0+ atau MariaDB 10.5+ (UTF8mb4)
- **Frontend / Aset**:
  - Seluruh layout dan komponen visual SetorKu menggunakan CSS statis (`public/css/setorku-theme.css`) dan script mandiri (`public/js/map-picker.js`).
  - Framework CSS, ikon, peta, chart, dan scanner QR dimuat via CDN publik tepercaya:
    - Bootstrap 5.3.3 & Bootstrap Icons 1.11.3 (`cdn.jsdelivr.net`)
    - Google Fonts Plus Jakarta Sans (`fonts.googleapis.com`)
    - Leaflet 1.9.4 (`unpkg.com`)
    - Chart.js 4.4.1 (`cdn.jsdelivr.net`)
    - Html5-QRCode (`unpkg.com`)
    - QRCode.js (`cdnjs.cloudflare.com`)
  - **Catatan Aset (Vite/Node.js)**: **TIDAK memerlukan `npm run build` di server production**. Server tidak memerlukan Node.js/npm untuk menjalankan aplikasi secara penuh.
- **Autentikasi**:
  - Login lokal (email + password) untuk Petugas/Admin (dan Nasabah lama).
  - Google OAuth 2.0 (PBI-15) untuk Admin, Petugas, dan Nasabah.
  - **Keamanan Akun Production**: Akun demo (`admin@banksampah.test`, `petugas@banksampah.test`, `nasabah*@banksampah.test`) serta seeder demo (12 penjemputan contoh) **otomatis dicegah** berjalan di `APP_ENV=production`. Admin pertama hanya dibuat berdasarkan `SEED_ADMIN_EMAIL` dan dibuat tanpa password lokal (`password: null`), sehingga hanya dapat masuk menggunakan Google OAuth.

---

## 2. Persyaratan Sistem Server

| Komponen | Spesifikasi Minimum | Rekomendasi |
|---|---|---|
| Sistem Operasi | Ubuntu 22.04 LTS / 24.04 LTS / Debian 12 | Ubuntu 24.04 LTS |
| PHP | PHP 8.2 atau 8.3 | PHP 8.3 (FPM) |
| Ekstensi PHP | `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `curl`, `gd`, `zip`, `opcache` | Aktifkan `opcache` untuk performa |
| Database | MySQL 8.0+ atau MariaDB 10.5+ | MySQL 8.0 |
| Dependency Manager | Composer 2.x | Composer 2.7+ |
| Web Server | Nginx 1.20+ atau Apache 2.4+ | Nginx (Reverse Proxy / PHP-FPM) |
| SSL / TLS | Sertifikat SSL valid (Let's Encrypt / Cloudflare) | Wajib HTTPS |

---

## 3. Daftar Variabel Lingkungan (`.env`)

Salin file contoh `.env.production.example` menjadi `.env`:

```bash
cp .env.production.example .env
```

Berikut daftar variabel kunci dan panduan konfigurasinya:

| Variabel | Contoh Nilai | Keterangan |
|---|---|---|
| `APP_NAME` | `SetorKu` | Nama aplikasi yang tampil di antarmuka |
| `APP_ENV` | `production` | **Wajib `production`**. Mengaktifkan proteksi seeder, pemaksaan HTTPS, dan menonaktifkan debug bar |
| `APP_KEY` | *(hasil `php artisan key:generate`)* | Kunci enkripsi Laravel (32 karakter acak base64) |
| `APP_DEBUG` | `false` | **Wajib `false`** di production untuk mencegah kebocoran informasi sensitif |
| `APP_URL` | `https://setorku.domainanda.com` | URL domain publik aplikasi (wajib diawali `https://`) |
| `DB_CONNECTION` | `mysql` | Driver database |
| `DB_HOST` | `127.0.0.1` | Host MySQL/MariaDB |
| `DB_PORT` | `3306` | Port MySQL |
| `DB_DATABASE` | `setorku_production` | Nama database |
| `DB_USERNAME` | `setorku_user` | Username database |
| `DB_PASSWORD` | `(password acak kuat)` | Password database |
| `SESSION_DRIVER` | `database` | Penyimpanan sesi HTTP (tabel `sessions`) |
| `SESSION_SECURE_COOKIE` | `true` | **Wajib `true`** agar cookie sesi hanya dikirim via HTTPS |
| `SESSION_LIFETIME` | `120` | Durasi sesi (menit) |
| `BANK_SAMPAH_NAME` | `"Bank Sampah SetorKu Yogyakarta"` | Nama depot bank sampah |
| `BANK_SAMPAH_LAT` | `-7.7970680` | Titik depot awal & akhir rute (Latitude) |
| `BANK_SAMPAH_LNG` | `110.3705290` | Titik depot awal & akhir rute (Longitude) |
| `GOOGLE_CLIENT_ID` | `123456...apps.googleusercontent.com` | Google Cloud OAuth Client ID |
| `GOOGLE_CLIENT_SECRET` | `GOCSPX-...` | Google Cloud OAuth Client Secret |
| `GOOGLE_REDIRECT_URI` | `https://setorku.domainanda.com/auth/google/callback` | Callback URL OAuth (harus identik dengan yang didaftarkan di Google Console) |
| `SEED_ADMIN_EMAIL` | `admin.utama@gmail.com` | **Email Google Admin Utama**. Akun admin pertama akan diinisialisasi dengan email ini |

---

## 4. Urutan Perintah Deployment Pertama Kali (Initial Setup)

Jalankan perintah berikut secara berurutan pada server:

```bash
# 1. Masuk ke direktori web server
cd /var/www

# 2. Clone repository SetorKu
git clone https://github.com/Premasesar/SetorKu.git setorku
cd setorku

# 3. Salin konfigurasi environment production
cp .env.production.example .env

# 4. Edit .env dan sesuaikan DB_*, APP_URL, GOOGLE_*, dan SEED_ADMIN_EMAIL
nano .env

# 5. Pasang dependensi PHP tanpa paket pengembangan (no-dev)
composer install --no-dev --optimize-autoloader

# 6. Generate APP_KEY
php artisan key:generate --force

# 7. Jalankan migrasi database
php artisan migrate --force

# 8. Inisialisasi master data jenis sampah & akun Admin pertama
# (Perintah ini aman: hanya membuat master jenis sampah dan akun admin SEED_ADMIN_EMAIL tanpa password)
php artisan db:seed --force

# 9. Buat symbolic link storage (jika ada file upload di masa depan)
php artisan storage:link

# 10. Optimasi & caching konfigurasi, route, dan view
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 11. Atur izin kepemilikan dan hak akses direktori storage dan cache
sudo chown -R www-data:www-data /var/www/setorku
sudo chmod -R 775 /var/www/setorku/storage /var/www/setorku/bootstrap/cache
```

---

## 5. Urutan Perintah Update / Redeployment

Saat merilis pembaruan kode dari GitHub ke server production, jalankan urutan berikut:

```bash
cd /var/www/setorku

# 1. Aktifkan mode pemeliharaan (maintenance mode)
php artisan down --retry=60

# 2. Ambil kode terbaru dari branch utama
git pull origin main

# 3. Perbarui dependensi jika ada perubahan composer.json/lock
composer install --no-dev --optimize-autoloader

# 4. Jalankan migrasi database baru jika ada
php artisan migrate --force

# 5. Segarkan cache konfigurasi, route, dan view
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Kembalikan aplikasi ke status online
php artisan up
```

---

## 6. Konfigurasi Web Server

### A. Nginx + PHP 8.3-FPM (Direkomendasikan)

Simpan konfigurasi berikut di `/etc/nginx/sites-available/setorku.conf`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name setorku.domainanda.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name setorku.domainanda.com;

    # SSL Certificates (misal Let's Encrypt / Certbot)
    ssl_certificate /etc/letsencrypt/live/setorku.domainanda.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/setorku.domainanda.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    root /var/www/setorku/public;
    index index.php index.html;

    charset utf-8;

    # Forwarding Headers untuk Trusted Proxies di Laravel
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;

    # Keamanan header HTTP dasar
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan konfigurasi dan muat ulang Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/setorku.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

### B. Apache 2.4 VirtualHost (Alternatif)

Pastikan modul Apache aktif:
```bash
sudo a2enmod rewrite ssl headers proxy proxy_fcgi
```

Simpan konfigurasi di `/etc/apache2/sites-available/setorku.conf`:

```apache
<VirtualHost *:80>
    ServerName setorku.domainanda.com
    Redirect permanent / https://setorku.domainanda.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName setorku.domainanda.com
    DocumentRoot /var/www/setorku/public

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/setorku.domainanda.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/setorku.domainanda.com/privkey.pem

    <Directory /var/www/setorku/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Forwarding header proxy
    RequestHeader set X-Forwarded-Proto "https"

    ErrorLog ${APACHE_LOG_DIR}/setorku_error.log
    CustomLog ${APACHE_LOG_DIR}/setorku_access.log combined
</VirtualHost>
```

---

## 7. Opsi Deployment Menggunakan Docker

Bagi pengguna Docker, SetorKu menyediakan `Dockerfile` berbasis `php:8.3-apache` yang siap pakai.

### Build & Menjalankan Kontainer:

```bash
# 1. Build image Docker
docker build -t setorku:latest .

# 2. Jalankan container
docker run -d \
  --name setorku-app \
  -p 8080:80 \
  --env-file .env \
  -v setorku_storage:/var/www/html/storage \
  setorku:latest
```

Jika berada di belakang Nginx/Traefik reverse proxy pada host, arahkan traffic HTTPS port 443 ke port kontainer `8080`.

---

## 8. Verifikasi Pasca-Deployment (Post-Deployment Checklist)

Setelah menjalankan langkah-langkah di atas, lakukan verifikasi:

1. **Akses URL Aplikasi**: Buka `https://setorku.domainanda.com`. Pastikan redirect otomatis ke `/login` dengan gembok SSL valid (HTTPS).
2. **Uji Login Google**:
   - Klik "Masuk dengan Google".
   - Masuk menggunakan akun yang didaftarkan pada `SEED_ADMIN_EMAIL`.
   - Pastikan diarahkan langsung ke dashboard Admin (`/admin/dashboard`).
3. **Uji Pengamanan Kredensial**:
   - Coba login manual dengan email `admin@banksampah.test` / `password`. Pastikan login gagal (kredensial tidak ditemukan).
4. **Periksa Tampilan Antarmuka**:
   - Pastikan tema warna hijau Sage dan gaya *Ecological Modernism* tampil sempurna (CSS termuat dari `/css/setorku-theme.css`).
   - Periksa ikon Bootstrap dan font Plus Jakarta Sans termuat tanpa pesan peringatan Mixed Content.
5. **Cek Log Aplikasi**:
   - Pastikan `storage/logs/laravel.log` bersih dari exception yang fatal.
