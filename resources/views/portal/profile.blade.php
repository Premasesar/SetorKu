@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
@php
    $hasCoords = ($customer->latitude !== null && $customer->longitude !== null);
    $phoneDisplay = ($customer->phone !== null && trim((string) $customer->phone) !== '')
        ? $customer->phone
        : '-';
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="fw-bold mb-0"><i class="bi bi-person-gear"></i> Profil Saya</h4>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('nasabah.profil.ubah') }}" class="btn btn-success">
            <i class="bi bi-pencil-square"></i> Ubah Profil
        </a>
        <a href="{{ route('nasabah.dashboard') }}" class="btn btn-outline-secondary">
            Kembali
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-3">Informasi Akun</h6>
                <dl class="row mb-0">
                    <dt class="col-5">Kode</dt>
                    <dd class="col-7"><code>{{ $customer->code }}</code></dd>

                    <dt class="col-5">Peran</dt>
                    <dd class="col-7"><span class="badge badge-nasabah">Nasabah</span></dd>

                    <dt class="col-5">Email</dt>
                    <dd class="col-7">{{ auth()->user()->email }}</dd>

                    <dt class="col-5">Saldo</dt>
                    <dd class="col-7 fw-bold text-success mb-0">Rp {{ number_format($customer->balance, 2, ',', '.') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="text-muted mb-3">Data Diri</h6>
                <dl class="row mb-3">
                    <dt class="col-sm-4">Nama Lengkap</dt>
                    <dd class="col-sm-8">{{ $customer->name }}</dd>

                    <dt class="col-sm-4">Alamat</dt>
                    <dd class="col-sm-8">{!! nl2br(e($customer->address)) !!}</dd>

                    <dt class="col-sm-4">No. HP</dt>
                    <dd class="col-sm-8">{{ $phoneDisplay }}</dd>

                    <dt class="col-sm-4">Koordinat</dt>
                    <dd class="col-sm-8 mb-0">
                        @if($hasCoords)
                            <code>{{ $customer->latitude }}, {{ $customer->longitude }}</code>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </dd>
                </dl>

                @if($hasCoords)
                    <x-map-picker
                        id="profile-readonly-map"
                        mode="view"
                        :lat-value="$customer->latitude"
                        :lng-value="$customer->longitude"
                        title="Titik Lokasi Rumah"
                        :hint="null"
                        height="300px"
                    />
                @else
                    <div class="alert alert-light border mb-0">
                        <i class="bi bi-geo-alt text-muted"></i> Titik lokasi rumah belum ditentukan.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
