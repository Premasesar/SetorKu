Folder kerja HANYA: C:\laragon\www\Sistem Informasi Bank Sampah\bank-sampah (subfolder workspace). Jangan membuat/mengubah file di folder lain (termasuk C:\Sistem Informasi Bank Sampah dan c:\B). Gunakan perintah PowerShell untuk Windows, working directory di folder kerja. Folder ini harus berisi artisan, app, database, routes, .git; jika tidak, berhenti dan beri tahu saya.

KONTEKS
SetorKu (Laravel 12, MySQL, Bootstrap). PBI-01 dan PBI-02 sudah ter-commit (50 tes lulus). Target: semua fitur selesai secepat mungkin dengan tampilan SEDERHANA (Bootstrap standar, satu layout + menu per peran, tabel/form standar, flash message, Bahasa Indonesia). UI diganti nanti, jadi logika di Service/Controller, view tipis. Ikuti konvensi nama yang sudah ada (customers.balance, latitude, longitude).

ATURAN WAJIB
1. Jangan ubah teknologi, jangan tambah fitur di luar daftar, jangan instal package tanpa konfirmasi saya.
2. Otorisasi di backend (middleware/policy). Nasabah tidak boleh mengakses data nasabah lain.
3. Semua hitungan saldo/nominal di backend. Jangan percaya saldo, harga, total, role, atau user_id aktor dari browser.
4. Setoran & penarikan: DB::transaction + lockForUpdate pada baris nasabah. Saldo tidak boleh negatif.
5. Setoran menyimpan snapshot harga (price_per_unit + total_value). Perubahan harga master tidak boleh mengubah riwayat.
6. Satu setoran = satu jenis sampah. JANGAN buat DepositDetail/multi-item.
7. Setiap transaksi mencatat petugas (officer_id) dan waktu.
8. Data tidak dihapus permanen. Jangan reset/drop database, jangan git reset/force.

SIMPAN PROMPT INI: tulis seluruh teks prompt ini ke docs/PLAN_SETORKU.md dan commit, sebagai acuan giliran berikutnya.

PADA GILIRAN INI KERJAKAN: LANGKAH 0 + PBI-02b + PBI-03 + PBI-04 saja. Commit tiap PBI, lalu berhenti dan lapor.

LANGKAH 0 (tanpa menghapus apa pun)
a. PHP: CLI sekarang memakai C:\xampp\php\php.exe. Cari PHP Laragon (C:\laragon\bin\php\php-8.3.*\php.exe). Jika ada, jalankan semua perintah php/artisan/composer dengan PHP itu (set $env:Path untuk sesi, bukan ubah setting sistem). Verifikasi `php -v` dan ekstensi pdo_mysql, pdo_sqlite, mbstring, openssl, fileinfo. Jangan ubah php.ini tanpa izin saya.
b. Git: pastikan working tree clean. Buat branch `main` dari feature/pbi-02-data-nasabah. Jangan hapus branch lama. JANGAN push (origin masih laravel/laravel; saya beri URL repo GitHub nanti). Tiap PBI: branch baru dari main (feature/pbi-XX-nama), commit, merge ke main --no-ff setelah tes lulus.
c. phpunit.xml: pastikan BCRYPT_ROUNDS=4 dan DB tes SQLite :memory:. Selidiki kenapa tes login ~28 detik dan perbaiki jika penyebabnya di konfigurasi tes. Selama pengerjaan jalankan hanya tes relevan (`php artisan test --filter=...`); suite penuh di akhir giliran.
d. Buat CustomerFactory; lengkapi DatabaseSeeder: >=10 nasabah dummy dengan koordinat sekitar Yogyakarta, plus akun admin/petugas/nasabah yang sudah ada.

PBI-02b (perbaikan data nasabah)
- Nonaktifkan: aksi `deactivate` HANYA menonaktifkan (idempoten, tidak pernah mengaktifkan kembali). Tambah aksi `activate` terpisah (admin saja). Perbarui tes lama yang mengunci perilaku toggle.
- Login: akun yang dinonaktifkan ditolak login dengan pesan "Akun dinonaktifkan. Hubungi admin." Sesi yang sedang aktif milik akun nonaktif harus logout otomatis (middleware). Gunakan field is_active yang sudah ada (di users atau customers sesuai implementasi).
- Hak Petugas: boleh melihat/mencari daftar nasabah dan menambah nasabah baru (beserta akun user nasabah). TIDAK boleh edit, nonaktifkan, atau aktifkan.
- Tes untuk ketiganya.

PBI-03 Jenis sampah + harga
Tabel waste_types: name, unit (default 'kg'), price_per_unit decimal(12,2) > 0, is_active. Admin: CRUD + nonaktifkan/aktifkan. Petugas: hanya lihat jenis aktif. Seeder: Plastik PET, Kardus, Kertas, Botol Kaca, Kaleng (harga contoh). Tes: akses per peran, validasi.

PBI-04 Setoran
Tabel deposits: customer_id, waste_type_id, officer_id, weight decimal(10,3) > 0, price_per_unit (snapshot), total_value decimal(15,2), timestamps. Petugas: cari nasabah (nama/kode) -> pilih satu jenis sampah aktif -> input berat -> simpan -> halaman bukti setoran. Admin: lihat riwayat saja. Backend ambil harga dari master saat transaksi (bukan dari input), total = round(berat x harga, 2), menambah customers.balance dalam satu transaction. Tolak nasabah/jenis nonaktif. Tes: saldo bertambah sesuai hitungan manual, snapshot tidak berubah saat harga master diubah, berat 0/negatif/kosong ditolak, nasabah tidak bisa mencatat setoran.

(UNTUK GILIRAN BERIKUTNYA, jangan dikerjakan sekarang)
PBI-05 Penarikan: tabel withdrawals (customer_id, officer_id, amount decimal(15,2) > 0, timestamps). Petugas cari nasabah -> input nominal (>0 dan <= saldo) -> saldo berkurang -> halaman bukti. Tolak jika saldo kurang. Tes: saldo tidak pernah negatif, penarikan sebesar saldo -> 0, unit test konsistensi saldo = total setoran - total penarikan.
PBI-06 Portal nasabah: dashboard saldo + 5 transaksi terakhir; riwayat gabungan setoran & penarikan (urut waktu, paginasi); ubah profil sendiri (nama, alamat, no HP, koordinat; TIDAK boleh ubah saldo/kode/role). Tes: nasabah A tidak bisa melihat data nasabah B; input saldo di form profil diabaikan.

LAPORAN
Setelah tiap PBI: file dibuat/diubah, hasil tes, langkah uji manual. Di akhir giliran: hasil `php artisan test` penuh, `git log --oneline -10`, daftar asumsi. Berhenti dan lapor jika tes gagal setelah 2 kali perbaikan atau butuh keputusan.


PBI-07 Pengajuan penjemputan (Nasabah)
- Tabel pickup_requests: customer_id, requested_date (date, tidak boleh lampau), address (text), latitude decimal(10,7), longitude decimal(10,7), notes (nullable, perkiraan jenis/jumlah sampah), status string default 'menunggu', rejection_reason (nullable), scheduled_date (nullable), officer_id (nullable, FK users), scheduled_by (nullable, FK users), scheduled_at (nullable), completed_at (nullable), timestamps.
- Nilai status: menunggu, ditolak, terjadwal, selesai (gunakan konstanta/enum). Kode tampilan JMP-0001 dari id (accessor, seperti NSB/STR/TRK).
- Nasabah: form ajukan (alamat dan koordinat terisi dari profil jika ada; pilih titik di peta Leaflet dengan klik; koordinat wajib dan divalidasi rentang Indonesia, lat -11..6, lng 95..141), daftar penjemputan miliknya, detail + status. Akses milik nasabah lain = 403.
- Tes: validasi, nasabah A tidak bisa melihat milik B, status awal selalu 'menunggu' (input status dari browser diabaikan).

PBI-08 Kelola penjemputan (Admin)
- Admin: daftar + filter status/tanggal, detail dengan peta. Aksi: Tolak (alasan wajib; hanya dari 'menunggu'), Jadwalkan (tanggal >= hari ini + pilih petugas AKTIF lewat User::scopeActiveOfficers(); hanya dari 'menunggu'), Ubah jadwal (tanggal/petugas, hanya saat 'terjadwal'). Catat scheduled_by dan scheduled_at.
- Alur status ketat: menunggu -> ditolak | terjadwal -> selesai. Transisi lain ditolak dengan pesan jelas. Admin tidak bisa menyelesaikan penjemputan.
- Tes: tiap transisi sah dan tidak sah, petugas nonaktif tidak bisa dipilih, petugas/nasabah mendapat 403.

PBI-09 Hasil penjemputan (Petugas) -> setoran
- Migration baru: deposits.pickup_request_id (nullable, FK, index). Jangan ubah migration lama.
- Petugas: daftar tugas MILIKNYA yang berstatus 'terjadwal' (urut tanggal), detail dengan peta + tautan ke Google Maps/OSM. Tugas milik petugas lain = 403.
- Form hasil: 1 sampai 10 baris (jenis sampah aktif + berat > 0), tombol "Tambah baris" dengan JavaScript sederhana, jenis sampah tidak boleh dobel dalam satu penjemputan. Satu baris = satu Deposit (aturan satu setoran = satu jenis tetap berlaku), semua baris terhubung ke pickup_request_id yang sama.
- WAJIB memakai DepositService yang sudah ada (tambahkan parameter opsional pickup_request_id). Jangan menduplikasi logika saldo/snapshot harga.
- Satu DB::transaction untuk seluruh proses: kunci baris penjemputan (lockForUpdate), cek status 'terjadwal' dan officer_id = petugas yang login, buat semua Deposit, ubah status 'selesai', isi completed_at. Jika satu baris gagal, SEMUA dibatalkan (tidak ada setoran, saldo tidak berubah, status tetap 'terjadwal'). Tidak bisa diselesaikan dua kali.
- Riwayat setoran dan bukti setoran menampilkan "Dari penjemputan JMP-xxxx"; detail penjemputan (nasabah/admin) menampilkan daftar bukti setoran yang dihasilkan.
- Tes: saldo bertambah sesuai total semua baris, rollback total jika satu baris tidak valid (mis. jenis nonaktif), penyelesaian dobel ditolak, petugas lain 403, setiap Deposit punya pickup_request_id, nasabah melihat setoran hasil penjemputan di riwayatnya.

PBI-10 Dashboard per peran + rekap
- Admin: jumlah nasabah aktif, total saldo seluruh nasabah, setoran bulan ini (jumlah transaksi, total berat, total nilai), penarikan bulan ini, jumlah penjemputan per status, penjemputan terjadwal hari ini.
- Petugas: tugas penjemputan hari ini, jumlah setoran dan penarikan yang ia catat hari ini.
- Nasabah: tambahkan status penjemputan aktif terakhir ke dashboard yang sudah ada.
- Laporan (Admin saja), filter bulan/tahun: (1) rekap setoran per jenis sampah (jumlah transaksi, total berat, total nilai, baris total), (2) rekap penarikan (jumlah, total nominal), (3) rekap penjemputan per status. Tabel sederhana.
- Tes: angka rekap sesuai data seeder/hitungan manual, akses per peran.

SEEDER (tambahkan ke DatabaseSeeder, idempoten)
12 permintaan penjemputan dengan variasi status dan koordinat nyata di sekitar Yogyakarta; minimal 8 berstatus 'terjadwal' pada tanggal yang sama (hari ini) dan ditugaskan ke 2 petugas, untuk uji optimasi rute nanti.


PBI-11 Optimasi rute penjemputan (Admin menghitung, Petugas melihat)
- Class murni PHP `RouteOptimizer` (tanpa akses database, mudah dites): haversine(lat1,lng1,lat2,lng2) dalam km (R = 6371.0088), matriks jarak, nearestNeighbor() dan twoOpt() pada rute TERTUTUP: berangkat dari bank sampah (titik 0, tetap), mengunjungi semua titik, kembali ke bank sampah. twoOpt mengulang sampai tidak ada perbaikan dengan batas iterasi pengaman.
- Titik bank sampah: config/setorku.php membaca .env (BANK_SAMPAH_NAME, BANK_SAMPAH_LAT, BANK_SAMPAH_LNG), tambahkan juga ke .env.example. Default pusat Kota Yogyakarta. Tambahkan ke .env tanpa menimpa isi lama.
- Migration baru: pickup_requests.route_sequence (nullable unsigned int). Jangan ubah migration lama.
- Halaman Admin "Rute Penjemputan": pilih tanggal + petugas (penjemputan berstatus 'terjadwal' pada tanggal itu untuk petugas itu) -> tombol "Hitung rute". Tampilkan: tabel urutan (nomor, kode JMP, nasabah, alamat, jarak dari titik sebelumnya km, jarak kumulatif km); tiga total jarak: urutan awal (berdasarkan waktu permintaan masuk, created_at), hasil Nearest Neighbor, hasil Nearest Neighbor + 2-Opt; persentase penghematan terhadap urutan awal; peta Leaflet dengan marker bernomor, marker bank sampah, dan garis rute. Catatan di halaman: jarak garis lurus (Haversine), bukan jarak jalan, dan 2-Opt tidak menjamin optimum global.
- Tombol "Simpan urutan rute" menyimpan route_sequence untuk penjemputan tersebut. Admin bisa menghitung ulang kapan saja.
- Validasi kualitas koordinat sebelum hitung: titik yang kosong, bukan angka, di luar rentang Indonesia (lat -11..6, lng 95..141), atau tepat 0,0 DIKECUALIKAN dari perhitungan dan ditampilkan di daftar "dikecualikan" beserta alasannya. Jika titik valid kurang dari 2, tampilkan pesan bahwa optimasi tidak diperlukan.
- Petugas: daftar "Tugas Jemput" hari ini diurutkan menurut route_sequence (yang null di akhir), dengan tampilan peta rute miliknya (read-only). Petugas tidak bisa melihat rute petugas lain (403).
- Tes unit RouteOptimizer dengan kasus yang bisa dihitung manual: jarak (0,0)-(0,1) mendekati 111,19 km; titik segaris dengan hasil yang dapat diprediksi; empat titik di sudut persegi dengan urutan awal menyilang dan twoOpt menghilangkan persilangan; hasil twoOpt tidak pernah lebih panjang dari nearestNeighbor; semua titik terkunjungi tepat sekali. Tes fitur: akses per peran, koordinat tidak valid dikecualikan, route_sequence tersimpan, petugas lain 403.
- Dokumentasi: docs/OPTIMASI_RUTE.md berisi penjelasan Haversine, Nearest Neighbor, 2-Opt (pseudo-code singkat dan kompleksitas), contoh hasil nyata dari data seeder (jarak awal vs akhir, persen penghematan), dan keterbatasan.

PBI-12 Kartu QR nasabah
- QR hanya berisi kode nasabah (mis. NSB-0001), tidak ada saldo atau data sensitif. Pembuatan QR di sisi browser memakai library QR via CDN (cdnjs). Tidak instal package PHP.
- Nasabah: halaman "Kartu Saya" siap cetak (CSS print) berisi nama, kode, QR. Admin dan Petugas: tombol "Cetak kartu" di detail nasabah.
- Pencarian cepat: di form Catat Setoran dan Catat Penarikan (Petugas), tambahkan kolom isian kode yang otomatis fokus (cocok untuk alat pemindai QR/barcode yang bekerja seperti keyboard) dan tombol "Scan dengan kamera" memakai library pemindai QR via CDN. Hasil scan mengisi kolom pencarian nasabah yang SUDAH ADA (pencarian sudah menerima format NSB-0001) lalu mengirimkannya. Kamera hanya bekerja di HTTPS atau localhost; jika tidak tersedia, tampilkan pesan jelas dan input manual tetap bisa dipakai.
- Hanya nasabah aktif yang bisa ditemukan untuk transaksi. Nasabah tidak bisa membuka kartu nasabah lain (403).
- Tes: akses per peran, kode tidak valid ditampilkan sebagai pesan "Nasabah tidak ditemukan", nasabah nonaktif tidak bisa dipilih untuk setoran/penarikan, isi QR hanya kode nasabah.

PBI-13 Dashboard dampak lingkungan (Admin) dan kontribusi nasabah
- Admin: halaman "Dampak" berisi total sampah terkelola (kg) seluruh waktu, total nilai setoran, jumlah nasabah aktif, grafik batang berat per bulan 12 bulan terakhir (Chart.js via CDN), dan tabel per jenis sampah (total kg, persentase). Data dari tabel deposits.
- Nasabah: kartu "Kontribusi saya" di dashboard: total kg yang pernah disetor dan jumlah setoran.
- JANGAN menambahkan estimasi emisi/CO2 atau angka konversi lain yang tidak bersumber dari dokumen proyek. Tampilkan hanya data yang benar-benar tersimpan.
- Tes: angka sesuai data seeder/hitungan manual, nasabah hanya melihat datanya sendiri, akses per peran.

PENUTUP
- README.md di root: ringkasan SetorKu, kebutuhan (PHP, Composer, MySQL, Laragon), langkah instalasi (composer install, salin .env, php artisan key:generate, buat database, php artisan migrate --seed), cara menjalankan tes, daftar akun uji (admin, petugas, petugas2, nasabah) dan kata sandinya, serta variabel .env khusus (koordinat bank sampah).
- docs/PANDUAN_PENGGUNA.md: langkah penggunaan per peran (Admin, Petugas, Nasabah) berbahasa Indonesia, singkat dan berurutan.
- Pastikan php artisan migrate:fresh --seed di database tes/dev lokal tetap berjalan tanpa error (jalankan hanya pada database SQLite sementara atau :memory:, JANGAN pada database MySQL bank_sampah).


PBI-14 Ekspor laporan PDF/Excel
- Dicadangkan (belum dikerjakan).

PBI-15 Login & Pendaftaran Mandiri dengan Akun Google (Gmail Asli)
- Paket resmi `laravel/socialite` (stateful OAuth bawaan, tanpa `stateless()`, tanpa menyimpan `access_token` atau `refresh_token`).
- Migration baru: `users.google_id` (`nullable`, `unique`) dan `users.password` menjadi `nullable` (tanpa mengubah migration lama dan tanpa `migrate:fresh`).
- Login Google untuk ketiga peran (`admin`, `petugas`, `nasabah`) berdasarkan pencocokan email ternormalisasi (`trim` + huruf kecil), wajib `email_verified === true`, menolak `google_id` berbeda bila sudah tertaut, dan menolak akun nonaktif (`Akun dinonaktifkan. Hubungi admin.`).
- Email Google yang belum terdaftar disimpan sementara di session server (kedaluwarsa 15 menit) dan diarahkan ke `/daftar/lengkapi` untuk membuat akun `User` ber-role `nasabah` + `Customer` bersaldo awal `0` dalam satu `DB::transaction` (`CustomerService`). Field sensitif (`role`, `email`, `google_id`, `is_active`, `balance`) tidak pernah dibaca dari input form.
- Kelola Petugas (PBI-06b): password awal menjadi opsional dan label email diperjelas menjadi `Email akun Google petugas`.
- Seeder: email Admin membaca `SEED_ADMIN_EMAIL` (default `admin@banksampah.test`). Keputusan pemilik proyek: akun `admin@banksampah.test` tetap dipertahankan sebagai akun Admin cadangan (email+password), sedangkan Gmail asli pemilik ditambahkan sebagai akun Admin kedua melalui `SEED_ADMIN_EMAIL` saat menjalankan `php artisan db:seed`.
- Lingkungan uji lokal: pengujian lokal resmi menggunakan `php artisan serve --host=localhost --port=8000` (`http://localhost:8000`, selaras dengan `GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback`, bukan virtual host `.test`).
- Dokumentasi setup lokal Google Cloud Console tersedia di `docs/SETUP_GOOGLE_LOGIN.md`.

PBI-16 Pilih titik lokasi rumah lewat peta saat pendaftaran nasabah (Leaflet + OSM)
- Cakupan: HANYA halaman Lengkapi Pendaftaran (`/daftar/lengkapi`). Komponen Blade reusable `resources/views/components/map-picker.blade.php` + JavaScript terpisah `public/js/map-picker.js`; belum dipasang di Profil Saya, form admin, atau halaman lain.
- Leaflet 1.9.4 via CDN unpkg (sama seperti halaman peta lain di proyek, dengan integrity resmi), tile OpenStreetMap dengan atribusi "OpenStreetMap contributors". Tanpa API key, tanpa geocoding/Nominatim, tanpa library tambahan.
- Peta lebar penuh tinggi 320px di antara Alamat Lengkap dan Latitude/Longitude; pusat awal Yogyakarta (-7.7970680, 110.3705290) zoom 13; bila ada `old()` koordinat, pusat di titik itu zoom 17 dengan pin. Pin draggable, klik peta memindahkan pin, tombol "Gunakan Lokasi Saya" (geolocation hanya atas klik, pesan ramah tanpa `alert()`), tombol "Hapus Titik". Input latitude/longitude readonly diisi `toFixed(7)`.
- Server: aturan `CompleteGoogleRegistrationRequest` tetap (nullable, numeric, between -90..90 / -180..180, required_with berpasangan). Nama field, migration, alur OAuth, session, dan aturan peran tidak berubah.
- `phpunit.xml` memaksa `SEED_ADMIN_EMAIL=admin@banksampah.test` agar tes seeder tidak bergantung pada `.env` lokal pengembang.
- Catatan pengujian: render HTML komponen, atribut konfigurasi peta, dan validasi server diuji otomatis lewat `RegistrationMapPickerTest`; interaksi browser (klik peta, geser pin, geolokasi, dan layar sentuh HP) diuji manual di browser.
