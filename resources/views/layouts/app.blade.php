<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SetorKu') — SetorKu</title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar {
            min-height: 100vh;
            background-color: #198754;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.85);
            padding: 0.75rem 1rem;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255,255,255,0.15);
            border-radius: 0.375rem;
        }
        .sidebar .brand-text {
            color: #fff;
            font-weight: 700;
            font-size: 1.25rem;
        }
        .content-wrapper {
            min-height: 100vh;
        }
        .badge-admin { background-color: #0d6efd; }
        .badge-petugas { background-color: #ffc107; color: #000; }
        .badge-nasabah { background-color: #198754; }
    </style>
    @stack('styles')
</head>
<body>
    <div class="d-flex">
        {{-- Sidebar --}}
        <nav class="sidebar d-none d-md-flex flex-column p-3" style="width: 250px;">
            <div class="mb-4">
                <span class="brand-text"><i class="bi bi-recycle"></i> SetorKu</span>
                <br>
                <small class="text-white-50">Bank Sampah</small>
            </div>

            <ul class="nav flex-column">
                @auth
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route(auth()->user()->role . '.dashboard') }}">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </li>

                    @if(auth()->user()->isAdmin())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.nasabah.*') ? 'active' : '' }}" href="{{ route('admin.nasabah.index') }}">
                                <i class="bi bi-people"></i> Data Nasabah
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.jenis-sampah.*') ? 'active' : '' }}" href="{{ route('admin.jenis-sampah.index') }}">
                                <i class="bi bi-tags"></i> Jenis & Harga Sampah
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.setoran.*') ? 'active' : '' }}" href="{{ route('admin.setoran.index') }}">
                                <i class="bi bi-arrow-down-circle"></i> Riwayat Setoran
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.penarikan.*') ? 'active' : '' }}" href="{{ route('admin.penarikan.index') }}">
                                <i class="bi bi-arrow-up-circle"></i> Riwayat Penarikan
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()->isPetugas())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('petugas.nasabah.*') ? 'active' : '' }}" href="{{ route('petugas.nasabah.index') }}">
                                <i class="bi bi-people"></i> Data Nasabah
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('petugas.jenis-sampah.*') ? 'active' : '' }}" href="{{ route('petugas.jenis-sampah.index') }}">
                                <i class="bi bi-tags"></i> Jenis & Harga Sampah
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('petugas.setoran.*') ? 'active' : '' }}" href="{{ route('petugas.setoran.index') }}">
                                <i class="bi bi-arrow-down-circle"></i> Setoran Sampah
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('petugas.penarikan.*') ? 'active' : '' }}" href="{{ route('petugas.penarikan.index') }}">
                                <i class="bi bi-arrow-up-circle"></i> Penarikan Saldo
                            </a>
                        </li>
                    @endif

                    @if(auth()->user()->isNasabah())
                        {{-- Menu Nasabah — Portal akan ditambahkan di Sprint 2 --}}
                    @endif
                @endauth
            </ul>

            {{-- Logout --}}
            @auth
            <div class="mt-auto">
                <hr class="text-white-50">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="nav-link border-0 bg-transparent w-100 text-start">
                        <i class="bi bi-box-arrow-left"></i> Keluar
                    </button>
                </form>
            </div>
            @endauth
        </nav>

        {{-- Main Content --}}
        <div class="content-wrapper flex-grow-1">
            {{-- Top Navbar (mobile) --}}
            <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm d-md-none">
                <div class="container-fluid">
                    <a class="navbar-brand text-success fw-bold" href="#">
                        <i class="bi bi-recycle"></i> SetorKu
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>
            </nav>

            {{-- Mobile Offcanvas Sidebar --}}
            <div class="offcanvas offcanvas-start bg-success text-white" id="mobileSidebar">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title"><i class="bi bi-recycle"></i> SetorKu</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
                </div>
                <div class="offcanvas-body">
                    <ul class="nav flex-column">
                        @auth
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route(auth()->user()->role . '.dashboard') }}">
                                <i class="bi bi-house-door"></i> Dashboard
                            </a>
                        </li>
                        @if(auth()->user()->isAdmin())
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('admin.nasabah.index') }}">
                                <i class="bi bi-people"></i> Data Nasabah
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('admin.jenis-sampah.index') }}">
                                <i class="bi bi-tags"></i> Jenis & Harga Sampah
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('admin.setoran.index') }}">
                                <i class="bi bi-arrow-down-circle"></i> Riwayat Setoran
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('admin.penarikan.index') }}">
                                <i class="bi bi-arrow-up-circle"></i> Riwayat Penarikan
                            </a>
                        </li>
                        @endif
                        @if(auth()->user()->isPetugas())
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('petugas.nasabah.index') }}">
                                <i class="bi bi-people"></i> Data Nasabah
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('petugas.jenis-sampah.index') }}">
                                <i class="bi bi-tags"></i> Jenis & Harga Sampah
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('petugas.setoran.index') }}">
                                <i class="bi bi-arrow-down-circle"></i> Setoran Sampah
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('petugas.penarikan.index') }}">
                                <i class="bi bi-arrow-up-circle"></i> Penarikan Saldo
                            </a>
                        </li>
                        @endif
                        @endauth
                    </ul>
                    @auth
                    <hr class="text-white-50">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="nav-link border-0 bg-transparent text-white">
                            <i class="bi bi-box-arrow-left"></i> Keluar
                        </button>
                    </form>
                    @endauth
                </div>
            </div>

            {{-- Page Content --}}
            <div class="p-4">
                {{-- Flash Messages --}}
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
