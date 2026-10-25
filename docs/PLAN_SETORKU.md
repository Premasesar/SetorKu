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
