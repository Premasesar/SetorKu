<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SetorKu') — SetorKu</title>
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- SetorKu Theme (Ecological Modernism) -->
    <link href="{{ asset('css/setorku-theme.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="sk-theme">
    <div class="sk-guest">
        {{-- Panel Kiri: Identitas & Informasi Layanan SetorKu --}}
        <aside class="sk-guest__aside">
            <div class="d-flex align-items-center gap-3">
                <span class="sk-logo" style="width:2.5rem;height:2.5rem;font-size:1.25rem;">
                    <i class="bi bi-recycle"></i>
                </span>
                <div>
                    <div class="fw-bold fs-5 text-white lh-1">SetorKu</div>
                    <small class="sk-sidebar__subtitle">Sistem Informasi Bank Sampah</small>
                </div>
            </div>

            <div class="my-auto py-3">
                <span class="sk-hero-chip mb-3">
                    <i class="bi bi-shield-check"></i> Transparan &bull; Terukur &bull; Akuntabel
                </span>
                <h1 class="sk-guest__headline">
                    @yield('aside_title', 'Kelola Setoran Sampah & Tabungan Nasabah dalam Satu Portal.')
                </h1>
                <p class="sk-guest__lead mb-0">
                    @yield('aside_subtitle', 'Pencatatan setoran terpilah dengan snapshot harga resmi, penarikan saldo terverifikasi, dan penjemputan terjadwal untuk nasabah, petugas, serta pengelola bank sampah.')
                </p>

                <ul class="sk-guest__points">
                    <li>
                        <i class="bi bi-calculator"></i>
                        <div>
                            <strong>Timbangan & Harga Snapshot Akurat</strong>
                            Nilai setoran dihitung otomatis dan tersimpan permanen pada buku saldo nasabah.
                        </div>
                    </li>
                    <li>
                        <i class="bi bi-truck"></i>
                        <div>
                            <strong>Penjemputan Terjadwal & Rute Teroptimasi</strong>
                            Pengajuan penjemputan nasabah dijadwalkan dengan rute petugas yang terukur.
                        </div>
                    </li>
                    <li>
                        <i class="bi bi-qr-code-scan"></i>
                        <div>
                            <strong>Kartu QR & Bukti Transaksi Resmi</strong>
                            Identifikasi cepat nasabah serta riwayat bukti setoran dan penarikan yang rapi.
                        </div>
                    </li>
                </ul>
            </div>

            <p class="sk-guest__copy">
                &copy; {{ date('Y') }} SetorKu — Capstone Project
            </p>
        </aside>

        {{-- Panel Kanan: Area Interaksi Form --}}
        <main class="sk-guest__panel">
            <div class="sk-guest__card @yield('card_class')">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Bootstrap 5 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
