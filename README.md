# SetorKu — Sistem Informasi Bank Sampah

**SetorKu** adalah aplikasi web pengelolaan operasional Bank Sampah berbasis **Laravel 12**, **MySQL**, dan **Bootstrap 5** yang dirancang untuk mendigitalisasi pencatatan tabungan sampah, penjemputan terjadwal berbasis peta, optimasi rute penjemputan, kartu identitas QR nasabah, pelaporan bulanan, serta pemantauan dampak pengelolaan sampah.

---

## Ringkasan Fitur Utama

1. **Autentikasi & Hak Akses Berbasis Peran**: Pemisahan otorisasi ketat di sisi backend untuk **Admin**, **Petugas**, dan **Nasabah**, termasuk penolakan login & *auto-logout* bagi akun nonaktif.
2. **Manajemen Nasabah, Petugas & Master Jenis Sampah**: Pengelolaan data tanpa penghapusan permanen (*non-destructive activation/deactivation*).
3. **Transaksi Setoran & Penarikan Atomik**: Perhitungan nominal di sisi server dengan *snapshot* harga (`price_per_unit` & `total_value`), penguncian baris (`DB::transaction` + `lockForUpdate`), dan jaminan saldo tidak pernah negatif.
4. **Penjemputan Sampah Berbasis Peta (Leaflet + OpenStreetMap)**: Pengajuan titik jemput oleh nasabah, penjadwalan/penolakan oleh admin, serta pencatatan hasil penjemputan multi-jenis sampah oleh petugas yang otomatis menghasilkan bukti setoran atomik.
5. **Optimasi Rute Penjemputan**: Perhitungan rute tertutup dari dan kembali ke Bank Sampah menggunakan rumus **Haversine + Nearest Neighbor + 2-Opt** (`App\Services\RouteOptimizer`) lengkap dengan visualisasi peta bernomor.
6. **Kartu QR Nasabah & Pemindaian Cepat**: Kartu anggota siap cetak (hanya memuat kode `NSB-xxxx` tanpa data sensitif) serta pencarian cepat via alat *scanner* maupun kamera di form setoran dan penarikan.
7. **Dashboard Peran, Laporan Bulanan & Dashboard Dampak**: Rekapitulasi periodik serta grafik tren berat sampah 12 bulan terakhir (Chart.js) berdasarkan data aktual yang tersimpan.

Seluruh pustaka antarmuka (Bootstrap 5, Bootstrap Icons, Leaflet, QRCode.js, Html5-QRCode, dan Chart.js) dimuat melalui **CDN publik** tanpa memerlukan *API key* maupun kompilasi aset tambahan.

---

## Kebutuhan Sistem

- **PHP**: `>= 8.2` (direkomendasikan PHP `8.3` pada Laragon) dengan ekstensi `pdo_mysql`, `pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`
- **Composer**: `>= 2.x`
- **Database**: **MySQL 8.x** / MariaDB (tersedia pada bundel **Laragon**)
- **Lingkungan Lokal**: Windows + **Laragon** (atau server web setara)

---

## Langkah Instalasi

Jalankan perintah berikut pada terminal PowerShell di dalam direktori proyek (`bank-sampah`):

```powershell
# 1. Pasang dependensi PHP
composer install

# 2. Salin berkas konfigurasi environment
Copy-Item .env.example .env

# 3. Bangkitkan kunci enkripsi aplikasi
php artisan key:generate
```

4. **Buat Database MySQL**:
   Pastikan layanan MySQL di Laragon aktif, lalu buat database kosong bernama `bank_sampah` (misalnya melalui HeidiSQL/phpMyAdmin atau CLI MySQL: `CREATE DATABASE bank_sampah;`).

5. **Sesuaikan Konfigurasi `.env`**:
   Pastikan pengaturan koneksi database dan titik koordinat Bank Sampah di `.env` sesuai dengan lingkungan lokal Anda:

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=bank_sampah
   DB_USERNAME=root
   DB_PASSWORD=

   BANK_SAMPAH_NAME="Bank Sampah SetorKu Yogyakarta"
   BANK_SAMPAH_LAT=-7.7970680
   BANK_SAMPAH_LNG=110.3705290
   ```

6. **Jalankan Migrasi dan Seeder Awal**:

   ```powershell
   php artisan migrate --seed
   ```

7. **Jalankan Aplikasi**:

   ```powershell
   php artisan serve
   ```
   Buka `http://127.0.0.1:8000` (atau URL virtual host Laragon Anda) di peramban.

---

## Variabel `.env` Khusus SetorKu

Konfigurasi titik keberangkatan dan kepulangan rute penjemputan dibaca oleh `config/setorku.php` dari variabel berikut:

| Variabel | Deskripsi | Nilai Bawaan (*Default*) |
| --- | --- | --- |
| `BANK_SAMPAH_NAME` | Nama lokasi depot Bank Sampah pada peta rute & kartu QR | `"Bank Sampah SetorKu Yogyakarta"` |
| `BANK_SAMPAH_LAT` | Garis lintang (*latitude*) titik Bank Sampah (rentang `-11..6`) | `-7.7970680` |
| `BANK_SAMPAH_LNG` | Garis bujur (*longitude*) titik Bank Sampah (rentang `95..141`) | `110.3705290` |

---

## Daftar Akun Uji (*Seeder*)

Setelah menjalankan `php artisan migrate --seed`, Anda dapat masuk menggunakan akun uji berikut:

| Peran | Email | Kata Sandi | Keterangan |
| --- | --- | --- | --- |
| **Admin** | `admin@banksampah.test` | `password` | Akses penuh manajemen, penjadwalan, optimasi rute, laporan & dampak |
| **Petugas 1** | `petugas@banksampah.test` | `password` | Petugas lapangan (memiliki 4 jadwal jemput hari ini pada data seeder) |
| **Petugas 2** | `petugas2@banksampah.test` | `password` | Petugas lapangan (memiliki 4 jadwal jemput hari ini pada data seeder) |
| **Nasabah** | `nasabah1@banksampah.test` s.d. `nasabah11@banksampah.test` | `password` | 11 akun nasabah berkoordinat area Yogyakarta (`NSB-0001` s.d. `NSB-0011`) |

---

## Menjalankan Pengujian Otomatis

Pengujian otomatis dikonfigurasi pada `phpunit.xml` menggunakan SQLite `:memory:` sehingga cepat dan tidak menyentuh database MySQL lokal Anda:

```powershell
# Menjalankan seluruh unit & feature test
php artisan test

# Menjalankan kelompok tes tertentu
php artisan test --filter=RouteOptimizerTest
```

---

## Dokumentasi Tambahan

- [Panduan Pengguna per Peran (`docs/PANDUAN_PENGGUNA.md`)](docs/PANDUAN_PENGGUNA.md)
- [Dokumentasi Algoritma Optimasi Rute (`docs/OPTIMASI_RUTE.md`)](docs/OPTIMASI_RUTE.md)
