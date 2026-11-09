@extends('layouts.ui-staff')

@section('title', 'Detail Nasabah')

@section('content')
@php($routePrefix = auth()->user()->isAdmin() ? 'admin.nasabah' : 'petugas.nasabah')

<x-page-hero
    :title="$customer->name"
    icon="bi-person-vcard"
    :breadcrumbs="[
        ['label' => 'Data Nasabah', 'url' => route($routePrefix . '.index')],
        ['label' => $customer->name],
    ]"
>
    <code>{{ $customer->code }}</code>
    <x-status-pill :status="$customer->is_active ? 'aktif' : 'nonaktif'" class="ms-1" />

    <x-slot:actions>
        <a href="{{ route($routePrefix . '.card', $customer) }}" class="btn btn-outline-success">
            <i class="bi bi-qr-code"></i> Cetak kartu
        </a>
        @if(auth()->user()->isAdmin())
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
            @else
                <form method="POST" action="{{ route('admin.nasabah.activate', $customer) }}"
                      onsubmit="return confirm('Aktifkan kembali nasabah ini?');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success"><i class="bi bi-person-check"></i> Aktifkan</button>
                </form>
            @endif
        @endif
    </x-slot:actions>
</x-page-hero>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-person-lines-fill"></i> Identitas & Biodata Nasabah
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Kode Nasabah</dt>
                    <dd class="col-sm-8"><code>{{ $customer->code }}</code></dd>

                    <dt class="col-sm-4">Nama</dt>
                    <dd class="col-sm-8 fw-semibold">{{ $customer->name }}</dd>

                    <dt class="col-sm-4">Email (akun login)</dt>
                    <dd class="col-sm-8">{{ $customer->user?->email ?? '-' }}</dd>

                    <dt class="col-sm-4">No. HP</dt>
                    <dd class="col-sm-8">{{ $customer->phone ?: '-' }}</dd>

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
        <div class="card shadow-sm border-success h-100">
            <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                <i class="bi bi-wallet2 fs-1 text-success"></i>
                <h6 class="mt-2 text-muted">Saldo</h6>
                <h3 class="fw-bold text-success mb-0">Rp {{ number_format($customer->balance, 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>
</div>

<a href="{{ route($routePrefix . '.index') }}" class="btn btn-link mt-3 px-0"><i class="bi bi-arrow-left"></i> Kembali ke daftar</a>
@endsection
