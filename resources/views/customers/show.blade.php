@extends('layouts.app')

@section('title', 'Detail Nasabah')

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.nasabah.index') }}">Data Nasabah</a></li>
        <li class="breadcrumb-item active">{{ $customer->name }}</li>
    </ol>
</nav>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="fw-bold mb-0">
        <i class="bi bi-person-vcard"></i> {{ $customer->name }}
        @if($customer->is_active)
            <span class="badge bg-success fs-6 align-middle">Aktif</span>
        @else
            <span class="badge bg-secondary fs-6 align-middle">Nonaktif</span>
        @endif
    </h4>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.nasabah.edit', $customer) }}" class="btn btn-outline-secondary">
            <i class="bi bi-pencil"></i> Ubah
        </a>
        @if($customer->is_active)
            <form method="POST" action="{{ route('admin.nasabah.deactivate', $customer) }}"
                  onsubmit="return confirm('Nonaktifkan nasabah ini? Data tidak akan dihapus.');">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-danger"><i class="bi bi-person-x"></i> Nonaktifkan</button>
            </form>
        @endif
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Nama</dt>
                    <dd class="col-sm-8">{{ $customer->name }}</dd>

                    <dt class="col-sm-4">Email (akun login)</dt>
                    <dd class="col-sm-8">{{ $customer->user?->email ?? '-' }}</dd>

                    <dt class="col-sm-4">Alamat</dt>
                    <dd class="col-sm-8">{!! nl2br(e($customer->address)) !!}</dd>

                    <dt class="col-sm-4">Koordinat</dt>
                    <dd class="col-sm-8">
                        @if($customer->latitude !== null && $customer->longitude !== null)
                            {{ $customer->latitude }}, {{ $customer->longitude }}
                        @else
                            <span class="text-muted">Belum diisi</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4">Terdaftar</dt>
                    <dd class="col-sm-8 mb-0">{{ $customer->created_at?->format('d-m-Y H:i') }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm border-success">
            <div class="card-body text-center">
                <i class="bi bi-wallet2 fs-1 text-success"></i>
                <h6 class="mt-2 text-muted">Saldo</h6>
                <h3 class="fw-bold text-success mb-0">Rp {{ number_format($customer->balance, 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>
</div>

<a href="{{ route('admin.nasabah.index') }}" class="btn btn-link mt-3 px-0"><i class="bi bi-arrow-left"></i> Kembali ke daftar</a>
@endsection
