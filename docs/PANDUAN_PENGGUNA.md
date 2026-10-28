# Panduan Pengguna SetorKu

Panduan singkat penggunaan aplikasi **SetorKu** secara berurutan berdasarkan peran pengguna.

---

## 1. Peran Admin

1. **Masuk ke Sistem**:
   - Buka halaman `/login`, lalu masukkan email Admin (mis. `admin@banksampah.test`) dan kata sandi, **atau** klik tombol **Masuk dengan Google** menggunakan akun Gmail Admin yang telah diatur pada `SEED_ADMIN_EMAIL`.
2. **Pantau Ringkasan Operasional**: Pada **Dashboard**, periksa jumlah nasabah aktif, total saldo tabungan, ringkasan setoran & penarikan bulan berjalan, serta jadwal penjemputan hari ini.
3. **Kelola Data Nasabah**:
   - Buka menu **Data Nasabah** untuk mencari, menambah nasabah baru (otomatis membuat akun login nasabah), atau mengubah profil nasabah.
   - Buka detail nasabah untuk menekan **Cetak kartu** (mencetak kartu QR nasabah) atau **Nonaktifkan / Aktifkan** akun nasabah.
4. **Kelola Petugas**: Buka menu **Kelola Petugas** untuk menambah akun petugas lapangan dengan mengisi **Email akun Google petugas** (kata sandi awal bersifat opsional jika petugas masuk memakai Google), memperbarui kontak, me-reset kata sandi, atau menonaktifkan akun petugas yang sudah tidak bertugas.
5. **Kelola Jenis & Harga Sampah**: Buka menu **Jenis & Harga Sampah** untuk menambah atau memperbarui harga beli per kilogram. Perubahan harga hanya berlaku bagi transaksi baru dan tidak mengubah riwayat transaksi lama.
6. **Kelola & Jadwalkan Penjemputan**:
   - Buka menu **Penjemputan Sampah**, saring status **Menunggu**, lalu klik **Detail**.
   - Periksa titik lokasi pada peta; pilih tanggal jemput beserta petugas aktif lalu klik **Jadwalkan**, atau isi alasan penolakan lalu klik **Tolak**.
   - Bila diperlukan perubahan sebelum selesai, gunakan form **Ubah Jadwal / Petugas** pada penjemputan berstatus **Terjadwal**.
7. **Hitung & Simpan Rute Penjemputan**:
   - Buka menu **Rute Penjemputan**, pilih **Tanggal Jadwal Jemput** dan **Petugas Aktif**, lalu klik **Hitung rute**.
   - Tinjau perbandingan jarak (Urutan Awal, Nearest Neighbor, Nearest Neighbor + 2-Opt), peta rute bernomor, serta tabel urutan kunjungan.
   - Klik **Simpan urutan rute** agar urutan kunjungan tersimpan ke daftar tugas petugas.
8. **Lihat Riwayat, Laporan Bulanan & Dampak**:
   - Menu **Riwayat Setoran** dan **Riwayat Penarikan** menampilkan seluruh transaksi dan bukti resminya.
   - Menu **Laporan Bulanan** menyajikan rekap setoran per jenis sampah, penarikan, dan status penjemputan berdasarkan filter bulan & tahun.
   - Menu **Dampak** menampilkan total berat sampah terkelola seluruh waktu, grafik batang 12 bulan terakhir, serta persentase komposisi per jenis sampah.

---

## 2. Peran Petugas

1. **Masuk ke Sistem**: Login melalui `/login` menggunakan email & kata sandi Petugas, **atau** klik **Masuk dengan Google** memakai alamat Gmail yang telah didaftarkan Admin pada menu **Kelola Petugas**.
2. **Lihat Tugas Penjemputan & Peta Rute**:
   - Buka **Dashboard** atau menu **Tugas Penjemputan** untuk melihat daftar tugas berstatus **Terjadwal** yang sudah diurutkan mengikuti urutan rute (`#1`, `#2`, dst.) beserta peta rute *read-only*.
   - Klik **Detail & Catat Hasil** pada salah satu titik untuk melihat peta detail atau membuka navigasi di **Google Maps / OpenStreetMap**.
3. **Catat Hasil Penjemputan Menjadi Setoran**:
   - Pada halaman detail tugas jemput, pilih jenis sampah aktif dan masukkan berat timbangan (klik **Tambah baris** bila terdapat beberapa jenis sampah berbeda).
   - Klik **Simpan Hasil & Buat Setoran**. Sistem otomatis menandai penjemputan **Selesai**, menerbitkan bukti setoran `STR-xxxx` untuk setiap baris, dan menambah saldo nasabah.
4. **Daftarkan Nasabah Baru & Cetak Kartu QR**:
   - Buka menu **Data Nasabah** -> **Tambah Nasabah** untuk mendaftarkan warga baru di lapangan.
   - Buka detail nasabah lalu klik **Cetak kartu** untuk mencetak kartu QR anggota.
5. **Catat Setoran Langsung di Bank Sampah**:
   - Buka menu **Setoran Sampah** -> **Catat Setoran**.
   - Arahkan alat *scanner* ke kolom **Input Cepat Kode** (otomatis fokus), klik **Scan dengan kamera** (pada koneksi HTTPS / `localhost`), atau cari nama/kode nasabah secara manual.
   - Pilih nasabah aktif, pilih satu jenis sampah aktif, isi berat (`kg`), lalu klik **Simpan Setoran** untuk menampilkan bukti setoran.
6. **Catat Penarikan Saldo**:
   - Buka menu **Penarikan Saldo** -> **Catat Penarikan**.
   - Pindai kartu QR atau cari nasabah aktif, masukkan nominal penarikan (tidak melebihi saldo nasabah), lalu klik **Simpan Penarikan** untuk menampilkan bukti penarikan.

---

## 3. Peran Nasabah

1. **Daftar Mandiri atau Masuk ke Portal Nasabah**:
   - **Sudah punya akun**: Buka `/login`, masuk menggunakan email & kata sandi nasabah **atau** klik **Masuk dengan Google** memakai akun Gmail Anda.
   - **Belum punya akun (Pendaftaran Mandiri)**: Pada halaman `/login`, klik **Daftar dengan Google** (atau **Masuk dengan Google**). Setelah memilih akun Gmail terverifikasi, lengkapi **Nama Lengkap**, **No. HP**, dan **Alamat Lengkap** pada halaman **Lengkapi Pendaftaran**. Bila ingin, tentukan **Titik Lokasi Rumah (opsional)**: klik pada peta atau geser pin ke lokasi rumah, atau tekan **Gunakan Lokasi Saya** (browser akan meminta izin lokasi); tekan **Hapus Titik** untuk mengosongkannya. Kolom Latitude/Longitude terisi otomatis dari peta. Klik **Selesaikan Pendaftaran**; akun **Nasabah** bersaldo awal **Rp 0** otomatis aktif dan langsung masuk ke Portal Nasabah.
2. **Pantau Saldo & Kontribusi**: Pada **Dashboard**, lihat saldo tabungan terkini, kartu **Kontribusi saya** (total berat kilogram sampah yang pernah disetor & jumlah setoran), status penjemputan aktif terakhir, serta 5 transaksi terbaru.
3. **Lihat & Cetak Kartu QR Saya**: Buka menu **Kartu Saya**, lalu klik **Cetak Kartu** untuk mencetak atau menyimpan kartu anggota ber-QR (hanya berisi kode `NSB-xxxx` tanpa informasi saldo).
4. **Ajukan Penjemputan Sampah**:
   - Buka menu **Penjemputan Sampah** -> **Ajukan Penjemputan**.
   - Pilih tanggal penjemputan (hari ini atau mendatang), periksa alamat, klik titik jemput pada peta interaktif untuk menyesuaikan koordinat, isi perkiraan sampah pada catatan, lalu klik **Kirim Pengajuan**.
   - Pantau perubahan status (`Menunggu`, `Terjadwal`, `Ditolak`, atau `Selesai` beserta daftar bukti setorannya) pada detail pengajuan.
5. **Periksa Riwayat Transaksi**: Buka menu **Riwayat Transaksi** untuk menyaring riwayat gabungan setoran dan penarikan serta membuka halaman bukti tiap transaksi.
6. **Perbarui Profil Mandiri**: Buka menu **Profil Saya** untuk memperbarui nama, nomor HP, dan alamat. Untuk mengatur atau mengubah **Titik Lokasi Rumah (opsional)**, klik pada peta atau geser pin ke lokasi rumah, atau tekan **Gunakan Lokasi Saya** (browser akan meminta izin lokasi); untuk mengosongkan koordinat yang tersimpan, tekan **Hapus Titik** lalu klik **Simpan Profil**.
