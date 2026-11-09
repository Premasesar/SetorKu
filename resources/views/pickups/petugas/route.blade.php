@extends('layouts.ui-staff')

@section('title', 'Peta Rute Tugas Jemput Saya')

@section('content')
<x-page-hero
    title="Rute Penjemputan Saya (Read-Only)"
    subtitle="Urutan kunjungan berdasarkan rute yang disimpan Admin untuk tugas penjemputan Anda."
    icon="bi-signpost-split"
    :breadcrumbs="[
        ['label' => 'Tugas Penjemputan', 'url' => route('petugas.penjemputan.index')],
        ['label' => 'Peta Rute Saya'],
    ]"
>
    <x-slot:actions>
        <a href="{{ route('petugas.penjemputan.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali ke Tugas Jemput
        </a>
    </x-slot:actions>
</x-page-hero>

@include('pickups.petugas._route_map', ['todayRoute' => $todayRoute])

@if($todayRoute['pickups']->isNotEmpty())
<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-list-ol"></i> Daftar Titik pada Tanggal {{ $todayRoute['date'] }}
    </div>
    <div class="card-body p-0">
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover align-middle mb-0 sk-table">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Urutan</th>
                        <th>Kode</th>
                        <th>Nasabah</th>
                        <th>Alamat</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($todayRoute['pickups'] as $pickup)
                        <tr>
                            <td class="ps-3">
                                @if($pickup->route_sequence !== null)
                                    <span class="badge bg-success">#{{ $pickup->route_sequence }}</span>
                                @else
                                    <span class="badge bg-secondary">Belum diurutkan</span>
                                @endif
                            </td>
                            <td><code>{{ $pickup->code }}</code></td>
                            <td>{{ $pickup->customer?->name }}</td>
                            <td>{{ $pickup->address }}</td>
                            <td class="text-end pe-3">
                                <a href="{{ route('petugas.penjemputan.show', $pickup) }}" class="btn btn-sm btn-success">
                                    Detail & Catat Hasil
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="sk-mobile-list d-md-none p-3">
            @foreach($todayRoute['pickups'] as $pickup)
                <div class="sk-mobile-card">
                    <div class="sk-mobile-card__head">
                        <div>
                            @if($pickup->route_sequence !== null)
                                <span class="badge bg-success me-1">#{{ $pickup->route_sequence }}</span>
                            @else
                                <span class="badge bg-secondary me-1">Belum diurutkan</span>
                            @endif
                            <code>{{ $pickup->code }}</code>
                        </div>
                    </div>
                    <div class="sk-mobile-card__row">
                        <span class="sk-mobile-card__label">Nasabah</span>
                        <span class="sk-mobile-card__value">{{ $pickup->customer?->name }}</span>
                    </div>
                    <div class="sk-mobile-card__row">
                        <span class="sk-mobile-card__label">Alamat</span>
                        <span class="sk-mobile-card__value">{{ $pickup->address }}</span>
                    </div>
                    <div class="sk-mobile-card__actions">
                        <a href="{{ route('petugas.penjemputan.show', $pickup) }}" class="btn btn-sm btn-success w-100">
                            Detail & Catat Hasil
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
@endsection
