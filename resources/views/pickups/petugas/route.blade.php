@extends('layouts.app')

@section('title', 'Peta Rute Tugas Jemput Saya')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-signpost-split"></i> Rute Penjemputan Saya (Read-Only)</h4>
        <small class="text-muted">Urutan kunjungan berdasarkan rute yang disimpan Admin untuk tugas penjemputan Anda.</small>
    </div>
    <a href="{{ route('petugas.penjemputan.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Kembali ke Tugas Jemput
    </a>
</div>

@include('pickups.petugas._route_map', ['todayRoute' => $todayRoute])

@if($todayRoute['pickups']->isNotEmpty())
<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">Daftar Titik pada Tanggal {{ $todayRoute['date'] }}</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
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
    </div>
</div>
@endif
@endsection
