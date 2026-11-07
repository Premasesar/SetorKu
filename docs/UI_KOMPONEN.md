# Dokumentasi Fondasi Tema & Komponen UI SetorKu (`PBI-UI-00`)

Dokumen ini menjelaskan pemakaian tema **Ecological Modernism** (`public/css/setorku-theme.css`), tiga layout baru (`ui-staff`, `ui-nasabah`, `ui-guest`), enam komponen Blade yang dapat dipakai ulang (`resources/views/components/`), paginasi Bahasa Indonesia, dan helper format uang/tanggal.

---

## 1. File Tema (`public/css/setorku-theme.css`)

File `public/css/setorku-theme.css` dimuat **setelah** Bootstrap 5.3.3 CDN pada tiga layout baru (`ui-staff`, `ui-nasabah`, `ui-guest`) dengan kelas `<body class="sk-theme">`. Layout lama (`layouts/app.blade.php`) tidak diubah dan tetap dapat dipakai berdampingan.

### Variabel CSS Utama (`:root`)
- **Warna merek**:
  - `--sk-forest`: `#0f2e22` (hijau hutan — sidebar, sub-hero, panel kiri guest)
  - `--sk-primary`: `#006b23` (hijau utama / `--bs-primary`)
  - `--sk-brand`: `#1c8634` (hijau merek / `primary-container` / `--bs-success`)
  - `--sk-mint`: `#c5e8d5` (`secondary-container`)
- **Permukaan & Teks**:
  - `--sk-bg`: `#f8faf8`
  - `--sk-surface-low`: `#f2f4f2` (header tabel, input readonly)
  - `--sk-surface`: `#eceeec`
  - `--sk-surface-highest`: `#e1e3e1`
  - `--sk-text`: `#191c1b`
  - `--sk-text-muted`: `#3f4a3d`
- **Status / Error**:
  - `--sk-error`: `#ba1a1a` dengan latar `--sk-error-bg`: `#ffdad6`
- **Tipografi**:
  - `--sk-font`: `"Plus Jakarta Sans", system-ui, -apple-system, sans-serif`
  - Kelas skala: `.sk-display`, `.sk-headline-xl`, `.sk-headline-lg`, `.sk-headline-md`, `.sk-body-lg`, `.sk-body-md`, `.sk-body-sm`, `.sk-label-lg`, `.sk-label-md`, `.sk-label-sm` (11px kapital).

---

## 2. Tiga Layout Baru (`resources/views/layouts/`)

Seluruh layout menyediakan `@yield('title')`, `@stack('styles')`, `@yield('hero')`, `@yield('content')`, `@stack('scripts')`, `<meta name="csrf-token">`, dan flash message `session('success')` / `session('error')`.

| Layout | Peruntukan | Ciri Utama |
| :--- | :--- | :--- |
| `layouts.ui-staff` | Admin & Petugas | Sidebar kiri `264px` (`#0f2e22`) dengan 10 menu Admin / 6 menu Petugas, indikator menu aktif, profil & tombol **Keluar** (`POST /logout`), offcanvas mobile dengan tombol hamburger, area sub-hero (`@section('hero')`), konten, dan footer. |
| `layouts.ui-nasabah` | Nasabah | Header atas `80px` dengan 5 menu Portal Nasabah (`Dashboard`, `Penjemputan Sampah`, `Riwayat Transaksi`, `Kartu Saya`, `Profil Saya`), chip identitas nasabah (`Nama` + `NSB-xxxx`), tombol **Keluar**, offcanvas mobile, area sub-hero (`@section('hero')`), konten, dan footer. |
| `layouts.ui-guest` | Publik / Tamu (`login`, `google-complete`) | Layout dua panel: panel kiri hijau hutan (`#0f2e22`) berisi branding & keunggulan sistem, panel kanan berisi kartu form autentikasi (`@yield('card_class')` untuk lebar tambahan seperti `sk-guest__card--wide`). |

---

## 3. Komponen Blade (`resources/views/components/`)

### 3.1 `<x-page-hero>`
Sub-hero hijau hutan (`#0f2e22`) di bagian atas halaman untuk menampilkan breadcrumb, judul, subjudul, chip meta, dan tombol aksi.

**Props:**
- `title` (`string`): Judul utama halaman.
- `subtitle` (`string|null`): Subjudul pendamping (opsional, atau lewat default `$slot`).
- `icon` (`string|null`): Nama ikon Bootstrap Icons (mis. `bi-speedometer2`).
- `breadcrumbs` (`array`): Daftar breadcrumb, tiap elemen berupa `'Teks'` atau `['label' => '...', 'url' => '...']`.
- `badge` (`string|null`): Teks chip di kanan atas (opsional).
- Slot bernama `actions`: Tombol-tombol aksi di sisi kanan sub-hero.

**Contoh Pemakaian:**
```blade
@section('hero')
    <x-page-hero
        title="Dashboard Admin"
        icon="bi-speedometer2"
        :breadcrumbs="[['label' => 'Portal Admin'], ['label' => 'Dashboard']]"
        badge="Admin"
    >
        Selamat datang, {{ auth()->user()->name }}!
        <x-slot:actions>
            <a href="{{ route('admin.laporan.index') }}" class="btn btn-outline-success">
                <i class="bi bi-journal-text"></i> Buka Laporan Bulanan
            </a>
        </x-slot:actions>
    </x-page-hero>
@endsection
```

---

### 3.2 `<x-stat-card>`
Kartu ringkasan statistik/KPI dengan label kapital `11px`, angka utama, teks keterangan bawah (`hint`), dan ikon.

**Props:**
- `label` (`string`): Judul metrik.
- `value` (`string|null`): Nilai utama metrik (atau isi lewat `$slot`).
- `hint` (`string|null`): Keterangan tambahan di bawah nilai.
- `icon` (`string|null`): Ikon Bootstrap Icons (mis. `bi-people`, `bi-wallet2`).
- `variant` (`string`): `'default' | 'success' | 'primary' | 'danger' | 'warning'`.
- `accent` (`bool`): Bila `true`, memberi aksen garis tepi mint (`#c5e8d5`).
- `compact` (`bool`): Bila `true`, memakai ukuran angka sedikit lebih ringkas (`1.25rem`).

**Contoh Pemakaian:**
```blade
<x-stat-card
    label="Nasabah Aktif"
    :value="number_format($stats['active_customers_count'], 0, ',', '.')"
    icon="bi-people"
    variant="success"
    :accent="true"
/>
```

---

### 3.3 `<x-status-pill>`
Lencana status berbentuk pil (`border-radius: 9999px`) yang memetakan status penjemputan, status akun/master, dan jenis transaksi secara otomatis.

**Props:**
- `status` (`string|bool|int|\BackedEnum`): Nilai status (`menunggu`, `terjadwal`, `selesai`, `ditolak`, `aktif`, `nonaktif`, `setoran`, `penarikan`, atau `PickupStatus`).
- `label` (`string|null`): Teks kustom (bila dikosongkan, otomatis dipetakan ke `Menunggu`, `Terjadwal`, `Selesai`, `Ditolak`, `Aktif`, `Nonaktif`, `Setoran`, `Penarikan`).

**Contoh Pemakaian:**
```blade
<x-status-pill :status="$pickup->status" :label="$pickup->statusLabel()" />
<x-status-pill :status="$customer->is_active ? 'aktif' : 'nonaktif'" />
<x-status-pill status="setoran" />
```

---

### 3.4 `<x-table-toolbar>`
Bilah alat pencarian dan filter di atas tabel lengkap dengan tombol submit (`Cari` / `Filter`) dan tombol `Reset`.

**Props:**
- `action` (`string`): URL tujuan form.
- `method` (`string`, default `'GET'`): Metode HTTP.
- `searchName` (`string|false`, default `'q'`): Atribut `name` untuk kolom pencarian (`false` jika tanpa kolom teks).
- `searchValue` (`string|null`): Nilai pencarian saat ini (default `request($searchName)`).
- `placeholder` (`string`, default `'Cari data...'`): Placeholder input cari.
- `submitLabel` (`string`, default `'Cari'`): Teks tombol submit.
- `resetUrl` (`string|null`): URL tombol Reset.
- `resetLabel` (`string`, default `'Reset'`): Teks tombol Reset.
- Default `$slot`: Elemen filter tambahan (dropdown `status`, input `date`, dll.).

**Contoh Pemakaian:**
```blade
<x-table-toolbar
    :action="route('admin.nasabah.index')"
    placeholder="Cari kode atau nama nasabah..."
    :reset-url="route('admin.nasabah.index')"
/>
```

---

### 3.5 `<x-empty-state>`
Tampilan pesan kosong ketika tabel atau daftar tidak memiliki data.

**Props:**
- `icon` (`string`, default `'bi-inbox'`): Ikon Bootstrap Icons.
- `title` (`string|null`): Judul opsional.
- `message` (`string`, default `'Belum ada data.'`): Pesan kosong utama.
- `compact` (`bool`, default `false`): Padding lebih ringkas untuk di dalam kartu kecil.
- Default `$slot`: Tombol aksi opsional di bawah pesan.

**Contoh Pemakaian:**
```blade
<x-empty-state
    icon="bi-inbox"
    message="Belum ada transaksi tercatat."
/>
```

---

### 3.6 `<x-form-field>`
Pembungkus field form yang merender `<label>`, `<input>` / `<textarea>` (atau kontrol kustom melalui `$slot`), ikon awalan (`input-group`), pesan error validasi (`invalid-feedback`), serta teks bantuan (`form-text`).

**Props:**
- `name` (`string`): Nama atribut `name` input.
- `id` (`string|null`): Atribut `id` (default sama dengan `name`).
- `label` (`string|null`): Teks label.
- `type` (`string`, default `'text'`): Jenis input (`text`, `email`, `number`, `date`, `textarea`).
- `value` (`mixed`): Nilai awal (otomatis dibungkus `old($name, $value)`).
- `placeholder` (`string|null`): Placeholder input.
- `required`, `readonly`, `disabled` (`bool`).
- `prefixIcon` (`string|null`): Ikon Bootstrap Icons di sisi kiri input (mis. `bi-envelope`).
- `helpText` (`string|null`): Teks bantuan di bawah input.
- `errorKey` (`string|null`): Kunci error validasi jika berbeda dari `name`.

**Contoh Pemakaian:**
```blade
<x-form-field
    name="email"
    id="email"
    type="email"
    label="Email"
    placeholder="contoh@banksampah.test"
    prefix-icon="bi-envelope"
    :required="true"
    autofocus
/>
```

---

## 4. Paginasi Bahasa Indonesia & Helper Format

- **Paginasi Default**: `Paginator::defaultView('vendor.pagination.setorku')` pada `AppServiceProvider` menampilkan informasi `"Menampilkan 1–10 dari 12 data"` (atau label khusus jika diberikan ke `$paginator->links('vendor.pagination.setorku', ['itemLabel' => 'nasabah'])`) dan tombol navigasi `‹ 1 2 ›` tanpa teks bahasa Inggris `"Showing ... results"`.
- **Helper Format (`app/Support/helpers.php`)**:
  - `format_rupiah($amount, $decimals = 2)` → `"Rp 9.750,00"`
  - `format_tanggal($date, $format = 'd-m-Y')` → `"18-09-2026"`
  - `format_waktu($date)` → `"18-09-2026 14:30"`
