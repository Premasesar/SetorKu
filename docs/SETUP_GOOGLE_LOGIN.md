# Panduan Konfigurasi Login & Pendaftaran Google OAuth (PBI-15)

Dokumen ini menjelaskan langkah demi langkah penyiapan **Google OAuth 2.0** di **Google Cloud Console** agar fitur **Masuk dengan Google** dan **Daftar dengan Google** pada aplikasi **SetorKu** dapat digunakan secara nyata di lingkungan lokal (Laragon / `php artisan serve`).

---

## 1. Buat Project di Google Cloud Console

1. Buka [Google Cloud Console](https://console.cloud.google.com/) menggunakan akun Google (Gmail) Anda.
2. Klik pemilih *project* di bilah atas, lalu klik **New Project**.
3. Isi **Project name** (misalnya: `SetorKu Bank Sampah`), lalu klik **Create** dan pastikan *project* tersebut aktif terpilih.

---

## 2. Konfigurasi OAuth Consent Screen & Test Users

1. Buka menu **APIs & Services** -> **OAuth consent screen** (atau **Google Auth Platform** -> **Branding / Audience** pada tampilan baru).
2. Pilih tipe pengguna **External**, lalu klik **Create** / **Get Started**:
   - **App name**: `SetorKu`
   - **User support email**: pilih alamat Gmail Anda
   - **Developer contact information**: isi alamat Gmail Anda
   - Klik **Save and Continue**.
3. Pada tahap **Scopes**, pastikan cakupan dasar OpenID aktif (`openid`, `.../auth/userinfo.email`, `.../auth/userinfo.profile`), lalu klik **Save and Continue**.
4. Pada tahap **Test users** (**PENTING selama status aplikasi masih *Testing***):
   - Klik **+ Add Users**.
   - Masukkan **seluruh alamat Gmail asli** yang akan dipakai menguji aplikasi (Gmail pemilik/Admin, Gmail Petugas, dan Gmail calon Nasabah).
   - Klik **Save**. *(Selama aplikasi berstatus Testing dan belum dipublikasikan ke Production, hanya alamat Gmail yang terdaftar di Test users yang diizinkan login oleh Google).*

---

## 3. Buat OAuth 2.0 Client ID & Redirect URI

1. Buka menu **APIs & Services** -> **Credentials** (atau **Clients**).
2. Klik **+ Create Credentials** -> pilih **OAuth client ID**.
3. Pilih **Application type**: **Web application**.
4. Isi **Name**: `SetorKu Local Web`.
5. Pada bagian **Authorized JavaScript origins**, tambahkan URL lokal yang Anda pakai:
   - `http://localhost:8000` *(jika menjalankan `php artisan serve`)*
   - *(Opsional: tambahkan `http://127.0.0.1:8000` bila mengakses lewat `127.0.0.1`, tetapi Google menyarankan `http://localhost:8000` untuk OAuth lokal).*
6. Pada bagian **Authorized redirect URIs**, tambahkan URI callback **persis** sama dengan nilai `GOOGLE_REDIRECT_URI` di `.env`:
   - `http://localhost:8000/auth/google/callback`
7. Klik **Create**, lalu salin nilai **Client ID** dan **Client Secret** yang muncul.

> **Keputusan Standar Pengujian Lokal**:
> Pengujian lokal SetorKu ditetapkan menggunakan `php artisan serve --host=localhost --port=8000` (akses melalui `http://localhost:8000`, **bukan** `127.0.0.1` dan **bukan** virtual host `.test` karena Google OAuth menolak akhiran `.test`). Pastikan domain pada browser sama persis dengan `APP_URL` dan `GOOGLE_REDIRECT_URI` agar cookie session OAuth `state` cocok.

---

## 4. Isi File `.env` Lokal

Buka file `.env` di direktori proyek `bank-sampah` (**jangan pernah meng-commit `.env` ke Git**), lalu sesuaikan variabel berikut:

```env
APP_URL=http://localhost:8000

GOOGLE_CLIENT_ID=isi_client_id_dari_google_cloud_console
GOOGLE_CLIENT_SECRET=isi_client_secret_dari_google_cloud_console
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback

# Opsional: isi dengan Gmail asli pemilik proyek sebelum menjalankan seeder
SEED_ADMIN_EMAIL=akun.admin.anda@gmail.com
```

---

## 5. Jalankan Migrasi, Seeder (Opsional), dan Bersihkan Cache Konfigurasi

Jalankan perintah berikut di terminal PowerShell (pastikan menggunakan PHP 8.3 Laragon):

```powershell
# 1. Jalankan migrasi baru (menambahkan google_id & password nullable tanpa menghapus data)
php artisan migrate

# 2. (Opsional) Jalankan seeder bila ingin membuat akun Admin dengan SEED_ADMIN_EMAIL
php artisan db:seed

# 3. Bersihkan cache konfigurasi agar perubahan .env terbaca
php artisan config:clear

# 4. Jalankan server lokal sesuai host pada GOOGLE_REDIRECT_URI
php artisan serve --host=localhost --port=8000
```

Buka `http://localhost:8000/login` di browser dan uji tombol **Masuk dengan Google** serta **Daftar dengan Google**.

---

## 6. Ringkasan Aturan Peran & Keputusan Akun Admin pada Login Google

- **Admin (Dua Akun Admin — Cadangan + Gmail Pemilik)**:
  - Akun bawaan `admin@banksampah.test` tetap dipertahankan di database sebagai **akun Admin cadangan** (login email + password).
  - Saat pemilik proyek mengisi `SEED_ADMIN_EMAIL=gmail.pemilik@gmail.com` di `.env` lalu menjalankan `php artisan db:seed`, seeder (`firstOrCreate`) menambahkan Gmail tersebut sebagai **akun Admin kedua** tanpa menghapus `admin@banksampah.test`. Saat pemilik login memakai tombol **Masuk dengan Google**, akun Admin kedua tersebut otomatis terhubung.
- **Petugas**: Didaftarkan terlebih dahulu oleh Admin melalui menu **Kelola Petugas** dengan mengisi **Email akun Google petugas** (kolom password bersifat opsional). Saat petugas login dengan Google memakai Gmail tersebut, ia langsung masuk ke **Dashboard Petugas**.
- **Nasabah**:
  - Jika sudah pernah didaftarkan oleh Admin/Petugas dengan alamat Gmail yang sama, langsung masuk ke **Portal Nasabah**.
  - Jika alamat Gmail belum terdaftar di sistem, pengguna diarahkan ke halaman **Lengkapi Pendaftaran** (`/daftar/lengkapi`) untuk mengisi Nama, No. HP, dan Alamat, lalu otomatis dibuatkan akun **Nasabah** bersaldo awal **Rp 0**.
