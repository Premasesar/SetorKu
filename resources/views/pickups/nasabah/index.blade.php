@extends('layouts.app')

@section('title', 'Penjemputan Sampah Saya')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="fw-bold mb-0"><i class="bi bi-truck"></i> Penjemputan Sampah Saya</h4>
    <a href="{{ route('nasabah.penjemputan.create') }}" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> Ajukan Penjemputan
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        @if($pickups->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-truck fs-1"></i>
                <p class="mt-2 mb-0">Belum ada pengajuan penjemputan sampah.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th>Tgl Pengajuan</th>
                            <th>Jadwal Jemput</th>
                            <th>Alamat</th>
                            <th>Petugas</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pickups as $pickup)
                            <tr>
                                <td><code>{{ $pickup->code }}</code></td>
                                <td>{{ $pickup->requested_date?->format('d-m-Y') }}</td>
                                <td>{{ $pickup->scheduled_date?->format('d-m-Y') ?? '-' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($pickup->address, 45) }}</td>
                                <td>{{ $pickup->officer?->name ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $pickup->statusBadgeClass() }}">{{ $pickup->statusLabel() }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('nasabah.penjemputan.show', $pickup) }}" class="btn btn-sm btn-outline-primary">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted">
                    Menampilkan {{ $pickups->firstItem() }}–{{ $pickups->lastItem() }} dari {{ $pickups->total() }} penjemputan
                </small>
                {{ $pickups->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
