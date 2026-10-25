@extends('layouts.app')

@section('title', 'Kelola Penjemputan Sampah')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h4 class="fw-bold mb-0"><i class="bi bi-truck"></i> Kelola Penjemputan Sampah</h4>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.penjemputan.index') }}" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" name="q" value="{{ $search }}" class="form-control"
                       placeholder="Cari kode JMP atau nama nasabah...">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    @foreach(\App\Enums\PickupStatus::cases() as $case)
                        <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="date" value="{{ $date }}" class="form-control" title="Filter tanggal pengajuan/jadwal">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-success"><i class="bi bi-funnel"></i> Filter</button>
                @if($status !== '' || $date !== '' || $search !== '')
                    <a href="{{ route('admin.penjemputan.index') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>

        @if($pickups->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="bi bi-truck fs-1"></i>
                <p class="mt-2 mb-0">Tidak ada data penjemputan yang ditemukan.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th>Nasabah</th>
                            <th>Tgl Pengajuan</th>
                            <th>Tgl Jadwal</th>
                            <th>Petugas</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pickups as $pickup)
                            <tr>
                                <td><code>{{ $pickup->code }}</code></td>
                                <td>
                                    {{ $pickup->customer?->name }}
                                    <br><small class="text-muted">{{ $pickup->customer?->code }}</small>
                                </td>
                                <td>{{ $pickup->requested_date?->format('d-m-Y') }}</td>
                                <td>{{ $pickup->scheduled_date?->format('d-m-Y') ?? '-' }}</td>
                                <td>{{ $pickup->officer?->name ?? '-' }}</td>
                                <td><span class="badge {{ $pickup->statusBadgeClass() }}">{{ $pickup->statusLabel() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.penjemputan.show', $pickup) }}" class="btn btn-sm btn-outline-primary">
                                        Detail & Kelola
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
