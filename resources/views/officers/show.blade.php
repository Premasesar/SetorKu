@extends('layouts.app')

@section('title', 'Detail Petugas')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.petugas.index') }}">Kelola Petugas</a></li>
        <li class="breadcrumb-item active">{{ $officer->name }}</li>
    </ol>
</nav>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="fw-bold mb-0">
        <i class="bi bi-person-badge"></i> {{ $officer->name }}
        @if($officer->is_active)
            <span class="badge bg-success fs-6 align-middle">Aktif</span>
        @else
            <span class="badge bg-secondary fs-6 align-middle">Nonaktif</span>
        @endif
    </h4>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.petugas.edit', $officer) }}" class="btn btn-outline-secondary">
            <i class="bi bi-pencil"></i> Ubah Data
        </a>
        @if($officer->is_active)
            <form method="POST" action="{{ route('admin.petugas.deactivate', $officer) }}"
                  onsubmit="return confirm('Nonaktifkan akun petugas ini? Sesi aktif petugas akan di-logout otomatis.');">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-danger"><i class="bi bi-person-x"></i> Nonaktifkan</button>
            </form>
        @else
            <form method="POST" action="{{ route('admin.petugas.activate', $officer) }}"
                  onsubmit="return confirm('Aktifkan kembali akun petugas ini?');">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-success"><i class="bi bi-person-check"></i> Aktifkan</button>
            </form>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Informasi Akun Petugas</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Kode</dt>
                    <dd class="col-sm-8"><code>{{ $officer->officer_code }}</code></dd>

                    <dt class="col-sm-4">Nama</dt>
                    <dd class="col-sm-8">{{ $officer->name }}</dd>

                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8">{{ $officer->email }}</dd>

                    <dt class="col-sm-4">No. HP</dt>
                    <dd class="col-sm-8">{{ $officer->phone ?: '-' }}</dd>

                    <dt class="col-sm-4">Peran</dt>
                    <dd class="col-sm-8"><span class="badge badge-petugas">Petugas</span></dd>

                    <dt class="col-sm-4">Terdaftar</dt>
                    <dd class="col-sm-8 mb-0">{{ $officer->created_at?->format('d-m-Y H:i') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-key"></i> Reset Password Petugas</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.petugas.reset-password', $officer) }}" novalidate>
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <x-password-input
                            name="password"
                            id="password"
                            label="Password Baru"
                            autocomplete="new-password"
                            help-text="Minimal 8 karakter."
                            minlength="8"
                            :required="true"
                            :required-indicator="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-password-input
                            name="password_confirmation"
                            id="password_confirmation"
                            label="Konfirmasi Password Baru"
                            autocomplete="new-password"
                            minlength="8"
                            :required="true"
                            :required-indicator="true"
                        />
                    </div>

                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-key-fill"></i> Reset Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<a href="{{ route('admin.petugas.index') }}" class="btn btn-link mt-3 px-0"><i class="bi bi-arrow-left"></i> Kembali ke daftar petugas</a>
@endsection
