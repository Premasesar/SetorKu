A. PRINSIP
1. Aplikasi yang sekarang berjalan adalah SUMBER KEBENARAN fitur. Desain Stitch hanya acuan TAMPILAN (warna, tipografi, spacing, susunan, komponen).
2. Elemen yang ada di aplikasi tetapi tidak ada di desain: TETAP DIPERTAHANKAN dan digayakan dengan komponen tema.
3. Elemen yang ada di desain tetapi tidak ada di aplikasi: JANGAN dibuat. Contoh: klaim emisi CO₂, Eco-Points, notifikasi WhatsApp otomatis, tombol Unduh/Ekspor PDF, tombol Tarik Saldo untuk Nasabah, Saldo pada kartu cetak, klaim keamanan (SHA-256, terenkripsi, terverifikasi), OTP, Bluetooth, tautan SOP/Hotline, Cetak Surat Jalan, live tracking. Pengecualian hanya untuk fitur tambahan yang sudah dicatat sebagai disetujui di docs/PLAN_SETORKU.md.
4. Contoh angka dan data di desain (saldo, jumlah nasabah, nama) hanya contoh visual. Jangan dijadikan data atau di-hardcode.
5. Route tetap. Halaman tetap halaman. Jangan mengubah halaman menjadi modal atau drawer atau sebaliknya. Jika desain menampilkan drawer atau modal (mis. detail penjemputan Admin), gunakan isinya pada halaman atau modal yang sudah ada di aplikasi.

B. ANTI-HILANG FITUR
1. Sebelum mengubah sebuah view, catat inventarisnya: semua name="..." input/select/textarea, id elemen yang dipakai JavaScript atau tes, tombol dan tautan (route(...), href), kolom tabel, filter dan pencarian, urutan, paginasi, kondisi @can/@if/@auth/@role, @push/@stack/@section, script dan CDN (Leaflet, Chart.js, html5-qrcode, qrcodejs), pesan flash dan validasi, modal dan konfirmasi.
2. Setelah selesai, bandingkan dengan inventaris. Tidak boleh ada yang hilang, berubah nama, atau berpindah halaman. Jika perubahan tidak terhindarkan, BERHENTI dan laporkan sebelum melanjutkan.
3. Dilarang menghapus file view, partial, komponen, route, tes, atau kolom/tabel database. layouts.app dan view lama tidak dihapus.
4. Label tombol, kolom, dan field TIDAK diganti. Teks pendamping (subjudul, pesan kosong, pesan sukses) boleh dibuat lebih ramah.
5. Tiap view mendapat tes smoke: dirender (HTTP 200) untuk peran yang benar, ditolak atau dialihkan untuk peran lain, dan memuat elemen kunci (tombol aksi utama).
6. Jangan mengubah route, controller, model, validasi, seeder, migration, atau logika bisnis.

C. TEKNIS
1. Tetap Bootstrap. DILARANG menambah Tailwind atau framework CSS/JS baru, dan jangan menambah package.
2. Stitch menghasilkan Tailwind. TERJEMAHKAN ke Bootstrap + public/css/setorku-theme.css. Jangan menyalin class Tailwind.
3. Pakai komponen dan layout dari PBI-UI-00. Jika kurang, TAMBAH komponen baru (jangan menghapus atau mengubah arti komponen lama) dan dokumentasikan di docs/UI_KOMPONEN.md.
4. Ikon: Bootstrap Icons. Tanggal d-m-Y, uang Rp 9.750,00 lewat helper. Paginasi memakai view Bahasa Indonesia.
5. JavaScript yang sudah ada (Leaflet, pin peta, kamera QR, form dinamis, Chart.js, hitung otomatis) dipertahankan beserta id dan name-nya.
6. Tambahan CSS masuk ke setorku-theme.css. Hindari style inline.
7. Aksesibilitas dasar: label pada input, kontras teks cukup, tombol ikon punya aria-label.

D. LAYOUT DAN RESPONSIF
1. Admin dan Petugas memakai layouts.ui-staff. Nasabah memakai layouts.ui-nasabah. Login dan Lengkapi Pendaftaran memakai layouts.ui-guest. View yang dipakai bersama Admin dan Petugas tetap satu view, elemen khusus peran memakai kondisi yang sudah ada.
2. Sub-hero memakai komponen page-hero dengan breadcrumb "PORTAL ADMIN / PORTAL PETUGAS / PORTAL NASABAH / NAMA HALAMAN".
3. Desktop (lebar 992 px ke atas) mengikuti desain Desktop. Phone (di bawah 768 px) mengikuti desain Phone bila ada. Tablet ditangani breakpoint Bootstrap.
4. Di Phone, tabel lebar menjadi daftar kartu (d-md-none dan d-none d-md-block) TANPA mengurangi kolom atau aksi. Sidebar menjadi offcanvas. Tombol aksi utama lebar penuh. Tinggi peta dikurangi.

E. PROSES
1. Satu branch per batch (feature/pbi-ui-NN-...). Satu commit lokal per view. Setelah semua view dalam batch selesai: jalankan php artisan test penuh, perbarui docs/PROGRES_PENERAPAN_UI.md, lalu merge --no-ff ke main. Jangan git push.
2. Jangan pakai subagen. Kerjakan berurutan.
3. StitchMCP HANYA MEMBACA (get_screen, list_screens). Jangan membuat, mengubah, atau menghapus apa pun di Stitch. Baca layar acuan SATU PER SATU, ambil hanya struktur dan gaya yang dibutuhkan, catat ringkas, jangan menumpuk seluruh HTML di konteks. Pola yang sudah ada di komponen tidak perlu dibaca ulang.
4. Jika kuota habis, berhenti dan laporkan view mana yang sudah selesai. Lanjutan dimulai dari docs/PROGRES_PENERAPAN_UI.md.
5. Status di PROGRES: Belum, Dikerjakan, Selesai-Terverifikasi (inventaris sebelum dan sesudah sama dan tes lulus).

F. LAPORAN SETIAP BATCH
1. Tabel: view | layar acuan | hasil perbandingan inventaris (tidak ada yang hilang, atau daftar selisih) | status.
2. File yang dibuat dan diubah.
3. Jumlah tes lulus sebelum dan sesudah.
4. Jumlah view yang masih memakai layouts.app (hitung dengan grep) dan daftarnya.
5. Langkah uji manual untuk pemilik proyek: URL per peran dan hasil yang seharusnya tampak.
6. Hal yang meragukan atau keputusan yang butuh persetujuan pemilik proyek.
