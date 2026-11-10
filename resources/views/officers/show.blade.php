@extends('layouts.ui-staff')

@section('title', 'Detail Petugas')

@section('content')
<nav aria-label="breadcrumb" class="mb-2">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.petugas.index') }}">Kelola Petugas</a></li>
        <li class="breadcrumb-item active">{{ $officer->name }}</li>
    </ol>
</nav>

<x-page-hero
    :title="$officer->name"
    :subtitle="'Detail akun petugas operasional (' . $officer->officer_code . '), status keaktifan, serta pengaturan ulang password.'"
    icon="bi-person-badge"
    :breadcrumbs="[
        ['label' => 'Portal Admin'],
        ['label' => 'Kelola Petugas', 'url' => route('admin.petugas.index')],
        ['label' => $officer->name],
    ]"
>
    <x-slot:actions>
        @if($officer->is_active)
            <span class="badge bg-success fs-6 align-middle">Aktif</span>
        @else
            <span class="badge bg-secondary fs-6 align-middle">Nonaktif</span>
        @endif
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
    </x-slot:actions>
</x-page-hero>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card sk-card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold py-3">
                <i class="bi bi-person-vcard text-success me-1"></i> Informasi Akun Petugas
            </div>
            <div class="card-body p-3 p-md-4">
                <dl class="row mb-0 g-2">
                    <dt class="col-sm-4 text-muted">Kode</dt>
                    <dd class="col-sm-8"><code>{{ $officer->officer_code }}</code></dd>

                    <dt class="col-sm-4 text-muted">Nama</dt>
                    <dd class="col-sm-8 fw-semibold">{{ $officer->name }}</dd>

                    <dt class="col-sm-4 text-muted">Email</dt>
                    <dd class="col-sm-8">{{ $officer->email }}</dd>

                    <dt class="col-sm-4 text-muted">No. HP</dt>
                    <dd class="col-sm-8">{{ $officer->phone ?: '-' }}</dd>

                    <dt class="col-sm-4 text-muted">Peran</dt>
                    <dd class="col-sm-8"><span class="badge badge-petugas">Petugas</span></dd>

                    <dt class="col-sm-4 text-muted">Terdaftar</dt>
                    <dd class="col-sm-8 mb-0">{{ $officer->created_at?->format('d-m-Y H:i') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card sk-card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold py-3">
                <i class="bi bi-key text-warning me-1"></i> Reset Password Petugas
            </div>
            <div class="card-body p-3 p-md-4">
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
