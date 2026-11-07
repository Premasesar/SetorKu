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
<body class="sk-theme d-flex flex-column min-vh-100">
    @php
        $currentUser = auth()->user();
        $customerModel = $customer ?? $currentUser?->customer;
        $customerCode = $customerModel?->code;
    @endphp

    {{-- Header Menu Atas Setinggi 80px --}}
    <header class="sk-nasabah-header">
        <div class="sk-nasabah-header__bar">
            <div class="d-flex align-items-center gap-4">
                <a class="sk-brand" href="{{ auth()->check() ? route('nasabah.dashboard') : '#' }}">
                    <span class="sk-logo"><i class="bi bi-recycle"></i></span>
                    <span>
                        <span class="sk-brand__name">SetorKu</span>
                        <span class="sk-brand__tag">Bank Sampah</span>
                    </span>
                </a>

                @auth
                    <nav class="sk-topnav sk-topnav--desktop" aria-label="Menu Portal Nasabah">
                        <a class="sk-topnav__link {{ request()->routeIs('nasabah.dashboard') ? 'active' : '' }}"
                           href="{{ route('nasabah.dashboard') }}">
                            <i class="bi bi-house-door"></i>
                            <span>Dashboard</span>
                        </a>
                        <a class="sk-topnav__link {{ request()->routeIs('nasabah.penjemputan.*') ? 'active' : '' }}"
                           href="{{ route('nasabah.penjemputan.index') }}">
                            <i class="bi bi-truck"></i>
                            <span>Penjemputan Sampah</span>
                        </a>
                        <a class="sk-topnav__link {{ request()->routeIs('nasabah.riwayat.*') ? 'active' : '' }}"
                           href="{{ route('nasabah.riwayat.index') }}">
                            <i class="bi bi-clock-history"></i>
                            <span>Riwayat Transaksi</span>
                        </a>
                        <a class="sk-topnav__link {{ request()->routeIs('nasabah.kartu.*') ? 'active' : '' }}"
                           href="{{ route('nasabah.kartu.show') }}">
                            <i class="bi bi-qr-code"></i>
                            <span>Kartu Saya</span>
                        </a>
                        <a class="sk-topnav__link {{ request()->routeIs('nasabah.profil.*') ? 'active' : '' }}"
                           href="{{ route('nasabah.profil.edit') }}">
                            <i class="bi bi-person-gear"></i>
                            <span>Profil Saya</span>
                        </a>
                    </nav>
                @endauth
            </div>

            <div class="d-flex align-items-center gap-2">
                @auth
                    <div class="sk-user-chip d-none d-md-inline-flex">
                        <span class="sk-user-chip__icon"><i class="bi bi-person-badge"></i></span>
                        <span>
                            <span class="sk-user-chip__name">{{ $currentUser->name }}</span>
                            <span class="sk-user-chip__meta">
                                @if($customerCode)
                                    {{ $customerCode }} &bull; Nasabah
                                @else
                                    Nasabah
                                @endif
                            </span>
                        </span>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="d-none d-md-inline">
                        @csrf
                        <button type="submit" class="sk-btn-logout-light">
                            <i class="bi bi-box-arrow-left"></i>
                            <span>Keluar</span>
                        </button>
                    </form>

                    <button class="btn btn-light d-xl-none"
                            type="button"
                            data-bs-toggle="offcanvas"
                            data-bs-target="#nasabahMobileMenu"
                            aria-controls="nasabahMobileMenu"
                            aria-label="Buka Menu">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                @endauth
            </div>
        </div>
    </header>

    {{-- Offcanvas Menu Mobile Nasabah --}}
    @auth
        <div class="offcanvas offcanvas-end sk-offcanvas-light" tabindex="-1" id="nasabahMobileMenu" aria-labelledby="nasabahMobileMenuLabel">
            <div class="offcanvas-header border-bottom">
                <div class="d-flex align-items-center gap-2" id="nasabahMobileMenuLabel">
                    <span class="sk-logo"><i class="bi bi-recycle"></i></span>
                    <div>
                        <div class="fw-bold">{{ $currentUser->name }}</div>
                        <small class="text-muted">{{ $customerCode ? $customerCode . ' • ' : '' }}Nasabah</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column justify-content-between">
                <nav class="sk-topnav" aria-label="Menu Mobile Nasabah">
                    <a class="sk-topnav__link {{ request()->routeIs('nasabah.dashboard') ? 'active' : '' }}"
                       href="{{ route('nasabah.dashboard') }}">
                        <i class="bi bi-house-door"></i>
                        <span>Dashboard</span>
                    </a>
                    <a class="sk-topnav__link {{ request()->routeIs('nasabah.penjemputan.*') ? 'active' : '' }}"
                       href="{{ route('nasabah.penjemputan.index') }}">
                        <i class="bi bi-truck"></i>
                        <span>Penjemputan Sampah</span>
                    </a>
                    <a class="sk-topnav__link {{ request()->routeIs('nasabah.riwayat.*') ? 'active' : '' }}"
                       href="{{ route('nasabah.riwayat.index') }}">
                        <i class="bi bi-clock-history"></i>
                        <span>Riwayat Transaksi</span>
                    </a>
                    <a class="sk-topnav__link {{ request()->routeIs('nasabah.kartu.*') ? 'active' : '' }}"
                       href="{{ route('nasabah.kartu.show') }}">
                        <i class="bi bi-qr-code"></i>
                        <span>Kartu Saya</span>
                    </a>
                    <a class="sk-topnav__link {{ request()->routeIs('nasabah.profil.*') ? 'active' : '' }}"
                       href="{{ route('nasabah.profil.edit') }}">
                        <i class="bi bi-person-gear"></i>
                        <span>Profil Saya</span>
                    </a>
                </nav>
                <div class="border-top pt-3">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="sk-btn-logout-light w-100 justify-content-center">
                            <i class="bi bi-box-arrow-left"></i>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endauth

    {{-- Sub-Hero & Konten --}}
    <div class="sk-nasabah-main flex-grow-1 d-flex flex-column">
        @hasSection('hero')
            @yield('hero')
        @endif

        <main class="sk-content">
            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <footer class="sk-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span>&copy; {{ date('Y') }} SetorKu — Sistem Informasi Bank Sampah</span>
        <span class="text-muted">Portal Layanan Nasabah</span>
    </footer>

    <!-- Bootstrap 5 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
