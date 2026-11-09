@extends('layouts.ui-staff')

@section('title', 'Tugas Penjemputan Saya')

@section('content')
<x-page-hero
    title="Tugas Penjemputan Terjadwal"
    subtitle="Daftar tugas terjadwal Anda, diurutkan menurut tanggal jadwal dan urutan rute."
    icon="bi-truck"
    :breadcrumbs="['Portal Petugas', 'Tugas Penjemputan']"
>
    <x-slot:actions>
        <a href="{{ route('petugas.rute.show') }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-map"></i> Halaman Peta Rute Saya
        </a>
    </x-slot:actions>
</x-page-hero>

@include('pickups.petugas._route_map', ['todayRoute' => $todayRoute])

<div class="card shadow-sm">
    <div class="card-body">
        @if($pickups->isEmpty())
            <x-empty-state
                icon="bi-check2-circle"
                message="Tidak ada tugas penjemputan terjadwal untuk Anda saat ini."
            />
        @else
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle sk-table">
                    <thead class="table-light">
                        <tr>
                            <th>Urutan</th>
                            <th>Kode</th>
                            <th>Tgl Jadwal</th>
                            <th>Nasabah</th>
                            <th>Alamat</th>
                            <th>Catatan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pickups as $pickup)
                            <tr>
                                <td>
                                    @if($pickup->route_sequence !== null)
                                        <span class="badge bg-success">#{{ $pickup->route_sequence }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td><code>{{ $pickup->code }}</code></td>
                                <td class="fw-semibold">{{ $pickup->scheduled_date?->format('d-m-Y') }}</td>
                                <td>
                                    {{ $pickup->customer?->name }}
                                    <br><small class="text-muted">{{ $pickup->customer?->code }}</small>
                                </td>
                                <td>{{ \Illuminate\Support\Str::limit($pickup->address, 50) }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($pickup->notes ?? '-', 35) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('petugas.penjemputan.show', $pickup) }}" class="btn btn-sm btn-success">
                                        Detail & Catat Hasil
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="sk-mobile-list d-md-none mb-3">
                @foreach($pickups as $pickup)
                    <div class="sk-mobile-card">
                        <div class="sk-mobile-card__head">
                            <div>
                                @if($pickup->route_sequence !== null)
                                    <span class="badge bg-success me-1">#{{ $pickup->route_sequence }}</span>
                                @else
                                    <span class="text-muted me-1">-</span>
                                @endif
                                <code>{{ $pickup->code }}</code>
                            </div>
                            <span class="fw-semibold small">{{ $pickup->scheduled_date?->format('d-m-Y') }}</span>
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Nasabah</span>
                            <span class="sk-mobile-card__value">
                                {{ $pickup->customer?->name }}
                                <small class="text-muted d-block">{{ $pickup->customer?->code }}</small>
                            </span>
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Alamat</span>
                            <span class="sk-mobile-card__value">{{ \Illuminate\Support\Str::limit($pickup->address, 50) }}</span>
                        </div>
                        <div class="sk-mobile-card__row">
                            <span class="sk-mobile-card__label">Catatan</span>
                            <span class="sk-mobile-card__value">{{ \Illuminate\Support\Str::limit($pickup->notes ?? '-', 35) }}</span>
                        </div>
                        <div class="sk-mobile-card__actions">
                            <a href="{{ route('petugas.penjemputan.show', $pickup) }}" class="btn btn-sm btn-success w-100">
                                Detail & Catat Hasil
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted">
                    Menampilkan {{ $pickups->firstItem() }}–{{ $pickups->lastItem() }} dari {{ $pickups->total() }} tugas
                </small>
                {{ $pickups->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
