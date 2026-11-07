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
    @php
        $currentUser = auth()->user();
        $roleLabel = $currentUser?->isAdmin() ? 'Admin' : ($currentUser?->isPetugas() ? 'Petugas' : 'Nasabah');
        $initials = collect(explode(' ', trim($currentUser?->name ?? 'SK')))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('') ?: 'SK';
    @endphp

    <div class="sk-staff">
        {{-- Sidebar Kiri Desktop (264px, #0f2e22) --}}
        <aside class="sk-sidebar" aria-label="Navigasi Utama">
            <div class="sk-sidebar__brand">
                <span class="sk-logo" aria-hidden="true"><i class="bi bi-recycle"></i></span>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="sk-sidebar__title">SetorKu</span>
                        @auth
                            <span class="sk-role-tag">{{ $roleLabel }}</span>
                        @endauth
                    </div>
                    <span class="sk-sidebar__subtitle text-truncate">Bank Sampah</span>
                </div>
            </div>

            <nav class="sk-sidebar__nav">
                <ul class="nav flex-column">
                    @auth
                        <li class="nav-item">
                            <a class="sk-nav-link {{ request()->routeIs($currentUser->role . '.dashboard') ? 'active' : '' }}"
                               href="{{ route($currentUser->role . '.dashboard') }}">
                                <span class="sk-nav-icon"><i class="bi bi-house-door"></i></span>
                                <span>Dashboard</span>
                            </a>
                        </li>

                        @if($currentUser->isAdmin())
                            <li class="nav-item">
                                <a class="sk-nav-link {{ request()->routeIs('admin.nasabah.*') ? 'active' : '' }}"
                                   href="{{ route('admin.nasabah.index') }}">
                                    <span class="sk-nav-icon"><i class="bi bi-people"></i></span>
                                    <span>Data Nasabah</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="sk-nav-link {{ request()->routeIs('admin.petugas.*') ? 'active' : '' }}"
                                   href="{{ route('admin.petugas.index') }}">
                                    <span class="sk-nav-icon"><i class="bi bi-person-badge"></i></span>
                                    <span>Kelola Petugas</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="sk-nav-link {{ request()->routeIs('admin.jenis-sampah.*') ? 'active' : '' }}"
                                   href="{{ route('admin.jenis-sampah.index') }}">
                                    <span class="sk-nav-icon"><i class="bi bi-tags"></i></span>
                                    <span>Jenis & Harga Sampah</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="sk-nav-link {{ request()->routeIs('admin.setoran.*') ? 'active' : '' }}"
                                   href="{{ route('admin.setoran.index') }}">
                                    <span class="sk-nav-icon"><i class="bi bi-arrow-down-circle"></i></span>
                                    <span>Riwayat Setoran</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="sk-nav-link {{ request()->routeIs('admin.penarikan.*') ? 'active' : '' }}"
                                   href="{{ route('admin.penarikan.index') }}">
                                    <span class="sk-nav-icon"><i class="bi bi-arrow-up-circle"></i></span>
                                    <span>Riwayat Penarikan</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="sk-nav-link {{ request()->routeIs('admin.penjemputan.*') ? 'active' : '' }}"
                                   href="{{ route('admin.penjemputan.index') }}">
                                    <span class="sk-nav-icon"><i class="bi bi-truck"></i></span>
                                    <span>Penjemputan Sampah</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="sk-nav-link {{ request()->routeIs('admin.rute.*') ? 'active' : '' }}"
                                   href="{{ route('admin.rute.index') }}">
                                    <span class="sk-nav-icon"><i class="bi bi-signpost-split"></i></span>
                                    <span>Rute Penjemputan</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="sk-nav-link {{ request()->routeIs('admin.laporan.*') ? 'active' : '' }}"
                                   href="{{ route('admin.laporan.index') }}">
                                    <span class="sk-nav-icon"><i class="bi bi-journal-text"></i></span>
                                    <span>Laporan Bulanan</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="sk-nav-link {{ request()->routeIs('admin.dampak.*') ? 'active' : '' }}"
                                   href="{{ route('admin.dampak.index') }}">
                                    <span class="sk-nav-icon"><i class="bi bi-bar-chart-line"></i></span>
                                    <span>Dampak</span>
                                </a>
                            </li>
                        @endif

                        @if($currentUser->isPetugas())
                            <li class="nav-item">
                                <a class="sk-nav-link {{ request()->routeIs('petugas.penjemputan.*', 'petugas.rute.*') ? 'active' : '' }}"
                                   href="{{ route('petugas.penjemputan.index') }}">
                                    <span class="sk-nav-icon"><i class="bi bi-truck"></i></span>
                                    <span>Tugas Penjemputan</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="sk-nav-link {{ request()->routeIs('petugas.nasabah.*') ? 'active' : '' }}"
                                   href="{{ route('petugas.nasabah.index') }}">
                                    <span class="sk-nav-icon"><i class="bi bi-people"></i></span>
                                    <span>Data Nasabah</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="sk-nav-link {{ request()->routeIs('petugas.jenis-sampah.*') ? 'active' : '' }}"
                                   href="{{ route('petugas.jenis-sampah.index') }}">
                                    <span class="sk-nav-icon"><i class="bi bi-tags"></i></span>
                                    <span>Jenis & Harga Sampah</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="sk-nav-link {{ request()->routeIs('petugas.setoran.*') ? 'active' : '' }}"
                                   href="{{ route('petugas.setoran.index') }}">
                                    <span class="sk-nav-icon"><i class="bi bi-arrow-down-circle"></i></span>
                                    <span>Setoran Sampah</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="sk-nav-link {{ request()->routeIs('petugas.penarikan.*') ? 'active' : '' }}"
                                   href="{{ route('petugas.penarikan.index') }}">
                                    <span class="sk-nav-icon"><i class="bi bi-arrow-up-circle"></i></span>
                                    <span>Penarikan Saldo</span>
                                </a>
                            </li>
                        @endif

                        @if($currentUser->isNasabah())
                            <li class="nav-item">
                                <a class="sk-nav-link {{ request()->routeIs('nasabah.penjemputan.*') ? 'active' : '' }}"
                                   href="{{ route('nasabah.penjemputan.index') }}">
                                    <span class="sk-nav-icon"><i class="bi bi-truck"></i></span>
                                    <span>Penjemputan Sampah</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="sk-nav-link {{ request()->routeIs('nasabah.riwayat.*') ? 'active' : '' }}"
                                   href="{{ route('nasabah.riwayat.index') }}">
                                    <span class="sk-nav-icon"><i class="bi bi-clock-history"></i></span>
                                    <span>Riwayat Transaksi</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="sk-nav-link {{ request()->routeIs('nasabah.kartu.*') ? 'active' : '' }}"
                                   href="{{ route('nasabah.kartu.show') }}">
                                    <span class="sk-nav-icon"><i class="bi bi-qr-code"></i></span>
                                    <span>Kartu Saya</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="sk-nav-link {{ request()->routeIs('nasabah.profil.*') ? 'active' : '' }}"
                                   href="{{ route('nasabah.profil.edit') }}">
                                    <span class="sk-nav-icon"><i class="bi bi-person-gear"></i></span>
                                    <span>Profil Saya</span>
                                </a>
                            </li>
                        @endif
                    @endauth
                </ul>
            </nav>

            @auth
                <div class="sk-sidebar__footer">
                    <div class="sk-user-pill">
                        <span class="sk-avatar">{{ $initials }}</span>
                        <div class="flex-grow-1 min-w-0">
                            <div class="sk-user-pill__name text-truncate">{{ $currentUser->name }}</div>
                            <div class="sk-user-pill__role text-truncate">{{ $roleLabel }}</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="sk-logout-btn">
                            <i class="bi bi-box-arrow-left"></i>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            @endauth
        </aside>

        {{-- Area Kanan (Topbar Mobile + Sub-Hero + Konten + Footer) --}}
        <div class="sk-main">
            {{-- Top Navbar Mobile dengan Tombol Hamburger --}}
            <header class="sk-topbar">
                <a class="d-flex align-items-center gap-2 text-white text-decoration-none fw-bold"
                   href="{{ auth()->check() ? route(auth()->user()->role . '.dashboard') : '#' }}">
                    <span class="sk-logo" style="width:2rem;height:2rem;font-size:1rem;"><i class="bi bi-recycle"></i></span>
                    <span>SetorKu</span>
                    @auth
                        <span class="sk-role-tag">{{ $roleLabel }}</span>
                    @endauth
                </a>
                <button class="sk-hamburger"
                        type="button"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#mobileSidebar"
                        aria-controls="mobileSidebar"
                        aria-label="Buka Menu Navigasi">
                    <i class="bi bi-list"></i>
                </button>
            </header>

            {{-- Mobile Offcanvas Sidebar --}}
            <div class="offcanvas offcanvas-start sk-offcanvas" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
                <div class="offcanvas-header">
                    <div class="d-flex align-items-center gap-2" id="mobileSidebarLabel">
                        <span class="sk-logo"><i class="bi bi-recycle"></i></span>
                        <div>
                            <div class="sk-sidebar__title">SetorKu</div>
                            <small class="sk-sidebar__subtitle">Bank Sampah</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
                </div>
                <div class="offcanvas-body d-flex flex-column justify-content-between p-0">
                    <nav class="sk-sidebar__nav">
                        <ul class="nav flex-column">
                            @auth
                                <li class="nav-item">
                                    <a class="sk-nav-link {{ request()->routeIs($currentUser->role . '.dashboard') ? 'active' : '' }}"
                                       href="{{ route($currentUser->role . '.dashboard') }}">
                                        <span class="sk-nav-icon"><i class="bi bi-house-door"></i></span>
                                        <span>Dashboard</span>
                                    </a>
                                </li>
                                @if($currentUser->isAdmin())
                                    <li class="nav-item">
                                        <a class="sk-nav-link {{ request()->routeIs('admin.nasabah.*') ? 'active' : '' }}" href="{{ route('admin.nasabah.index') }}">
                                            <span class="sk-nav-icon"><i class="bi bi-people"></i></span>
                                            <span>Data Nasabah</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="sk-nav-link {{ request()->routeIs('admin.petugas.*') ? 'active' : '' }}" href="{{ route('admin.petugas.index') }}">
                                            <span class="sk-nav-icon"><i class="bi bi-person-badge"></i></span>
                                            <span>Kelola Petugas</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="sk-nav-link {{ request()->routeIs('admin.jenis-sampah.*') ? 'active' : '' }}" href="{{ route('admin.jenis-sampah.index') }}">
                                            <span class="sk-nav-icon"><i class="bi bi-tags"></i></span>
                                            <span>Jenis & Harga Sampah</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="sk-nav-link {{ request()->routeIs('admin.setoran.*') ? 'active' : '' }}" href="{{ route('admin.setoran.index') }}">
                                            <span class="sk-nav-icon"><i class="bi bi-arrow-down-circle"></i></span>
                                            <span>Riwayat Setoran</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="sk-nav-link {{ request()->routeIs('admin.penarikan.*') ? 'active' : '' }}" href="{{ route('admin.penarikan.index') }}">
                                            <span class="sk-nav-icon"><i class="bi bi-arrow-up-circle"></i></span>
                                            <span>Riwayat Penarikan</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="sk-nav-link {{ request()->routeIs('admin.penjemputan.*') ? 'active' : '' }}" href="{{ route('admin.penjemputan.index') }}">
                                            <span class="sk-nav-icon"><i class="bi bi-truck"></i></span>
                                            <span>Penjemputan Sampah</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="sk-nav-link {{ request()->routeIs('admin.rute.*') ? 'active' : '' }}" href="{{ route('admin.rute.index') }}">
                                            <span class="sk-nav-icon"><i class="bi bi-signpost-split"></i></span>
                                            <span>Rute Penjemputan</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="sk-nav-link {{ request()->routeIs('admin.laporan.*') ? 'active' : '' }}" href="{{ route('admin.laporan.index') }}">
                                            <span class="sk-nav-icon"><i class="bi bi-journal-text"></i></span>
                                            <span>Laporan Bulanan</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="sk-nav-link {{ request()->routeIs('admin.dampak.*') ? 'active' : '' }}" href="{{ route('admin.dampak.index') }}">
                                            <span class="sk-nav-icon"><i class="bi bi-bar-chart-line"></i></span>
                                            <span>Dampak</span>
                                        </a>
                                    </li>
                                @endif
                                @if($currentUser->isPetugas())
                                    <li class="nav-item">
                                        <a class="sk-nav-link {{ request()->routeIs('petugas.penjemputan.*', 'petugas.rute.*') ? 'active' : '' }}" href="{{ route('petugas.penjemputan.index') }}">
                                            <span class="sk-nav-icon"><i class="bi bi-truck"></i></span>
                                            <span>Tugas Penjemputan</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="sk-nav-link {{ request()->routeIs('petugas.nasabah.*') ? 'active' : '' }}" href="{{ route('petugas.nasabah.index') }}">
                                            <span class="sk-nav-icon"><i class="bi bi-people"></i></span>
                                            <span>Data Nasabah</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="sk-nav-link {{ request()->routeIs('petugas.jenis-sampah.*') ? 'active' : '' }}" href="{{ route('petugas.jenis-sampah.index') }}">
                                            <span class="sk-nav-icon"><i class="bi bi-tags"></i></span>
                                            <span>Jenis & Harga Sampah</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="sk-nav-link {{ request()->routeIs('petugas.setoran.*') ? 'active' : '' }}" href="{{ route('petugas.setoran.index') }}">
                                            <span class="sk-nav-icon"><i class="bi bi-arrow-down-circle"></i></span>
                                            <span>Setoran Sampah</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="sk-nav-link {{ request()->routeIs('petugas.penarikan.*') ? 'active' : '' }}" href="{{ route('petugas.penarikan.index') }}">
                                            <span class="sk-nav-icon"><i class="bi bi-arrow-up-circle"></i></span>
                                            <span>Penarikan Saldo</span>
                                        </a>
                                    </li>
                                @endif
                                @if($currentUser->isNasabah())
                                    <li class="nav-item">
                                        <a class="sk-nav-link {{ request()->routeIs('nasabah.penjemputan.*') ? 'active' : '' }}" href="{{ route('nasabah.penjemputan.index') }}">
                                            <span class="sk-nav-icon"><i class="bi bi-truck"></i></span>
                                            <span>Penjemputan Sampah</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="sk-nav-link {{ request()->routeIs('nasabah.riwayat.*') ? 'active' : '' }}" href="{{ route('nasabah.riwayat.index') }}">
                                            <span class="sk-nav-icon"><i class="bi bi-clock-history"></i></span>
                                            <span>Riwayat Transaksi</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="sk-nav-link {{ request()->routeIs('nasabah.kartu.*') ? 'active' : '' }}" href="{{ route('nasabah.kartu.show') }}">
                                            <span class="sk-nav-icon"><i class="bi bi-qr-code"></i></span>
                                            <span>Kartu Saya</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="sk-nav-link {{ request()->routeIs('nasabah.profil.*') ? 'active' : '' }}" href="{{ route('nasabah.profil.edit') }}">
                                            <span class="sk-nav-icon"><i class="bi bi-person-gear"></i></span>
                                            <span>Profil Saya</span>
                                        </a>
                                    </li>
                                @endif
                            @endauth
                        </ul>
                    </nav>
                    @auth
                        <div class="sk-sidebar__footer">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="sk-logout-btn">
                                    <i class="bi bi-box-arrow-left"></i>
                                    <span>Keluar</span>
                                </button>
                            </form>
                        </div>
                    @endauth
                </div>
            </div>

            {{-- Sub-Hero Hijau Hutan (opsional lewat @section('hero')) --}}
            @hasSection('hero')
                @yield('hero')
            @endif

            {{-- Konten Utama --}}
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

            <footer class="sk-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span>&copy; {{ date('Y') }} SetorKu — Sistem Informasi Bank Sampah</span>
                <span class="text-muted">Portal Operasional {{ $roleLabel }}</span>
            </footer>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
