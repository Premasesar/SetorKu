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
