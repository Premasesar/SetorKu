@extends('layouts.app')

@section('title', 'Tugas Penjemputan Saya')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-truck"></i> Tugas Penjemputan Terjadwal</h4>
        <small class="text-muted">Daftar tugas penjemputan berstatus terjadwal yang ditugaskan kepada Anda, diurutkan menurut tanggal jadwal.</small>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        @if($pickups->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-check2-circle fs-1 text-success"></i>
                <p class="mt-2 mb-0">Tidak ada tugas penjemputan terjadwal untuk Anda saat ini.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
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
